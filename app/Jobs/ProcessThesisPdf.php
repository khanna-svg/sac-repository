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

        $pdfPath = "{$tempDir}/temp_{$this->document->id}.pdf";

        try {

            // =====================================================
            // 1. DOWNLOAD PDF FROM SUPABASE
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

            // IMPORTANT:
            // Use SERVICE ROLE key for server-side PDF processing.
            $supabaseKey = config(
                'services.supabase.service_role_key',
                env('SUPABASE_SERVICE_ROLE_KEY')
            );

            if (
                empty($supabaseUrl) ||
                empty($supabaseKey) ||
                empty($bucketName)
            ) {
                throw new \Exception(
                    'Supabase Storage configuration is missing.'
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
                        strlen("{$bucketName}/")
                    );
            }


            // Try authenticated storage endpoint first.
            $fileUrl =
                "{$supabaseUrl}/storage/v1/object/{$bucketName}/{$cleanFilePath}";

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
                    "Failed to download PDF from Supabase. " .
                    "HTTP {$response->status()}"
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
            // 2. PARSE PDF
            // =====================================================

            $parser = new Parser();

            $pdf =
                $parser->parseFile($pdfPath);

            $pages =
                $pdf->getPages();


            $chunks = [];

            $now = now();


            foreach ($pages as $index => $page) {

                $pageNum =
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


                if (!empty($cleanText)) {

                    $chunks[] = [
                        'document_id' =>
                            $this->document->id,

                        'page_number' =>
                            $pageNum,

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
                    'No readable text was extracted from the PDF.'
                );
            }


            Log::info(
                "PDF extracted successfully.",
                [
                    'document_id' =>
                        $this->document->id,

                    'pages' =>
                        count($pages),

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
            // 4. GENERATE EMBEDDINGS + SAVE CHUNKS
            // =====================================================

            $savedChunks = 0;


            foreach ($chunks as $index => $chunk) {

                Log::info(
                    "Generating embedding.",
                    [
                        'document_id' =>
                            $this->document->id,

                        'chunk' =>
                            ($index + 1) .
                            '/' .
                            count($chunks),
                    ]
                );


                // Generate 768-dimensional Gemini embedding.
                $embedding =
                    $geminiService
                        ->generateEmbedding(
                            $chunk['chunk_text']
                        );


                if (
                    empty($embedding) ||
                    count($embedding) !== 768
                ) {
                    throw new \Exception(
                        'Invalid embedding returned for chunk ' .
                        ($index + 1)
                    );
                }


                // pgvector format:
                // [0.123,0.456,...]
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


                $savedChunks++;


                Log::info(
                    "Embedding saved.",
                    [
                        'document_id' =>
                            $this->document->id,

                        'chunk' =>
                            ($index + 1),

                        'saved' =>
                            $savedChunks,
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
                "Thesis processing completed.",
                [
                    'document_id' =>
                        $this->document->id,

                    'total_chunks' =>
                        $savedChunks,

                    'embedded_chunks' =>
                        $embeddedCount,
                ]
            );


            if ($embeddedCount !== $savedChunks) {

                throw new \Exception(
                    "Embedding verification failed. " .
                    "Expected {$savedChunks}, " .
                    "found {$embeddedCount}."
                );
            }

        } catch (\Throwable $e) {

            Log::error(
                "Error processing document ID " .
                $this->document->id,
                [
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

            // =====================================================
            // 6. CLEAN TEMP FILE
            // =====================================================

            if (file_exists($pdfPath)) {
                @unlink($pdfPath);
            }
        }
    }
}