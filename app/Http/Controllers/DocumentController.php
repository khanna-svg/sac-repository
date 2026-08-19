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
     * Generate a temporary signed URL and redirect directly to Supabase PDF.
     */
    public function viewPdf(Document $document)
    {
        $baseUrl = rtrim((string) config('services.supabase.url', env('SUPABASE_URL')), '/');
        $key = (string) config('services.supabase.service_role_key', env('SUPABASE_SERVICE_ROLE_KEY'));
        $bucket = (string) config('services.supabase.bucket', env('SUPABASE_STORAGE_BUCKET', 'thesis'));

        if ($baseUrl === '' || $key === '' || $bucket === '') {
            abort(500, 'Supabase Storage is not configured.');
        }

        $path = ltrim(trim((string) $document->file_path), '/');

        if ($path === '') {
            abort(404, 'Document file path is empty.');
        }

        if (str_starts_with($path, $bucket . '/')) {
            $path = substr($path, strlen($bucket) + 1);
        }

        $encodedBucket = rawurlencode($bucket);
        $encodedPath = collect(explode('/', $path))
            ->map(fn($part) => rawurlencode($part))
            ->implode('/');

        $signUrl = "{$baseUrl}/storage/v1/object/sign/{$encodedBucket}/{$encodedPath}";

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

            if (!$response->successful()) {
                Log::error('Supabase signed PDF failed', [
                    'document_id' => $document->id,
                    'status' => $response->status(),
                ]);
                abort(404, 'PDF could not be found in Supabase Storage.');
            }

            $data = $response->json();
            $relativeSignedUrl = $data['signedURL'] ?? $data['signedUrl'] ?? null;

            if (!$relativeSignedUrl) {
                abort(500, 'Supabase did not return a signed URL.');
            }

            if (str_starts_with($relativeSignedUrl, 'http://') || str_starts_with($relativeSignedUrl, 'https://')) {
                $signedUrl = $relativeSignedUrl;
            } elseif (str_starts_with($relativeSignedUrl, '/storage/v1/')) {
                $signedUrl = $baseUrl . $relativeSignedUrl;
            } elseif (str_starts_with($relativeSignedUrl, '/object/')) {
                $signedUrl = $baseUrl . '/storage/v1' . $relativeSignedUrl;
            } else {
                $signedUrl = $baseUrl . '/storage/v1/' . ltrim($relativeSignedUrl, '/');
            }

            return redirect()->away($signedUrl);
        } catch (\Throwable $e) {
            Log::error('PDF view exception: ' . $e->getMessage());
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
            $searchTerm = '%' . strtolower(trim($search)) . '%';

            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(author) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(abstract) LIKE ?', [$searchTerm]);
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
        try {
            // Load document with page_number and sort by page_number
            $document = Document::with(['chunks' => function ($query) {
                $query->select('id', 'document_id', 'page_number', 'chunk_text')
                    ->orderBy('page_number', 'asc');
            }])->findOrFail($id);

            return view('document_detail', [
                'document' => $document,
            ]);
        } catch (\Throwable $e) {
            Log::error('Document detail error: ' . $e->getMessage());

            return response()->json([
                'status' => 'Error loading document',
                'message' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Generate a signed upload URL for direct client-side uploads to Supabase.
     */
    public function createUploadUrl(Request $request)
    {
        if ($request->session()->get('sac_user_role') !== 'admin') {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $filename = (string) $request->input('filename');

        if ($filename === '') {
            return response()->json([
                'error' => true,
                'message' => 'No filename was provided.',
            ], 400);
        }

        $baseUrl = rtrim((string) config('services.supabase.url', env('SUPABASE_URL')), '/');
        $key = (string) config('services.supabase.service_role_key', env('SUPABASE_SERVICE_ROLE_KEY'));
        $bucket = (string) config('services.supabase.bucket', env('SUPABASE_STORAGE_BUCKET', 'thesis'));

        if ($baseUrl === '' || $key === '' || $bucket === '') {
            return response()->json([
                'error' => true,
                'message' => 'Supabase Storage is not configured.',
            ], 500);
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return response()->json([
                'error' => true,
                'message' => 'Only PDF files are allowed.',
            ], 422);
        }

        $path = 'documents/' . Str::uuid() . '.pdf';

        try {
            $encodedBucket = rawurlencode($bucket);
            $encodedPath = collect(explode('/', $path))
                ->map(fn($part) => rawurlencode($part))
                ->implode('/');

            $url = "{$baseUrl}/storage/v1/object/upload/sign/{$encodedBucket}/{$encodedPath}";

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$key}",
                    'apikey' => $key,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, []);

            if (!$response->successful()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Supabase could not create the upload URL.',
                ], 500);
            }

            $data = $response->json();
            $relativeUrl = $data['url'] ?? $data['signedURL'] ?? null;

            if (!$relativeUrl) {
                return response()->json([
                    'error' => true,
                    'message' => 'Supabase did not return an upload URL.',
                ], 500);
            }

            $signedUrl = str_starts_with($relativeUrl, 'http://') || str_starts_with($relativeUrl, 'https://')
                ? $relativeUrl
                : $baseUrl . $relativeUrl;

            $parsedUrl = parse_url($signedUrl);
            $queryParams = [];
            parse_str($parsedUrl['query'] ?? '', $queryParams);
            $token = $queryParams['token'] ?? null;

            if (!$token) {
                return response()->json([
                    'error' => true,
                    'message' => 'Supabase returned an invalid upload URL.',
                ], 500);
            }

            return response()->json([
                'error' => false,
                'path' => $path,
                'signedUrl' => $signedUrl,
                'token' => $token,
            ]);
        } catch (\Throwable $e) {
            Log::error('Create Supabase upload URL failed: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Could not prepare the file upload.',
            ], 500);
        }
    }

    /**
     * Store thesis metadata and all extracted full-text chunks from the PDF.
     */
    public function storeFromSignedUrl(Request $request)
    {
        try {
            $title = trim((string) $request->input('title'));
            $author = trim((string) $request->input('author'));
            $abstract = trim((string) $request->input('abstract'));
            $filePath = trim((string) $request->input('file_path'));
            $extractedChunks = $request->input('chunks', []);

            if ($title === '' || $author === '' || $abstract === '' || $filePath === '') {
                return response()->json([
                    'error' => true,
                    'message' => 'Thesis information is incomplete.',
                ], 400);
            }

            if (!str_starts_with($filePath, 'documents/')) {
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid thesis file path.',
                ], 400);
            }

            // 1. Create Document
            $document = Document::create([
                'title' => $title,
                'author' => $author,
                'abstract' => $abstract,
                'file_path' => $filePath,
                'file_url' => '',
            ]);

            $document->update([
                'file_url' => "/backend/documents/{$document->id}/view",
            ]);

            // 2. Prepare Chunks (Abstract + Extracted Pages)
            $allChunks = [];
            if (!empty($abstract)) {
                $allChunks[] = "ABSTRACT: " . $abstract;
            }

            if (is_array($extractedChunks)) {
                foreach ($extractedChunks as $chunkText) {
                    $clean = trim((string) $chunkText);
                    if ($clean !== '') {
                        $allChunks[] = $clean;
                    }
                }
            }

            // 3. Optimized Chunk Storage
            $gemini = app(GeminiService::class);
            $maxEmbeddedChunks = 5; // Limits live API calls to prevent 504 timeouts

            foreach ($allChunks as $index => $chunk) {
                if ($index < $maxEmbeddedChunks) {
                    try {
                        $embedding = $gemini->generateEmbedding($chunk);
                        $vector = '[' . implode(',', $embedding) . ']';

                        DB::statement(
                            'INSERT INTO document_chunks (document_id, chunk_text, embedding, created_at, updated_at) 
                             VALUES (?, ?, ?::extensions.vector, NOW(), NOW())',
                            [$document->id, $chunk, $vector]
                        );
                        continue;
                    } catch (\Throwable $chunkError) {
                        Log::warning("Embedding generation failed for chunk {$index}: " . $chunkError->getMessage());
                    }
                }

                // Fast Direct SQL Insert for all remaining pages
                DB::statement(
                    'INSERT INTO document_chunks (document_id, chunk_text, created_at, updated_at) 
                     VALUES (?, ?, NOW(), NOW())',
                    [$document->id, $chunk]
                );
            }

            return response()->json([
                'error' => false,
                'message' => 'Thesis uploaded successfully.',
                'document' => $document,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Store signed metadata failed: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Failed to save thesis.',
            ], 500);
        }
    }

    /**
     * Direct file streaming upload fallback.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'abstract' => 'required|string',
            'file' => 'required|file|mimes:pdf|max:51200',
        ]);

        try {
            $file = $request->file('file');
            $filePath = 'documents/' . Str::uuid() . '.pdf';
            $baseUrl = rtrim((string) config('services.supabase.url', env('SUPABASE_URL')), '/');
            $key = (string) config('services.supabase.service_role_key', env('SUPABASE_SERVICE_ROLE_KEY'));
            $bucket = (string) config('services.supabase.bucket', env('SUPABASE_STORAGE_BUCKET', 'thesis'));

            if ($baseUrl === '' || $key === '' || $bucket === '') {
                return response()->json([
                    'error' => true,
                    'message' => 'Supabase Storage is not configured.',
                ], 500);
            }

            $fileStream = fopen($file->getRealPath(), 'r');

            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => "Bearer {$key}",
                    'apikey' => $key,
                ])
                ->withBody($fileStream, 'application/pdf')
                ->post("{$baseUrl}/storage/v1/object/{$bucket}/{$filePath}");

            if (is_resource($fileStream)) {
                fclose($fileStream);
            }

            if (!$response->successful()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Failed to store file in Supabase Storage.',
                ], 500);
            }

            $parser = new Parser();
            $pdf = $parser->parseFile($file->getRealPath());
            $rawText = trim(preg_replace('/\s+/', ' ', $pdf->getText()));

            $document = Document::create([
                'title' => $request->input('title'),
                'author' => $request->input('author'),
                'abstract' => $request->input('abstract'),
                'file_path' => $filePath,
                'file_url' => '',
            ]);

            $document->update([
                'file_url' => "/backend/documents/{$document->id}/view",
            ]);

            try {
                $gemini = app(GeminiService::class);
                $chunks = array_slice(str_split($rawText, 800), 0, 5); // Capped at 5 to prevent timeouts

                foreach ($chunks as $chunk) {
                    $chunk = trim($chunk);
                    if ($chunk === '') continue;

                    $embedding = $gemini->generateEmbedding($chunk);
                    if (!is_array($embedding) || count($embedding) === 0) continue;

                    $vector = '[' . implode(',', $embedding) . ']';

                    DB::statement(
                        'INSERT INTO document_chunks (document_id, chunk_text, embedding, created_at, updated_at) 
                         VALUES (?, ?, ?::extensions.vector, NOW(), NOW())',
                        [$document->id, $chunk, $vector]
                    );
                }
            } catch (\Throwable $embeddingError) {
                Log::error('Fallback embedding failed: ' . $embeddingError->getMessage());
            }

            return response()->json([
                'error' => false,
                'message' => 'Thesis uploaded successfully.',
                'document' => $document,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Upload failed: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Upload failed.',
            ], 500);
        }
    }
}
