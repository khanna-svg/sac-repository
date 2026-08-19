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
                throw new \Exception('Gemini returned an empty query embedding.');
            }

            $embeddingVector = '[' . implode(',', $embedding) . ']';

            // -----------------------------------------------------
            // 2. Fetch the best matching chunk for EACH unique thesis
            // -----------------------------------------------------

            $chunks = DB::select("
                SELECT *
                FROM (
                    SELECT DISTINCT ON (dc.document_id)
                        dc.chunk_text,
                        dc.document_id,
                        d.title AS document_title,
                        d.author AS document_author,
                        1 - (
                            dc.embedding OPERATOR(extensions.<=>)
                            ?::extensions.vector
                        ) AS similarity
                    FROM document_chunks dc
                    JOIN documents d ON d.id = dc.document_id
                    WHERE
                        1 - (
                            dc.embedding OPERATOR(extensions.<=>)
                            ?::extensions.vector
                        ) > 0.15
                    ORDER BY
                        dc.document_id,
                        dc.embedding OPERATOR(extensions.<=>)
                        ?::extensions.vector ASC
                ) unique_theses
                ORDER BY similarity DESC
                LIMIT 8
            ", [
                $embeddingVector,
                $embeddingVector,
                $embeddingVector
            ]);

            if (empty($chunks)) {
                return response()->json([
                    'error' => false,
                    'answer' => 'I could not find relevant information in the uploaded thesis documents.',
                    'sources' => []
                ]);
            }

            // -----------------------------------------------------
            // 3. Build context for Gemini from unique theses
            // -----------------------------------------------------

            $contextParts = [];

            foreach ($chunks as $index => $chunk) {
                $score = round($chunk->similarity * 100, 1);
                $docTitle = $chunk->document_title ?? 'Thesis Document';
                $docAuthor = $chunk->document_author ?? 'Unknown Author';

                $contextParts[] =
                    "[Thesis #" . ($index + 1) . ": \"{$docTitle}\" by {$docAuthor} ({$score}% match)]\n" .
                    $chunk->chunk_text;
            }

            $contextText = implode("\n\n---\n\n", $contextParts);

            // -----------------------------------------------------
            // 4. Generate answer with Gemini
            // -----------------------------------------------------

            $answer = $this->geminiService
                ->generateAnswer(
                    $userQuestion,
                    $contextText
                );

            // -----------------------------------------------------
            // 5. Return all distinct matching theses
            // -----------------------------------------------------

            return response()->json([
                'error' => false,
                'answer' => $answer,
                'sources' => $chunks
            ]);

        } catch (\Exception $e) {

            Log::error('RAG CHATBOT ERROR: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'RAG chatbot error: ' . $e->getMessage()
            ], 500);
        }
    }
}