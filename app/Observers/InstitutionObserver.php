<?php

namespace App\Observers;

use App\Models\AuditRecord;
use App\Models\Institution;
use Illuminate\Support\Facades\Auth;

class InstitutionObserver
{
    public function updated(Institution $institution)
    {
        if ($institution->wasChanged('last_audited_at') && $institution->last_audited_at) {
            AuditRecord::create([
                'institution_id' => $institution->id,
                'user_id' => Auth::id() ?? 1, // Default to admin if no user
                'audit_date' => $institution->last_audited_at,
                'validation_status' => 'completed', // Or derive from other fields
            ]);
        }
    }
}
