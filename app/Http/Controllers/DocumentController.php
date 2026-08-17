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
            ->get("{$baseUrl}/storage/v1/object/{$bucket}/{$encodedPath}");

        if (!$response->successful()) {
            abort(404, 'PDF file was not found in Supabase Storage.');
        }

        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type') ?: 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'Cache-Control' => 'private, no-store',
        ]);
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
     * Generate a signed upload URL for direct client-side uploads (Legacy/Fallback).
     */
    public function createUploadUrl(Request $request)
    {
        if ($request->session()->get('sac_user_role') !== 'admin') {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $request->validate([
            'filename' => 'required|string|max:255',
        ]);

        $baseUrl = rtrim((string) getenv('SUPABASE_URL'), '/');
        $key = (string) getenv('SUPABASE_SERVICE_ROLE_KEY');
        $bucket = (string) getenv('SUPABASE_STORAGE_BUCKET');

        if ($baseUrl === '' || $key === '' || $bucket === '') {
            return response()->json([
                'error' => true,
                'message' => 'Supabase Storage is not configured.',
            ], 500);
        }

        $extension = strtolower(pathinfo($request->filename, PATHINFO_EXTENSION));

        if ($extension !== 'pdf') {
            return response()->json([
                'error' => true,
                'message' => 'Only PDF files are allowed.',
            ], 422);
        }

        $path = 'documents/' . Str::uuid() . '.pdf';

        try {
            $encodedPath = collect(explode('/', $path))
                ->map(fn ($part) => rawurlencode($part))
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
                Log::error('Supabase signed upload URL failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'error' => true,
                    'message' => 'Supabase could not create the upload URL.',
                ], 500);
            }

            $data = $response->json();
            $relativeUrl = $data['url'] ?? null;

            if (!$relativeUrl) {
                Log::error('Supabase signed upload response missing URL', [
                    'response' => $data,
                ]);

                return response()->json([
                    'error' => true,
                    'message' => 'Supabase did not return an upload URL.',
                ], 500);
            }

            $signedUrl = str_starts_with($relativeUrl, 'http://') || str_starts_with($relativeUrl, 'https://')
                ? $relativeUrl
                : $baseUrl . $relativeUrl;

            $parsedUrl = parse_url($signedUrl);
            parse_str($parsedUrl['query'] ?? '', $queryParams);
            $token = $queryParams['token'] ?? null;

            if (!$token) {
                Log::error('Supabase signed upload URL has no token', [
                    'url' => $signedUrl,
                    'response' => $data,
                ]);

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
     * Handle direct file submission, storage in Supabase, and vector embeddings generation.
     */
    public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'author' => 'required|string|max:255',
        'abstract' => 'required|string',
        'file' => 'required|file|mimes:pdf|max:51200', // 50MB
    ]);

    try {
        $file = $request->file('file');
        $filePath = 'documents/' . Str::uuid() . '.pdf';

        $baseUrl = rtrim((string) getenv('SUPABASE_URL'), '/');
        $key = (string) getenv('SUPABASE_SERVICE_ROLE_KEY');
        $bucket = (string) getenv('SUPABASE_STORAGE_BUCKET');

        if ($baseUrl === '' || $key === '' || $bucket === '') {
            return response()->json([
                'error' => true,
                'message' => 'Supabase Storage is not configured.',
            ], 500);
        }

        // Stream file directly from Laravel to Supabase Storage (Bypasses Browser CORS)
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
            Log::error('Supabase upload failed', ['body' => $response->body()]);
            return response()->json([
                'error' => true,
                'message' => 'Failed to store file in Supabase Storage.',
            ], 500);
        }

        // Extract text and vector embeddings
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

        // Process Gemini Embeddings
        $gemini = app(GeminiService::class);
        $chunks = array_slice(str_split($rawText, 800), 0, 20);

        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') continue;

            $embedding = $gemini->generateEmbedding($chunk);
            $vector = '[' . implode(',', $embedding) . ']';

            DB::statement(
                'INSERT INTO document_chunks (document_id, chunk_text, embedding, created_at, updated_at) VALUES (?, ?, ?::extensions.vector, NOW(), NOW())',
                [$document->id, $chunk, $vector]
            );
        }

        return response()->json([
            'error' => false,
            'message' => 'Thesis uploaded and vectorized successfully.',
            'document' => $document,
        ], 201);

    } catch (\Throwable $e) {
        Log::error('Upload failed: ' . $e->getMessage());

        return response()->json([
            'error' => true,
            'message' => 'Upload failed: ' . $e->getMessage(),
        ], 500);
    }
}
}