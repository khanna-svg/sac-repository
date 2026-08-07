<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    // Fetch all documents or search by keyword/meaning
    public function index(Request $request)
    {
        $query = $request->query('query');

        if ($query) {
            // Basic database search matching title, author, or abstract
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

    // Handle Upload with Metadata
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'abstract' => 'required|string',
            'pdf' => 'required|mimes:pdf|max:20480', // Max 20MB
        ]);

        $path = $request->file('pdf')->store('documents', 'public');
        $url = asset('storage/' . $path);

        $document = Document::create([
            'title' => $request->title,
            'author' => $request->author,
            'abstract' => $request->abstract,
            'file_path' => $path,
            'file_url' => $url,
        ]);

        return response()->json([
            'message' => 'Thesis uploaded successfully!',
            'document' => $document
        ], 201);
    }
}