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

    public function ask(Request $request)
    {
        $userQuestion = $request->input('message')
            ?? $request->input('question');

        if (!$userQuestion || trim($userQuestion) === '') {
            return response()->json([
                'error' => true,
                'message' => 'The message field is required.'
            ], 422);
        }

        $userQuestion = trim($userQuestion);

        try {

            // -----------------------------------------------------
            // 1. Generate embedding for user's question
            // -----------------------------------------------------

            $embedding = $this->geminiService
                ->generateEmbedding($userQuestion);

            if (empty($embedding)) {
                throw new \Exception(
                    'Gemini returned an empty query embedding.'
                );
            }

            $embeddingVector = '[' . implode(',', $embedding) . ']';


            // -----------------------------------------------------
            // 2. Retrieve the best matching chunks
            //    We do NOT use a similarity threshold here.
            // -----------------------------------------------------

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

                INNER JOIN documents d
                    ON d.id = dc.document_id

                WHERE dc.embedding IS NOT NULL

                ORDER BY
                    dc.embedding OPERATOR(extensions.<=>)
                    ?::extensions.vector ASC

                LIMIT 5
            ", [
                $embeddingVector,
                $embeddingVector
            ]);


            // -----------------------------------------------------
            // 3. Check if there are actually chunks in the database
            // -----------------------------------------------------

            if (empty($chunks)) {

                Log::warning('RAG: document_chunks returned no results.', [
                    'question' => $userQuestion
                ]);

                return response()->json([
                    'error' => false,
                    'answer' =>
                        'There are currently no processed thesis document chunks available for semantic search.',
                    'sources' => []
                ]);
            }


            // -----------------------------------------------------
            // 4. Log similarity results for debugging
            // -----------------------------------------------------

            Log::info('RAG SEARCH RESULTS', [
                'question' => $userQuestion,
                'chunk_count' => count($chunks),
                'results' => collect($chunks)->map(function ($chunk) {
                    return [
                        'document_id' => $chunk->document_id,
                        'title' => $chunk->document_title,
                        'similarity' => $chunk->similarity
                    ];
                })->values()->toArray()
            ]);


            // -----------------------------------------------------
            // 5. Build thesis context
            // -----------------------------------------------------

            $contextParts = [];

            foreach ($chunks as $index => $chunk) {

                $score = round(
                    ((float) $chunk->similarity) * 100,
                    1
                );

                $docTitle =
                    $chunk->document_title
                    ?? 'Thesis Document';

                $docAuthor =
                    $chunk->document_author
                    ?? 'Unknown Author';

                $contextParts[] =
                    "[Source #" . ($index + 1) . "]\n" .
                    "Thesis Title: {$docTitle}\n" .
                    "Author: {$docAuthor}\n" .
                    "Similarity: {$score}%\n" .
                    "Content:\n" .
                    $chunk->chunk_text;
            }

            $contextText =
                implode("\n\n---\n\n", $contextParts);


            // -----------------------------------------------------
            // 6. Generate answer using retrieved context
            // -----------------------------------------------------

            $answer = $this->geminiService
                ->generateAnswer(
                    $userQuestion,
                    $contextText
                );


            // -----------------------------------------------------
            // 7. Return answer + deduplicated unique sources
            // -----------------------------------------------------

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
                'message' =>
                    'RAG chatbot error: ' . $e->getMessage()
            ], 500);
        }
    }
}