<?php

namespace App\Http\Controllers;

use App\Mail\ThesisClearanceMail;
use App\Models\Document;
use App\Models\ThesisNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
            if ($tab === 'approved' || $tab === 'cleared') {
                $query->whereIn('status', ['cleared', 'approved']);
            } else {
                $query->where('status', $tab);
            }
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
            'approved' => Document::whereNotNull('submitted_by_email')->whereIn('status', ['cleared', 'approved'])->count(),
            'resubmit' => Document::whereNotNull('submitted_by_email')->where('status', 'resubmit')->count(),
            'all' => Document::whereNotNull('submitted_by_email')->count(),
        ];

        return response()->json([
            'submissions' => $submissions,
            'counts' => $counts,
        ]);
    }

    /**
     * Mark thesis as Passed / Cleared for Turnitin and Grammarly.
     */
    public function approve(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        $similarity = trim((string) $request->input('turnitin_similarity', ''));
        if ($similarity !== '' && !str_ends_with($similarity, '%') && is_numeric($similarity)) {
            $similarity .= '%';
        }
        $notes = trim((string) $request->input('admin_notes', ''));

        $updateData = [
            'status' => 'cleared',
        ];
        if ($similarity !== '') {
            $updateData['turnitin_similarity'] = $similarity;
        }
        if ($notes !== '') {
            $updateData['admin_notes'] = $notes;
        }

        $document->update($updateData);

        // Dispatch in-app notification to student
        if ($document->submitted_by_email) {
            $msg = "Congratulations! Your thesis \"{$document->title}\" has passed Turnitin plagiarism screening"
                . ($similarity ? " (Similarity: {$similarity})" : "")
                . " and Grammarly review. You are cleared for your defense / final manuscript submission.";
            if ($notes !== '') {
                $msg .= "\n\nReviewer Remarks:\n{$notes}";
            }

            ThesisNotification::create([
                'user_email' => $document->submitted_by_email,
                'title' => '🎉 Turnitin & Grammarly Clearance Passed',
                'message' => $msg,
                'type' => 'cleared',
                'document_id' => $document->id,
                'is_read' => false,
            ]);

            // Dispatch official clearance email to student's @sac.edu.ph address
            try {
                Mail::to($document->submitted_by_email)->send(
                    new ThesisClearanceMail($document, 'passed', $similarity ?: null, $notes ?: null)
                );
            } catch (\Throwable $e) {
                Log::warning("Failed to send clearance passed email to {$document->submitted_by_email}: " . $e->getMessage());
            }
        }

        return response()->json([
            'error' => false,
            'message' => 'Thesis marked as Turnitin & Grammarly Cleared. Student notified via in-app alert and @sac.edu.ph email.',
            'document' => $document,
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
            'turnitin_similarity' => $document->turnitin_similarity,
            'admin_notes' => $document->admin_notes,
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
        $similarity = trim((string) $request->input('turnitin_similarity', ''));
        if ($similarity !== '' && !str_ends_with($similarity, '%') && is_numeric($similarity)) {
            $similarity .= '%';
        }

        $updateData = [
            'status' => 'resubmit',
            'admin_notes' => $notes,
        ];
        if ($similarity !== '') {
            $updateData['turnitin_similarity'] = $similarity;
        }

        $document->update($updateData);

        // Dispatch resubmission in-app notification to student
        if ($document->submitted_by_email) {
            $msg = "Your thesis \"{$document->title}\" requires revisions before clearance can be granted."
                . ($similarity ? "\nTurnitin Similarity: {$similarity} (exceeds threshold)." : "")
                . "\n\nReviewer Feedback:\n{$notes}";

            ThesisNotification::create([
                'user_email' => $document->submitted_by_email,
                'title' => '⚠️ Turnitin / Grammarly Revisions Required',
                'message' => $msg,
                'type' => 'resubmit',
                'document_id' => $document->id,
                'is_read' => false,
            ]);

            // Dispatch official revision email to student's @sac.edu.ph address
            try {
                Mail::to($document->submitted_by_email)->send(
                    new ThesisClearanceMail($document, 'resubmit', $similarity ?: null, $notes)
                );
            } catch (\Throwable $e) {
                Log::warning("Failed to send revision email to {$document->submitted_by_email}: " . $e->getMessage());
            }
        }

        return response()->json([
            'error' => false,
            'message' => 'Thesis marked for resubmission. Student notified via in-app alert and @sac.edu.ph email.',
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
