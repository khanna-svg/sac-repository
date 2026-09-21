<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ThesisNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Retrieve student or admin notifications and unread badge count.
     */
    public function index(Request $request)
    {
        $email = strtolower((string) $request->session()->get('sac_user_email'));
        $role = (string) $request->session()->get('sac_user_role');

        if ($email === '') {
            return response()->json([
                'notifications' => [],
                'unread_count' => 0,
            ]);
        }

        // If user is Admin, Librarian, or Coordinator:
        // Their notifications are student submissions awaiting review + recently processed submissions
        if (in_array($role, ['admin', 'librarian', 'coordinator'], true)) {
            $lastReadAt = $request->session()->get('admin_notifications_read_at');

            $pendingDocs = Document::whereNotNull('submitted_by_email')
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->take(15)
                ->get();

            $recentReviewed = Document::whereNotNull('submitted_by_email')
                ->whereIn('status', ['approved', 'resubmit'])
                ->orderBy('updated_at', 'desc')
                ->take(5)
                ->get();

            $notifications = [];

            foreach ($pendingDocs as $doc) {
                $isRead = false;
                if ($lastReadAt && $doc->created_at && $doc->created_at <= $lastReadAt) {
                    $isRead = true;
                }

                $studentName = $doc->submitted_by_name ?: ($doc->author ?: 'A student');
                $notifications[] = [
                    'id' => 'doc_pending_' . $doc->id,
                    'title' => 'New Thesis Submission',
                    'message' => "{$studentName} submitted \"{$doc->title}\" for review.",
                    'type' => 'pending',
                    'document_id' => $doc->id,
                    'is_read' => $isRead,
                    'created_at' => $doc->created_at ? $doc->created_at->toIso8601String() : now()->toIso8601String(),
                    'link' => '/admin/submissions',
                ];
            }

            foreach ($recentReviewed as $doc) {
                $statusText = $doc->status === 'approved' ? 'Approved' : 'Needs Resubmission';
                $notifications[] = [
                    'id' => 'doc_reviewed_' . $doc->id,
                    'title' => "Submission {$statusText}",
                    'message' => "\"{$doc->title}\" was evaluated as {$statusText}.",
                    'type' => $doc->status,
                    'document_id' => $doc->id,
                    'is_read' => true,
                    'created_at' => $doc->updated_at ? $doc->updated_at->toIso8601String() : ($doc->created_at ? $doc->created_at->toIso8601String() : now()->toIso8601String()),
                    'link' => '/admin/submissions',
                ];
            }

            $unreadCount = 0;
            foreach ($notifications as $n) {
                if (!$n['is_read']) {
                    $unreadCount++;
                }
            }

            return response()->json([
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
            ]);
        }

        // Student notifications
        $notifications = ThesisNotification::where('user_email', $email)
            ->with(['document:id,title,status'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $unreadCount = ThesisNotification::where('user_email', $email)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark single notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $role = (string) $request->session()->get('sac_user_role');
        $email = strtolower((string) $request->session()->get('sac_user_email'));

        if (in_array($role, ['admin', 'librarian', 'coordinator'], true)) {
            return response()->json(['success' => true]);
        }

        ThesisNotification::where('id', $id)
            ->where('user_email', $email)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $role = (string) $request->session()->get('sac_user_role');
        $email = strtolower((string) $request->session()->get('sac_user_email'));

        if (in_array($role, ['admin', 'librarian', 'coordinator'], true)) {
            $request->session()->put('admin_notifications_read_at', now());
            return response()->json(['success' => true]);
        }

        ThesisNotification::where('user_email', $email)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }
}
