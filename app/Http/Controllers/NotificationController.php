<?php

namespace App\Http\Controllers;

use App\Models\ThesisNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Retrieve student notifications and unread badge count.
     */
    public function index(Request $request)
    {
        $email = strtolower((string) $request->session()->get('sac_user_email'));
        if ($email === '') {
            return response()->json([
                'notifications' => [],
                'unread_count' => 0,
            ]);
        }

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
        $email = strtolower((string) $request->session()->get('sac_user_email'));

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
        $email = strtolower((string) $request->session()->get('sac_user_email'));

        ThesisNotification::where('user_email', $email)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }
}
