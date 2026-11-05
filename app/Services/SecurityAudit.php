<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SecurityAudit
{
    public static function record(string $event, ?int $target = null, string $outcome = 'success', ?string $invitation = null): void
    {
        $request = request();
        DB::table('security_events')->insert(['actor_id' => auth()->id(), 'target_id' => $target, 'invitation_id' => $invitation,
            'event' => $event, 'outcome' => $outcome, 'occurred_at' => now(),
            'request_fingerprint' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'))]);
    }
}
