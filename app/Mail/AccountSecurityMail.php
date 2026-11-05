<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AccountSecurityMail extends Mailable
{
    public function __construct(public string $kind, public array $details = []) {}

    public function build(): static
    {
        $titles = ['verification' => 'Your TALASTAS verification code', 'invitation' => 'Your TALASTAS invitation', 'recovery' => 'Your password recovery code', 'admin_reset' => 'A System Admin requested your password reset',
            'password_changed' => 'Your password was changed', 'deactivated' => 'Your account was deactivated', 'activated' => 'Your TALASTAS account is ready', 'role_changed' => 'Your TALASTAS access was updated', 'test' => 'TALASTAS email delivery test'];
        $this->from(config('mail.from.address'), 'TALASTAS ICT Mapping');
        if (filter_var(config('account-security.reply_to'), FILTER_VALIDATE_EMAIL)) {
            $this->replyTo(config('account-security.reply_to'));
        }

        return $this->subject($titles[$this->kind] ?? 'TALASTAS account security')->view('emails.account-security', ['title' => $titles[$this->kind] ?? 'Account security']);
    }
}
