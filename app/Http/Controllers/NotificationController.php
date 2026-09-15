<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('notifications.index', compact('notifications'));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        // Query through the authenticated user's relationship to prevent IDOR.
        $record = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $record->markAsRead();

        $actionUrl = $this->safeLocalPath($record->data['action_url'] ?? null);

        return $actionUrl
            ? redirect($actionUrl)
            : back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return back()->with('status', 'notifications-read');
    }

    public function destroyRead(Request $request): RedirectResponse
    {
        $request->user()
            ->notifications()
            ->whereNotNull('read_at')
            ->delete();

        return back()->with('status', 'read-notifications-cleared');
    }

    private function safeLocalPath(mixed $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (! Str::startsWith($path, '/') || Str::startsWith($path, '//')) {
            return null;
        }

        return $path;
    }
}
