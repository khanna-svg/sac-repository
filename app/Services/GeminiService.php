<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;

    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY') ?? '';

        if (empty($this->apiKey)) {
            throw new \Exception('GEMINI_API_KEY is not configured in the .env file.');
        }
    }

    /**
     * Generate a 768-dimensional embedding using Google's text-embedding-004.
     */
    public function generateEmbedding(string $text): array
    {
        // 8 second timeout to stay safely under Vercel's 10s limit
        $response = Http::timeout(8)
            ->post(
                "{$this->baseUrl}/models/text-embedding-004:embedContent?key={$this->apiKey}",
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
            $values = $response->json('embedding.values');

            if (!is_array($values) || empty($values)) {
                throw new \Exception('Gemini returned an empty embedding array.');
            }

            return $values;
        }

        $errorBody = $response->json('error.message') ?? $response->body();
        Log::error('Gemini Embedding Error: ' . $errorBody);
        
        throw new \Exception('Embedding API Error: ' . $errorBody);
    }

    /**
     * Generate an answer using gemini-1.5-flash with retrieved thesis context.
     */
    public function generateAnswer(string $userQuestion, string $contextText): string
    {
        $prompt =
            "You are an expert AI Thesis Assistant for St. Anthony's College (SAC) Thesis Repository System.\n"
            . "Answer the user's question clearly using markdown formatting (bullet points, bold text).\n"
            . "Use ONLY the provided thesis context below. If the context does not contain enough information, state clearly that the answer is not in the uploaded documents.\n\n"
            . "--- THESIS CONTEXT ---\n"
            . $contextText
            . "\n\n"
            . "--- USER QUESTION ---\n"
            . $userQuestion;

        // 8 second timeout to stay safely under Vercel's 10s limit
        $response = Http::timeout(8)
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
            return $response->json('candidates.0.content.parts.0.text') ?? 'No response generated.';
        }

        $errorBody = $response->json('error.message') ?? $response->body();
        Log::error('Gemini Generation Error: ' . $errorBody);

        return 'Gemini AI Error: ' . $errorBody;
    }
}