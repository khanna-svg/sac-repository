<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class DocumentController extends Controller
{
    public function viewPdf(Document $document)
    {
        $baseUrl = rtrim((string) env('SUPABASE_URL'), '/');
        $key = (string) env('SUPABASE_SERVICE_ROLE_KEY');
        $bucket = (string) env('SUPABASE_STORAGE_BUCKET');

        if ($baseUrl === '' || $key === '' || $bucket === '') {
            abort(500, 'Supabase Storage is not configured.');
        }

        // Example:
        // documents/21c4eaf7-e373-4b26-b42d-662d813f8b66.pdf
        $path = ltrim(trim((string) $document->file_path), '/');

        if ($path === '') {
            abort(404, 'Document file path is empty.');
        }

        // Make sure the bucket name is NOT part of the stored path.
        if (str_starts_with($path, $bucket . '/')) {
            $path = substr($path, strlen($bucket) + 1);
        }

        $encodedBucket = rawurlencode($bucket);

        $encodedPath = collect(explode('/', $path))
            ->map(fn($part) => rawurlencode($part))
            ->implode('/');

        /*
     * Supabase Storage signing endpoint.
     */
        $signUrl =
            "{$baseUrl}/storage/v1/object/sign/"
            . "{$encodedBucket}/{$encodedPath}";

        Log::info('PDF SIGN REQUEST', [
            'document_id' => $document->id,
            'bucket' => $bucket,
            'path' => $path,
            'url' => $signUrl,
        ]);

        try {

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$key}",
                    'apikey' => $key,
                    'Content-Type' => 'application/json',
                ])
                ->post($signUrl, [
                    'expiresIn' => 3600,
                ]);

            Log::info('PDF SIGN RESPONSE', [
                'document_id' => $document->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {

                Log::error('Supabase signed PDF failed', [
                    'document_id' => $document->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                abort(404, 'PDF could not be found in Supabase Storage.');
            }

            $data = $response->json();

            $relativeSignedUrl =
                $data['signedURL']
                ?? $data['signedUrl']
                ?? null;

            if (!$relativeSignedUrl) {

                Log::error('No signed URL returned by Supabase', [
                    'response' => $data,
                ]);

                abort(500, 'Supabase did not return a signed URL.');
            }

            /*
         * Supabase returns something like:
         *
         * /object/sign/thesis/documents/file.pdf?token=...
         *
         * IMPORTANT:
         * The response does NOT necessarily include /storage/v1.
         */

            if (str_starts_with($relativeSignedUrl, 'http://')) {

                $signedUrl = $relativeSignedUrl;
            } elseif (str_starts_with($relativeSignedUrl, 'https://')) {

                $signedUrl = $relativeSignedUrl;
            } elseif (str_starts_with($relativeSignedUrl, '/storage/v1/')) {

                $signedUrl = $baseUrl . $relativeSignedUrl;
            } elseif (str_starts_with($relativeSignedUrl, '/object/')) {

                $signedUrl = $baseUrl
                    . '/storage/v1'
                    . $relativeSignedUrl;
            } else {

                $signedUrl = $baseUrl
                    . '/storage/v1/'
                    . ltrim($relativeSignedUrl, '/');
            }

            Log::info('FINAL PDF URL', [
                'document_id' => $document->id,
                'signed_url' => $signedUrl,
            ]);

            /*
         * Redirect directly to Supabase.
         *
         * Laravel does NOT stream the 20MB PDF.
         */
            return redirect()->away($signedUrl);
        } catch (\Throwable $e) {

            Log::error('PDF view exception', [
                'document_id' => $document->id,
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            abort(500, 'Unable to open the thesis PDF.');
        }
    }


    /**
     * Fetch document list with optional keyword search.
     */
    public function index(Request $request)
    {
        $query = Document::query();

        if ($search = $request->input('search')) {

            $searchTerm =
                '%' .
                strtolower(trim($search)) .
                '%';

            $query->where(function ($q) use ($searchTerm) {

                $q->whereRaw(
                    'LOWER(title) LIKE ?',
                    [$searchTerm]
                )

                    ->orWhereRaw(
                        'LOWER(author) LIKE ?',
                        [$searchTerm]
                    )

                    ->orWhereRaw(
                        'LOWER(abstract) LIKE ?',
                        [$searchTerm]
                    );
            });
        }

        return response()->json(
            $query->latest()->get()
        );
    }

    /**
     * Display a single thesis document in ProQuest reading view.
     */
    public function show($id)
    {
        $document = Document::with('chunks')->findOrFail($id);
        return view('document-detail', [
            'document' => $document,
        ]);
    }


    /**
     * Generate a signed upload URL for direct
     * client-side uploads to Supabase.
     */
    public function createUploadUrl(Request $request)
    {
        /*
         * Admin check.
         *
         * Your route already uses RequireSacAdmin,
         * but keeping this here provides an additional
         * protection layer.
         */
        if (
            $request->session()->get('sac_user_role')
            !== 'admin'
        ) {
            return response()->json([
                'error' => true,
                'message' =>
                'Unauthorized. Admin access required.',
            ], 403);
        }


        /*
         * Get filename.
         */
        $filename =
            (string) $request->input('filename');


        if ($filename === '') {
            return response()->json([
                'error' => true,
                'message' => 'No filename was provided.',
            ], 400);
        }


        /*
         * Supabase configuration.
         */
        $baseUrl =
            rtrim(
                (string) env('SUPABASE_URL'),
                '/'
            );

        $key =
            (string) env(
                'SUPABASE_SERVICE_ROLE_KEY'
            );

        $bucket =
            (string) env(
                'SUPABASE_STORAGE_BUCKET'
            );


        if (
            $baseUrl === '' ||
            $key === '' ||
            $bucket === ''
        ) {
            Log::error(
                'Supabase environment variables are missing.'
            );

            return response()->json([
                'error' => true,
                'message' =>
                'Supabase Storage is not configured.',
            ], 500);
        }


        /*
         * Only PDF files.
         */
        $extension =
            strtolower(
                pathinfo(
                    $filename,
                    PATHINFO_EXTENSION
                )
            );


        if ($extension !== 'pdf') {
            return response()->json([
                'error' => true,
                'message' =>
                'Only PDF files are allowed.',
            ], 422);
        }


        /*
         * Generate unique storage path.
         *
         * Example:
         *
         * documents/
         * 12345678-....pdf
         */
        $path =
            'documents/' .
            Str::uuid() .
            '.pdf';


        try {

            /*
             * Encode bucket and path correctly.
             */
            $encodedBucket =
                rawurlencode($bucket);


            $encodedPath =
                collect(
                    explode('/', $path)
                )
                ->map(
                    fn($part) =>
                    rawurlencode($part)
                )
                ->implode('/');


            /*
             * Supabase signed upload endpoint.
             */
            $url =
                "{$baseUrl}/storage/v1/object/upload/sign/"
                . "{$encodedBucket}/{$encodedPath}";


            Log::info(
                'Creating Supabase signed upload URL',
                [
                    'bucket' => $bucket,
                    'path' => $path,
                ]
            );


            /*
             * Ask Supabase to create signed URL.
             */
            $response =
                Http::timeout(30)
                ->withHeaders([
                    'Authorization' =>
                    "Bearer {$key}",

                    'apikey' =>
                    $key,

                    'Content-Type' =>
                    'application/json',
                ])
                ->post($url, []);


            Log::info(
                'Supabase signed upload response',
                [
                    'status' =>
                    $response->status(),

                    'body' =>
                    $response->json(),
                ]
            );


            if (!$response->successful()) {

                return response()->json([
                    'error' => true,

                    'message' =>
                    'Supabase could not create the upload URL.',

                    'supabase_status' =>
                    $response->status(),

                    'supabase_error' =>
                    $response->json(),
                ], 500);
            }


            $data =
                $response->json();


            /*
             * Supabase can return "url" or "signedURL".
             */
            $relativeUrl =
                $data['url']
                ?? $data['signedURL']
                ?? null;


            if (!$relativeUrl) {

                Log::error(
                    'Supabase signed upload response missing URL',
                    [
                        'response' => $data,
                    ]
                );

                return response()->json([
                    'error' => true,
                    'message' =>
                    'Supabase did not return an upload URL.',
                ], 500);
            }


            /*
             * Convert relative URL into absolute URL.
             */
            $signedUrl =
                str_starts_with(
                    $relativeUrl,
                    'http://'
                )

                ||

                str_starts_with(
                    $relativeUrl,
                    'https://'
                )

                ? $relativeUrl

                : $baseUrl . $relativeUrl;


            /*
             * Extract signed token.
             */
            $parsedUrl =
                parse_url($signedUrl);


            $queryParams = [];


            parse_str(
                $parsedUrl['query'] ?? '',
                $queryParams
            );


            $token =
                $queryParams['token']
                ?? null;


            if (!$token) {

                Log::error(
                    'Supabase signed upload URL has no token',
                    [
                        'signed_url' =>
                        $signedUrl,

                        'response' =>
                        $data,
                    ]
                );

                return response()->json([
                    'error' => true,
                    'message' =>
                    'Supabase returned an invalid upload URL.',
                ], 500);
            }


            /*
             * Send information to browser.
             */
            return response()->json([
                'error' => false,

                'path' =>
                $path,

                'signedUrl' =>
                $signedUrl,

                'token' =>
                $token,
            ]);
        } catch (\Throwable $e) {

            Log::error(
                'Create Supabase upload URL failed',
                [
                    'message' =>
                    $e->getMessage(),

                    'trace' =>
                    $e->getTraceAsString(),
                ]
            );


            return response()->json([
                'error' => true,
                'message' =>
                'Could not prepare the file upload.',
            ], 500);
        }
    }


    /**
     * Store thesis metadata after the browser has
     * already uploaded the PDF directly to Supabase.
     *
     * IMPORTANT:
     *
     * The PDF upload and database record are treated
     * separately from Gemini/vector processing.
     *
     * If Gemini fails, the thesis is still saved.
     */
    public function storeFromSignedUrl(Request $request)
    {
        try {

            /*
             * Get values.
             */
            $title =
                trim(
                    (string) $request->input('title')
                );

            $author =
                trim(
                    (string) $request->input('author')
                );

            $abstract =
                trim(
                    (string) $request->input('abstract')
                );

            $filePath =
                trim(
                    (string) $request->input('file_path')
                );


            /*
             * Basic data check.
             *
             * This is NOT Laravel validation.
             * It simply prevents an empty database record.
             */
            if (
                $title === '' ||
                $author === '' ||
                $abstract === '' ||
                $filePath === ''
            ) {

                Log::warning(
                    'Store signed metadata received incomplete data.',
                    [
                        'title' =>
                        $title,

                        'author' =>
                        $author,

                        'has_abstract' =>
                        $abstract !== '',

                        'file_path' =>
                        $filePath,
                    ]
                );


                return response()->json([
                    'error' => true,
                    'message' =>
                    'Thesis information is incomplete.',
                ], 400);
            }


            /*
             * Make sure the file path is inside the
             * expected documents directory.
             */
            if (
                !str_starts_with(
                    $filePath,
                    'documents/'
                )
            ) {

                return response()->json([
                    'error' => true,
                    'message' =>
                    'Invalid thesis file path.',
                ], 400);
            }


            /*
             * ========================================
             * 1. CREATE DOCUMENT
             * ========================================
             */
            $document =
                Document::create([
                    'title' =>
                    $title,

                    'author' =>
                    $author,

                    'abstract' =>
                    $abstract,

                    'file_path' =>
                    $filePath,

                    'file_url' =>
                    '',
                ]);


            /*
             * ========================================
             * 2. CREATE INTERNAL VIEW URL
             * ========================================
             */
            $document->update([
                'file_url' =>
                "/backend/documents/{$document->id}/view",
            ]);


            /*
             * ========================================
             * 3. TRY GEMINI EMBEDDING
             * ========================================
             *
             * This is deliberately isolated.
             *
             * If Gemini fails:
             *
             * - thesis stays in database
             * - PDF stays in Supabase
             * - upload still succeeds
             */
            try {

                $gemini =
                    app(GeminiService::class);


                if ($abstract !== '') {

                    $embedding =
                        $gemini->generateEmbedding(
                            $abstract
                        );


                    if (
                        is_array($embedding) &&
                        count($embedding) > 0
                    ) {

                        $vector =
                            '[' .
                            implode(
                                ',',
                                $embedding
                            ) .
                            ']';


                        DB::statement(
                            'INSERT INTO document_chunks
                            (
                                document_id,
                                chunk_text,
                                embedding,
                                created_at,
                                updated_at
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                ?::extensions.vector,
                                NOW(),
                                NOW()
                            )',
                            [
                                $document->id,
                                $abstract,
                                $vector,
                            ]
                        );
                    }
                }
            } catch (\Throwable $embeddingError) {

                /*
                 * DO NOT fail the upload.
                 */
                Log::error(
                    'Gemini/vector processing failed, but thesis was saved.',
                    [
                        'document_id' =>
                        $document->id,

                        'message' =>
                        $embeddingError->getMessage(),

                        'trace' =>
                        $embeddingError->getTraceAsString(),
                    ]
                );
            }


            /*
             * ========================================
             * 4. SUCCESS
             * ========================================
             */
            return response()->json([
                'error' => false,

                'message' =>
                'Thesis uploaded successfully.',

                'document' =>
                $document,
            ], 201);
        } catch (\Throwable $e) {

            /*
             * This catches actual database/system
             * failures.
             */
            Log::error(
                'Store signed metadata failed.',
                [
                    'message' =>
                    $e->getMessage(),

                    'trace' =>
                    $e->getTraceAsString(),
                ]
            );


            return response()->json([
                'error' => true,

                'message' =>
                'Failed to save thesis.',
            ], 500);
        }
    }


    /**
     * Direct file streaming upload fallback.
     *
     * NOTE:
     *
     * This method is NOT used by the new admin
     * direct-to-Supabase upload flow.
     *
     * It is kept here as a fallback.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' =>
            'required|string|max:255',

            'author' =>
            'required|string|max:255',

            'abstract' =>
            'required|string',

            'file' =>
            'required|file|mimes:pdf|max:51200',
        ]);


        try {

            $file =
                $request->file('file');


            $filePath =
                'documents/' .
                Str::uuid() .
                '.pdf';


            $baseUrl =
                rtrim(
                    (string) env('SUPABASE_URL'),
                    '/'
                );


            $key =
                (string) env(
                    'SUPABASE_SERVICE_ROLE_KEY'
                );


            $bucket =
                (string) env(
                    'SUPABASE_STORAGE_BUCKET'
                );


            if (
                $baseUrl === '' ||
                $key === '' ||
                $bucket === ''
            ) {

                return response()->json([
                    'error' => true,

                    'message' =>
                    'Supabase Storage is not configured.',
                ], 500);
            }


            /*
             * Upload directly from Laravel.
             */
            $fileStream =
                fopen(
                    $file->getRealPath(),
                    'r'
                );


            $response =
                Http::timeout(120)
                ->withHeaders([
                    'Authorization' =>
                    "Bearer {$key}",

                    'apikey' =>
                    $key,
                ])
                ->withBody(
                    $fileStream,
                    'application/pdf'
                )
                ->post(
                    "{$baseUrl}/storage/v1/object/{$bucket}/{$filePath}"
                );


            if (is_resource($fileStream)) {
                fclose($fileStream);
            }


            if (!$response->successful()) {

                Log::error(
                    'Supabase upload failed',
                    [
                        'status' =>
                        $response->status(),

                        'body' =>
                        $response->body(),
                    ]
                );


                return response()->json([
                    'error' => true,

                    'message' =>
                    'Failed to store file in Supabase Storage.',
                ], 500);
            }


            /*
             * Parse PDF text.
             */
            $parser =
                new Parser();


            $pdf =
                $parser->parseFile(
                    $file->getRealPath()
                );


            $rawText =
                trim(
                    preg_replace(
                        '/\s+/',
                        ' ',
                        $pdf->getText()
                    )
                );


            /*
             * Create document.
             */
            $document =
                Document::create([
                    'title' =>
                    $request->input('title'),

                    'author' =>
                    $request->input('author'),

                    'abstract' =>
                    $request->input('abstract'),

                    'file_path' =>
                    $filePath,

                    'file_url' =>
                    '',
                ]);


            $document->update([
                'file_url' =>
                "/backend/documents/{$document->id}/view",
            ]);


            /*
             * Generate embeddings.
             */
            try {

                $gemini =
                    app(GeminiService::class);


                $chunks =
                    array_slice(
                        str_split(
                            $rawText,
                            800
                        ),
                        0,
                        20
                    );


                foreach ($chunks as $chunk) {

                    $chunk =
                        trim($chunk);


                    if ($chunk === '') {
                        continue;
                    }


                    $embedding =
                        $gemini->generateEmbedding(
                            $chunk
                        );


                    if (
                        !is_array($embedding) ||
                        count($embedding) === 0
                    ) {
                        continue;
                    }


                    $vector =
                        '[' .
                        implode(
                            ',',
                            $embedding
                        ) .
                        ']';


                    DB::statement(
                        'INSERT INTO document_chunks
                        (
                            document_id,
                            chunk_text,
                            embedding,
                            created_at,
                            updated_at
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?::extensions.vector,
                            NOW(),
                            NOW()
                        )',
                        [
                            $document->id,
                            $chunk,
                            $vector,
                        ]
                    );
                }
            } catch (\Throwable $embeddingError) {

                /*
                 * Do not destroy successful upload.
                 */
                Log::error(
                    'Fallback embedding failed, but thesis was saved.',
                    [
                        'document_id' =>
                        $document->id,

                        'message' =>
                        $embeddingError->getMessage(),
                    ]
                );
            }


            return response()->json([
                'error' => false,

                'message' =>
                'Thesis uploaded successfully.',

                'document' =>
                $document,
            ], 201);
        } catch (\Throwable $e) {

            Log::error(
                'Upload failed',
                [
                    'message' =>
                    $e->getMessage(),

                    'trace' =>
                    $e->getTraceAsString(),
                ]
            );


            return response()->json([
                'error' => true,

                'message' =>
                'Upload failed.',
            ], 500);
        }
    }
}
