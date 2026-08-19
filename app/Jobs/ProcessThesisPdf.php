<?php

namespace App\Jobs;

use App\Models\Document;
use Smalot\PdfParser\Parser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessThesisPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout for large PDFs

    protected $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function handle()
    {
        // Allocate extra memory for large PDF processing (200+ pages)
        ini_set('memory_limit', '512M');

        // Ensure target directory exists for temp files
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $pdfPath = "{$tempDir}/temp_{$this->document->id}.pdf";

        try {
            // 1. Download file content from Supabase Storage
            $bucketName  = config('services.supabase.bucket', env('SUPABASE_STORAGE_BUCKET', 'thesis'));
            $supabaseUrl = rtrim(config('services.supabase.url', env('SUPABASE_URL')), '/');
            $supabaseKey = config('services.supabase.key', env('SUPABASE_ANON_KEY', env('SUPABASE_SERVICE_ROLE_KEY')));

            // Sanitize file path to avoid leading slashes or duplicate bucket names
            $cleanFilePath = ltrim($this->document->file_path, '/');
            if (str_starts_with($cleanFilePath, "{$bucketName}/")) {
                $cleanFilePath = substr($cleanFilePath, strlen("{$bucketName}/"));
            }

            // Standard Public Bucket Endpoint
            $fileUrl = "{$supabaseUrl}/storage/v1/object/public/{$bucketName}/{$cleanFilePath}";

            // Fetch with Supabase API Headers (handles restricted/private buckets)
            $response = Http::withHeaders([
                'apikey'        => $supabaseKey,
                'Authorization' => "Bearer {$supabaseKey}",
            ])->get($fileUrl);

            // Fallback to Private Authenticated Endpoint if Public Endpoint returns 400/404
            if ($response->failed()) {
                $authenticatedUrl = "{$supabaseUrl}/storage/v1/object/{$bucketName}/{$cleanFilePath}";
                $response = Http::withHeaders([
                    'apikey'        => $supabaseKey,
                    'Authorization' => "Bearer {$supabaseKey}",
                ])->get($authenticatedUrl);
            }

            if ($response->failed()) {
                throw new \Exception("Failed to download PDF from Supabase ({$response->status()}): {$fileUrl}");
            }

            // Save binary stream locally to temp path
            file_put_contents($pdfPath, $response->body());

            // 2. Parse PDF
            $parser = new Parser();
            $pdf    = $parser->parseFile($pdfPath);
            $pages  = $pdf->getPages();

            $chunks = [];
            $now    = now();

            foreach ($pages as $index => $page) {
                $pageNum  = $index + 1;
                $rawText  = $page->getText();

                // Clean up whitespace and format double line breaks for paragraph rendering
                $cleanText = preg_replace('/[ \t]+/', ' ', $rawText);
                $cleanText = preg_replace('/[\r\n]+/', "\n\n", $cleanText);
                $cleanText = trim($cleanText);

                if (!empty($cleanText)) {
                    $chunks[] = [
                        'document_id' => $this->document->id,
                        'page_number' => $pageNum,
                        'chunk_text'  => $cleanText,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }
            }

            // 3. Clear old single-page abstract chunk if it exists
            DB::table('document_chunks')->where('document_id', $this->document->id)->delete();

            // 4. Save clean page chunks into Database in batches of 50
            foreach (array_chunk($chunks, 50) as $batch) {
                DB::table('document_chunks')->insert($batch);
            }
        } catch (\Exception $e) {
            Log::error("Error processing document ID {$this->document->id}: " . $e->getMessage());
            throw $e;
        } finally {
            // Clean up local temp file
            if (file_exists($pdfPath)) {
                @unlink($pdfPath);
            }
        }
    }
}
