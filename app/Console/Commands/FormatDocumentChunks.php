<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentChunk;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class FormatDocumentChunks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'airis:format-chunks 
                            {--doc= : Specific document ID to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-extract and format document chunks with authentic linebreaks and paragraphs from Supabase PDF';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $docId = $this->option('doc');

        $query = Document::query()->whereNotNull('file_path');
        if ($docId) {
            $query->where('id', $docId);
            $this->info("Targeting Document ID: {$docId}");
        }

        $documents = $query->get();
        if ($documents->isEmpty()) {
            $this->warn('No matching documents found.');
            return 0;
        }

        $baseUrl = rtrim((string) config('services.supabase.url', env('SUPABASE_URL')), '/');
        $key = (string) config('services.supabase.service_role_key', env('SUPABASE_SERVICE_ROLE_KEY'));
        $bucket = (string) config('services.supabase.bucket', env('SUPABASE_STORAGE_BUCKET', 'thesis'));

        $httpClient = new Client(['timeout' => 30]);
        $parser = new Parser();

        $this->info("Processing {$documents->count()} document(s)...");

        foreach ($documents as $doc) {
            $this->line("Processing Document #{$doc->id}: {$doc->title}...");

            $path = ltrim(trim((string) $doc->file_path), '/');
            if (!$path) {
                $this->warn("  Skipping: file_path is empty.");
                continue;
            }

            if (str_starts_with($path, $bucket . '/')) {
                $path = substr($path, strlen($bucket) + 1);
            }

            $encodedBucket = rawurlencode($bucket);
            $encodedPath = collect(explode('/', $path))->map(fn($part) => rawurlencode($part))->implode('/');
            $signUrl = "{$baseUrl}/storage/v1/object/sign/{$encodedBucket}/{$encodedPath}";

            $tempPdf = tempnam(sys_get_temp_dir(), 'airis_pdf_') . '.pdf';

            try {
                $res = $httpClient->post($signUrl, [
                    'headers' => [
                        'apikey' => $key,
                        'Authorization' => "Bearer {$key}",
                        'Content-Type' => 'application/json',
                    ],
                    'json' => ['expiresIn' => 3600],
                ]);

                $data = json_decode($res->getBody(), true);
                $relative = $data['signedURL'] ?? $data['signedUrl'] ?? null;
                if (!$relative) {
                    $this->error("  Failed to obtain signed URL from Supabase.");
                    continue;
                }

                if (str_starts_with($relative, 'http://') || str_starts_with($relative, 'https://')) {
                    $signedUrl = $relative;
                } elseif (str_starts_with($relative, '/storage/v1/')) {
                    $signedUrl = $baseUrl . $relative;
                } elseif (str_starts_with($relative, '/object/')) {
                    $signedUrl = $baseUrl . '/storage/v1' . $relative;
                } else {
                    $signedUrl = $baseUrl . '/storage/v1/' . ltrim($relative, '/');
                }

                $httpClient->get($signedUrl, ['sink' => $tempPdf]);

                if (!file_exists($tempPdf) || filesize($tempPdf) === 0) {
                    $this->error("  Downloaded PDF is empty.");
                    continue;
                }

                $pdf = $parser->parseFile($tempPdf);
                $pages = $pdf->getPages();

                $chunks = DocumentChunk::where('document_id', $doc->id)->orderBy('page_number')->get();
                $cleanTitle = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($doc->title));
                $updatedCount = 0;

                foreach ($chunks as $chunk) {
                    $pNum = (int) $chunk->page_number;
                    if ($pNum < 1 || $pNum > count($pages)) {
                        continue;
                    }

                    $rawText = $pages[$pNum - 1]->getText();
                    $text = str_replace(["\r\n", "\r"], "\n", $rawText);

                    // Strip top running header/page numbers for pages > 1
                    if ($pNum > 1) {
                        $lines = explode("\n", $text);
                        while (!empty($lines)) {
                            $first = trim($lines[0]);
                            if ($first === '') {
                                array_shift($lines);
                                continue;
                            }
                            if (preg_match('/^([0-9]{1,4}|[ivxlcdm]{1,6})$/i', $first)) {
                                array_shift($lines);
                                continue;
                            }
                            $cleanFirst = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($first));
                            if ($cleanTitle !== '' && strlen($cleanFirst) >= 15 && str_starts_with($cleanTitle, substr($cleanFirst, 0, 30))) {
                                array_shift($lines);
                                continue;
                            }
                            break;
                        }

                        // Trailing page numbers at bottom
                        while (!empty($lines)) {
                            $lastIdx = count($lines) - 1;
                            $last = trim($lines[$lastIdx]);
                            if ($last === '') {
                                array_pop($lines);
                                continue;
                            }
                            if (preg_match('/^([0-9]{1,4}|[ivxlcdm]{1,6})$/i', $last)) {
                                array_pop($lines);
                                continue;
                            }
                            if (preg_match('/^(.*?)\s+([ivxlcdm]{1,6}|[0-9]{1,4})$/i', $last, $m)) {
                                if (strlen($m[2]) <= 4 && !preg_match('/^(in|am|an|or|to|we|he|by|at|is|it|as|do|if|my|no|so|up|us)$/i', $m[2])) {
                                    $lines[$lastIdx] = $m[1];
                                }
                            }
                            break;
                        }

                        $text = implode("\n", $lines);
                    }

                    // Fix hyphenated line breaks (e.g. "gender-\n sensitive" -> "gender-sensitive")
                    $text = preg_replace('/([a-zA-Z])-[\n\s]+([a-zA-Z])/', '$1-$2', $text);
                    $text = preg_replace('/\n{3,}/', "\n\n", $text);
                    $text = trim($text);

                    if ($text !== '') {
                        $chunk->chunk_text = $text;
                        $chunk->save();
                        $updatedCount++;
                    }
                }

                $this->info("  Done: Updated {$updatedCount} chunks for Document #{$doc->id}.");
            } catch (\Throwable $e) {
                $this->error("  Error processing Document #{$doc->id}: " . $e->getMessage());
            } finally {
                if (file_exists($tempPdf)) {
                    @unlink($tempPdf);
                }
            }
        }

        $this->info("Formatting process completed.");
        return 0;
    }
}
