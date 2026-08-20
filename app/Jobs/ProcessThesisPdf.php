<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\GeminiService;
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

    public $timeout = 300;

    protected $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function handle(GeminiService $geminiService)
    {
        ini_set('memory_limit', '512M');

        $tempDir = storage_path('app/temp');

        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $pdfPath =
            "{$tempDir}/temp_{$this->document->id}.pdf";

        try {

            // =====================================================
            // 1. DOWNLOAD PDF
            // =====================================================

            $bucketName = config(
                'services.supabase.bucket',
                env('SUPABASE_STORAGE_BUCKET', 'thesis')
            );

            $supabaseUrl = rtrim(
                config(
                    'services.supabase.url',
                    env('SUPABASE_URL')
                ),
                '/'
            );

            $supabaseKey = config(
                'services.supabase.service_role_key',
                env('SUPABASE_SERVICE_ROLE_KEY')
            );

            if (
                empty($bucketName) ||
                empty($supabaseUrl) ||
                empty($supabaseKey)
            ) {
                throw new \Exception(
                    'Supabase configuration is missing.'
                );
            }

            $cleanFilePath =
                ltrim(
                    (string) $this->document->file_path,
                    '/'
                );

            if (
                str_starts_with(
                    $cleanFilePath,
                    "{$bucketName}/"
                )
            ) {
                $cleanFilePath =
                    substr(
                        $cleanFilePath,
                        strlen($bucketName) + 1
                    );
            }

            $fileUrl =
                "{$supabaseUrl}/storage/v1/object/" .
                "{$bucketName}/{$cleanFilePath}";

            $response = Http::timeout(120)
                ->withHeaders([
                    'apikey' =>
                        $supabaseKey,

                    'Authorization' =>
                        "Bearer {$supabaseKey}",
                ])
                ->get($fileUrl);

            if ($response->failed()) {

                throw new \Exception(
                    'Failed to download PDF from Supabase. HTTP ' .
                    $response->status() .
                    ': ' .
                    $response->body()
                );
            }

            file_put_contents(
                $pdfPath,
                $response->body()
            );

            if (
                !file_exists($pdfPath) ||
                filesize($pdfPath) === 0
            ) {
                throw new \Exception(
                    'Downloaded PDF is empty.'
                );
            }


            // =====================================================
            // 2. EXTRACT TEXT
            // =====================================================

            $parser = new Parser();

            $pdf =
                $parser->parseFile($pdfPath);

            $pages =
                $pdf->getPages();

            $chunks = [];

            $now = now();

            foreach ($pages as $index => $page) {

                $pageNumber =
                    $index + 1;

                $rawText =
                    $page->getText();

                $cleanText =
                    preg_replace(
                        '/[ \t]+/',
                        ' ',
                        $rawText
                    );

                $cleanText =
                    preg_replace(
                        '/[\r\n]+/',
                        "\n\n",
                        $cleanText
                    );

                $cleanText =
                    trim($cleanText);

                if ($cleanText !== '') {

                    $chunks[] = [
                        'document_id' =>
                            $this->document->id,

                        'page_number' =>
                            $pageNumber,

                        'chunk_text' =>
                            $cleanText,

                        'created_at' =>
                            $now,

                        'updated_at' =>
                            $now,
                    ];
                }
            }

            if (empty($chunks)) {

                throw new \Exception(
                    'No readable text was extracted from PDF.'
                );
            }

            Log::info(
                'PDF chunks extracted',
                [
                    'document_id' =>
                        $this->document->id,

                    'chunks' =>
                        count($chunks),
                ]
            );


            // =====================================================
            // 3. DELETE OLD CHUNKS
            // =====================================================

            DB::table('document_chunks')
                ->where(
                    'document_id',
                    $this->document->id
                )
                ->delete();


            // =====================================================
            // 4. PROCESS EMBEDDINGS IN SMALL BATCHES
            // =====================================================

            /*
             * Do NOT send all 288 chunks in one request.
             *
             * We use 20 chunks per Gemini request.
             */

            $batchSize = 20;

            $totalChunks =
                count($chunks);

            $saved = 0;


            foreach (
                array_chunk(
                    $chunks,
                    $batchSize
                ) as $batchIndex => $batch
            ) {

                Log::info(
                    'Generating Gemini embeddings',
                    [
                        'document_id' =>
                            $this->document->id,

                        'batch' =>
                            $batchIndex + 1,

                        'chunks_in_batch' =>
                            count($batch),
                    ]
                );


                $texts =
                    array_map(
                        fn($chunk) =>
                            $chunk['chunk_text'],
                        $batch
                    );


                $embeddings =
                    $geminiService
                        ->generateEmbeddings(
                            $texts
                        );


                if (
                    count($embeddings) !==
                    count($batch)
                ) {
                    throw new \Exception(
                        'Embedding count does not match chunk count.'
                    );
                }


                foreach (
                    $batch as $index => $chunk
                ) {

                    $embedding =
                        $embeddings[$index];

                    $embeddingVector =
                        '[' .
                        implode(
                            ',',
                            $embedding
                        ) .
                        ']';


                    DB::table('document_chunks')
                        ->insert([
                            'document_id' =>
                                $chunk['document_id'],

                            'page_number' =>
                                $chunk['page_number'],

                            'chunk_text' =>
                                $chunk['chunk_text'],

                            'embedding' =>
                                $embeddingVector,

                            'created_at' =>
                                $chunk['created_at'],

                            'updated_at' =>
                                $chunk['updated_at'],
                        ]);

                    $saved++;
                }


                Log::info(
                    'Embedding batch saved',
                    [
                        'document_id' =>
                            $this->document->id,

                        'saved' =>
                            $saved,

                        'total' =>
                            $totalChunks,
                    ]
                );
            }


            // =====================================================
            // 5. VERIFY
            // =====================================================

            $embeddedCount =
                DB::table('document_chunks')
                    ->where(
                        'document_id',
                        $this->document->id
                    )
                    ->whereNotNull('embedding')
                    ->count();


            Log::info(
                'Thesis embedding complete',
                [
                    'document_id' =>
                        $this->document->id,

                    'total' =>
                        $totalChunks,

                    'embedded' =>
                        $embeddedCount,
                ]
            );


            if (
                $embeddedCount !==
                $totalChunks
            ) {

                throw new \Exception(
                    "Embedding verification failed. " .
                    "Expected {$totalChunks}, got {$embeddedCount}."
                );
            }

        } catch (\Throwable $e) {

            Log::error(
                'ProcessThesisPdf failed',
                [
                    'document_id' =>
                        $this->document->id,

                    'message' =>
                        $e->getMessage(),

                    'file' =>
                        $e->getFile(),

                    'line' =>
                        $e->getLine(),
                ]
            );

            throw $e;

        } finally {

            if (file_exists($pdfPath)) {
                @unlink($pdfPath);
            }
        }
    }
}