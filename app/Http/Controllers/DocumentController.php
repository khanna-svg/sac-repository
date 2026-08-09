<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Stream PDF inline to prevent direct download links.
     */
    public function viewPdf($filename)
    {
        $path = 'documents/' . $filename;

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File not found');
        }

        $fullPath = Storage::disk('public')->path($path);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Fetch all documents or search by keyword.
     */
    public function index(Request $request)
    {
        $query = $request->query('query');

        if ($query) {
            $documents = Document::where('title', 'ILIKE', "%{$query}%")
                ->orWhere('author', 'ILIKE', "%{$query}%")
                ->orWhere('abstract', 'ILIKE', "%{$query}%")
                ->latest()
                ->get();
        } else {
            $documents = Document::latest()->get();
        }

        return response()->json($documents);
    }

    /**
     * Upload document, extract PDF text, generate embeddings, and save chunks.
     */
    public function store(Request $request)
    {
        // ---------------------------------------------------------
        // 1. Validate upload
        // ---------------------------------------------------------
        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'abstract' => 'required|string',
            'pdf' => 'required|mimes:pdf|max:20480',
        ]);

        $file = $request->file('pdf');

        // ---------------------------------------------------------
        // 2. Store PDF
        // ---------------------------------------------------------
        $path = $file->store('documents', 'public');
        $filename = basename($path);
        
        // Generate secure route URL instead of static asset link
        $url = route('documents.file', ['filename' => $filename]);

        // ---------------------------------------------------------
        // 3. Save document metadata
        // ---------------------------------------------------------
        $document = Document::create([
            'title' => $request->title,
            'author' => $request->author,
            'abstract' => $request->abstract,
            'file_path' => $path,
            'file_url' => $url,
        ]);

        try {
            // -----------------------------------------------------
            // 4. Extract text from PDF
            // -----------------------------------------------------
            Log::info("Starting PDF processing for document ID: {$document->id}");

            $parser = new Parser();
            $pdfData = $parser->parseFile($file->getRealPath());
            $rawText = $pdfData->getText();

            // -----------------------------------------------------
            // 5. Clean extracted text
            // -----------------------------------------------------
            $cleanedText = preg_replace('/\s+/', ' ', $rawText);
            $cleanedText = trim($cleanedText);

            if (empty($cleanedText)) {
                Log::warning("PDF contains no readable text. Document ID: {$document->id}");

                return response()->json([
                    'error' => true,
                    'message' => 'The PDF was uploaded, but no readable text could be extracted.',
                    'document' => $document
                ], 422);
            }

            Log::info("PDF text extracted successfully. Characters: " . strlen($cleanedText) . " | Document ID: {$document->id}");

            // -----------------------------------------------------
            // 6. Split text into chunks
            // -----------------------------------------------------
            $chunks = str_split($cleanedText, 800);
            $chunksToProcess = array_slice($chunks, 0, 20);

            Log::info("Total chunks: " . count($chunks) . " | Chunks to process: " . count($chunksToProcess) . " | Document ID: {$document->id}");

            $processedChunks = 0;

            // -----------------------------------------------------
            // 7. Generate embeddings and save chunks
            // -----------------------------------------------------
            foreach ($chunksToProcess as $index => $chunk) {
                $chunk = trim($chunk);

                if ($chunk === '') {
                    continue;
                }

                Log::info("Generating embedding for document {$document->id}, chunk " . ($index + 1));

                $embedding = $this->gemini->generateEmbedding($chunk);

                if (empty($embedding)) {
                    throw new \Exception("Gemini returned an empty embedding for chunk " . ($index + 1));
                }

                // -------------------------------------------------
                // 8. Verify embedding dimensions
                // -------------------------------------------------
                $dimension = count($embedding);

                Log::info("Embedding dimension: {$dimension} | Document ID: {$document->id} | Chunk: " . ($index + 1));

                if ($dimension !== 768) {
                    throw new \Exception("Invalid embedding dimension. Expected 768, received {$dimension}.");
                }

                // -------------------------------------------------
                // 9. Convert embedding to PostgreSQL vector format
                // -------------------------------------------------
                $embeddingVector = '[' . implode(',', $embedding) . ']';

                // -------------------------------------------------
                // 10. Insert into document_chunks
                // -------------------------------------------------
                DB::statement("
                    INSERT INTO document_chunks (document_id, chunk_text, embedding, created_at, updated_at)
                    VALUES (?, ?, ?::extensions.vector, NOW(), NOW())
                ", [
                    $document->id,
                    $chunk,
                    $embeddingVector
                ]);

                $processedChunks++;

                Log::info("Chunk " . ($index + 1) . " inserted successfully for document {$document->id}");
            }

            if ($processedChunks === 0) {
                throw new \Exception("No chunks were successfully inserted.");
            }

            Log::info("Document {$document->id} successfully vectorized. Chunks inserted: {$processedChunks}");

            return response()->json([
                'error' => false,
                'message' => 'Thesis uploaded and vectorized successfully!',
                'chunks_created' => $processedChunks,
                'document' => $document
            ], 201);

        } catch (\Exception $e) {
            Log::error('PDF Extraction/Embedding failed for Doc ID ' . $document->id . ': ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'error' => true,
                'message' => 'The thesis was uploaded, but vectorization failed.',
                'details' => $e->getMessage(),
                'document' => $document
            ], 500);
        }
    }
}