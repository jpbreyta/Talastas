<?php

namespace App\Services;

use App\Jobs\SendAccountSecurityMail;
use App\Mail\AccountSecurityMail;
use App\Models\AccountRecovery;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SecurityMail
{
    public static function available(): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        return config('account-security.mail_enabled') && config('mail.default') === 'smtp'
         && filled(config('mail.mailers.smtp.host')) && filled(config('mail.mailers.smtp.username'))
         && filled(config('mail.mailers.smtp.password')) && filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)
         && (app()->environment('local') || str_starts_with((string) config('app.url'), 'https://'))
         && ! in_array(config('queue.default'), ['sync', 'null'], true);
    }

    public static function requireConfigured(): void
    {
        if (! self::available()) {
            throw ValidationException::withMessages(['mail' => 'System email is not configured. Ask the System Admin to complete sender and queue setup. No email was sent.']);
        }
    }

    public static function queue(string $kind, string $id, int $generation): void
    {
        DB::afterCommit(function () use ($kind, $id, $generation) {
            try {
                SendAccountSecurityMail::dispatch($kind, $id, $generation);
            } catch (\Throwable $exception) {
                if ($kind === 'invitation') {
                    UserInvitation::whereKey($id)->where('generation', $generation)->update(['delivery_status' => 'failed']);
                }
                if ($kind === 'recovery') {
                    AccountRecovery::whereKey($id)->where('generation', $generation)->update(['delivery_status' => 'failed']);
                }
                SecurityAudit::record('mail.queue_failed', null, 'failed', $kind === 'invitation' ? $id : null);
                Log::warning('Account security mail could not be queued.', ['kind' => $kind, 'record_id' => $id]);
            }
        });
    }

    public static function send(string $email, string $kind, array $data = []): void
    {
        self::requireConfigured();
        Mail::to($email)->send(new AccountSecurityMail($kind, $data));
    }
}
