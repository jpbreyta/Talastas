<?php

namespace App\Jobs;

use App\Services\SecurityAudit;
use App\Services\SecurityMail;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendProfileEmailChangedNotice implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $previousEmail, public int $userId) {}

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        SecurityMail::send($this->previousEmail, 'email_changed');
    }

    public function failed(?\Throwable $exception): void
    {
        SecurityAudit::record('profile.email_notice_failed', $this->userId, 'failed');
    }
}
