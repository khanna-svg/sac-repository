<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    protected string $embeddingModel = 'gemini-embedding-001';

    protected string $generationModel = 'gemini-3.6-flash';

    public function __construct()
    {
        $this->apiKey = (string) env('GEMINI_API_KEY');

        if ($this->apiKey === '') {
            throw new \Exception('GEMINI_API_KEY is not configured in .env file.');
        }
    }

    public function generateEmbedding(string $text): array
    {
        $response = Http::withoutVerifying()
            ->timeout(3.5)
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

        $response = Http::withoutVerifying()
            ->timeout(180)
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

        $modelsToTry = [
            $this->generationModel,
            'gemini-3-flash-preview',
        ];

        foreach (array_unique($modelsToTry) as $modelName) {
            try {
                $payload = [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                    ],
                ];

                if (str_contains($modelName, 'thinking') || str_contains($modelName, '3.7') || str_contains($modelName, '3-flash') || str_contains($modelName, '3.6')) {
                    $payload['generationConfig']['thinkingConfig'] = ['thinkingBudget' => 0];
                }

                $response = Http::withoutVerifying()
                    ->timeout(4.5)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'x-goog-api-key' => $this->apiKey,
                    ])
                    ->post("{$this->baseUrl}/models/{$modelName}:generateContent", $payload);

                if ($response->successful()) {
                    $answer = $response->json('candidates.0.content.parts.0.text');
                    if ($answer) {
                        return $this->cleanModelResponse((string) $answer);
                    }
                } else {
                    Log::warning("Gemini model {$modelName} returned HTTP {$response->status()}: " . substr($response->body(), 0, 150));
                }
            } catch (\Throwable $e) {
                Log::warning("Gemini model {$modelName} failed, trying fallback: " . $e->getMessage());
            }
        }

        throw new \Exception('Google AI is currently experiencing high demand. Please try asking again in a moment.');
    }

    public function generateChatResponse(string $userQuestion, string $contextText, array $history = []): string
    {
        $systemInstruction = "You are an expert AI Research Assistant for St. Anthony's College Institutional Research Repository.\n" .
            "Answer the student's questions accurately using the provided thesis context and conversation history.\n" .
            "Maintain conversational continuity: if the user asks a follow-up question (such as 'Who were the authors of it?', 'What did they find?', or 'Summarize chapter 3'), understand that 'it' refers to the thesis previously discussed.\n" .
            "Do not invent facts not grounded in the thesis context.\n" .
            "If the information is not in the thesis context or previous messages, politely explain that the detail is not found in the uploaded documents.";

        $rawTurns = [];
        $recentHistory = array_slice($history, -8);
        foreach ($recentHistory as $turn) {
            $role = (isset($turn['role']) && ($turn['role'] === 'assistant' || $turn['role'] === 'model')) ? 'model' : 'user';
            $text = trim((string)($turn['content'] ?? ''));
            if ($text !== '') {
                $rawTurns[] = ['role' => $role, 'text' => $text];
            }
        }

        $currentPrompt = "--- RETRIEVED THESIS CONTEXT ---\n" .
            $contextText .
            "\n\n--- CURRENT STUDENT QUESTION ---\n" .
            $userQuestion;

        $rawTurns[] = ['role' => 'user', 'text' => $currentPrompt];

        // Sanitize contents for Google Gemini API:
        // 1. First turn must be 'user'
        // 2. Roles must strictly alternate: user -> model -> user -> model
        $contents = [];
        $lastRole = null;

        foreach ($rawTurns as $t) {
            if ($lastRole === null) {
                if ($t['role'] === 'model') {
                    $contents[] = [
                        'role' => 'user',
                        'parts' => [['text' => 'Can you summarize or provide details on this thesis?']]
                    ];
                }
                $contents[] = [
                    'role' => $t['role'],
                    'parts' => [['text' => $t['text']]]
                ];
                $lastRole = $t['role'];
            } else if ($t['role'] === $lastRole) {
                $lastIdx = count($contents) - 1;
                $contents[$lastIdx]['parts'][0]['text'] .= "\n\n" . $t['text'];
            } else {
                $contents[] = [
                    'role' => $t['role'],
                    'parts' => [['text' => $t['text']]]
                ];
                $lastRole = $t['role'];
            }
        }

        $modelsToTry = [
            $this->generationModel,
            'gemini-3-flash-preview',
        ];

        foreach (array_unique($modelsToTry) as $modelName) {
            try {
                $payload = [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.3,
                    ],
                ];

                if (!str_contains($modelName, 'gemma')) {
                    $payload['systemInstruction'] = [
                        'parts' => [
                            ['text' => $systemInstruction]
                        ]
                    ];
                    if (str_contains($modelName, 'thinking') || str_contains($modelName, '3.7') || str_contains($modelName, '3-flash') || str_contains($modelName, '3.6')) {
                        $payload['generationConfig']['thinkingConfig'] = ['thinkingBudget' => 0];
                    }
                } else {
                    // For Gemma, prepend system instructions into the first user message
                    if (!empty($payload['contents'][0]['parts'][0]['text'])) {
                        $payload['contents'][0]['parts'][0]['text'] = "System Instructions: {$systemInstruction}\nOutput only the final helpful answer directly to the student without any internal scratchpad, analysis, or draft notes.\n\n" . $payload['contents'][0]['parts'][0]['text'];
                    }
                }

                $response = Http::withoutVerifying()
                    ->timeout(4.5)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'x-goog-api-key' => $this->apiKey,
                    ])
                    ->post("{$this->baseUrl}/models/{$modelName}:generateContent", $payload);

                if ($response->successful()) {
                    $answer = $response->json('candidates.0.content.parts.0.text');
                    if ($answer) {
                        return $this->cleanModelResponse((string) $answer);
                    }
                } else {
                    Log::warning("Gemini multi-turn model {$modelName} returned HTTP {$response->status()}: " . substr($response->body(), 0, 150));
                }
            } catch (\Throwable $e) {
                Log::warning("Gemini multi-turn model {$modelName} failed, trying fallback: " . $e->getMessage());
            }
        }

        throw new \Exception('Google AI is currently experiencing high demand. Please try asking again in a moment.');
    }

    protected function cleanModelResponse(string $text): string
    {
        $trimmed = trim($text);

        // If it ends with a quoted answer block
        if (preg_match('/(?:\*|\#|-|\s)*"([^"]{20,})"\s*$/s', $trimmed, $m)) {
            return trim($m[1]);
        }

        // Fast-path: if text does not look like a scratchpad and has no checklist, return as-is
        $firstLine = trim(strtok($trimmed, "\n"));
        if (!$this->isScratchpadLine($firstLine) && !preg_match('/\?\s*(?:Yes|No)\b/i', $trimmed)) {
            return $trimmed;
        }

        // Split text into distinct paragraphs/blocks
        $blocks = preg_split('/\n\s*\n/', $trimmed);

        // Filter out trailing blocks that are self-evaluation checklists
        while (!empty($blocks)) {
            $lastBlock = trim(end($blocks));
            if ($lastBlock === '' || $this->isChecklistBlock($lastBlock)) {
                array_pop($blocks);
            } else {
                break;
            }
        }

        if (empty($blocks)) {
            return $trimmed;
        }

        // The final block before the self-evaluation checklist is the actual answer
        $answerBlock = trim(end($blocks));

        $lines = explode("\n", $answerBlock);
        $formattedLines = [];
        foreach ($lines as $line) {
            if ($this->isScratchpadLine($line)) {
                continue;
            }

            if (preg_match('/^\s*(?:\*|\#|-)\s+(.*)$/', $line, $m)) {
                $content = trim($m[1]);
                if (str_ends_with($content, ':') || (strlen($content) > 60 && str_ends_with($content, '.'))) {
                    $formattedLines[] = $content;
                } else {
                    $formattedLines[] = '* ' . $content;
                }
            } else {
                $formattedLines[] = trim($line);
            }
        }

        $result = trim(implode("\n", $formattedLines));
        return strlen($result) > 15 ? $result : $trimmed;
    }

    protected function isChecklistBlock(string $block): bool
    {
        $lines = array_filter(explode("\n", trim($block)), fn($l) => trim($l) !== '');
        if (empty($lines)) {
            return true;
        }

        $checklistCount = 0;
        foreach ($lines as $line) {
            if (preg_match('/\?\s*(?:Yes|No)\b/i', $line)) {
                $checklistCount++;
            }
        }

        return ($checklistCount / count($lines)) >= 0.5;
    }

    protected function isScratchpadLine(string $line): bool
    {
        $clean = trim($line);
        if ($clean === '') {
            return true;
        }

        if (preg_match('/\?\s*(?:Yes|No)\b/i', $clean)) {
            return true;
        }

        if (preg_match('/^(?:\*|\#|-|\s)*(?:Role|Task|Constraint(?:\s*\d+)?|User Question|Question|Context|Goal|Thesis Title|Abstract|Information in context|Draft|Analysis|Checklist|Self-Correction|Evaluation|Step(?:\s*\d+)?)\s*:/i', $clean)) {
            return true;
        }

        if (preg_match('/^(?:\*|\#|-|\s)*\*(?:Thesis|Source|Overview)[^*]+\*\s*:/i', $clean)) {
            return true;
        }

        if (preg_match('/^(?:\*|\#|-|\s)*(?:\[Source|\[Thesis|Source\s*#)/i', $clean)) {
            return true;
        }

        return false;
    }

    public function extractProposalConcepts(string $proposalText): array
    {
        $truncated = mb_substr($proposalText, 0, 4500);

        $prompt = "You are an institutional academic research assistant. Analyze the following thesis concept proposal text and output a JSON object with these EXACT keys:
- \"title\": Estimated or extracted title of the proposed study (string)
- \"summary\": A concise 2-sentence synthesis of the core research problem, proposed solution/system, and methodology (string)
- \"topics\": An array of 4 to 6 specific academic keywords or technology domains (array of strings, e.g. [\"Machine Learning\", \"Agriculture\", \"IoT\"])
- \"embedding_query\": A dense 80-120 word paragraph summarizing the scientific/technical domain, problem, and methodology to be converted to vector embeddings for semantic literature matching.

Respond with ONLY valid JSON (no markdown formatting, no code fences, no explanations).

Proposal Text:
" . $truncated;

        $modelsToTry = [$this->generationModel, 'gemini-3-flash-preview', 'gemini-flash-latest', 'gemini-3.5-flash'];

        foreach (array_unique($modelsToTry) as $modelName) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(22)
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
                            'generationConfig' => [
                                'thinkingConfig' => [
                                    'thinkingBudget' => 0,
                                ],
                                'temperature' => 0.2,
                            ],
                        ]
                    );

                if ($response->successful()) {
                    $rawJson = $response->json('candidates.0.content.parts.0.text');
                    if ($rawJson) {
                        $clean = trim($rawJson);
                        // Strip code blocks if any
                        if (str_starts_with($clean, '```json')) {
                            $clean = substr($clean, 7);
                        } elseif (str_starts_with($clean, '```')) {
                            $clean = substr($clean, 3);
                        }
                        if (str_ends_with($clean, '```')) {
                            $clean = substr($clean, 0, -3);
                        }
                        $clean = trim($clean);

                        $data = json_decode($clean, true);
                        if (is_array($data) && !empty($data['summary'])) {
                            return [
                                'title' => (string) ($data['title'] ?? 'Concept Proposal'),
                                'summary' => (string) ($data['summary'] ?? ''),
                                'topics' => is_array($data['topics'] ?? null) ? $data['topics'] : [],
                                'embedding_query' => (string) ($data['embedding_query'] ?? $data['summary']),
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Gemini extractProposalConcepts with {$modelName} failed: " . $e->getMessage());
            }
        }

        // Fallback if AI synthesis fails
        $cleanFallback = trim(preg_replace('/\s+/', ' ', $truncated));
        return [
            'title' => 'Concept Proposal',
            'summary' => mb_substr($cleanFallback, 0, 220) . '...',
            'topics' => ['Research Proposal', 'Literature Review'],
            'embedding_query' => mb_substr($cleanFallback, 0, 1000),
        ];
    }
}