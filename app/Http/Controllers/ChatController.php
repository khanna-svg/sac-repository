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
        // ---------------------------------------------------------
        // 1. Get user's question
        // ---------------------------------------------------------

        $userQuestion = $request->input('message')
            ?? $request->input('question');

        if (!$userQuestion || trim($userQuestion) === '') {
            return response()->json([
                'error' => true,
                'message' => 'The message field is required.'
            ], 422);
        }

        $userQuestion = trim($userQuestion);

        Log::info(
            'RAG Chatbot question: ' . $userQuestion
        );

        try {

            // -----------------------------------------------------
            // 2. Generate embedding for user's question
            // -----------------------------------------------------

            Log::info(
                'Generating query embedding...'
            );

            $embedding = $this->geminiService
                ->generateEmbedding($userQuestion);

            if (empty($embedding)) {
                throw new \Exception(
                    'Gemini returned an empty query embedding.'
                );
            }

            // Verify dimension
            $dimension = count($embedding);

            Log::info(
                "Query embedding generated. Dimension: {$dimension}"
            );

            if ($dimension !== 768) {
                throw new \Exception(
                    "Query embedding has {$dimension} dimensions. Expected 768."
                );
            }

            // -----------------------------------------------------
            // 3. Convert embedding to PostgreSQL vector
            // -----------------------------------------------------

            $embeddingVector =
                '[' . implode(',', $embedding) . ']';

            Log::info(
                'Querying document_chunks using vector similarity...'
            );

            // -----------------------------------------------------
            // 4. Search document chunks
            // -----------------------------------------------------

            $chunks = DB::select("
                SELECT
                    chunk_text,
                    document_id,
                    1 - (
                        embedding OPERATOR(extensions.<=>)
                        ?::extensions.vector
                    ) AS similarity
                FROM document_chunks
                WHERE
                    1 - (
                        embedding OPERATOR(extensions.<=>)
                        ?::extensions.vector
                    ) > 0.15
                ORDER BY
                    embedding OPERATOR(extensions.<=>)
                    ?::extensions.vector ASC
                LIMIT 5
            ", [
                $embeddingVector,
                $embeddingVector,
                $embeddingVector
            ]);

            Log::info(
                'Vector search returned ' .
                count($chunks) .
                ' chunks.'
            );

            // -----------------------------------------------------
            // 5. No relevant chunks
            // -----------------------------------------------------

            if (empty($chunks)) {

                Log::info(
                    'No relevant thesis chunks found.'
                );

                return response()->json([
                    'error' => false,
                    'answer' =>
                        'I could not find relevant information in the uploaded thesis documents.',
                    'sources' => []
                ]);
            }

            // -----------------------------------------------------
            // 6. Build RAG context
            // -----------------------------------------------------

            $contextParts = [];

            foreach ($chunks as $index => $chunk) {

                $score = round(
                    $chunk->similarity * 100,
                    1
                );

                $contextParts[] =
                    "[Source #" .
                    ($index + 1) .
                    " - Similarity: {$score}%]\n" .
                    $chunk->chunk_text;
            }

            $contextText = implode(
                "\n\n---\n\n",
                $contextParts
            );

            Log::info(
                'RAG context successfully created.'
            );

            // -----------------------------------------------------
            // 7. Generate answer with Gemini
            // -----------------------------------------------------

            Log::info(
                'Sending RAG context to Gemini...'
            );

            $answer = $this->geminiService
                ->generateAnswer(
                    $userQuestion,
                    $contextText
                );

            Log::info(
                'Gemini generated chatbot answer successfully.'
            );

            // -----------------------------------------------------
            // 8. Return answer
            // -----------------------------------------------------

            return response()->json([
                'error' => false,
                'answer' => $answer,
                'sources' => $chunks
            ]);

        } catch (\Exception $e) {

            // -----------------------------------------------------
            // 9. Log exact error
            // -----------------------------------------------------

            Log::error(
                'RAG CHATBOT ERROR: ' .
                $e->getMessage()
            );

            Log::error(
                $e->getTraceAsString()
            );

            // -----------------------------------------------------
            // 10. Return actual error during development
            // -----------------------------------------------------

            return response()->json([
                'error' => true,
                'message' =>
                    'RAG chatbot error: ' .
                    $e->getMessage()
            ], 500);
        }
    }
}