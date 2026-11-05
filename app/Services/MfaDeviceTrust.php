<?php

namespace App\Services;

use App\Models\MfaTrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as HttpCookie;

class MfaDeviceTrust
{
    public const COOKIE = 'talastas_mfa_trust';

    public const MAX_AGE_DAYS = 30;

    public const FRESH_SECONDS = 600;

    public function inactivityDays(): int
    {
        return max(1, min(self::MAX_AGE_DAYS, (int) config('account-security.mfa_inactivity_days', 7)));
    }

    /** Call only after an email code verification, never after cookie-based resumption. */
    public function establish(User $user, Request $request, bool $trust): void
    {
        DB::transaction(function () use ($user, $request, $trust): void {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            $verifiedAt = $locked->mfa_verified_at;
            $currentTime = now();
            abort_unless($this->eligible($locked) && $locked->security_version === (int) $user->security_version
                && $verifiedAt && $verifiedAt->gt($currentTime->copy()->subSeconds(self::FRESH_SECONDS)) && $verifiedAt->lte($currentTime), 409,
                'Your account security changed. Sign in and verify your email code again.');

            // Replace this session's old authorization and any valid cookie presented by this browser.
            $old = MfaTrustedDevice::where('user_id', $locked->id)->whereKey($request->session()->get('mfa_device_id'))->lockForUpdate()->first();
            if ($old && hash_equals($old->session_hash, $this->sessionHash($request))) {
                $this->invalidate($old, 'mfa.device.replaced');
            }
            [$selector, $secret] = $this->cookieParts($request);
            $previous = $selector ? MfaTrustedDevice::where('user_id', $locked->id)->whereKey($selector)->lockForUpdate()->first() : null;
            if ($previous && $previous->token_hash && hash_equals($previous->token_hash, hash('sha256', $secret))) {
                $this->invalidate($previous, 'mfa.trust.replaced');
            }
            $request->session()->regenerate();
            $remember = $trust && $this->canUsePersistentTrust($request);
            $token = $remember ? bin2hex(random_bytes(32)) : null;
            $device = MfaTrustedDevice::create([
                'id' => (string) Str::uuid(), 'user_id' => $locked->id, 'security_version' => $locked->security_version,
                'token_hash' => $token ? hash('sha256', $token) : null,
                'session_hash' => $this->sessionHash($request), 'browser_hash' => $this->browserHash($request),
                'network_hash' => $this->networkHash($request), 'last_verified_at' => $locked->mfa_verified_at,
                'last_activity_at' => now(), 'expires_at' => now()->addDays(self::MAX_AGE_DAYS),
                'trusted_at' => $remember ? now() : null,
            ]);
            $this->bindSession($locked, $device, $request);
            $request->session()->put('mfa_fresh_at', $locked->mfa_verified_at->timestamp);
            $request->session()->forget('mfa_challenge_reason');
            RateLimiter::clear($this->riskKey($locked));
            if ($token) {
                $this->queueCookie($request, $device->id.':'.$token, $device);
                SecurityAudit::record('mfa.trust.created', $locked->id);
            } else {
                $this->forgetCookie();
                if ($trust) {
                    SecurityAudit::record('mfa.trust.transport_denied', $locked->id, 'denied');
                }
            }
        });
    }

    /** Password authentication must have succeeded before consulting this cookie. */
    public function resume(User $user, Request $request): bool
    {
        return DB::transaction(function () use ($user, $request): bool {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            [$selector, $secret] = $this->cookieParts($request);
            $device = $selector ? MfaTrustedDevice::where('user_id', $locked->id)->whereKey($selector)->lockForUpdate()->first() : null;
            if (! $device || ! $device->trusted_at || ! $this->canUsePersistentTrust($request)) {
                return $this->challenge($request, 'Verify your email code on this browser.');
            }
            $hash = hash('sha256', $secret);
            if (! $device->token_hash || ! hash_equals($device->token_hash, $hash)) {
                if ($device->previous_token_hash && hash_equals($device->previous_token_hash, $hash)) {
                    $this->invalidate($device, 'mfa.trust.replay_rejected');
                }
                SecurityAudit::record('mfa.trust.invalid', $locked->id, 'denied');

                return $this->challenge($request, 'Browser trust is unavailable. Verify your email code again.');
            }
            $reason = $this->challengeReason($device, $request);
            if (! $this->valid($locked, $device, $request)) {
                return $this->challenge($request, $reason);
            }
            // No grace token: concurrent/replayed old cookies cannot authorize another session.
            $token = bin2hex(random_bytes(32));
            $device->previous_token_hash = $device->token_hash;
            $device->token_hash = hash('sha256', $token);
            $device->session_hash = $this->sessionHash($request);
            $device->last_activity_at = now();
            $device->save();
            $this->bindSession($locked, $device, $request);
            $request->session()->forget(['mfa_fresh_at', 'mfa_challenge_reason']);
            $this->queueCookie($request, $device->id.':'.$token, $device);
            SecurityAudit::record('mfa.trust.used', $locked->id);

            return true;
        });
    }

