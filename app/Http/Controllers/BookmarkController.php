<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookmarkController extends Controller
{
    /**
     * Show Saved / Bookmarks Page
     */
    public function indexView()
    {
        return view('bookmarks');
    }

    /**
     * Fetch Bookmarked Documents (API)
     */
    public function index(Request $request)
    {
        $userEmail = session('sac_user_email');

        if (!$userEmail) {
            return response()->json([]);
        }

        try {
            $bookmarks = Bookmark::with('document')
                ->where('user_email', $userEmail)
                ->latest()
                ->get();

            $documents = $bookmarks
                ->pluck('document')
                ->filter()
                ->values();

            return response()->json($documents);
        } catch (\Throwable $e) {
            Log::warning('Bookmarks query notice: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Fetch array of bookmarked document IDs for current user
     */
    public function getIds(Request $request)
    {
        $userEmail = session('sac_user_email');

        if (!$userEmail) {
            return response()->json([]);
        }

        try {
            $ids = Bookmark::where('user_email', $userEmail)
                ->pluck('document_id')
                ->toArray();

            return response()->json($ids);
        } catch (\Throwable $e) {
            Log::warning('Bookmarks getIds notice: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Toggle bookmark state for a document
     */
    public function toggle(Request $request)
    {
        $userEmail = session('sac_user_email');
        $documentId = (int) $request->input('document_id');

        if (!$userEmail) {
            return response()->json([
                'error' => true,
                'message' => 'Please sign in to save documents.',
            ], 401);
        }

        if (!$documentId) {
            return response()->json([
                'error' => true,
                'message' => 'Document ID is required.',
            ], 400);
        }

        try {
            $existing = Bookmark::where('user_email', $userEmail)
                ->where('document_id', $documentId)
                ->first();

            if ($existing) {
                $existing->delete();
                $bookmarked = false;
                $message = 'Removed from bookmarks.';
            } else {
                Bookmark::create([
                    'user_email' => $userEmail,
                    'document_id' => $documentId,
                ]);
                $bookmarked = true;
                $message = 'Added to bookmarks.';
            }

            return response()->json([
                'error' => false,
                'bookmarked' => $bookmarked,
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error toggling bookmark: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Unable to save bookmark. Make sure bookmarks table exists in Supabase.',
            ], 500);
        }
    }
}
