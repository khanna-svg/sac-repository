<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeminiService
 * This service handles all communications between our Laravel system and Google's Gemini AI API.
 * It is responsible for:
 * 1. Creating vector embeddings (for semantic search & RAG retrieval).
 * 2. Generating answers to research questions (for the AI Research Assistant).
 */
class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    // Model used for creating vector embeddings (768 dimensions)
    protected string $embeddingModel = 'gemini-embedding-001';

    // Model used for generating chatbot answers
    protected string $generationModel = 'gemini-3.5-flash';

    public function __construct()
    {
        $this->apiKey = (string) env('GEMINI_API_KEY');

        if ($this->apiKey === '') {
            throw new \Exception('GEMINI_API_KEY is not configured in .env file.');
        }
    }

    /**
     * 1. Generate a single vector embedding (768 numbers).
     * Used by Semantic Search when a student types a search query.
     */
    public function generateEmbedding(string $text): array
    {
        $response = Http::timeout(10)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
            ])
            ->post(
                "{$this->baseUrl}/models/{$this->embeddingModel}:embedContent",
                [
                    'model' => "models/{$this->embeddingModel}",
                    'content' => [
                        'parts' => [
                            ['text' => $text],
                        ],
                    ],
                    'outputDimensionality' => 768,
                ]
            );

        if (!$response->successful()) {
            Log::error('Gemini embedding failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Gemini embedding error: ' . $response->status());
        }

        $values = $response->json('embedding.values');

        if (!is_array($values) || empty($values)) {
            throw new \Exception('Gemini returned an empty embedding vector.');
        }

        return array_map('floatval', $values);
    }

    /**
     * 2. Generate vector embeddings for multiple text chunks in batch.
     * Used when an Admin uploads a thesis PDF to index all pages at once.
     */
    public function generateEmbeddings(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        $requests = [];
        foreach ($texts as $text) {
            $requests[] = [
                'model' => "models/{$this->embeddingModel}",
                'content' => [
                    'parts' => [
                        ['text' => (string) $text],
                    ],
                ],
                'outputDimensionality' => 768,
            ];
        }

        $response = Http::timeout(180)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
            ])
            ->post(
                "{$this->baseUrl}/models/{$this->embeddingModel}:batchEmbedContents",
                [
                    'requests' => $requests,
                ]
            );

        if (!$response->successful()) {
            Log::error('Gemini batch embedding failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Gemini batch embedding error: ' . $response->status());
        }

        $embeddings = $response->json('embeddings');

        if (!is_array($embeddings)) {
            throw new \Exception('Gemini did not return valid embeddings array.');
        }

        $result = [];
        foreach ($embeddings as $embedding) {
            $values = $embedding['values'] ?? null;
            if (is_array($values) && count($values) === 768) {
                $result[] = array_map('floatval', $values);
            }
        }

        return $result;
    }

    /**
     * 3. Generate AI Answer (RAG)
     * Takes the user's question and relevant thesis excerpts, then instructs Gemini
     * to formulate a grounded, citation-ready response.
     */
    public function generateAnswer(string $userQuestion, string $contextText): string
    {
        // Strict system prompt to avoid hallucinations
        $prompt =
            "You are an expert AI Thesis Assistant for St. Anthony's College Institutional Research Repository.\n\n" .
            "Answer the student's question using ONLY the thesis context provided below.\n" .
            "Do not invent or fabricate information.\n" .
            "If the context does not contain enough information, clearly state that the answer could not be found in the uploaded thesis documents.\n\n" .
            "--- RETRIEVED THESIS CONTEXT ---\n" .
            $contextText .
            "\n\n" .
            "--- USER QUESTION ---\n" .
            $userQuestion;

        // Multi-model fast fallback array to prevent timeouts
        $modelsToTry = [$this->generationModel, 'gemini-3.5-flash-lite', 'gemini-3.6-flash'];

        foreach (array_unique($modelsToTry) as $modelName) {
            try {
                $response = Http::timeout(8)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'x-goog-api-key' => $this->apiKey,
                    ])
                    ->post(
                        "{$this->baseUrl}/models/{$modelName}:generateContent",
                        [
                            'contents' => [
                                [
                                    'role' => 'user',
                                    'parts' => [
                                        ['text' => $prompt],
                                    ],
                                ],
                            ],
                        ]
                    );

                if ($response->successful()) {
                    $answer = $response->json('candidates.0.content.parts.0.text');
                    if ($answer) {
                        return $answer;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Gemini model {$modelName} failed, trying fallback: " . $e->getMessage());
            }
        }

        throw new \Exception('Google AI is currently experiencing high demand. Please try asking again in a moment.');
    }

    /**
     * 4. Generate Multi-Turn Conversational AI Answer (RAG with Memory)
     * Maintains conversation context while grounding answers on retrieved thesis passages.
     */
    public function generateChatResponse(string $userQuestion, string $contextText, array $history = []): string
    {
        $systemInstruction = "You are an expert AI Research Assistant for St. Anthony's College Institutional Research Repository.\n" .
            "Answer the student's questions accurately using the provided thesis context and conversation history.\n" .
            "Maintain conversational continuity: if the user asks a follow-up question (such as 'Who were the authors of it?', 'What did they find?', or 'Summarize chapter 3'), understand that 'it' refers to the thesis previously discussed.\n" .
            "Do not invent facts not grounded in the thesis context.\n" .
            "If the information is not in the thesis context or previous messages, politely explain that the detail is not found in the uploaded documents.";

        $contents = [];

        // Add sanitized history (max 8 past turns)
        $recentHistory = array_slice($history, -8);
        foreach ($recentHistory as $turn) {
            $role = (isset($turn['role']) && ($turn['role'] === 'assistant' || $turn['role'] === 'model')) ? 'model' : 'user';
            $text = trim((string)($turn['content'] ?? ''));
            if ($text !== '') {
                $contents[] = [
                    'role' => $role,
                    'parts' => [
                        ['text' => $text]
                    ]
                ];
            }
        }

        // Current turn grounded with retrieved thesis context
        $currentPrompt = "--- RETRIEVED THESIS CONTEXT ---\n" .
            $contextText .
            "\n\n--- CURRENT STUDENT QUESTION ---\n" .
            $userQuestion;

        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $currentPrompt]
            ]
        ];

        $modelsToTry = [$this->generationModel, 'gemini-3.5-flash-lite', 'gemini-3.6-flash'];

        foreach (array_unique($modelsToTry) as $modelName) {
            try {
                $response = Http::timeout(12)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'x-goog-api-key' => $this->apiKey,
                    ])
                    ->post(
                        "{$this->baseUrl}/models/{$modelName}:generateContent",
                        [
                            'systemInstruction' => [
                                'parts' => [
                                    ['text' => $systemInstruction]
                                ]
                            ],
                            'contents' => $contents,
                        ]
                    );

                if ($response->successful()) {
                    $answer = $response->json('candidates.0.content.parts.0.text');
                    if ($answer) {
                        return $answer;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Gemini multi-turn model {$modelName} failed, trying fallback: " . $e->getMessage());
            }
        }

        // Fallback to standard generateAnswer
        return $this->generateAnswer($userQuestion, $contextText);
    }
}