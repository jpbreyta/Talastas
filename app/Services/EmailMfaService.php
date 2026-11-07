<?php

namespace App\Services;

use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmailMfaService
{
    public const LIFETIME_MINUTES = 5;

    /** Send directly so signing in does not depend on a running queue worker. */
    public function send(User $user, bool $resend = false): EmailVerificationCode
    {
        SecurityMail::requireConfigured();

        return DB::transaction(function () use ($user, $resend) {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            abort_unless($locked->isAdmin() && $locked->is_active
                && $locked->security_version === (int) $user->security_version, 403);
            $context = $this->context();
            $row = EmailVerificationCode::where('user_id', $locked->id)->where('context_hash', $context)->lockForUpdate()->first();
            if (
                ! $resend && $row && $row->security_version === $locked->security_version && ! $row->consumed_at
                && $row->expires_at->isFuture()
            ) {
                return $row;
            }
            if (
                RateLimiter::tooManyAttempts('email-mfa-send:' . $locked->id, 5)
                || RateLimiter::tooManyAttempts('email-mfa-cooldown:' . $locked->id, 1)
            ) {
                throw ValidationException::withMessages(['code' => 'Please wait before requesting another email code. You can request up to five codes per hour.']);
            }
            RateLimiter::hit('email-mfa-send:' . $locked->id, 3600);
            RateLimiter::hit('email-mfa-cooldown:' . $locked->id, 60);
            $row ??= new EmailVerificationCode(['id' => (string) Str::uuid(), 'user_id' => $locked->id, 'context_hash' => $context]);
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $row->fill([
                'security_version' => $locked->security_version,
                'attempts' => 0,
                'requested_at' => now(),
                'expires_at' => now()->addMinutes(self::LIFETIME_MINUTES),
                'consumed_at' => null
            ]);
            $row->code_hash = $this->digest($row, $locked, $code);
            $row->save();
            try {
                SecurityMail::send($locked->email, 'verification', ['code' => $code]);
            } catch (\Throwable $exception) {
                $safeMessage = $exception->getMessage();

                // Redact sensitive values before writing diagnostic logs.
                foreach ([
                    config('services.resend.key'),
                    config('mail.from.address'),
                    $locked->email,
                ] as $sensitive) {
                    if (is_string($sensitive) && $sensitive !== '') {
                        $safeMessage = str_replace($sensitive, '[redacted]', $safeMessage);
                    }
                }

                $safeMessage = preg_replace(
                    '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',
                    '[redacted-email]',
                    $safeMessage
                ) ?? 'Unable to capture provider error.';

                $safeMessage = preg_replace('/\b\d{6}\b/', '[redacted-code]', $safeMessage);
                $safeMessage = Str::limit($safeMessage, 300);

                Log::warning('Verification email delivery failed.', [
                    'user_id' => $locked->id,
                    'exception_class' => get_class($exception),
                    'provider_error' => $safeMessage,
                ]);

                throw ValidationException::withMessages([
                    'mail' => 'We could not send your email code. Please try again in a minute or contact your System Admin.',
                ]);
            }
            SecurityAudit::record('mfa.email_sent', $locked->id, 'transport_accepted');

            return $row;
        });
    }

    public function verify(User $user, string $code, ?string $challengeId = null): bool
    {
        return DB::transaction(function () use ($user, $code, $challengeId) {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            $limit = 'mfa-factor:' . $locked->id;
            if (RateLimiter::tooManyAttempts($limit, 5)) {
                SecurityAudit::record('mfa.throttled', $locked->id, 'denied');

                return false;
            }
            $query = EmailVerificationCode::where('user_id', $locked->id)->where('context_hash', $this->context());
            if ($challengeId !== null) {
                $query->whereKey($challengeId);
            }
            $row = $query->lockForUpdate()->first();
            if (
                ! $locked->is_active || ! $locked->isAdmin() || $locked->security_version !== (int) $user->security_version
                || ! $row || $row->security_version !== $locked->security_version || $row->consumed_at
                || ! $row->expires_at->isFuture() || $row->attempts >= 5 || ! $row->code_hash
            ) {
                RateLimiter::hit($limit, 60);
                SecurityAudit::record('mfa.failed', $locked->id, 'denied');

                return false;
            }
            $row->attempts++;
            $valid = preg_match('/\A[0-9]{6}\z/D', $code) && hash_equals($row->code_hash, $this->digest($row, $locked, $code));
            if (! $valid) {
                if ($row->attempts >= 5) {
                    $row->code_hash = null;
                }
                $row->save();
                RateLimiter::hit($limit, 60);
                app(MfaDeviceTrust::class)->failedFactor($locked, request());
                SecurityAudit::record('mfa.failed', $locked->id, 'denied');

                return false;
            }
            $row->fill(['consumed_at' => now(), 'code_hash' => null])->save();
            if (! $locked->email_mfa_confirmed_at) {
                // Authenticator-era browser grants must not become valid again after migration.
                app(MfaDeviceTrust::class)->revokeAll($locked);
            }
            $locked->email_mfa_confirmed_at = now();
            $locked->mfa_confirmed_at = now();
            $locked->mfa_verified_at = now();
            $locked->email_verified_at ??= now();
            // Retire legacy authenticator credentials after the first successful email check.
            $locked->mfa_secret = null;
            $locked->mfa_recovery_codes = null;
            $locked->mfa_last_step = -1;
            $locked->save();
            app(MfaDeviceTrust::class)->recordFresh($locked, request());
            RateLimiter::clear($limit);
            SecurityAudit::record('mfa.verified', $locked->id);

            return true;
        });
    }

    private function context(): string
    {
        $request = request();
        $value = $request->hasSession() ? 'web:' . $request->session()->getId()
            : 'api:' . $request->ip() . ':' . $request->userAgent();

        return hash_hmac('sha256', 'email-mfa:' . $value, (string) config('app.key'));
    }

    private function digest(EmailVerificationCode $row, User $user, string $code): string
    {
        return hash_hmac('sha256', $row->id . ':' . $user->id . ':' . $user->email . ':' . $row->security_version . ':' . $row->context_hash . ':' . $code, (string) config('app.key'));
    }
}
