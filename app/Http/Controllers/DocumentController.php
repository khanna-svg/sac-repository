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
        $baseUrl = rtrim((string) getenv('SUPABASE_PROJECT_URL'), '/');
        $key = (string) getenv('SUPABASE_SERVICE_ROLE_KEY');
        $bucket = (string) getenv('SUPABASE_STORAGE_BUCKET');
        
        if (!$baseUrl || !$key || !$bucket) {
            abort(500, 'Supabase Storage is not configured.');
        }

        $path = ltrim($document->file_path, '/');

        $encodedPath = collect(explode('/', $path))
            ->map(fn ($part) => rawurlencode($part))
            ->implode('/');

        $response = Http::timeout(30)
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

    public function index(Request $request)
    {
        $query = $request->query('query');

        $documents = Document::query()
            ->when($query, function ($builder) use ($query) {
                $builder->where('title', 'ILIKE', "%{$query}%")
                    ->orWhere('author', 'ILIKE', "%{$query}%")
                    ->orWhere('abstract', 'ILIKE', "%{$query}%");
            })
            ->latest()
            ->get();

        return response()->json($documents);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'abstract' => 'required|string',
            'pdf' => 'required|mimes:pdf|max:20480',
        ]);

        $file = $request->file('pdf');

        try {
            // Extract text while the uploaded temporary file is available.
            $parser = new Parser();
            $pdfData = $parser->parseFile($file->getRealPath());
            $rawText = trim(preg_replace('/\s+/', ' ', $pdfData->getText()));

            if ($rawText === '') {
                return response()->json([
                    'error' => true,
                    'message' => 'The PDF contains no readable text.',
                ], 422);
            }

            // Upload the actual PDF to private Supabase Storage.
            $baseUrl = rtrim(env('SUPABASE_PROJECT_URL'), '/');
            $key = env('SUPABASE_SERVICE_ROLE_KEY');
            $bucket = env('SUPABASE_STORAGE_BUCKET');

            if (!$baseUrl || !$key || !$bucket) {
                throw new \Exception('Supabase Storage is not configured.');
            }

            $path = 'documents/' . Str::uuid() . '.pdf';

            $upload = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => "Bearer {$key}",
                    'apikey' => $key,
                    'x-upsert' => 'false',
                ])
                ->withBody(
                    file_get_contents($file->getRealPath()),
                    'application/pdf'
                )
                ->post("{$baseUrl}/storage/v1/object/{$bucket}/{$path}");

            if (!$upload->successful()) {
                throw new \Exception(
                    'Supabase Storage upload failed: ' . $upload->body()
                );
            }

            // Save metadata in Supabase Postgres.
            $document = Document::create([
                'title' => $request->title,
                'author' => $request->author,
                'abstract' => $request->abstract,
                'file_path' => $path,
                'file_url' => '',
            ]);

            $document->update([
                'file_url' => "/backend/documents/{$document->id}/view",
            ]);

            // Generate and save RAG embeddings.
            $gemini = app(GeminiService::class);

            $chunks = array_slice(str_split($rawText, 800), 0, 20);
            $processedChunks = 0;

            foreach ($chunks as $chunk) {
                $chunk = trim($chunk);

                if ($chunk === '') {
                    continue;
                }

                $embedding = $gemini->generateEmbedding($chunk);

                if (count($embedding) !== 768) {
                    throw new \Exception('Gemini returned an invalid embedding.');
                }

                $embeddingVector = '[' . implode(',', $embedding) . ']';

                DB::statement(
                    'INSERT INTO document_chunks
                    (document_id, chunk_text, embedding, created_at, updated_at)
                    VALUES (?, ?, ?::extensions.vector, NOW(), NOW())',
                    [$document->id, $chunk, $embeddingVector]
                );

                $processedChunks++;
            }

            return response()->json([
                'error' => false,
                'message' => 'Thesis uploaded and vectorized successfully.',
                'chunks_created' => $processedChunks,
                'document' => $document,
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Document upload failed: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Upload failed. Check the Vercel Runtime Logs.',
            ], 500);
        }
    }
}