<?php

namespace App\Jobs;

use App\Models\Thesis;
use Smalot\PdfParser\Parser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessThesisPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 300; // 5 minutes for large PDFs

    protected $thesis;

    public function __construct(Thesis $thesis)
    {
        $this->thesis = $thesis;
    }

    public function handle()
    {
        $pdfPath = storage_path("app/temp_{$this->thesis->id}.pdf");

        try {
            // 1. Download file content from Supabase Storage
            $bucketName = config('services.supabase.bucket', env('SUPABASE_STORAGE_BUCKET', 'thesis'));
            $supabaseUrl = config('services.supabase.url', env('SUPABASE_URL'));
            
            // Construct the public/signed retrieval URL
            $fileUrl = "{$supabaseUrl}/storage/v1/object/public/{$bucketName}/{$this->thesis->file_path}";

            $response = Http::get($fileUrl);

            if ($response->failed()) {
                throw new \Exception("Failed to download PDF from Supabase: " . $response->status());
            }

            // Save binary stream locally to temp path
            file_put_contents($pdfPath, $response->body());

            // 2. Parse PDF
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $pages = $pdf->getPages();

            $chunks = [];
            $now = now();

            foreach ($pages as $index => $page) {
                $pageNum = $index + 1;
                $rawText = $page->getText();

                // Clean up ugly OCR formatting & whitespace
                $cleanText = preg_replace('/[ \t]+/', ' ', $rawText);
                $cleanText = preg_replace('/\n\s*\n/', "\n\n", $cleanText);
                $cleanText = trim($cleanText);

                if (!empty($cleanText)) {
                    $chunks[] = [
                        'thesis_id'   => $this->thesis->id,
                        'page_number' => $pageNum,
                        'content'     => $cleanText,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }
            }

            // 3. Save clean chunks into Database in batches of 50
            foreach (array_chunk($chunks, 50) as $batch) {
                DB::table('document_chunks')->insert($batch);
            }

        } catch (\Exception $e) {
            Log::error("Error processing thesis ID {$this->thesis->id}: " . $e->getMessage());
            throw $e;
        } finally {
            // Always clean up local temp file
            if (file_exists($pdfPath)) {
                @unlink($pdfPath);
            }
        }
    }
}