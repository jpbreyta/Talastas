<?php

namespace App\Observers;

use App\Jobs\ProcessInstitutionAnalytics;
use App\Models\AuditRecord;
use App\Models\Institution;
use Illuminate\Support\Facades\Auth;

class InstitutionObserver
{
    public function created(Institution $institution): void
    {
        ProcessInstitutionAnalytics::dispatch($institution->id)
            ->afterCommit();
    }

    public function updated(Institution $institution): void
    {
        $analyticsFields = [
            'name',
            'type',
            'student_population',
            'staff_population',
            'internet_speed',
            'number_of_computers',
            'has_computer_lab',
            'has_projected_whiteboard',
            'has_smart_tv',
            'has_public_address_system',
            'needs_maintenance',
        ];

        if ($institution->wasChanged($analyticsFields)) {
            ProcessInstitutionAnalytics::dispatch($institution->id)
                ->afterCommit();
        }

        if (
            $institution->wasChanged('last_audited_at')
            && $institution->last_audited_at !== null
            && Auth::check()
        ) {
            AuditRecord::create([
                'institution_id' => $institution->id,
                'user_id' => Auth::id(),
                'audit_date' => $institution->last_audited_at->toDateString(),
                'validation_status' => 'completed',
            ]);
        }
    }
}