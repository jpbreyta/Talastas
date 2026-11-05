<?php

namespace App\Jobs;

use App\Models\AccountRecovery;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\RecoveryService;
use App\Services\SecurityAudit;
use App\Services\SecurityMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendAccountSecurityMail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public string $kind, public string $recordId, public int $generation) {}

    public function backoff(): array
    {
        return [15, 45];
    }

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                if ($this->kind === 'invitation') {
                    $row = UserInvitation::lockForUpdate()->find($this->recordId);
                    if (! $row || $row->generation !== $this->generation || $row->status !== 'pending') {
                        return;
                    }
                    if ($row->expires_at->isPast()) {
                        $row->update(['status' => 'expired', 'token_hash' => null]);
                        SecurityAudit::record('invitation.expired', null, 'success', $row->id);

                        return;
                    }
                    $token = bin2hex(random_bytes(32));
                    $row->token_hash = hash('sha256', $token);
                    $row->save();
                    SecurityMail::send($row->email, 'invitation', ['name' => $row->name, 'role' => $row->role, 'expires' => $row->expires_at->toIso8601String(),
                        'url' => route('security.invitation', ['id' => $row->id]).'#token='.$token]);
                    $row->update(['delivery_status' => 'transport_accepted', 'sent_at' => now()]);
                    SecurityAudit::record('invitation.sent', null, 'transport_accepted', $row->id);
                } elseif ($this->kind === 'recovery') {
                    $peek = AccountRecovery::find($this->recordId);
                    if (! $peek) {
                        return;
                    }
                    $user = User::lockForUpdate()->find($peek->user_id);
                    $row = AccountRecovery::lockForUpdate()->find($this->recordId);
                    if (! $user || ! $user->is_active || $row->generation !== $this->generation || $row->attempts >= 5 || $row->verified_at || $row->consumed_at || $row->expires_at->isPast()) {
                        return;
                    }
                    if ($user->isAdmin() && $row->purpose !== 'admin_reset') {
                        return;
                    }
                    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $row->code_hash = RecoveryService::digest($row, $code);
                    $row->save();
                    SecurityMail::send($user->email, $row->purpose === 'admin_reset' ? 'admin_reset' : 'recovery', ['code' => $code, 'url' => route('security.recovery'), 'expires' => $row->expires_at->toIso8601String()]);
                    $row->update(['delivery_status' => 'transport_accepted']);
                    SecurityAudit::record('otp.sent', $user->id, 'transport_accepted');
                } else {
                    $user = User::lockForUpdate()->find($this->recordId);
                    if (! $user || (int) $user->security_version !== $this->generation) {
                        return;
                    }
                    SecurityMail::send($user->email, $this->kind);
                    SecurityAudit::record('notification.sent', $user->id, 'transport_accepted');
                }
            });
        } catch (\Throwable $exception) {
            // Do not serialize provider exception text, message bodies, SMTP credentials or bearer links.
            if ($this->kind === 'invitation') {
                UserInvitation::whereKey($this->recordId)->where('generation', $this->generation)->update(['delivery_status' => 'failed']);
            }
            if ($this->kind === 'recovery') {
                AccountRecovery::whereKey($this->recordId)->where('generation', $this->generation)->update(['delivery_status' => 'failed']);
            }
            SecurityAudit::record('mail.failed', null, 'failed', $this->kind === 'invitation' ? $this->recordId : null);
            Log::warning('Account security mail failed.', ['kind' => $this->kind, 'record_id' => $this->recordId]);
            throw new \RuntimeException('Account security email could not be sent. Check sender configuration and retry.');
        }
    }
}