    public function authorized(User $user, Request $request): bool
    {
        if (! $request->hasSession() || (int) $request->session()->get('mfa_version', 0) !== (int) $user->security_version) {
            return false;
        }

        return DB::transaction(function () use ($user, $request): bool {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            $device = MfaTrustedDevice::where('user_id', $locked->id)->whereKey($request->session()->get('mfa_device_id'))->lockForUpdate()->first();
            if (! $device || ! hash_equals($device->session_hash, $this->sessionHash($request)) || ! $this->valid($locked, $device, $request)) {
                return $this->challenge($request, 'Verify your email code again before continuing.');
            }

            return true;
        });
    }

    public function touch(User $user, Request $request): void
    {
        // Background polling alone must not keep a browser trusted indefinitely.
        if ($request->routeIs('activity.heartbeat', 'notifications.stream', 'notifications.recent', 'notifications.unread-count', 'institutions.analytics.state', 'institutions.analytics.stream', 'security.mfa*', 'logout')
            || ($request->isMethod('GET') && ($request->ajax() || $request->expectsJson() || in_array('text/event-stream', $request->getAcceptableContentTypes(), true)))) {
            return;
        }
        MfaTrustedDevice::where('user_id', $user->id)->whereKey($request->session()->get('mfa_device_id'))
            ->where('security_version', $user->security_version)->whereNull('revoked_at')
            ->where('session_hash', $this->sessionHash($request))->update(['last_activity_at' => now()]);
    }

    public function fresh(Request $request): bool
    {
        $time = (int) $request->session()->get('mfa_fresh_at', 0);
        $device = MfaTrustedDevice::where('user_id', $request->user()->id)->whereKey($request->session()->get('mfa_device_id'))
            ->whereNull('revoked_at')->where('session_hash', $this->sessionHash($request))->first();

        return $device && $device->security_version === (int) $request->user()->security_version
            && $time === $device->last_verified_at->timestamp
            && $time > 0 && $time <= now()->timestamp && $time > now()->timestamp - self::FRESH_SECONDS;
    }

