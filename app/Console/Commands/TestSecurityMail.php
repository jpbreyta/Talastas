<?php

namespace App\Console\Commands;

use App\Services\SecurityMail;
use Illuminate\Console\Command;

class TestSecurityMail extends Command
{
    protected $signature = 'security:test-mail {--send : Explicitly send one harmless test message}';

    protected $description = 'Send one nonsecret test message to the configured local/test mailbox';

    public function handle(): int
    {
        if (! app()->environment('local', 'testing') || ! $this->option('send')) {
            $this->error('Only available locally with --send.');

            return self::FAILURE;
        }
        $recipient = config('account-security.mail_test_recipient');
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('Configure SECURITY_MAIL_TEST_RECIPIENT with a mailbox you control.');

            return self::FAILURE;
        }
        try {
            SecurityMail::send($recipient, 'test');
            $this->info('Mail transport accepted the test message. Check the inbox and spam folder; arrival is not confirmed.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Test message failed. Check sender configuration without sharing credentials.');

            return self::FAILURE;
        }
    }
}
