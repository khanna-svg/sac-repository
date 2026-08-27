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

        try {
            // Step 1: Convert student's question into vector numbers using Gemini
            $embedding = $this->geminiService->generateEmbedding($userQuestion);

            if (empty($embedding)) {
                throw new \Exception('Failed to generate embedding for the question.');
            }

            $embeddingVector = '[' . implode(',', $embedding) . ']';

            $documentId = $request->input('document_id');
            $docFilterSql = $documentId ? "AND dc.document_id = ?" : "";
            $bindings = $documentId 
                ? [$embeddingVector, $documentId, $embeddingVector]
                : [$embeddingVector, $embeddingVector];

            // Step 2: Search database for top 5 closest matching thesis text chunks
            $chunks = DB::select("
                SELECT
                    dc.chunk_text,
                    dc.document_id,
                    d.title AS document_title,
                    d.author AS document_author,
                    1 - (
                        dc.embedding OPERATOR(extensions.<=>)
                        ?::extensions.vector
                    ) AS similarity
                FROM document_chunks dc
                INNER JOIN documents d ON d.id = dc.document_id
                WHERE dc.embedding IS NOT NULL
                {$docFilterSql}
                ORDER BY
                    dc.embedding OPERATOR(extensions.<=>)
                    ?::extensions.vector ASC
                LIMIT 5
            ", $bindings);

            // Step 3: Handle case when no thesis chunks exist yet
            if (empty($chunks)) {
                if ($documentId) {
                    $doc = DB::table('documents')->where('id', $documentId)->first();
                    if ($doc) {
                        $contextText = "Thesis Title: {$doc->title}\nAuthor: {$doc->author}\nDepartment: {$doc->department}\nAbstract:\n{$doc->abstract}";
                        $answer = $this->geminiService->generateAnswer($userQuestion, $contextText);
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

            // Step 4: Build thesis context text to feed into Gemini AI
            $contextParts = [];
            foreach ($chunks as $index => $chunk) {
                $score = round(((float) $chunk->similarity) * 100, 1);
                $docTitle = $chunk->document_title ?? 'Thesis Document';
                $docAuthor = $chunk->document_author ?? 'Unknown Author';

                $contextParts[] =
                    "[Source #" . ($index + 1) . "]\n" .
                    "Thesis Title: {$docTitle}\n" .
                    "Author: {$docAuthor}\n" .
                    "Similarity: {$score}%\n" .
                    "Content:\n" .
                    $chunk->chunk_text;
            }

            $contextText = implode("\n\n---\n\n", $contextParts);

            // Step 5: Ask Gemini to answer the question using the retrieved thesis passages
            $answer = $this->geminiService->generateAnswer($userQuestion, $contextText);

            // Step 6: Deduplicate sources so each thesis card appears only once in the UI
            $uniqueSources = [];
            foreach ($chunks as $chunk) {
                $docId = $chunk->document_id;
                if (!isset($uniqueSources[$docId])) {
                    $uniqueSources[$docId] = $chunk;
                } else {
                    if ((float)$chunk->similarity > (float)$uniqueSources[$docId]->similarity) {
                        $uniqueSources[$docId] = $chunk;
                    }
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
}