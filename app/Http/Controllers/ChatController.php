<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * RAG Research Assistant (Ask AI)
     * How it works:
     * 1. Takes the student's question.
     * 2. Converts question into vector embedding.
     * 3. Retrieves top 5 most relevant thesis passages from PostgreSQL using cosine similarity.
     * 4. Sends the passages + question to Google Gemini to generate a grounded answer.
     * 5. Returns the AI answer with deduplicated thesis source citations.
     */
    public function ask(Request $request)
    {
        $userQuestion = $request->input('message') ?? $request->input('question');

        if (!$userQuestion || trim($userQuestion) === '') {
            return response()->json([
                'error' => true,
                'message' => 'Please enter a question to ask the AI assistant.'
            ], 422);
        }

        $userQuestion = trim($userQuestion);
        $history = (array) $request->input('history', []);

        try {
            $embeddingVector = null;
            $keywords = $this->extractSearchKeywords($userQuestion);

            // Step 1: Create contextual retrieval query for vector search
            // If question is a follow-up (e.g. "Who wrote it?"), blend recent context for accurate embedding search
            $searchQuery = $userQuestion;
            if (!empty($history)) {
                $lastUserMsg = '';
                for ($i = count($history) - 1; $i >= 0; $i--) {
                    if (isset($history[$i]['role']) && $history[$i]['role'] === 'user') {
                        $lastUserMsg = trim((string)($history[$i]['content'] ?? ''));
                        break;
                    }
                }
                if ($lastUserMsg !== '' && strlen($userQuestion) < 60) {
                    $searchQuery = $lastUserMsg . ' ' . $userQuestion;
                }
            }

            $documentId = $request->input('document_id');
            $chunks = [];

            // Determine if vector embedding is needed
            // For scoped single-document queries, only generate vector if document actually has embedded chunks
            $shouldGenerateEmbedding = true;
            if ($documentId) {
                $hasEmbeddings = DB::table('document_chunks')
                    ->where('document_id', (int) $documentId)
                    ->whereNotNull('embedding')
                    ->exists();
                if (!$hasEmbeddings) {
                    $shouldGenerateEmbedding = false;
                }
            }

            if ($shouldGenerateEmbedding) {
                try {
                    // Convert search query into vector numbers using Gemini (if quota allows)
                    $embedding = $this->geminiService->generateEmbedding($searchQuery);
                    if (!empty($embedding)) {
                        $embeddingVector = '[' . implode(',', $embedding) . ']';
                    }
                } catch (\Throwable $embedError) {
                    Log::warning('RAG: Embedding generation failed (falling back to hybrid full-text search): ' . $embedError->getMessage());
                }
            }

            // Step 2: Search database using Hybrid Keyword + Vector Retrieval
            if ($documentId) {
                // Scoped search for a single document (Brave-style drawer)
                $chunks = $this->retrieveScopedChunks((int) $documentId, $embeddingVector, $keywords);
            } else {
                // Global repository search (Floating AI Assistant across all theses)
                $chunks = $this->retrieveGlobalChunks($embeddingVector, $keywords, $userQuestion);
            }

            // Step 3: Handle case when no thesis chunks exist yet
            if (empty($chunks)) {
                if ($documentId) {
                    $doc = DB::table('documents')->where('id', $documentId)->where('status', 'approved')->first();
                    if ($doc) {
                        $contextText = "Thesis Title: {$doc->title}\nAuthor: {$doc->author}\nDepartment: {$doc->department}\nAbstract:\n{$doc->abstract}";
                        $answer = $this->geminiService->generateChatResponse($userQuestion, $contextText, $history);
                        return response()->json([
                            'error' => false,
                            'answer' => $answer,
                            'sources' => [[
                                'id' => $doc->id,
                                'title' => $doc->title,
                                'author' => $doc->author,
                                'similarity' => 100
                            ]]
                        ]);
                    }
                }

                Log::warning('RAG: document_chunks returned no results.', ['question' => $userQuestion]);

                return response()->json([
                    'error' => false,
                    'answer' => 'There are currently no processed thesis documents available in the repository.',
                    'sources' => []
                ]);
            }

            // Step 4: Build rich thesis context text to feed into Gemini AI (up to 8 chunks, 1400 chars each)
            $contextParts = [];

            if ($documentId) {
                $mainDoc = DB::table('documents')->where('id', $documentId)->first();
                if ($mainDoc) {
                    $contextParts[] =
                        "[Thesis Overview]\n" .
                        "Title: {$mainDoc->title}\n" .
                        "Author: {$mainDoc->author}\n" .
                        "Department: {$mainDoc->department}\n" .
                        "Abstract:\n" .
                        mb_substr((string) $mainDoc->abstract, 0, 1200);
                }
            }

            foreach (array_slice($chunks, 0, 5) as $index => $chunk) {
                $score = round(((float) ($chunk->similarity ?? 0.85)) * 100, 1);
                $docTitle = $chunk->document_title ?? 'Thesis Document';
                $docAuthor = $chunk->document_author ?? 'Unknown Author';
                $cleanText = mb_substr(trim((string) $chunk->chunk_text), 0, 750);

                $contextParts[] =
                    "[Source #" . ($index + 1) . "]\n" .
                    "Thesis Title: {$docTitle}\n" .
                    "Author: {$docAuthor}\n" .
                    "Similarity: {$score}%\n" .
                    "Content:\n" .
                    $cleanText;
            }

            $contextText = implode("\n\n---\n\n", $contextParts);

            // Step 5: Ask Gemini to answer the question using multi-turn conversation memory
            $answer = null;
            try {
                $answer = $this->geminiService->generateChatResponse($userQuestion, $contextText, $history);
            } catch (\Throwable $llmErr) {
                Log::warning('RAG: Google AI generation failed or high demand, using grounded manuscript passages: ' . $llmErr->getMessage());

                // Fallback: ground response directly from the top matching manuscript passages so students never hit 504 Gateway Timeout
                $snippets = [];
                foreach (array_slice($chunks, 0, 3) as $c) {
                    $raw = trim((string) $c->chunk_text);
                    $clean = preg_replace('/ST\.\s*ANTHONY.*?Antique\s*\d{4}/si', '', $raw);
                    $clean = trim((string) preg_replace('/\s+/', ' ', (string) $clean));
                    if ($clean !== '') {
                        $pInfo = !empty($c->page_number) ? " *(Page {$c->page_number})*" : "";
                        $snippets[] = "> \"" . mb_substr($clean, 0, 320) . "...\"{$pInfo}";
                    }
                }

                $mainTitle = $chunks[0]->document_title ?? 'the thesis manuscript';
                $answer = "Based directly on the technical documentation found in **{$mainTitle}**:\n\n" .
                    implode("\n\n", $snippets) . "\n\n" .
                    "*(Direct manuscript excerpt. Conversational AI synthesis will automatically refresh once Google AI traffic subsides).*";
            }

            // Step 6: Deduplicate sources so each thesis card appears cleanly in the UI
            $uniqueSources = [];
            foreach ($chunks as $chunk) {
                $docId = $chunk->document_id;
                if (!isset($uniqueSources[$docId])) {
                    $uniqueSources[$docId] = [
                        'id' => $docId,
                        'title' => $chunk->document_title ?? 'Thesis Document',
                        'author' => $chunk->document_author ?? 'Unknown Author',
                        'similarity' => round(((float) ($chunk->similarity ?? 0.85)) * 100, 1),
                    ];
                }
            }

            // Step 7: Return AI answer and cited source documents
            return response()->json([
                'error' => false,
                'answer' => $answer,
                'sources' => array_values($uniqueSources)
            ]);

        } catch (\Throwable $e) {
            Log::error('RAG CHATBOT ERROR', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'AI Assistant temporarily unavailable: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function extractSearchKeywords(string $text): array
    {
        $stopWords = [
            'what', 'which', 'where', 'when', 'who', 'whom', 'whose', 'why', 'how',
            'the', 'a', 'an', 'and', 'or', 'but', 'is', 'are', 'was', 'were', 'be', 'been',
            'being', 'have', 'has', 'had', 'do', 'does', 'did', 'to', 'from', 'in', 'out',
            'on', 'off', 'over', 'under', 'again', 'further', 'then', 'once', 'here',
            'there', 'all', 'any', 'both', 'each', 'few', 'more', 'most', 'other', 'some',
            'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very',
            'can', 'will', 'just', 'should', 'now', 'used', 'using', 'study', 'thesis',
            'research', 'paper', 'project', 'document', 'documents', 'tell', 'about',
            'give', 'summarize', 'summary', 'explain', 'detail', 'details', 'find',
            'their', 'they', 'them', 'these', 'those', 'also', 'with', 'researcher', 'researchers',
            'could', 'would', 'done', 'does', 'item', 'items', 'make', 'made'
        ];

        // Normalize repeated letters (e.g. microcontrolllers -> microcontrollers)
        $clean = preg_replace('/(.)\\1{2,}/u', '$1$1', mb_strtolower($text));
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', (string) $clean);
        $words = array_filter(explode(' ', (string) $clean), fn($w) => strlen($w) >= 3);

        $filtered = array_values(array_filter($words, fn($w) => !in_array($w, $stopWords, true)));

        $normalized = [];
        foreach ($filtered as $w) {
            $normalized[] = $w;
            if (str_ends_with($w, 's') && strlen($w) > 4) {
                $normalized[] = substr($w, 0, -1);
            }
            if (strlen($w) > 8) {
                $normalized[] = substr($w, 0, 8);
            }
        }

        return array_slice(array_unique($normalized), 0, 16);
    }

    protected function retrieveScopedChunks(int $documentId, ?string $embeddingVector, array $keywords): array
    {
        $chunksById = [];

        // 1. Keyword search inside this document
        if (!empty($keywords)) {
            $scoreClauses = [];
            $whereClauses = [];
            $bindings = [];

            foreach ($keywords as $kw) {
                $scoreClauses[] = "(CASE WHEN dc.chunk_text ILIKE ? THEN 3 ELSE 0 END)";
                $whereClauses[] = "dc.chunk_text ILIKE ?";
                $bindings[] = "%{$kw}%";
            }

            $scoreSql = implode(' + ', $scoreClauses) . " + (CASE WHEN dc.page_number > 5 THEN 1 ELSE 0 END)";
            $sql = "
                SELECT
                    dc.id,
                    dc.document_id,
                    dc.page_number,
                    dc.chunk_text,
                    d.title AS document_title,
                    d.author AS document_author,
                    ($scoreSql) AS score
                FROM document_chunks dc
                INNER JOIN documents d ON d.id = dc.document_id
                WHERE dc.document_id = ?
                  AND (" . implode(' OR ', $whereClauses) . ")
                ORDER BY score DESC, dc.page_number ASC
                LIMIT 8
            ";

            $fullBindings = array_merge($bindings, [$documentId], $bindings);
            $kwChunks = DB::select($sql, $fullBindings);

            foreach ($kwChunks as $c) {
                $c->similarity = min(0.98, 0.70 + ((float) $c->score * 0.05));
                $chunksById[$c->id] = $c;
            }
        }

        // 2. Vector search if embedding is available and document has embedded chunks
        if ($embeddingVector) {
            $vecChunks = DB::select("
                SELECT
                    dc.id,
                    dc.chunk_text,
                    dc.page_number,
                    dc.document_id,
                    d.title AS document_title,
                    d.author AS document_author,
                    1 - (dc.embedding OPERATOR(extensions.<=>) ?::extensions.vector) AS similarity
                FROM document_chunks dc
                INNER JOIN documents d ON d.id = dc.document_id
                WHERE dc.embedding IS NOT NULL
                  AND d.status = 'approved'
                  AND dc.document_id = ?
                ORDER BY dc.embedding OPERATOR(extensions.<=>) ?::extensions.vector ASC
                LIMIT 6
            ", [$embeddingVector, $documentId, $embeddingVector]);

            foreach ($vecChunks as $c) {
                if (!isset($chunksById[$c->id])) {
                    $chunksById[$c->id] = $c;
                }
            }
        }

        // 3. Fallback: if still empty, pull first 8 pages
        if (empty($chunksById)) {
            $raw = DB::table('document_chunks')
                ->join('documents', 'documents.id', '=', 'document_chunks.document_id')
                ->where('document_chunks.document_id', $documentId)
                ->where('documents.status', 'approved')
                ->orderBy('document_chunks.page_number', 'asc')
                ->limit(8)
                ->select([
                    'document_chunks.id',
                    'document_chunks.chunk_text',
                    'document_chunks.page_number',
                    'document_chunks.document_id',
                    'documents.title as document_title',
                    'documents.author as document_author',
                    DB::raw('0.90 as similarity')
                ])
                ->get();

            foreach ($raw as $c) {
                $chunksById[$c->id] = $c;
            }
        }

        return array_values($chunksById);
    }

    protected function retrieveGlobalChunks(?string $embeddingVector, array $keywords, string $userQuestion): array
    {
        $chunksById = [];

        // 1. Keyword search across ALL approved documents and chunks
        if (!empty($keywords)) {
            // Find top matching documents based on title + abstract relevance
            $docScoreClauses = [];
            $docWhereClauses = [];
            $docBindings = [];

            foreach ($keywords as $kw) {
                $docScoreClauses[] = "(CASE WHEN title ILIKE ? THEN 5 WHEN abstract ILIKE ? THEN 2 ELSE 0 END)";
                $docWhereClauses[] = "title ILIKE ? OR abstract ILIKE ?";
                $docBindings[] = "%{$kw}%";
                $docBindings[] = "%{$kw}%";
            }

            $docScoreSql = implode(' + ', $docScoreClauses);
            $docWhereSql = implode(' OR ', $docWhereClauses);

            $topDocs = DB::select("
                SELECT id, ($docScoreSql) as score
                FROM documents
                WHERE status = 'approved' AND ($docWhereSql)
                ORDER BY score DESC
                LIMIT 4
            ", array_merge($docBindings, $docBindings));

            $matchedDocIds = array_column($topDocs, 'id');

            // Build chunk search clauses
            $scoreClauses = [];
            $whereClauses = [];
            $bindings = [];

            foreach ($keywords as $kw) {
                $scoreClauses[] = "(CASE WHEN dc.chunk_text ILIKE ? THEN 3 ELSE 0 END)";
                $whereClauses[] = "dc.chunk_text ILIKE ?";
                $bindings[] = "%{$kw}%";
            }

            $scoreSql = implode(' + ', $scoreClauses) . " + (CASE WHEN dc.page_number > 5 THEN 1 ELSE 0 END)";

            $docFilterSql = !empty($matchedDocIds)
                ? "AND dc.document_id IN (" . implode(',', $matchedDocIds) . ")"
                : "";

            $sql = "
                WITH ranked_chunks AS (
                    SELECT
                        dc.id,
                        dc.document_id,
                        dc.page_number,
                        dc.chunk_text,
                        d.title AS document_title,
                        d.author AS document_author,
                        ($scoreSql) AS score,
                        ROW_NUMBER() OVER (
                            PARTITION BY dc.document_id
                            ORDER BY ($scoreSql) DESC, dc.page_number ASC
                        ) as rn
                    FROM document_chunks dc
                    INNER JOIN documents d ON d.id = dc.document_id
                    WHERE d.status = 'approved'
                      {$docFilterSql}
                      AND (" . implode(' OR ', $whereClauses) . ")
                )
                SELECT * FROM ranked_chunks
                WHERE rn <= 3
                ORDER BY score DESC
                LIMIT 10
            ";

            $fullBindings = array_merge($bindings, $bindings, $bindings);
            $kwChunks = DB::select($sql, $fullBindings);

            foreach ($kwChunks as $c) {
                $c->similarity = min(0.98, 0.75 + ((float) $c->score * 0.04));
                $chunksById[$c->id] = $c;
            }
        }

        // 2. Vector search on embedded chunks (if vector exists)
        if ($embeddingVector) {
            $vecChunks = DB::select("
                WITH ranked_chunks AS (
                    SELECT
                        dc.id,
                        dc.chunk_text,
                        dc.page_number,
                        dc.document_id,
                        d.title AS document_title,
                        d.author AS document_author,
                        1 - (dc.embedding OPERATOR(extensions.<=>) ?::extensions.vector) AS similarity,
                        ROW_NUMBER() OVER (
                            PARTITION BY dc.document_id
                            ORDER BY dc.embedding OPERATOR(extensions.<=>) ?::extensions.vector ASC
                        ) as rn
                    FROM document_chunks dc
                    INNER JOIN documents d ON d.id = dc.document_id
                    WHERE dc.embedding IS NOT NULL
                      AND d.status = 'approved'
                )
                SELECT * FROM ranked_chunks
                WHERE rn <= 2
                ORDER BY similarity DESC
                LIMIT 6
            ", [$embeddingVector, $embeddingVector]);

            foreach ($vecChunks as $c) {
                if (!isset($chunksById[$c->id])) {
                    $chunksById[$c->id] = $c;
                }
            }
        }

        return array_values($chunksById);
    }
}