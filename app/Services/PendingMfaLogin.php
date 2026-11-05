<?php

namespace App\Services;

use Illuminate\Http\Request;

/** Evidence of the password factor, never MFA authorization itself. */
class PendingMfaLogin
{
    public const LIFETIME = 300;

    public function begin(Request $request): void
    {
        if (config('session.driver') === 'cookie') {
            return; // Password reuse requires server-side session storage.
        }
        $request->session()->put('pending_mfa_login', [
            'user_id' => $request->user()->id,
            'security_version' => (int) $request->user()->security_version,
            'verified_at' => now()->timestamp,
            'session_hash' => $this->sessionHash($request),
        ]);
    }

    public function valid(Request $request): bool
    {
        $state = $request->session()->get('pending_mfa_login', []);
        $user = $request->user();
        $time = $state['verified_at'] ?? 0;

        return config('session.driver') !== 'cookie' && $user && $user->isAdmin() && $user->is_active
            && ($state['user_id'] ?? null) === $user->id
            && ($state['security_version'] ?? null) === (int) $user->security_version
            && is_int($time) && $time <= now()->timestamp && $time > now()->timestamp - self::LIFETIME
            && is_string($state['session_hash'] ?? null)
            && hash_equals($this->sessionHash($request), $state['session_hash']);
    }

    private function sessionHash(Request $request): string
    {
        return hash_hmac('sha256', 'pending-mfa-login:'.$request->session()->getId(), (string) config('app.key'));
    }
}
