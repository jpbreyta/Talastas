<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserActivityController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255'], 'view' => ['nullable', 'in:changes,security'], 'security_page' => ['nullable', 'integer', 'min:1']]);
        $search = trim((string) $request->query('search', ''));
        if ($request->query('tab') === 'changes' && $request->query('view') === 'security') {
            abort_unless($request->user()?->isAdmin() && $request->user()->is_active, 403);

            return view('admin.users.logs', ['securityEvents' => $this->securityEvents($search)]);
        }

        return view('admin.users.logs', $this->activityData($search));
    }

    private function securityEvents(string $search)
    {
        // Only the audit display fields are read. Never select fingerprints or account credentials.
        return DB::table('security_events as events')
            ->leftJoin('users as actor', 'actor.id', '=', 'events.actor_id')
            ->leftJoin('users as target', 'target.id', '=', 'events.target_id')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($matches) use ($search) {
                    $matches->where('events.event', 'like', '%'.$search.'%')
                        ->orWhere('events.outcome', 'like', '%'.$search.'%')
                        ->orWhere('actor.name', 'like', '%'.$search.'%')
                        ->orWhere('target.name', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('events.occurred_at')->orderByDesc('events.id')
            ->paginate(20, ['events.id', 'events.event', 'events.outcome', 'events.occurred_at',
                'events.actor_id', 'events.target_id', 'events.invitation_id', 'actor.name as actor_name', 'target.name as target_name'], 'security_page')
            ->withQueryString();
    }

    public function activityData(string $search = ''): array
    {
        $sessions = DB::table('user_activity_sessions')->when($search !== '', fn ($q) => $q->where('user_name', 'like', '%'.$search.'%'))->orderByDesc('last_seen_at')->paginate(15, ['*'], 'sessions_page')->withQueryString();
        $logs = DB::table('user_activity_logs')->when($search !== '', fn ($q) => $q->where('user_name', 'like', '%'.$search.'%'))->orderByDesc('occurred_at')->paginate(20, ['*'], 'logs_page')->withQueryString();
        $online = DB::table('user_activity_sessions')->whereNull('closed_at')->where('last_seen_at', '>=', now()->subMinutes(2))->distinct()->count('user_id');

        return compact('sessions', 'logs', 'online');
    }

    public function heartbeat()
    {
        return response()->noContent();
    }
}
