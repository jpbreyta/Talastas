<?php

namespace App\Observers;

use App\Models\AuditRecord;
use App\Models\Institution;
use Illuminate\Support\Facades\Auth;

class InstitutionObserver
{
    public function created(Institution $institution)
    {
        // Dispatches the analytics update to run asynchronously in the background.
        dispatch(function () use ($institution) {
            $institution->updateAnalytics();
        });
    }

    public function updated(Institution $institution)
    {
        $relevantFields = [
            'internet_speed', 'student_population', 'staff_population', 'number_of_computers',
            'has_computer_lab', 'has_projected_whiteboard', 'has_smart_tv', 'has_public_address_system',
            'needs_maintenance', 'name', 'type', 'barangay_id', 'latitude', 'longitude'
        ];

        if ($institution->wasChanged($relevantFields)) {
            // Dispatches the analytics update to run asynchronously in the background.
            dispatch(function () use ($institution) {
                $institution->updateAnalytics();
            });
        }

        if ($institution->wasChanged('last_audited_at') && $institution->last_audited_at) {
            AuditRecord::create([
                'institution_id' => $institution->id,
                'user_id' => Auth::id() ?? 1,
                'audit_date' => $institution->last_audited_at,
                'validation_status' => 'completed',
            ]);
        }
    }
}