    /** Update this session's server record only after an actual factor was consumed. */
    public function recordFresh(User $user, Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }
        $device = MfaTrustedDevice::where('user_id', $user->id)->whereKey($request->session()->get('mfa_device_id'))
            ->where('security_version', $user->security_version)->whereNull('revoked_at')
            ->where('session_hash', $this->sessionHash($request))->lockForUpdate()->first();
        if ($device) {
            $device->update(['last_verified_at' => $user->mfa_verified_at]);
            $request->session()->put('mfa_fresh_at', $user->mfa_verified_at->timestamp);
        }
    }

    public function revoke(User $user, string $id): void
    {
        DB::transaction(function () use ($user, $id): void {
            User::lockForUpdate()->findOrFail($user->id);
            $device = MfaTrustedDevice::where('user_id', $user->id)->whereKey($id)->lockForUpdate()->firstOrFail();
            $this->invalidate($device, 'mfa.trust.revoked');
        });
    }

    public function revokeAll(User $user): void
    {
        DB::transaction(function () use ($user): void {
            User::lockForUpdate()->findOrFail($user->id);
            $count = MfaTrustedDevice::where('user_id', $user->id)->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'token_hash' => null, 'previous_token_hash' => null]);
            if ($count) {
                SecurityAudit::record('mfa.trust.all_revoked', $user->id);
            }
        });
    }

    public function failedPassword(Request $request, string $email): void
    {
        $user = User::whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($email))])->first();
        if (! $user || ! $user->isAdmin()) {
            return;
        }
        // Three failures in fifteen minutes require a factor even on otherwise trusted browsers.
        RateLimiter::hit($this->riskKey($user), 900);
        [$selector, $secret] = $this->cookieParts($request);
        DB::transaction(function () use ($user, $selector, $secret): void {
            User::lockForUpdate()->findOrFail($user->id);
            $device = $selector ? MfaTrustedDevice::where('user_id', $user->id)->whereKey($selector)->lockForUpdate()->first() : null;
            if ($device && $device->token_hash && hash_equals($device->token_hash, hash('sha256', $secret))) {
                $this->invalidate($device, 'mfa.trust.suspicious_password');
            }
            SecurityAudit::record('login.password_failed', $user->id, 'denied');
        });
    }

    public function forgetCookie(): void
    {
        // Bypass CookieJar's default SESSION_DOMAIN: trust must stay host-only.
        Cookie::queue(new HttpCookie(self::COOKIE, '', now()->subYear(), '/', null, (bool) config('session.secure') || request()->isSecure() || app()->isProduction(), true, false, 'strict'));
    }

    public function failedFactor(User $user, Request $request): void
    {
        RateLimiter::hit($this->riskKey($user), 900);
        if ($request->hasSession()) {
            $device = MfaTrustedDevice::where('user_id', $user->id)->whereKey($request->session()->get('mfa_device_id'))->lockForUpdate()->first();
            if ($device && hash_equals($device->session_hash, $this->sessionHash($request))) {
                $this->invalidate($device, 'mfa.trust.suspicious_factor');
            }
        }
    }

    private function eligible(User $user): bool
    {
        return $user->isAdmin() && $user->is_active && $user->email_mfa_confirmed_at
            && $user->mfa_verified_at && ! $user->mfa_secret;
    }

    private function challengeReason(MfaTrustedDevice $device, Request $request): string
    {
        if ($device->revoked_at) {
            return 'This browser authorization was revoked. Verify your email code again.';
        }
        if ($device->expires_at->lte(now()) || $device->last_activity_at->lte(now()->subDays($this->inactivityDays()))) {
            return 'Browser trust expired after inactivity or its maximum 30-day lifetime. Verify your email code again.';
        }
        if (! hash_equals($device->browser_hash, $this->browserHash($request)) || ! hash_equals($device->network_hash, $this->networkHash($request))) {
            return 'Your browser or network context changed. This can happen when changing networks or using a VPN. Verify your email code again.';
        }

        return 'A security change or unusual sign-in activity requires email verification again.';
    }

    private function valid(User $user, MfaTrustedDevice $device, Request $request): bool
    {
        if ($device->revoked_at) {
            return false;
        }
        $expired = $device->expires_at->lte(now()) || $device->last_activity_at->lte(now()->subDays($this->inactivityDays()));
        $suspicious = ! hash_equals($device->browser_hash, $this->browserHash($request))
            || ! hash_equals($device->network_hash, $this->networkHash($request))
            || RateLimiter::tooManyAttempts($this->riskKey($user), 3);
        if (! $this->eligible($user) || $device->security_version !== (int) $user->security_version || $expired || $suspicious) {
            $this->invalidate($device, $suspicious ? 'mfa.trust.suspicious' : 'mfa.trust.expired');

            return false;
        }

        return true;
    }

    private function invalidate(MfaTrustedDevice $device, string $event): void
    {
        if (! $device->revoked_at) {
            $device->update(['revoked_at' => now(), 'token_hash' => null, 'previous_token_hash' => null]);
            SecurityAudit::record($event, $device->user_id, str_contains($event, 'suspicious') || str_contains($event, 'replay') ? 'denied' : 'success');
        }
    }

    private function bindSession(User $user, MfaTrustedDevice $device, Request $request): void
    {
        $request->session()->put(['mfa_version' => (int) $user->security_version, 'mfa_device_id' => $device->id]);
    }

    private function challenge(Request $request, string $reason): bool
    {
        $request->session()->forget(['mfa_version', 'mfa_device_id', 'mfa_fresh_at']);
        $request->session()->put('mfa_challenge_reason', $reason);
        $this->forgetCookie();

        return false;
    }

    private function cookieParts(Request $request): array
    {
        $value = $request->cookie(self::COOKIE);
        if (! is_string($value) || strlen($value) > 110) {
            return [null, ''];
        }
        $parts = explode(':', $value);

        return count($parts) === 2 && Str::isUuid($parts[0]) && preg_match('/\A[0-9a-f]{64}\z/D', $parts[1]) ? $parts : [null, ''];
    }

    private function sessionHash(Request $request): string
    {
        return hash_hmac('sha256', 'mfa-session:'.$request->session()->getId(), (string) config('app.key'));
    }

    private function browserHash(Request $request): string
    {
        return hash_hmac('sha256', 'mfa-browser:'.(string) $request->userAgent(), (string) config('app.key'));
    }

    private function networkHash(Request $request): string
    {
        $packed = @inet_pton((string) $request->ip());
        $network = $packed === false ? 'unknown' : substr($packed, 0, strlen($packed) === 4 ? 3 : 7);

        return hash_hmac('sha256', 'mfa-network:'.$network, (string) config('app.key'));
    }

    private function riskKey(User $user): string
    {
        return 'mfa-login-risk:'.$user->id;
    }

    private function canUsePersistentTrust(Request $request): bool
    {
        return $request->isSecure() || app()->environment('local', 'testing');
    }

    private function queueCookie(Request $request, string $value, MfaTrustedDevice $device): void
    {
        $secure = $request->isSecure() || (bool) config('session.secure') || app()->isProduction();
        Cookie::queue(new HttpCookie(self::COOKIE, $value, $device->expires_at, '/', null, $secure, true, false, 'strict'));
    }
}
