<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest() 
            ->paginate(15)
            ->withQueryString();

        return view(
            'notifications.index',
            compact('notifications')
        );
    }

    public function recent(Request $request): JsonResponse
    {
        return response()->json(
            $this->notificationSnapshot(
                $request->user()
            )
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' =>
                $request->user()
                    ->unreadNotifications()
                    ->count(),
        ]);
    }

    public function stream(Request $request): StreamedResponse|RedirectResponse
    {
        // This endpoint is for EventSource, not a full-page navigation or login destination.
        if ($request->header('Sec-Fetch-Dest') === 'document'
            || !in_array('text/event-stream', $request->getAcceptableContentTypes(), true)) {
            return redirect()->route('notifications.index');
        }

        $userId = (int) $request->user()->id;

        return response()->stream(
            function () use ($userId): void {
                @set_time_limit(0);
                echo "retry: 2000\n\n";

                $lastSignature = null;
                $startedAt = microtime(true);

                while (
                    connection_aborted() === 0
                    && (microtime(true) - $startedAt) < 55
                ) {
                    $user = User::find($userId);

                    if (!$user) {
                        break;
                    }

                    $snapshot =
                        $this->notificationSnapshot($user);

                    $encoded = json_encode(
                        $snapshot,
                        JSON_UNESCAPED_SLASHES
                        | JSON_UNESCAPED_UNICODE
                    );

                    $signature = sha1(
                        (string) $encoded
                    );

                    if ($signature !== $lastSignature) {
                        echo "event: notifications\n";
                        echo "data: {$encoded}\n\n";

                        $lastSignature = $signature;
                    } else {
                        echo ": keep-alive\n\n";
                    }

                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }

                    flush();
                    usleep(1500000);
                }
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' =>
                    'no-cache, no-store, must-revalidate',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }

    public function markAsRead(
        Request $request,
        string $notification
    ): JsonResponse|RedirectResponse {
        // Query through the authenticated user's relationship to prevent IDOR.
        $record = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $record->markAsRead();

        $actionUrl = $this->safeLocalPath(
            $record->data['action_url'] ?? null
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'action_url' => $actionUrl,
                'notifications' =>
                    $this->notificationSnapshot(
                        $request->user()
                    ),
            ]);
        }

        return $actionUrl
            ? redirect($actionUrl)
            : back();
    }

    public function markAllAsRead(
        Request $request
    ): JsonResponse|RedirectResponse {
        $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'notifications' =>
                    $this->notificationSnapshot(
                        $request->user()
                    ),
            ]);
        }

        return back()->with(
            'status',
            'notifications-read'
        );
    }

    public function destroyRead(
        Request $request
    ): JsonResponse|RedirectResponse {
        $request->user()
            ->notifications()
            ->whereNotNull('read_at')
            ->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'notifications' =>
                    $this->notificationSnapshot(
                        $request->user()
                    ),
            ]);
        }

        return back()->with(
            'status',
            'read-notifications-cleared'
        );
    }

    private function notificationSnapshot(
        User $user
    ): array {
        $notifications = $user
            ->notifications()
            ->latest()
            ->limit(6)
            ->get();

        return [
            'unread_count' =>
                $user->unreadNotifications()->count(),

            'notifications' =>
                $notifications
                    ->map(function ($notification): array {
                        $data = is_array(
                            $notification->data
                        )
                            ? $notification->data
                            : [];

                        return [
                            'id' =>
                                (string) $notification->id,
                            'category' =>
                                (string) (
                                    $data['category']
                                    ?? 'system'
                                ),
                            'title' =>
                                (string) (
                                    $data['title']
                                    ?? 'System notification'
                                ),
                            'message' =>
                                (string) (
                                    $data['message']
                                    ?? ''
                                ),
                            'action_url' =>
                                $this->safeLocalPath(
                                    $data['action_url']
                                    ?? null
                                ),
                            'read' =>
                                $notification->read_at
                                !== null,
                            'created_at' =>
                                $notification
                                    ->created_at
                                    ?->toIso8601String(),
                            'created_at_human' =>
                                $notification
                                    ->created_at
                                    ?->diffForHumans()
                                ?? '',
                            'read_url' => route(
                                'notifications.read',
                                $notification->id
                            ),
                        ];
                    })
                    ->values()
                    ->all(),
        ];
    }

    private function safeLocalPath(
        mixed $path
    ): ?string {
        if (
            !is_string($path)
            || $path === ''
        ) {
            return null;
        }

        if (
            !Str::startsWith($path, '/')
            || Str::startsWith($path, '//')
            || preg_match('/[\\\\\x00-\x20\x7f]/', rawurldecode($path))
            || Str::startsWith(rawurldecode($path), '//')
        ) {
            return null;
        }

        return $path;
    }
}
