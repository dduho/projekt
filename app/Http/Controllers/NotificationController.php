<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;

class NotificationController extends Controller
{
    /**
     * List notifications - Web (Inertia) or API (JSON)
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Collection<int, DatabaseNotification> $notifications */
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
        /** @var User $user */
        $user = Auth::user();
        /** @var Collection<int, DatabaseNotification> $unreadNotifications */
        $unreadNotifications = $user->unreadNotifications();

        return response()->json([
            'notifications' => $unreadNotifications->limit(10)->get(),
            'unread_count' => $unreadNotifications->count(),
        ]);
    }

    /**
     * Get unread count (API)
     */
    public function unreadCount(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $count = $user->unreadNotifications()->count();
        return response()->json(['count' => $count]);
    }

    /**
     * Mark notification as read - Web or API
     */
    public function markAsRead(Request $request, string $id)
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()->findOrFail($id);
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
        /** @var User $user */
        $user = Auth::user();
        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();

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
        /** @var User $user */
        $user = Auth::user();
        $user->notifications()->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'All notifications deleted']);
        }

        return back()->with('success', 'All notifications deleted');
    }
}
