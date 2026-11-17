<?php

namespace App\Services;

use App\Jobs\SendProfileEmailChangedNotice;
use App\Models\User;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfileEmailChange
{
    private const LIFETIME = 300;

    public function supported(): bool
    {
        $store = Cache::store()->getStore();

        return $store instanceof LockProvider && ($store instanceof FileStore || $store instanceof DatabaseStore || $store instanceof RedisStore);
    }

    private function cache()
    {
        $cache = Cache::store();
        if (! $this->supported()) {
            throw ValidationException::withMessages(['email' => 'Email changes require persistent cache storage with atomic locking. Contact your System Administrator.']);
        }

        return $cache;
    }

    private function key(User $user): string
    {
        return 'profile-email-change:'.$user->id;
    }

    private function context(Request $request): string
    {
        abort_unless($request->hasSession(), 403);

        return hash_hmac('sha256', $request->session()->getId(), (string) config('app.key'));
    }

    private function read($cache, User $user): ?array
    {
        $stored = $cache->get($this->key($user));
        if (! is_string($stored)) {
            return null;
        }
        try {
            $value = json_decode(Crypt::decryptString($stored), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        return is_array($value) && ($value['expires'] ?? 0) > now()->timestamp ? $value : null;
    }

    private function bound(array $challenge, Request $request): bool
    {
        $user = $request->user();

        return ($challenge['user_id'] ?? null) === $user->id
            && ($challenge['version'] ?? null) === $user->security_version
            && ($challenge['old_email'] ?? null) === $user->email
            && hash_equals((string) ($challenge['context'] ?? ''), $this->context($request));
    }

    private function digest(array $challenge, string $code): string
    {
        return hash_hmac('sha256', implode('|', [$challenge['id'], $challenge['user_id'],
            $challenge['old_email'], $challenge['email'], $challenge['version'], $challenge['context'], $code]), (string) config('app.key'));
    }

    public function pending(Request $request): ?array
    {
        $challenge = $this->read($this->cache(), $request->user());

        return $challenge && $this->bound($challenge, $request)
            ? ['email' => $challenge['email'], 'expires_at' => $challenge['expires']] : null;
    }

    private function lockedUser(Request $request, string $password): User
    {
        $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
        abort_unless($user->is_active && $user->security_version === $request->user()->security_version, 409, 'Your account changed. Sign in again.');
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'The password is incorrect.']);
        }

        return $user;
    }

    private function uniqueEmail(string $email, User $user): void
    {
        if ($email === $user->email || User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'This email address cannot be used. Choose a different address.']);
        }
    }

    private function underLock(User $user, callable $work)
    {
        $cache = $this->cache();
        try {
            return $cache->lock($this->key($user).':lock', 120)->block(5, fn () => $work($cache));
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['email' => 'Another email-change request is processing. Please try again.']);
        }
    }

    public function request(Request $request, string $email, string $password): void
    {
        SecurityMail::requireConfigured();
        $this->underLock($request->user(), function ($cache) use ($request, $email, $password) {
            $limit = 'profile-email-send:'.$request->user()->id;
            if (RateLimiter::tooManyAttempts($limit, 5) || RateLimiter::tooManyAttempts($limit.':cooldown', 1)) {
                throw ValidationException::withMessages(['email' => 'Please wait before requesting another code. Up to five requests per hour are allowed.']);
            }
            $challenge = DB::transaction(function () use ($request, $email, $password) {
                $user = $this->lockedUser($request, $password);
                $this->uniqueEmail($email, $user);

                return ['id' => (string) Str::uuid(), 'user_id' => $user->id, 'old_email' => $user->email,
                    'email' => $email, 'version' => $user->security_version, 'context' => $this->context($request),
                    'expires' => now()->timestamp + self::LIFETIME, 'attempts' => 0];
            });
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $challenge['digest'] = $this->digest($challenge, $code);
            RateLimiter::hit($limit, 3600);
            RateLimiter::hit($limit.':cooldown', 60);
            if (! $cache->put($this->key($request->user()), Crypt::encryptString(json_encode($challenge, JSON_THROW_ON_ERROR)), self::LIFETIME)) {
                throw ValidationException::withMessages(['email' => 'Verification could not be saved. No email change was made.']);
            }
            try {
                SecurityMail::send($email, 'email_change_verification', ['code' => $code]);
            } catch (\Throwable) {
                $cache->forget($this->key($request->user()));
                SecurityAudit::record('profile.email_verification_delivery_failed', $request->user()->id, 'failed');
                throw ValidationException::withMessages(['email' => 'The verification email could not be sent. Your current email is unchanged. Try again later.']);
            }
            SecurityAudit::record('profile.email_change_requested', $request->user()->id, 'transport_accepted');
        });
    }

    public function confirm(Request $request, string $code, string $password): bool
    {
        $oldEmail = $this->underLock($request->user(), function ($cache) use ($request, $code, $password) {
            $challenge = $this->read($cache, $request->user());
            if (! $challenge || ! $this->bound($challenge, $request) || $challenge['attempts'] >= 5) {
                throw ValidationException::withMessages(['code' => 'The code is invalid or expired. Request a new code from this session.']);
            }
            $challenge['attempts']++;
            if (! hash_equals($challenge['digest'], $this->digest($challenge, $code))) {
                if ($challenge['attempts'] >= 5) {
                    $cache->forget($this->key($request->user()));
                } elseif (! $cache->put($this->key($request->user()), Crypt::encryptString(json_encode($challenge, JSON_THROW_ON_ERROR)), max(1, $challenge['expires'] - now()->timestamp))) {
                    $cache->forget($this->key($request->user()));
                }
                SecurityAudit::record('profile.email_verification_failed', $request->user()->id, 'denied');
                throw ValidationException::withMessages(['code' => 'The code is invalid or expired. After five failed attempts, request a new code.']);
            }
            try {
                return DB::transaction(function () use ($request, $challenge, $password, $cache) {
                    // This database fence remains authoritative even if a cache lock expires.
                    $user = $this->lockedUser($request, $password);
                    abort_unless($user->security_version === $challenge['version'] && $user->email === $challenge['old_email'], 409, 'This request is no longer valid.');
                    $this->uniqueEmail($challenge['email'], $user);
                    if (! $cache->forget($this->key($user))) {
                        throw ValidationException::withMessages(['code' => 'Verification could not be consumed. Request a new code.']);
                    }
                    $oldEmail = $user->email;
                    $user->email = $challenge['email'];
                    $user->email_verified_at = now();
                    $user->email_mfa_confirmed_at = null;
                    $user->mfa_confirmed_at = null;
                    $user->mfa_verified_at = null;
                    AccountAccess::invalidate($user);
                    SecurityAudit::record('profile.email_changed', $user->id);

                    return $oldEmail;
                });
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['email' => 'This email address cannot be used. Request a code for a different address.']);
            }
        });
        try {
            SendProfileEmailChangedNotice::dispatch($oldEmail, $request->user()->id);

            return true;
        } catch (\Throwable) {
            SecurityAudit::record('profile.email_notice_failed', $request->user()->id, 'failed');

            return false;
        }
    }
}
