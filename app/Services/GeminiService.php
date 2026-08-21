<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;

    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    protected string $embeddingModel = 'text-embedding-004';

    protected string $generationModel = 'gemini-2.5-flash';

    public function __construct()
    {
        $this->apiKey = (string) env('GEMINI_API_KEY');

        if ($this->apiKey === '') {
            throw new \Exception('GEMINI_API_KEY is not configured.');
        }
    }

    /**
     * Generate one embedding.
     */
    public function generateEmbedding(string $text): array
    {
        $response = Http::timeout(60)
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
                            [
                                'text' => $text,
                            ],
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

            throw new \Exception(
                'Gemini embedding error: ' .
                $response->status() .
                ' - ' .
                $response->body()
            );
        }

        $values = $response->json('embedding.values');

        if (!is_array($values) || empty($values)) {
            throw new \Exception(
                'Gemini returned an empty embedding: ' .
                $response->body()
            );
        }

        if (count($values) !== 768) {
            throw new \Exception(
                'Expected 768 dimensions, received ' .
                count($values)
            );
        }

        return array_map('floatval', $values);
    }

    /**
     * Generate embeddings for multiple chunks.
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
                        [
                            'text' => (string) $text,
                        ],
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
                'count' => count($texts),
            ]);

            throw new \Exception(
                'Gemini batch embedding error: ' .
                $response->status() .
                ' - ' .
                $response->body()
            );
        }

        $embeddings = $response->json('embeddings');

        if (!is_array($embeddings)) {
            throw new \Exception(
                'Gemini did not return embeddings: ' .
                $response->body()
            );
        }

        if (count($embeddings) !== count($texts)) {
            throw new \Exception(
                'Gemini returned ' .
                count($embeddings) .
                ' embeddings for ' .
                count($texts) .
                ' texts.'
            );
        }

        $result = [];

        foreach ($embeddings as $index => $embedding) {
            $values = $embedding['values'] ?? null;

            if (!is_array($values)) {
                throw new \Exception(
                    'Missing embedding values at index ' . $index
                );
            }

            if (count($values) !== 768) {
                throw new \Exception(
                    'Invalid embedding dimension at index ' .
                    $index .
                    '. Expected 768, received ' .
                    count($values)
                );
            }

            $result[] = array_map('floatval', $values);
        }

        return $result;
    }

    /**
     * Generate AI answer using retrieved thesis context.
     */
    public function generateAnswer(
        string $userQuestion,
        string $contextText
    ): string {
        $prompt =
            "You are an expert AI Thesis Assistant for the SAC Thesis System.\n\n" .

            "Answer the user's question using ONLY the thesis context provided below.\n" .

            "Do not invent information.\n" .

            "If the provided context does not contain enough information to answer " .
            "the question, clearly say that the answer could not be found in " .
            "the uploaded thesis documents.\n\n" .

            "--- THESIS CONTEXT ---\n" .
            $contextText .
            "\n\n" .

            "--- USER QUESTION ---\n" .
            $userQuestion;

        $response = Http::timeout(120)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
            ])
            ->post(
                "{$this->baseUrl}/models/{$this->generationModel}:generateContent",
                [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                ]
            );

        if (!$response->successful()) {
            Log::error('Gemini answer failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception(
                'Gemini answer error: ' .
                $response->status() .
                ' - ' .
                $response->body()
            );
        }

        $answer = $response->json(
            'candidates.0.content.parts.0.text'
        );

        if (!$answer) {
            throw new \Exception(
                'Gemini returned an empty answer.'
            );
        }

        return $answer;
    }
}