<?php

namespace App\Services;

use App\Models\AccountRecovery;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class RecoveryService
{
    public const MESSAGE = 'If an eligible account exists, password recovery instructions will be sent.';

    public function request(string $email, bool $adminTriggered = false): void
    {
        $email = strtolower(trim($email));
        $key = 'recovery:'.hash_hmac('sha256', $email, (string) config('app.key'));
        if (! RateLimiter::attempt($key, 5, fn () => true, 3600)) {
            SecurityAudit::record('otp.throttled', null, 'denied');

            return;
        }
        if (! SecurityMail::available()) {
            return;
        }
        DB::transaction(function () use ($email, $adminTriggered) {
            $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->lockForUpdate()->first();
            if (! $user || ! $user->is_active || (! in_array($user->role, ['editor', 'viewer'], true) && ! ($adminTriggered && $user->isAdmin() && $user->mfa_confirmed_at))) {
                return;
            }
            $row = AccountRecovery::where('user_id', $user->id)->lockForUpdate()->first();
            if ($row && $row->requested_at->gt(now()->subMinute())) {
                SecurityAudit::record('otp.cooldown', $user->id, 'denied');

                return;
            }
            $row ??= new AccountRecovery(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'generation' => 0]);
            $row->generation++;
            $row->fill(['purpose' => $user->isAdmin() ? 'admin_reset' : 'password_reset', 'code_hash' => null, 'grant_hash' => null, 'attempts' => 0,
                'requested_at' => now(), 'expires_at' => now()->addMinutes(5), 'verified_at' => null, 'consumed_at' => null, 'delivery_status' => 'queued'])->save();
            SecurityAudit::record($adminTriggered ? 'password.admin_reset_requested' : 'otp.requested', $user->id);
            SecurityMail::queue('recovery', $row->id, $row->generation);
        });
    }

    public static function digest(AccountRecovery $row, string $code): string
    {
        return hash_hmac('sha256', $row->id.':'.$row->user_id.':'.$row->purpose.':'.$row->generation.':'.$code, (string) config('app.key'));
    }

    public function verify(string $email, string $code): ?array
    {
        return DB::transaction(function () use ($email, $code) {
            $user = User::whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($email))])->lockForUpdate()->first();
            $row = $user ? AccountRecovery::where('user_id', $user->id)->lockForUpdate()->first() : null;
            if (! $row || ! $user->is_active || $row->consumed_at || $row->verified_at || ! $row->expires_at->isFuture() || $row->attempts >= 5 || ! $row->code_hash) {
                SecurityAudit::record('otp.rejected', $user?->id, 'denied');

                return null;
            }
            $row->attempts++;
            $row->save();
            if (! hash_equals($row->code_hash, self::digest($row, $code)) || ($user->isAdmin() && $row->purpose !== 'admin_reset')) {
                if ($row->attempts >= 5) {
                    $row->code_hash = null;
                    $row->save();
                }
                SecurityAudit::record($row->attempts >= 5 ? 'otp.locked' : 'otp.failed', $user->id, 'denied');

                return null;
            }
            $grant = bin2hex(random_bytes(32));
            $row->update(['code_hash' => null, 'verified_at' => now(), 'grant_hash' => hash('sha256', $grant), 'expires_at' => now()->addMinutes(10)]);
            SecurityAudit::record('otp.verified', $user->id);

            return ['id' => $row->id, 'generation' => $row->generation, 'grant' => $grant];
        });
    }

    public function reset(array $state, array $input): void
    {
        validator($input, ['password' => PasswordPolicy::rules()])->validate();
        DB::transaction(function () use ($state, $input) {
            $peek = AccountRecovery::find($state['id'] ?? '');
            abort_unless($peek, 422, 'Recovery expired. Request a new code.');
            $user = User::lockForUpdate()->findOrFail($peek->user_id);
            $row = AccountRecovery::lockForUpdate()->findOrFail($peek->id);
            abort_unless($user->is_active && $row->verified_at && ! $row->consumed_at && $row->expires_at->isFuture()
             && $row->generation === ($state['generation'] ?? null) && $row->grant_hash
             && hash_equals($row->grant_hash, hash('sha256', $state['grant'] ?? '')), 422, 'Recovery expired. Request a new code.');
            $user->password = $input['password'];
            AccountAccess::invalidate($user);
            SecurityAudit::record('password.reset', $user->id);
            SecurityMail::queue('password_changed', (string) $user->id, $user->security_version);
        });
    }
}
