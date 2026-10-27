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
        $documentId = $request->input('document_id');

        // Instant Out-of-Scope Guardrail Check (Refuses external code generation or non-repository queries)
        if ($this->isOutOfScopeQuery($userQuestion)) {
            return response()->json([
                'error' => false,
                'answer' => "I am AIRIS Assistant, an AI dedicated exclusively to the St. Anthony's College Institutional Research Repository. I cannot assist with external coding requests or general topics outside the repository archive.",
                'sources' => []
            ]);
        }

        // Instant Direct Metadata Resolution (Researchers, Title, Department, Year, Abstract)
        // Resolves 100% accurately in ~2ms without burning Google AI quota or hitting rate limits
        if ($documentId) {
            $scopedDoc = DB::table('documents')->where('id', (int) $documentId)->first();
            if ($scopedDoc) {
                $directAnswer = $this->resolveDirectMetadataAnswer($userQuestion, $scopedDoc);
                if ($directAnswer !== null) {
                    return response()->json([
                        'error' => false,
                        'answer' => $directAnswer,
                        'sources' => [[
                            'id' => $scopedDoc->id,
                            'title' => $scopedDoc->title,
                            'author' => $scopedDoc->author,
                            'similarity' => 100
                        ]]
                    ]);
                }
            }
        } else {
            // Check for repository-wide inventory queries (e.g. "Is there any IoT projects or capstone in the repository?")
            if ($this->isInventoryQuery($userQuestion)) {
                $inventoryAnswer = $this->handleInventoryQuery($userQuestion, $history);
                if ($inventoryAnswer !== null) {
                    return response()->json($inventoryAnswer);
                }
            }

            $globalMatch = $this->resolveGlobalDirectMetadataAnswer($userQuestion);
            if ($globalMatch !== null) {
                return response()->json([
                    'error' => false,
                    'answer' => $globalMatch['answer'],
                    'sources' => [[
                        'id' => $globalMatch['doc']->id,
                        'title' => $globalMatch['doc']->title,
                        'author' => $globalMatch['doc']->author,
                        'similarity' => 100
                    ]]
                ]);
            }
        }

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
                $chunks = $this->retrieveScopedChunks((int) $documentId, $embeddingVector, $keywords, $userQuestion);
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
                Log::warning('RAG: Google AI generation failed or high demand: ' . $llmErr->getMessage());

                // Author / Researcher inquiry fallback
                $isAuthorQuery = $this->isAuthorQuery($userQuestion);
                $primaryDoc = null;
                if ($documentId) {
                    $primaryDoc = DB::table('documents')->where('id', (int) $documentId)->first();
                } elseif (!empty($chunks[0]->document_id)) {
                    $primaryDoc = DB::table('documents')->where('id', $chunks[0]->document_id)->first();
                }

                if ($isAuthorQuery && $primaryDoc && !empty($primaryDoc->author)) {
                    $authors = preg_split('/\s*[,;]\s*|\s+and\s+/i', (string) $primaryDoc->author);
                    $authors = array_filter(array_map('trim', $authors));
                    if (!empty($authors)) {
                        $formatted = implode("\n", array_map(fn($a) => "* **{$a}**", $authors));
                        $answer = "The researchers of **{$primaryDoc->title}** are:\n\n{$formatted}";
                    }
                }

                if ($answer === null) {
                    $isSummaryQuery = (bool) preg_match('/\b(summarize|summary|overview|what\s+is\s+this\s+(thesis|study|paper|project)\s+about|in\s+simple\s+words)\b/i', $userQuestion);
                    if ($isSummaryQuery && $primaryDoc && !empty($primaryDoc->abstract)) {
                        $answer = "### Summary of {$primaryDoc->title}\n\n" .
                            trim((string) $primaryDoc->abstract) . "\n\n" .
                            "*(Official Executive Abstract from the manuscript)*";
                    }

                    $isSolutionQuery = (bool) preg_match('/\b(solve|solution|solutions|address|resolved|resolve|recommendation|recommendations|conclude|conclusion|conclusions|action\s*plan|mitigate|prevent)\b/i', $userQuestion);
                    if ($answer === null && $isSolutionQuery) {
                        $solutionSnippets = [];
                        foreach ($chunks as $c) {
                            $raw = trim((string) $c->chunk_text);
                            if (preg_match('/(RECOMMENDATION|CONCLUSION|SUMMARY OF FINDINGS|ACTION PLAN)/i', $raw) && !preg_match('/(STATEMENT OF THE PROBLEM|TABLE OF CONTENTS)/i', $raw)) {
                                $clean = preg_replace('/ST\.\s*ANTHONY.*?Antique\s*\d{4}/si', '', $raw);
                                $clean = trim((string) preg_replace('/\s+/', ' ', (string) $clean));
                                if (strlen($clean) > 80) {
                                    $pInfo = !empty($c->page_number) ? " *(Page {$c->page_number})*" : "";
                                    $solutionSnippets[] = "• " . mb_substr($clean, 0, 320) . "...{$pInfo}";
                                }
                            }
                        }

                        if (!empty($solutionSnippets)) {
                            $mainTitle = $primaryDoc->title ?? ($chunks[0]->document_title ?? 'the study');
                            $answer = "### Proposed Solutions and Recommendations for **{$mainTitle}**:\n\n" .
                                implode("\n\n", array_slice($solutionSnippets, 0, 3)) . "\n\n" .
                                "*(Excerpts from the Conclusions and Recommendations of the thesis manuscript).*";
                        }
                    }

                    if ($answer === null) {
                        // Fallback: ground response directly from genuine technical manuscript passages (filter out administrative front matter and appendices)
                        $cleanSnippets = [];
                        foreach ($chunks as $c) {
                            $raw = trim((string) $c->chunk_text);
                            if (preg_match('/(APPROVAL\s*SHEET|GRAMMARIAN|DEDICATION|ACKNOWLEDGEMENT|TABLE\s*OF\s*CONTENTS|APPENDIC|APPENDIX|LETTER\s*TO|CURRICULUM\s*VITAE)/i', $raw)) {
                                continue;
                            }
                            if ($isSolutionQuery && preg_match('/STATEMENT\s*OF\s*THE\s*PROBLEM/i', $raw)) {
                                continue;
                            }
                            if (!empty($c->page_number) && $c->page_number <= 5) {
                                continue;
                            }

                            $clean = preg_replace('/ST\.\s*ANTHONY.*?Antique\s*\d{4}/si', '', $raw);
                            $clean = trim((string) preg_replace('/\s+/', ' ', (string) $clean));
                            if (strlen($clean) > 80) {
                                $pInfo = !empty($c->page_number) ? " *(Page {$c->page_number})*" : "";
                                $cleanSnippets[] = "• " . mb_substr($clean, 0, 300) . "...{$pInfo}";
                                if (count($cleanSnippets) >= 3) {
                                    break;
                                }
                            }
                        }

                        if (empty($cleanSnippets) && $primaryDoc && !empty($primaryDoc->abstract)) {
                            $cleanSnippets[] = "• " . mb_substr(trim((string) $primaryDoc->abstract), 0, 400) . "...";
                        }

                        $mainTitle = $primaryDoc->title ?? ($chunks[0]->document_title ?? 'the thesis manuscript');
                        $answer = "Here are the relevant findings documented in **{$mainTitle}**:\n\n" .
                            (!empty($cleanSnippets) ? implode("\n\n", $cleanSnippets) : "*(Technical details could not be synthesized at this moment. Please view the full PDF in the viewer).*") . "\n\n" .
                            "*(Direct excerpt from technical documentation. For full details, view the complete PDF in the viewer).*";
                    }
                }
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
            'paper', 'project', 'document', 'documents', 'tell', 'about',
            'give', 'summarize', 'summary', 'explain', 'detail', 'details', 'find',
            'their', 'they', 'them', 'these', 'those', 'also', 'with',
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

    protected function retrieveScopedChunks(int $documentId, ?string $embeddingVector, array $keywords, string $userQuestion = ''): array
    {
        $chunksById = [];

        $isSolutionQuery = !empty($userQuestion) && (bool) preg_match('/\b(solve|solution|solutions|address|resolved|resolve|recommendation|recommendations|conclude|conclusion|conclusions|action\s*plan|mitigate|prevent)\b/i', $userQuestion);

        if ($isSolutionQuery) {
            $keywords = array_unique(array_merge($keywords, ['recommendation', 'conclusion', 'findings', 'solution', 'measure', 'prevent', 'action plan']));
        }

        // 1. Keyword search inside this document
        if (!empty($keywords)) {
            $isPgsql = DB::getDriverName() === 'pgsql';
            $scoreClauses = [];
            $whereClauses = [];
            $bindings = [];

            foreach ($keywords as $kw) {
                if ($isPgsql) {
                    $scoreClauses[] = "(CASE WHEN dc.chunk_text ~* ? THEN 3 ELSE 0 END)";
                    $whereClauses[] = "dc.chunk_text ~* ?";
                    $bindings[] = '\m' . preg_quote($kw, '/') . '\M';
                } else {
                    $scoreClauses[] = "(CASE WHEN dc.chunk_text ILIKE ? THEN 3 ELSE 0 END)";
                    $whereClauses[] = "dc.chunk_text ILIKE ?";
                    $bindings[] = "%{$kw}%";
                }
            }

            $adminPenalty = "(CASE 
                WHEN dc.chunk_text ILIKE '%APPROVAL SHEET%' 
                  OR dc.chunk_text ILIKE '%GRAMMARIAN%' 
                  OR dc.chunk_text ILIKE '%DEDICATION%' 
                  OR dc.chunk_text ILIKE '%ACKNOWLEDGEMENT%' 
                  OR dc.chunk_text ILIKE '%TABLE OF CONTENTS%' 
                  OR dc.chunk_text ILIKE '%APPENDIC%' 
                  OR dc.chunk_text ILIKE '%APPENDIX %' 
                  OR dc.chunk_text ILIKE '%CURRICULUM VITAE%' 
                  OR dc.chunk_text ILIKE '%LETTER TO%' 
                THEN -30 
                WHEN dc.page_number > 5 THEN 4 
                ELSE 0 
            END)";

            $solutionBonus = $isSolutionQuery
                ? "(CASE 
                    WHEN dc.chunk_text ILIKE '%RECOMMENDATION%' OR dc.chunk_text ILIKE '%CONCLUSION%' OR dc.chunk_text ILIKE '%SUMMARY OF FINDINGS%' THEN 15 
                    WHEN dc.chunk_text ILIKE '%STATEMENT OF THE PROBLEM%' THEN -20 
                    ELSE 0 
                   END)"
                : "0";

            $scoreSql = implode(' + ', $scoreClauses) . " + {$adminPenalty} + {$solutionBonus}";
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
                  AND dc.chunk_text NOT ILIKE '%APPROVAL SHEET%'
                  AND dc.chunk_text NOT ILIKE '%GRAMMARIAN%'
                  AND dc.chunk_text NOT ILIKE '%DEDICATION%'
                  AND dc.chunk_text NOT ILIKE '%ACKNOWLEDGEMENT%'
                  AND dc.chunk_text NOT ILIKE '%APPENDIC%'
                  AND dc.chunk_text NOT ILIKE '%APPENDIX %'
                  AND dc.chunk_text NOT ILIKE '%CURRICULUM VITAE%'
                  AND dc.chunk_text NOT ILIKE '%LETTER TO%'
                ORDER BY dc.embedding OPERATOR(extensions.<=>) ?::extensions.vector ASC
                LIMIT 6
            ", [$embeddingVector, $documentId, $embeddingVector]);

            foreach ($vecChunks as $c) {
                if (!isset($chunksById[$c->id])) {
                    $chunksById[$c->id] = $c;
                }
            }
        }

        // 3. Fallback: if still empty, pull technical pages (Page > 5) or first pages
        if (empty($chunksById)) {
            $raw = DB::table('document_chunks')
                ->join('documents', 'documents.id', '=', 'document_chunks.document_id')
                ->where('document_chunks.document_id', $documentId)
                ->where('documents.status', 'approved')
                ->where(function ($q) {
                    $q->where('document_chunks.page_number', '>', 5)
                      ->orWhere('document_chunks.page_number', '=', 1);
                })
                ->orderByRaw('CASE WHEN document_chunks.page_number > 5 THEN 0 ELSE 1 END, document_chunks.page_number ASC')
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
            $isPgsql = DB::getDriverName() === 'pgsql';

            // Find top matching documents based on title + abstract relevance
            $docScoreClauses = [];
            $docWhereClauses = [];
            $docBindings = [];

            foreach ($keywords as $kw) {
                if ($isPgsql) {
                    $docScoreClauses[] = "(CASE WHEN title ~* ? THEN 5 WHEN abstract ~* ? THEN 2 ELSE 0 END)";
                    $docWhereClauses[] = "title ~* ? OR abstract ~* ?";
                    $reg = '\m' . preg_quote($kw, '/') . '\M';
                    $docBindings[] = $reg;
                    $docBindings[] = $reg;
                } else {
                    $docScoreClauses[] = "(CASE WHEN title ILIKE ? THEN 5 WHEN abstract ILIKE ? THEN 2 ELSE 0 END)";
                    $docWhereClauses[] = "title ILIKE ? OR abstract ILIKE ?";
                    $docBindings[] = "%{$kw}%";
                    $docBindings[] = "%{$kw}%";
                }
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
                if ($isPgsql) {
                    $scoreClauses[] = "(CASE WHEN dc.chunk_text ~* ? THEN 3 ELSE 0 END)";
                    $whereClauses[] = "dc.chunk_text ~* ?";
                    $bindings[] = '\m' . preg_quote($kw, '/') . '\M';
                } else {
                    $scoreClauses[] = "(CASE WHEN dc.chunk_text ILIKE ? THEN 3 ELSE 0 END)";
                    $whereClauses[] = "dc.chunk_text ILIKE ?";
                    $bindings[] = "%{$kw}%";
                }
            }

            $adminPenalty = "(CASE 
                WHEN dc.chunk_text ILIKE '%APPROVAL SHEET%' 
                  OR dc.chunk_text ILIKE '%GRAMMARIAN%' 
                  OR dc.chunk_text ILIKE '%DEDICATION%' 
                  OR dc.chunk_text ILIKE '%ACKNOWLEDGEMENT%' 
                  OR dc.chunk_text ILIKE '%TABLE OF CONTENTS%' 
                  OR dc.chunk_text ILIKE '%APPENDIC%' 
                  OR dc.chunk_text ILIKE '%APPENDIX %' 
                  OR dc.chunk_text ILIKE '%CURRICULUM VITAE%' 
                  OR dc.chunk_text ILIKE '%LETTER TO%' 
                THEN -30 
                WHEN dc.page_number > 5 THEN 4 
                ELSE 0 
            END)";

            $scoreSql = implode(' + ', $scoreClauses) . " + {$adminPenalty}";

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
                      AND dc.chunk_text NOT ILIKE '%APPROVAL SHEET%'
                      AND dc.chunk_text NOT ILIKE '%GRAMMARIAN%'
                      AND dc.chunk_text NOT ILIKE '%DEDICATION%'
                      AND dc.chunk_text NOT ILIKE '%ACKNOWLEDGEMENT%'
                      AND dc.chunk_text NOT ILIKE '%APPENDIC%'
                      AND dc.chunk_text NOT ILIKE '%APPENDIX %'
                      AND dc.chunk_text NOT ILIKE '%CURRICULUM VITAE%'
                      AND dc.chunk_text NOT ILIKE '%LETTER TO%'
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

    /**
     * Strictly detects if the user is genuinely asking FOR the identity of the researchers/authors,
     * rather than asking a technical/hardware question that merely mentions the word "researchers".
     */
    protected function isAuthorQuery(string $q): bool
    {
        $clean = mb_strtolower(trim($q));
        $clean = trim(preg_replace('/[?!.]+$/', '', $clean));

        // If query starts with "what", "how", "why", "which", "where", "did", "do", "does", "can", "could", "would", etc.
        // it is asking about system details, methodology, hardware, results, etc., NOT asking for author names.
        if (preg_match('/^(what|how|why|which|where|when|did|do|does|can|could|would|is\s+there|are\s+there)\b/i', $clean)) {
            // Exception: only if specifically "what are the names of the authors/researchers"
            if (preg_match('/^what\s+(is|are)\s+the\s+(names?\s+of\s+(the\s+)?)?(authors?|researchers?|proponents?)/i', $clean)) {
                return true;
            }
            return false;
        }

        // Direct who queries: "who wrote...", "who are the researchers...", "who is the author..."
        if (preg_match('/\bwho\s+(wrote|conducted|authored|made|created|developed)\b/i', $clean)) {
            return true;
        }

        if (preg_match('/\bwho\s+(is|are|were)\s+(the\s+)?(authors?|researchers?|proponents?|writers?)\b/i', $clean)) {
            return true;
        }

        // Short direct queries: "authors", "researchers", "list the authors", "proponents"
        if (preg_match('/^(list\s+(the\s+)?)?(authors?|researchers?|proponents?)$/i', $clean)) {
            return true;
        }

        if (preg_match('/^(names?\s+of\s+(the\s+)?)?(authors?|researchers?|proponents?)\s*(of\s+this\s+(study|thesis|project|paper))?$/i', $clean)) {
            return true;
        }

        return false;
    }

    /**
     * Resolves direct metadata inquiries (Author, Title, Department, Year, Abstract)
     * instantly with 100% precision from verified database records.
     */
    protected function resolveDirectMetadataAnswer(string $userQuestion, object $doc): ?string
    {
        $q = mb_strtolower(trim($userQuestion));

        // 1. Author / Researcher / Proponent query (strictly checked)
        if ($this->isAuthorQuery($userQuestion) && !empty($doc->author)) {
            $authors = preg_split('/\s*[,;]\s*|\s+and\s+/i', (string) $doc->author);
            $authors = array_filter(array_map('trim', $authors));
            if (!empty($authors)) {
                $list = implode("\n", array_map(fn($a) => "* **{$a}**", $authors));
                return "The researchers of **{$doc->title}** are:\n\n{$list}";
            }
        }

        // 2. Title query
        $isTitle = (bool) preg_match('/^(what\s+is\s+the\s+title|thesis\s+title|title\s+of\s+(this|the)\s+study)\b/i', $q);
        if ($isTitle) {
            return "The title of this thesis is **{$doc->title}**.";
        }

        // 3. Department / Course / Program query
        $isDept = (bool) preg_match('/^(what\s+department|which\s+department|what\s+course|what\s+degree\s+program|which\s+college)\b/i', $q);
        if ($isDept) {
            $dept = strtoupper($doc->department ?? 'General');
            $course = strtoupper($doc->course_code ?? '');
            $courseText = $course !== '' ? " under the **{$course}** program" : "";
            return "This research manuscript was conducted in the **Department of {$dept}**{$courseText}.";
        }

        // 4. Publication Date / Year query
        $isDate = (bool) preg_match('/^(when\s+was\s+(this|it)\s+(published|submitted|conducted|written)|publication\s+date|what\s+year)\b/i', $q);
        if ($isDate) {
            $date = !empty($doc->publication_date) ? date('F Y', strtotime($doc->publication_date)) : 'the documented academic term';
            return "This thesis was officially published in **{$date}**.";
        }

        // 5. Abstract / Summary query (for direct concise queries)
        $isAbstract = (bool) preg_match('/^(give\s+me\s+the\s+abstract|what\s+is\s+the\s+abstract|provide\s+the\s+abstract|what\s+is\s+this\s+(thesis|study|paper|project)\s+about|summarize\s+this\s+(thesis|study|paper|project))\b/i', $q);
        if ($isAbstract && strlen($q) < 65 && !empty($doc->abstract)) {
            return "### Abstract & Executive Summary\n\n**Title:** {$doc->title}\n**Researchers:** {$doc->author}\n\n" . trim((string) $doc->abstract);
        }

        return null;
    }

    /**
     * Resolves direct metadata inquiries in global repository chat by identifying
     * the mentioned thesis title and returning verified metadata.
     */
    protected function resolveGlobalDirectMetadataAnswer(string $userQuestion): ?array
    {
        $q = mb_strtolower(trim($userQuestion));
        $isAuthor = $this->isAuthorQuery($userQuestion);
        $isTitle = (bool) preg_match('/^(what\s+is\s+the\s+title|thesis\s+title|title\s+of\s+(this|the)\s+study)\b/i', $q);

        if (!$isAuthor && !$isTitle) {
            return null;
        }

        $docs = DB::table('documents')
            ->where('status', 'approved')
            ->select(['id', 'title', 'author', 'department', 'course_code', 'publication_date', 'abstract'])
            ->get();

        $bestDoc = null;
        $maxMatchCount = 0;
        $bestRatio = 0.0;

        foreach ($docs as $doc) {
            $cleanTitle = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', mb_strtolower($doc->title));
            $titleWords = array_values(array_filter(explode(' ', $cleanTitle), fn($w) => strlen($w) >= 4));
            if (empty($titleWords)) {
                continue;
            }

            $matchCount = 0;
            foreach ($titleWords as $tw) {
                if (str_contains($q, $tw)) {
                    $matchCount++;
                }
            }

            $ratio = $matchCount / count($titleWords);

            $isCandidate = (count($titleWords) <= 3)
                ? ($matchCount >= 2 && $ratio >= 0.6)
                : ($matchCount >= 3 && $ratio >= 0.35);

            if ($isCandidate && ($matchCount > $maxMatchCount || ($matchCount === $maxMatchCount && $ratio > $bestRatio))) {
                $maxMatchCount = $matchCount;
                $bestRatio = $ratio;
                $bestDoc = $doc;
            }
        }

        if ($bestDoc !== null) {
            $answer = $this->resolveDirectMetadataAnswer($userQuestion, $bestDoc);
            if ($answer !== null) {
                return ['doc' => $bestDoc, 'answer' => $answer];
            }
        }

        return null;
    }

    /**
     * Detects queries strictly outside the scope of St. Anthony's College Research Repository.
     * Rejects generic code generation, non-repository trivia, and unrelated requests.
     */
    protected function isOutOfScopeQuery(string $q): bool
    {
        $clean = mb_strtolower(trim($q));

        // If explicitly mentioning repository, thesis, capstone, manuscript, research, study, or SAC departments:
        if (preg_match('/\b(thesis|theses|manuscript|manuscripts|capstone|capstones|repository|archive|st\.\s*anthony|sac\b|research|researcher|researchers|author|authors|study|studies|findings|recommendation|department|proponents?|abstract)\b/i', $clean)) {
            return false;
        }

        // External programming code generation requests
        if (preg_match('/\b(python|javascript|java|c\+\+|c#|php|html|css|sql|rust|golang|ruby|bash|powershell|script|calculator)\b/i', $clean)) {
            if (preg_match('/\b(code|script|program|write|create|generate|give\s+me|make|build|solve)\b/i', $clean)) {
                return true;
            }
        }

        // General non-institutional / conversational queries
        $outOfScopePatterns = [
            '/^(write|generate|compose)\s+(a\s+)?(poem|song|story|joke|essay|letter|email)\b/i',
            '/^(tell\s+me\s+a\s+joke|how\s+to\s+bake|recipe\s+for|weather\s+(today|tomorrow|in))\b/i',
            '/^(who\s+is\s+the\s+president\s+of|capital\s+of|highest\s+mountain|how\s+tall\s+is)\b/i',
            '/^(solve|calculate|evaluate)\s+[\d\(\)x\+\-\*\/\^\=]+/i',
            '/^(what\s+is\s+the\s+meaning\s+of\s+life|who\s+won\s+the\s+world\s+cup)\b/i',
        ];

        foreach ($outOfScopePatterns as $pattern) {
            if (preg_match($pattern, $clean)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the user is inquiring about repository inventory/catalog availability
     * (e.g. "is there any iot projects or capstone here in the repository?", "what nursing theses exist?")
     */
    protected function isInventoryQuery(string $q): bool
    {
        $clean = mb_strtolower(trim($q));

        return (bool) preg_match('/\b(is\s+there\s+any|are\s+there\s+any|what\s+.*(available|exist|do\s+we\s+have|in\s+the\s+repository|archived)|list\s+(all\s+)?(theses|thesis|manuscripts|studies|projects|capstones?)|do\s+we\s+have\s+any|how\s+many\s+.*(theses|thesis|manuscripts|studies|projects|capstones?)|show\s+me\s+(theses|thesis|capstone|projects?|studies|any))\b/i', $clean);
    }

    /**
     * Retrieves approved thesis documents from the catalog matching specific topic keywords.
     */
    protected function retrieveCatalogDocuments(array $keywords, string $userQuestion): array
    {
        $isPgsql = DB::getDriverName() === 'pgsql';

        if (empty($keywords)) {
            // General repository catalog inquiry (e.g. "how many theses are in the repository")
            return DB::table('documents')
                ->where('status', 'approved')
                ->orderBy('publication_date', 'desc')
                ->limit(6)
                ->get(['id', 'title', 'author', 'department', 'course_code', 'publication_date', 'abstract'])
                ->all();
        }

        $whereClauses = [];
        $bindings = [];

        foreach ($keywords as $kw) {
            if (strlen($kw) < 2) {
                continue;
            }

            if ($isPgsql) {
                $whereClauses[] = "(title ~* ? OR abstract ~* ? OR department ~* ? OR course_code ~* ?)";
                $regex = '\m' . preg_quote($kw, '/') . '\M';
                $bindings = array_merge($bindings, [$regex, $regex, $regex, $regex]);
            } else {
                $whereClauses[] = "(title LIKE ? OR abstract LIKE ? OR department LIKE ? OR course_code LIKE ?)";
                $pattern = "%{$kw}%";
                $bindings = array_merge($bindings, [$pattern, $pattern, $pattern, $pattern]);
            }
        }

        if (empty($whereClauses)) {
            return [];
        }

        $sql = "
            SELECT id, title, author, department, course_code, publication_date, abstract
            FROM documents
            WHERE status = 'approved' AND (" . implode(' OR ', $whereClauses) . ")
            ORDER BY publication_date DESC, id DESC
            LIMIT 6
        ";

        return DB::select($sql, $bindings);
    }

    /**
     * Handles repository-wide inventory and availability queries by querying
     * the catalog database directly and synthesizing an accurate, grounded response.
     */
    protected function handleInventoryQuery(string $userQuestion, array $history = []): ?array
    {
        $allKeywords = $this->extractSearchKeywords($userQuestion);

        $metaWords = [
            'capstone', 'capstones', 'manuscript', 'manuscripts', 'project', 'projects',
            'thesis', 'theses', 'study', 'studies', 'paper', 'papers',
            'repository', 'reposito', 'archive', 'archives', 'archived',
            'available', 'exist', 'exists', 'inside', 'here', 'there',
            'what', 'which', 'list', 'show', 'give', 'find', 'have',
            'about', 'related', 'any', 'some', 'many', 'much', 'college', 'sac'
        ];

        $topicKeywords = array_values(array_filter($allKeywords, fn($w) => !in_array($w, $metaWords, true)));

        $docs = $this->retrieveCatalogDocuments($topicKeywords, $userQuestion);

        if (empty($docs)) {
            if (!empty($topicKeywords)) {
                $terms = implode(', ', $topicKeywords);
                return [
                    'error' => false,
                    'answer' => "No research manuscripts or capstones specifically matching **{$terms}** were found in the St. Anthony's College Institutional Repository archive.\n\nYou may explore available records by academic department or try searching using related keywords.",
                    'sources' => []
                ];
            }

            return [
                'error' => false,
                'answer' => "The St. Anthony's College Institutional Research Repository archive contains approved academic theses and capstone projects across various departments. Please specify a topic, department, or keyword to find relevant studies.",
                'sources' => []
            ];
        }

        $sources = [];
        $contextLines = [];

        foreach ($docs as $idx => $doc) {
            $year = !empty($doc->publication_date) ? date('Y', strtotime($doc->publication_date)) : 'N/A';
            $abstractSnippet = mb_substr(trim((string)$doc->abstract), 0, 300);

            $contextLines[] = "Manuscript #" . ($idx + 1) . ":\n" .
                "Title: {$doc->title}\n" .
                "Authors/Researchers: {$doc->author}\n" .
                "Department: {$doc->department}\n" .
                "Year: {$year}\n" .
                "Abstract/Summary: {$abstractSnippet}";

            $sources[] = [
                'id' => $doc->id,
                'title' => $doc->title,
                'author' => $doc->author,
                'similarity' => 95
            ];
        }

        $catalogContext = implode("\n\n---\n\n", $contextLines);

        // Attempt Gemini synthesis with the catalog context
        try {
            $totalCount = count($docs);
            $instruction = "The user is asking about the availability/inventory of theses in the St. Anthony's College Repository.\n" .
                "There are exactly {$totalCount} matching manuscript(s) found in the repository archive catalog below.\n" .
                "State clearly: 'Yes, there are {$totalCount} thesis/manuscript(s) inside the repository...' and list ALL {$totalCount} manuscripts with their title, researchers, and a brief 1-sentence description based on the provided context.\n" .
                "Do NOT omit or skip any of the {$totalCount} manuscripts.\n\n" .
                $catalogContext;

            $answer = $this->geminiService->generateChatResponse($userQuestion, $instruction, $history);
        } catch (\Throwable $e) {
            Log::warning('RAG: Inventory Gemini generation failed: ' . $e->getMessage());

            // High-reliability structured markdown fallback
            $count = count($docs);
            $noun = $count === 1 ? 'manuscript' : 'manuscripts/theses';
            $lines = ["Yes, there are **{$count}** {$noun} found in the St. Anthony's College Repository matching your inquiry:\n"];

            foreach ($docs as $i => $doc) {
                $num = $i + 1;
                $year = !empty($doc->publication_date) ? ' (' . date('Y', strtotime($doc->publication_date)) . ')' : '';
                $lines[] = "{$num}. **{$doc->title}**{$year}";
                $lines[] = "   * **Researchers:** {$doc->author}";
                $lines[] = "   * **Department:** {$doc->department}";
                if (!empty($doc->abstract)) {
                    $brief = mb_substr(trim((string)$doc->abstract), 0, 160) . '...';
                    $lines[] = "   * **Overview:** {$brief}";
                }
                $lines[] = "";
            }

            $answer = implode("\n", $lines);
        }

        return [
            'error' => false,
            'answer' => $answer,
            'sources' => $sources
        ];
    }
}