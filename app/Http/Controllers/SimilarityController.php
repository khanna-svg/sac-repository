<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SimilarityController extends Controller
{
    public function check(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'abstract' => 'nullable|string|max:5000',
            'department' => 'nullable|string|max:100',
        ]);

        $title = trim($validated['title']);
        $abstract = trim($validated['abstract'] ?? '');
        $department = trim($validated['department'] ?? 'all');

        $fullProposalText = trim($title . "\n\n" . $abstract);

        try {
            /** @var GeminiService $geminiService */
            $geminiService = app(GeminiService::class);
            $queryEmbedding = $geminiService->generateEmbedding($fullProposalText);

            if (empty($queryEmbedding)) {
                return response()->json([
                    'error' => 'Could not generate vector embedding for the proposal. Please check your text and try again.'
                ], 422);
            }

            $embeddingString = '[' . implode(',', $queryEmbedding) . ']';

            // Query top matching document chunks from PostgreSQL using pgvector cosine distance <=>
            $results = DB::select("
                SELECT
                    dc.document_id,
                    MIN(dc.embedding OPERATOR(extensions.<=>) ?::extensions.vector) AS min_distance,
                    AVG(dc.embedding OPERATOR(extensions.<=>) ?::extensions.vector) AS avg_distance
                FROM document_chunks dc
                WHERE dc.embedding IS NOT NULL
                GROUP BY dc.document_id
                ORDER BY min_distance ASC
                LIMIT 10
            ", [$embeddingString, $embeddingString]);

            if (empty($results)) {
                return response()->json([
                    'overall_score' => 0,
                    'verdict' => 'No Archived Theses Found',
                    'verdict_desc' => 'There are currently no indexed documents in the repository to compare against.',
                    'risk_level' => 'low',
                    'top_matches' => [],
                    'ai_advisory' => 'No archived theses were found in the database.'
                ]);
            }

            $topMatches = [];
            $maxScore = 0;

            foreach ($results as $res) {
                $doc = Document::find($res->document_id);
                if (!$doc) continue;

                // Department filter if specified
                if ($department !== '' && strtolower($department) !== 'all') {
                    $docDept = strtolower($doc->department ?? '');
                    $docCourse = strtolower($doc->course_code ?? '');
                    $filterDept = strtolower($department);

                    if (!str_contains($docDept, $filterDept) && !str_contains($docCourse, $filterDept)) {
                        continue;
                    }
                }

                $minDist = (float) $res->min_distance;
                // Calibrate cosine distance for natural language embeddings:
                // distance <= 0.12 => 85%-99%, 0.21 => ~70%, 0.38 => ~30%, >= 0.44 => < 20%
                $normalized = 1 - (($minDist - 0.08) / 0.42);
                $score = max(5, min(99, (int) round($normalized * 100)));

                if ($score > $maxScore) {
                    $maxScore = $score;
                }

                // Retrieve the most similar chunk text for this document
                $topChunk = DB::selectOne("
                    SELECT
                        dc.chunk_text,
                        dc.page_number,
                        (dc.embedding OPERATOR(extensions.<=>) ?::extensions.vector) AS chunk_dist
                    FROM document_chunks dc
                    WHERE dc.document_id = ? AND dc.embedding IS NOT NULL
                    ORDER BY chunk_dist ASC
                    LIMIT 1
                ", [$embeddingString, $doc->id]);

                $snippet = '';
                $pageNumber = 1;
                if ($topChunk) {
                    $snippet = trim(preg_replace('/\s+/', ' ', $topChunk->chunk_text));
                    if (strlen($snippet) > 280) {
                        $snippet = substr($snippet, 0, 277) . '...';
                    }
                    $pageNumber = $topChunk->page_number ?? 1;
                }

                $topMatches[] = [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'author' => $doc->author,
                    'department' => $doc->department,
                    'course_code' => $doc->course_code,
                    'publication_date' => $doc->publication_date ? $doc->publication_date->format('M Y') : ($doc->created_at ? $doc->created_at->format('M Y') : ''),
                    'similarity_score' => $score,
                    'matched_chunk' => $snippet,
                    'page_number' => $pageNumber,
                ];

                if (count($topMatches) >= 5) {
                    break;
                }
            }

            // Determine Risk / Originality Verdict
            if ($maxScore >= 65) {
                $verdict = 'High Similarity Alert';
                $verdictDesc = 'Substantial thematic, methodological, or conceptual overlap detected with an existing archived thesis. We strongly recommend defining a novel scope, new variables, or distinct beneficiaries before proposal defense.';
                $riskLevel = 'high';
            } elseif ($maxScore >= 35) {
                $verdict = 'Moderate Overlap Detected';
                $verdictDesc = 'Related research in this domain or technology exists in the SAC repository. Review prior literature and highlight specific distinctions in your project methodology.';
                $riskLevel = 'moderate';
            } else {
                $verdict = 'High Originality / Novel Proposal';
                $verdictDesc = 'Low overlap with archived SAC theses. The proposed topic demonstrates high originality within the current institutional research catalog.';
                $riskLevel = 'novel';
            }

            // Generate AI Research Advisory via Gemini
            $aiAdvisory = $this->generateAdvisory($geminiService, $title, $abstract, $topMatches);

            return response()->json([
                'overall_score' => $maxScore,
                'verdict' => $verdict,
                'verdict_desc' => $verdictDesc,
                'risk_level' => $riskLevel,
                'top_matches' => $topMatches,
                'ai_advisory' => $aiAdvisory,
            ]);

        } catch (\Throwable $e) {
            Log::error('Similarity check error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'An error occurred while evaluating similarity: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function generateAdvisory(GeminiService $geminiService, string $title, string $abstract, array $topMatches): string
    {
        if (empty($topMatches)) {
            return "No close matching theses were found in the institutional repository. Your proposed topic shows high novelty.";
        }

        $topMatch = $topMatches[0];
        $topTitle = $topMatch['title'];
        $topScore = $topMatch['similarity_score'];
        $topSnippet = $topMatch['matched_chunk'];

        $prompt = "You are an expert Thesis Proposal Reviewer for St. Anthony's College.\n" .
            "A student has submitted a thesis proposal:\n" .
            "Proposed Title: {$title}\n" .
            "Proposed Abstract/Description: {$abstract}\n\n" .
            "The closest matching archived thesis in the college repository is:\n" .
            "Archived Title: {$topTitle} ({$topScore}% Similarity)\n" .
            "Relevant Passage: {$topSnippet}\n\n" .
            "Provide a concise, constructive review for the student formatted in 3 clear markdown bullet points:\n" .
            "- **Areas of Overlap:** Specifically highlight what concepts, domain, or technologies overlap.\n" .
            "- **Distinctions & Gaps:** Identify what differentiates the student's proposal or remains unaddressed.\n" .
            "- **Actionable Recommendations:** Provide 1-2 practical ways the student can enhance originality or scope to ensure defense approval.\n" .
            "Keep the tone encouraging, scholarly, and concise (under 180 words).";

        try {
            return $geminiService->generateAnswer($prompt, "Institutional Thesis Defense Guidelines: Ensure novelty, specific methodology, and clear contributions to the community.");
        } catch (\Throwable $e) {
            Log::warning('AI Advisory fallback: ' . $e->getMessage());
            return "Your proposal was evaluated against archived theses. The most similar project found is '{$topTitle}' with a {$topScore}% similarity match. Be sure to review its scope and methodology to clearly demonstrate your project's unique contributions during your proposal defense.";
        }
    }
}
