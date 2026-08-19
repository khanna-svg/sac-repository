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
            // 2. Search top 5 document chunks
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
                JOIN documents d ON d.id = dc.document_id
                WHERE
                    1 - (
                        dc.embedding OPERATOR(extensions.<=>)
                        ?::extensions.vector
                    ) > 0.15
                ORDER BY
                    dc.embedding OPERATOR(extensions.<=>)
                    ?::extensions.vector ASC
                LIMIT 5
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
            // 3. Build compact context for fastest AI response
            // -----------------------------------------------------

            $contextParts = [];
            // Take top 3 most relevant chunks to keep response fast
            $topChunks = array_slice($chunks, 0, 3);

            foreach ($topChunks as $index => $chunk) {
                $score = round($chunk->similarity * 100, 1);
                $docTitle = $chunk->document_title ?? 'Thesis Document';
                $docAuthor = $chunk->document_author ?? 'Unknown Author';

                $contextParts[] =
                    "[Source #" . ($index + 1) . " - \"{$docTitle}\" by {$docAuthor} ({$score}% match)]\n" .
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
            // 5. Deduplicate sources by document_id for the UI
            // -----------------------------------------------------

            $uniqueSources = [];
            foreach ($chunks as $chunk) {
                $docId = $chunk->document_id;
                if (!isset($uniqueSources[$docId])) {
                    $uniqueSources[$docId] = $chunk;
                }
            }

            return response()->json([
                'error' => false,
                'answer' => $answer,
                'sources' => array_values($uniqueSources)
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