<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected string $apiKey;

    protected string $baseUrl =
        'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');

        if (empty($this->apiKey)) {
            throw new \Exception(
                'GEMINI_API_KEY is not configured in the .env file.'
            );
        }
    }

    /**
     * Generate a 768-dimensional embedding.
     */
    public function generateEmbedding(string $text): array
    {
        $response = Http::timeout(60)
            ->post(
                "{$this->baseUrl}/models/gemini-embedding-001:embedContent?key={$this->apiKey}",
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => $text
                            ]
                        ]
                    ],
                    'outputDimensionality' => 768
                ]
            );

        if ($response->successful()) {

            $values = $response->json(
                'embedding.values'
            );

            if (!is_array($values) || empty($values)) {
                throw new \Exception(
                    'Gemini returned an invalid or empty embedding.'
                );
            }

            return $values;
        }

        throw new \Exception(
            'Gemini Embedding API Error: ' .
            $response->status() .
            ' - ' .
            $response->body()
        );
    }

    /**
     * Generate an answer using retrieved thesis context.
     */
    public function generateAnswer(
        string $userQuestion,
        string $contextText
    ): string {

        $prompt =
            "You are an expert AI Thesis Assistant for the SAC Thesis System. "
            . "Answer the user's question using ONLY the provided thesis "
            . "context below. "
            . "If the context does not contain enough information, "
            . "state clearly that the answer is not in the uploaded documents."
            . "\n\n"
            . "--- THESIS CONTEXT ---\n"
            . $contextText
            . "\n\n"
            . "--- USER QUESTION ---\n"
            . $userQuestion;

        $response = Http::timeout(60)
            ->post(
                "{$this->baseUrl}/models/gemini-3.6-flash:generateContent?key={$this->apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt
                                ]
                            ]
                        ]
                    ]
                ]
            );

        if ($response->successful()) {

            return $response->json(
                'candidates.0.content.parts.0.text'
            ) ?? 'No response generated.';
        }

        return 'Error connecting to Gemini AI: ' .
            $response->body();
    }
}