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
    /**
     * Display a PDF from private Supabase Storage.
     */
    public function viewPdf(Document $document)
    {
        $baseUrl = rtrim((string) getenv('SUPABASE_URL'), '/');
        $key = (string) getenv('SUPABASE_SERVICE_ROLE_KEY');
        $bucket = (string) getenv('SUPABASE_STORAGE_BUCKET');

        if ($baseUrl === '' || $key === '' || $bucket === '') {
            abort(500, 'Supabase Storage is not configured.');
        }

        $path = ltrim($document->file_path, '/');

        $encodedPath = collect(explode('/', $path))
            ->map(fn ($part) => rawurlencode($part))
            ->implode('/');

        $response = Http::timeout(60)
            ->withHeaders([
                'Authorization' => "Bearer {$key}",
                'apikey' => $key,
            ])
            ->get(
                "{$baseUrl}/storage/v1/object/{$bucket}/{$encodedPath}"
            );

        if (!$response->successful()) {
            abort(404, 'PDF file was not found in Supabase Storage.');
        }

        return response($response->body(), 200, [
            'Content-Type' =>
                $response->header('Content-Type') ?: 'application/pdf',

            'Content-Disposition' =>
                'inline; filename="' . basename($path) . '"',

            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function index(Request $request)
    {
        $query = Document::query();

        if ($search = $request->input('search')) {

            $searchTerm = '%' . strtolower(trim($search)) . '%';

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

    public function createUploadUrl(Request $request)
{
    // Make sure only admins can prepare an upload.
    if ($request->session()->get('user_role') !== 'admin') {
        return response()->json([
            'error' => true,
            'message' => 'Unauthorized. Admin access required.',
        ], 403);
    }

    $request->validate([
        'filename' => 'required|string|max:255',
    ]);

    $baseUrl = rtrim(
        (string) getenv('SUPABASE_URL'),
        '/'
    );

    $key = (string) getenv(
        'SUPABASE_SERVICE_ROLE_KEY'
    );

    $bucket = (string) getenv(
        'SUPABASE_STORAGE_BUCKET'
    );

    if (
        $baseUrl === '' ||
        $key === '' ||
        $bucket === ''
    ) {
        return response()->json([
            'error' => true,
            'message' => 'Supabase Storage is not configured.',
        ], 500);
    }

    // Only allow PDFs.
    $extension = strtolower(
        pathinfo($request->filename, PATHINFO_EXTENSION)
    );

    if ($extension !== 'pdf') {
        return response()->json([
            'error' => true,
            'message' => 'Only PDF files are allowed.',
        ], 422);
    }

    // Generate our own unique path.
    $path = 'documents/' . Str::uuid() . '.pdf';

    try {
        /*
         * Supabase signed upload endpoint.
         *
         * IMPORTANT:
         * Supabase returns:
         *
         * {
         *     "url": "/object/upload/sign/..."
         * }
         *
         * We must convert that relative URL into a full URL
         * and extract the token from its query string.
         */
        $encodedPath = collect(
            explode('/', $path)
        )
            ->map(
                fn ($part) => rawurlencode($part)
            )
            ->implode('/');

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => "Bearer {$key}",
                'apikey' => $key,
                'Content-Type' => 'application/json',
            ])
            ->post(
                "{$baseUrl}/storage/v1/object/upload/sign/"
                . rawurlencode($bucket)
                . "/{$encodedPath}",
                []
            );

        if (!$response->successful()) {

            Log::error(
                'Supabase signed upload URL failed',
                [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]
            );

            return response()->json([
                'error' => true,
                'message' =>
                    'Supabase could not create the upload URL.',
            ], 500);
        }

        $data = $response->json();

        /*
         * Supabase returns "url", NOT "signedUrl".
         */
        $relativeUrl = $data['url'] ?? null;

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
         * Convert Supabase's relative URL into a full URL.
         *
         * Example:
         *
         * /storage/v1/object/upload/sign/thesis/documents/...
         *
         * becomes:
         *
         * https://xxxxx.supabase.co/storage/v1/object/upload/sign/thesis/documents/...
         */
        $signedUrl = str_starts_with(
            $relativeUrl,
            'http://'
        ) || str_starts_with(
            $relativeUrl,
            'https://'
        )
            ? $relativeUrl
            : $baseUrl . $relativeUrl;

        /*
         * Extract the token from:
         *
         * ?token=xxxxxxxx
         */
        $parsedUrl = parse_url($signedUrl);

        parse_str(
            $parsedUrl['query'] ?? '',
            $queryParams
        );

        $token = $queryParams['token'] ?? null;

        if (!$token) {

            Log::error(
                'Supabase signed upload URL has no token',
                [
                    'url' => $signedUrl,
                    'response' => $data,
                ]
            );

            return response()->json([
                'error' => true,
                'message' =>
                    'Supabase returned an invalid upload URL.',
            ], 500);
        }

        return response()->json([
            'error' => false,
            'path' => $path,
            'signedUrl' => $signedUrl,
            'token' => $token,
        ]);

    } catch (\Throwable $e) {

        Log::error(
            'Create Supabase upload URL failed: '
            . $e->getMessage()
        );

        return response()->json([
            'error' => true,
            'message' =>
                'Could not prepare the file upload.',
        ], 500);
    }
}

    public function store(Request $request)
    {
        $request->validate([
            'title' =>
                'required|string|max:255',

            'author' =>
                'required|string|max:255',

            'abstract' =>
                'required|string',

            'file_path' => [
                'required',
                'string',
                'max:500',
                'regex:/^documents\/[a-f0-9-]+\.pdf$/i',
            ],
        ]);


        $filePath = ltrim(
            $request->input('file_path'),
            '/'
        );


        if (!preg_match(
            '/^documents\/[a-f0-9-]+\.pdf$/i',
            $filePath
        )) {

            return response()->json([
                'error' => true,
                'message' =>
                    'Invalid file path.',
            ], 422);
        }


        $baseUrl = rtrim(
            (string) getenv('SUPABASE_URL'),
            '/'
        );

        $key = (string) getenv(
            'SUPABASE_SERVICE_ROLE_KEY'
        );

        $bucket = (string) getenv(
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


        $tempPath = null;


        try {

            $encodedPath = collect(
                explode('/', $filePath)
            )
                ->map(
                    fn ($part) =>
                        rawurlencode($part)
                )
                ->implode('/');


            $tempPath = tempnam(
                sys_get_temp_dir(),
                'thesis_'
            );


            $pdfResponse = Http::timeout(120)
                ->withOptions([
                    'sink' =>
                        $tempPath,
                ])
                ->withHeaders([
                    'Authorization' =>
                        "Bearer {$key}",

                    'apikey' =>
                        $key,
                ])
                ->get(
                    "{$baseUrl}/storage/v1/object/"
                    . "{$bucket}/{$encodedPath}"
                );


            if (!$pdfResponse->successful()) {

                throw new \Exception(
                    'Could not retrieve the uploaded PDF '
                    . 'from Supabase Storage. HTTP '
                    . $pdfResponse->status()
                );
            }


            if (
                !file_exists($tempPath) ||
                filesize($tempPath) === 0
            ) {

                throw new \Exception(
                    'The uploaded PDF could not be retrieved.'
                );
            }

            $parser = new Parser();

            $pdf = $parser->parseFile(
                $tempPath
            );


            $rawText = trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $pdf->getText()
                )
            );


            if ($rawText === '') {

                @unlink($tempPath);

                return response()->json([
                    'error' => true,
                    'message' =>
                        'The PDF contains no readable text.',
                ], 422);
            }

            $document = Document::create([
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
                    "/backend/documents/"
                    . $document->id
                    . "/view",
            ]);

            $gemini = app(
                GeminiService::class
            );


            $chunks = array_slice(
                str_split($rawText, 800),
                0,
                20
            );


            $processedChunks = 0;


            foreach ($chunks as $chunk) {

                $chunk = trim($chunk);


                if ($chunk === '') {
                    continue;
                }


                $embedding =
                    $gemini->generateEmbedding(
                        $chunk
                    );


                if (
                    count($embedding) !== 768
                ) {

                    throw new \Exception(
                        'Gemini returned an invalid embedding.'
                    );
                }


                $vector =
                    '[' .
                    implode(',', $embedding) .
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
                    VALUES (?, ?, ?::extensions.vector, NOW(), NOW())',
                    [
                        $document->id,
                        $chunk,
                        $vector,
                    ]
                );


                $processedChunks++;
            }


            @unlink($tempPath);


            return response()->json([
                'error' =>
                    false,

                'message' =>
                    'Thesis uploaded and vectorized successfully.',

                'chunks_created' =>
                    $processedChunks,

                'document' =>
                    $document,
            ], 201);


        } catch (\Throwable $e) {

            if (
                $tempPath &&
                file_exists($tempPath)
            ) {
                @unlink($tempPath);
            }


            Log::error(
                'Document upload failed',
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
                    'Upload failed. Check the Vercel Runtime Logs.',
            ], 500);
        }
    }
}