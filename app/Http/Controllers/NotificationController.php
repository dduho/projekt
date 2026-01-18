<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * List notifications - Web (Inertia) or API (JSON)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        if ($request->wantsJson()) {
            return response()->json([
                'notifications' => $notifications->items(),
                'unread_count' => $user->unreadNotifications()->count(),
            ]);
        }

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Get unread notifications (API/AJAX)
     */
    public function unread(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'notifications' => $user->unreadNotifications()->limit(10)->get(),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Get unread count (API)
     */
    public function unreadCount(): JsonResponse
    {
        $count = Auth::user()->unreadNotifications()->count();
        return response()->json(['count' => $count]);
    }

    /**
     * Mark notification as read - Web or API
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Notification marked as read']);
        }

        return back();
    }

    /**
     * Mark all notifications as read - Web or API
     */
    public function markAllAsRead(Request $request)
    {
        Auth::user()->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'All notifications marked as read']);
        }

        return back()->with('success', 'All notifications marked as read');
    }

    /**
     * Delete notification - Web or API
     */
    public function destroy(Request $request, string $id)
    {
        Auth::user()->notifications()->findOrFail($id)->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Notification deleted']);
        }

        return back();
    }

    /**
     * Delete all notifications (Web)
     */
    public function destroyAll(Request $request)
    {
        Auth::user()->notifications()->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'All notifications deleted']);
        }

        return back()->with('success', 'All notifications deleted');
    }
}
