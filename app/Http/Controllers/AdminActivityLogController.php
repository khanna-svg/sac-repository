<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\Document;
use Illuminate\Http\Request;

class AdminActivityLogController extends Controller
{
    /**
     * Return JSON list of administrative activity logs.
     */
    public function index(Request $request)
    {
        // One-time auto-seed from existing documents if table is empty
        $this->ensureInitialLogsExist();

        $action = trim((string) $request->input('action', 'all'));
        $search = trim((string) $request->input('search', ''));

        $query = AdminActivityLog::query()->orderBy('created_at', 'desc');

        if ($action !== '' && $action !== 'all') {
            if ($action === 'approve') {
                $query->whereIn('action', ['approve', 'approved']);
            } elseif ($action === 'revision') {
                $query->whereIn('action', ['revision_request', 'revision', 'reject']);
            } elseif ($action === 'archive') {
                $query->whereIn('action', ['archive', 'archived', 'restore']);
            } elseif ($action === 'edit') {
                $query->whereIn('action', ['edit', 'edited', 'update']);
            } else {
                $query->where('action', $action);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $term = "%{$search}%";
                $q->where('document_title', 'like', $term)
                  ->orWhere('details', 'like', $term)
                  ->orWhere('admin_name', 'like', $term)
                  ->orWhere('admin_email', 'like', $term);
            });
        }

        $logs = $query->take(150)->get();

        // Calculate counts
        $counts = [
            'all' => AdminActivityLog::count(),
            'approve' => AdminActivityLog::whereIn('action', ['approve', 'approved'])->count(),
            'revision' => AdminActivityLog::whereIn('action', ['revision_request', 'revision', 'reject'])->count(),
            'archive' => AdminActivityLog::whereIn('action', ['archive', 'archived', 'restore'])->count(),
            'edit' => AdminActivityLog::whereIn('action', ['edit', 'edited', 'update'])->count(),
        ];

        return response()->json([
            'success' => true,
            'logs' => $logs,
            'counts' => $counts,
        ]);
    }

    /**
     * Seeds initial audit logs from existing documents if log database is currently empty.
     */
    protected function ensureInitialLogsExist(): void
    {
        if (AdminActivityLog::count() > 0) {
            return;
        }

        // Backfill from approved student submissions
        $approvedDocs = Document::whereNotNull('submitted_by_email')
            ->whereIn('status', ['cleared', 'approved'])
            ->orderBy('updated_at', 'desc')
            ->take(15)
            ->get();

        foreach ($approvedDocs as $doc) {
            AdminActivityLog::create([
                'admin_email' => 'admin@sac.edu.ph',
                'admin_name' => 'SAC Administrator',
                'action' => 'approve',
                'document_id' => $doc->id,
                'document_title' => $doc->title,
                'details' => 'Approved manuscript clearance and published to repository' . ($doc->turnitin_similarity ? " (Turnitin: {$doc->turnitin_similarity})" : ''),
                'ip_address' => '127.0.0.1',
                'created_at' => $doc->updated_at ?: $doc->created_at,
                'updated_at' => $doc->updated_at ?: $doc->created_at,
            ]);
        }

        // Backfill from needs revision documents
        $resubmitDocs = Document::whereNotNull('submitted_by_email')
            ->where('status', 'resubmit')
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        foreach ($resubmitDocs as $doc) {
            AdminActivityLog::create([
                'admin_email' => 'admin@sac.edu.ph',
                'admin_name' => 'SAC Administrator',
                'action' => 'revision_request',
                'document_id' => $doc->id,
                'document_title' => $doc->title,
                'details' => 'Requested revisions' . ($doc->turnitin_similarity ? " (Turnitin: {$doc->turnitin_similarity})" : '') . ($doc->admin_notes ? " - Feedback: {$doc->admin_notes}" : ''),
                'ip_address' => '127.0.0.1',
                'created_at' => $doc->updated_at ?: $doc->created_at,
                'updated_at' => $doc->updated_at ?: $doc->created_at,
            ]);
        }

        // Backfill from archived documents
        $archivedDocs = Document::where('status', 'archived')
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        foreach ($archivedDocs as $doc) {
            AdminActivityLog::create([
                'admin_email' => 'admin@sac.edu.ph',
                'admin_name' => 'SAC Administrator',
                'action' => 'archive',
                'document_id' => $doc->id,
                'document_title' => $doc->title,
                'details' => 'Archived manuscript and removed from active public catalog',
                'ip_address' => '127.0.0.1',
                'created_at' => $doc->updated_at ?: $doc->created_at,
                'updated_at' => $doc->updated_at ?: $doc->created_at,
            ]);
        }
    }
}
