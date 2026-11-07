<?php

namespace App\Services;

use App\Jobs\SendAccountSecurityMail;
use App\Mail\AccountSecurityMail;
use App\Models\AccountRecovery;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SecurityMail
{
    public const RESEND_ENDPOINT = 'https://api.resend.com/emails';

    public static function available(): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        return config('account-security.mail_enabled')
            && filled(config('services.resend.key'))
            && filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)
            && (app()->environment('local') || str_starts_with((string) config('app.url'), 'https://'))
            && ! in_array(config('queue.default'), ['sync', 'null'], true);
    }

    public static function requireConfigured(): void
    {
        if (! self::available()) {
            throw ValidationException::withMessages(['mail' => 'System email is not configured on the server. Sender and queue setup must be completed before email can be sent, including administrator verification codes. No email was sent.']);
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

        try {
            $mailable = new AccountSecurityMail($kind, $data);
            $subject = $mailable->subjectLine();
            $html = $mailable->render();
            $fromAddress = config('mail.from.address');
            $fromName = config('mail.from.name', 'TALASTAS');

            $payload = [
                'from' => $fromName.' <'.$fromAddress.'>',
                'to' => [$email],
                'subject' => $subject,
                'html' => $html,
            ];

            $replyTo = config('account-security.reply_to');
            if (filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $payload['reply_to'] = $replyTo;
            }

            $response = Http::timeout(30, 10)
                ->withHeaders([
                    'Authorization' => 'Bearer '.config('services.resend.key'),
                    'Content-Type' => 'application/json',
                ])
                ->post(self::RESEND_ENDPOINT, $payload);

            if ($response->failed()) {
                self::logProviderError($kind, $response, null);
                throw new \RuntimeException('Email transport rejected the message.');
            }

            // A 2xx response means Resend accepted the message for transport.
            // It is not proof of inbox delivery.
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            self::logProviderError($kind, null, $exception);
            throw new \RuntimeException('Email transport is temporarily unavailable. Please try again later.');
        } catch (\Illuminate\Http\Client\RequestException $exception) {
            self::logProviderError($kind, $exception->response, $exception);
            throw new \RuntimeException('Email transport rejected the message.');
        } catch (\Throwable $exception) {
            self::logProviderError($kind, null, $exception);
            throw new \RuntimeException('Email could not be sent. Check sender configuration.');
        }
    }

    private static function logProviderError(string $kind, $response, ?\Throwable $exception): void
    {
        $context = ['email_type' => $kind];

        if ($response !== null) {
            $context['http_status'] = $response->status();
            $json = $response->json();
            if (is_array($json) && isset($json['error'])) {
                $context['error_category'] = is_array($json['error']) ? ($json['error']['message'] ?? 'unknown') : $json['error'];
            }
        }

        if ($exception !== null) {
            $context['exception_class'] = get_class($exception);
            $context['exception_message'] = self::sanitizeExceptionMessage($exception->getMessage());
        }

        Log::warning('Resend email delivery failed.', $context);
    }

    private static function sanitizeExceptionMessage(string $message): string
    {
        $sensitive = [
            config('services.resend.key'),
            config('mail.from.address'),
        ];

        foreach ($sensitive as $value) {
            if (is_string($value) && $value !== '') {
                $message = str_replace($value, '[redacted]', $message);
            }
        }

        $message = preg_replace(
            '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',
            '[redacted-email]',
            $message
        ) ?? $message;

        $message = preg_replace('/\b\d{6}\b/', '[redacted-code]', $message);
        $message = Str::limit($message, 300);

        return $message;
    }
}
