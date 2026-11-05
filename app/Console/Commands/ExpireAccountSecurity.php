<?php

namespace App\Console\Commands;

use App\Models\AccountRecovery;
use App\Models\UserInvitation;
use App\Services\SecurityAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireAccountSecurity extends Command
{
    protected $signature = 'security:expire';

    protected $description = 'Expire pending invitations and erase expired recovery authorization';

    public function handle(): int
    {
        UserInvitation::where('status', 'pending')->where('expires_at', '<=', now())->select('id')->chunkById(100, function ($rows) {
            foreach ($rows as $item) {
                DB::transaction(function () use ($item) {
                    $row = UserInvitation::lockForUpdate()->find($item->id);
                    if ($row && $row->status === 'pending' && $row->expires_at->isPast()) {
                        $row->update(['status' => 'expired', 'token_hash' => null, 'generation' => $row->generation + 1]);
                        SecurityAudit::record('invitation.expired', null, 'success', $row->id);
                    }
                });
            }
        });
        AccountRecovery::where('expires_at', '<=', now())->update(['code_hash' => null, 'grant_hash' => null, 'consumed_at' => now()]);
        $this->info('Expired authorization cleared.');

        return self::SUCCESS;
    }
}
