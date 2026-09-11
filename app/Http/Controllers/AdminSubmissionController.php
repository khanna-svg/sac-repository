<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ThesisNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminSubmissionController extends Controller
{
    /**
     * Show admin thesis submissions moderation dashboard.
     */
    public function indexView()
    {
        $pendingCount = Document::whereNotNull('submitted_by_email')
            ->where('status', 'pending')
            ->count();

        return view('admin.submissions', [
            'pendingCount' => $pendingCount,
        ]);
    }

    /**
     * Return JSON list of submissions filtered by tab status.
     */
    public function list(Request $request)
    {
        $tab = $request->input('tab', 'pending');
        $search = trim((string) $request->input('search', ''));

        $query = Document::query()->whereNotNull('submitted_by_email');

        if ($tab !== 'all') {
            $query->where('status', $tab);
        }

        if ($search !== '') {
            $searchTerm = '%' . strtolower($search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(author) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(submitted_by_name) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(submitted_by_email) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(department) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(course_code) LIKE ?', [$searchTerm]);
            });
        }

        $submissions = $query->orderBy('created_at', 'desc')->get();

        $counts = [
            'pending' => Document::whereNotNull('submitted_by_email')->where('status', 'pending')->count(),
            'approved' => Document::whereNotNull('submitted_by_email')->where('status', 'approved')->count(),
            'resubmit' => Document::whereNotNull('submitted_by_email')->where('status', 'resubmit')->count(),
            'all' => Document::whereNotNull('submitted_by_email')->count(),
        ];

        return response()->json([
            'submissions' => $submissions,
            'counts' => $counts,
        ]);
    }

    /**
     * Approve and publish thesis to the public repository.
     */
    public function approve($id)
    {
        $document = Document::findOrFail($id);

        $document->update([
            'status' => 'approved',
            'admin_notes' => null,
        ]);

        // Dispatch approval notification to student
        if ($document->submitted_by_email) {
            ThesisNotification::create([
                'user_email' => $document->submitted_by_email,
                'title' => '🎉 Congratulations your thesis was approved',
                'message' => "Congratulations! Your thesis \"{$document->title}\" has been approved by the administrator and is now officially published in the St. Anthony's College Repository.",
                'type' => 'approved',
                'document_id' => $document->id,
                'is_read' => false,
            ]);
        }

        return response()->json([
            'error' => false,
            'message' => 'Thesis approved and published to repository successfully.',
            'document' => $document,
            'redirect_url' => '/admin/upload?from_submission=' . $document->id,
        ]);
    }

    /**
     * Return submission data to pre-fill admin upload form.
     */
    public function prefill($id)
    {
        $document = Document::findOrFail($id);

        $chunkCount = DB::table('document_chunks')
            ->where('document_id', $document->id)
            ->count();

        return response()->json([
            'id' => $document->id,
            'title' => $document->title,
            'author' => $document->author,
            'department' => $document->department,
            'course_code' => $document->course_code,
            'abstract' => $document->abstract,
            'publication_date' => $document->publication_date
                ? \Carbon\Carbon::parse($document->publication_date)->format('Y-m')
                : now()->format('Y-m'),
            'file_path' => $document->file_path,
            'file_name' => basename($document->file_path),
            'submitted_by_name' => $document->submitted_by_name,
            'submitted_by_email' => $document->submitted_by_email,
            'status' => $document->status,
            'chunks_count' => $chunkCount,
        ]);
    }

    /**
     * Request resubmission with admin feedback notes (for Turnitin, citations, format).
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $document = Document::findOrFail($id);
        $notes = trim($request->admin_notes);

        $document->update([
            'status' => 'resubmit',
            'admin_notes' => $notes,
        ]);

        // Dispatch resubmission notification to student
        if ($document->submitted_by_email) {
            ThesisNotification::create([
                'user_email' => $document->submitted_by_email,
                'title' => '⚠️ Needs Resubmission',
                'message' => "Your thesis \"{$document->title}\" requires revisions before it can be approved.\n\nReviewer Feedback:\n{$notes}",
                'type' => 'resubmit',
                'document_id' => $document->id,
                'is_read' => false,
            ]);
        }

        return response()->json([
            'error' => false,
            'message' => 'Thesis marked for resubmission and student notified.',
            'document' => $document,
        ]);
    }

    /**
     * Download original softcopy PDF for Turnitin / Grammarly plagiarism check.
     */
    public function download(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        $baseUrl = rtrim((string) config('services.supabase.url', env('SUPABASE_URL')), '/');
        $key = (string) config('services.supabase.service_role_key', env('SUPABASE_SERVICE_ROLE_KEY'));
        $bucket = (string) config('services.supabase.bucket', env('SUPABASE_STORAGE_BUCKET', 'thesis'));

        $path = ltrim(trim((string) $document->file_path), '/');
        if (str_starts_with($path, $bucket . '/')) {
            $path = substr($path, strlen($bucket) + 1);
        }

        $encodedBucket = rawurlencode($bucket);
        $encodedPath = collect(explode('/', $path))->map(fn($part) => rawurlencode($part))->implode('/');
        $signUrl = "{$baseUrl}/storage/v1/object/sign/{$encodedBucket}/{$encodedPath}";

        $safeFilename = preg_replace('/[^A-Za-z0-9_\-\. ]/', '', $document->title);
        $safeFilename = trim($safeFilename) ?: 'Thesis_Document';
        if (!str_ends_with(strtolower($safeFilename), '.pdf')) {
            $safeFilename .= '.pdf';
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(25)
                ->withHeaders([
                    'Authorization' => "Bearer {$key}",
                    'apikey' => $key,
                    'Content-Type' => 'application/json',
                ])
                ->post($signUrl, [
                    'expiresIn' => 3600,
                    'download' => $safeFilename,
                ]);

            if (!$response->successful()) {
                abort(404, 'Could not retrieve PDF file from storage.');
            }

            $data = $response->json();
            $relativeSignedUrl = $data['signedURL'] ?? $data['signedUrl'] ?? null;
            if (!$relativeSignedUrl) {
                abort(500, 'Storage did not return a download URL.');
            }

            $signedUrl = (str_starts_with($relativeSignedUrl, 'http://') || str_starts_with($relativeSignedUrl, 'https://'))
                ? $relativeSignedUrl
                : $baseUrl . '/storage/v1/' . ltrim($relativeSignedUrl, '/');

            if (!str_contains($signedUrl, 'download=')) {
                $signedUrl .= (str_contains($signedUrl, '?') ? '&' : '?') . 'download=' . urlencode($safeFilename);
            }

            return redirect()->away($signedUrl);
        } catch (\Throwable $e) {
            Log::error('Admin submission download failed: ' . $e->getMessage());
            abort(500, 'Unable to download manuscript PDF.');
        }
    }
}
