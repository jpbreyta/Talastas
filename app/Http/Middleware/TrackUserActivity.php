<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $sessionKey = $request->hasSession()
            ? $request->session()->get('_activity_session_key', $this->key($request))
            : $this->key($request);

        $response = $next($request);

        if ($user && $request->routeIs('logout', 'api.v1.auth.logout', 'legal.decline', 'api.v1.legal.decline')) {
            DB::table('user_activity_sessions')
                ->where('session_key', $sessionKey)
                ->where('user_id', $user->id)
                ->update(['closed_at' => now(), 'last_seen_at' => now()]);

            return $response;
        }

        if ($request->routeIs('api.v1.auth.login')) {
            return $response;
        }

        $user = $request->user();
        if (! $user || $response->getStatusCode() >= 400) {
            return $response;
        }

        $sessionKey = $this->key($request);
        if ($request->hasSession()) {
            $request->session()->put('_activity_session_key', $sessionKey);
        }

        // One atomic write replaces a schema check, lookup, and separate insert/update.
        DB::table('user_activity_sessions')->upsert([
            [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'session_key' => $sessionKey,
                'channel' => $request->hasSession() ? 'web' : 'api',
                'opened_at' => now(),
                'last_seen_at' => now(),
                'closed_at' => null,
            ],
        ], ['session_key'], ['user_name', 'last_seen_at']);

        return $response;
    }

    private function key(Request $request): string
    {
        $token = $request->user()?->currentAccessToken();
        $source = $request->hasSession()
            ? 'web:'.$request->session()->getId()
            : 'api:'.($token?->id ?? 'unknown').':'.($request->user()?->id ?? 'guest');

        return hash('sha256', $source);
    }
}
