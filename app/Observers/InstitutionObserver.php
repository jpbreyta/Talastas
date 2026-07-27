<?php

namespace App\Observers;

use App\Models\AuditRecord;
use App\Models\Institution;
use Illuminate\Support\Facades\Auth;

class InstitutionObserver
{
    public function updated(Institution $institution): void
    {
        if (
            !$institution->wasChanged('last_audited_at')
            || $institution->last_audited_at === null
            || !Auth::check()
        ) {
            return;
        }

        AuditRecord::create([
            'institution_id' => $institution->id,
            'user_id' => Auth::id(),
            'audit_date' => $institution->last_audited_at->toDateString(),
            'validation_status' => 'completed',
        ]);
    }
}
