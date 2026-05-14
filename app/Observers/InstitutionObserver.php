<?php

namespace App\Observers;

use App\Models\AuditRecord;
use App\Models\Institution;
use Illuminate\Support\Facades\Auth;

class InstitutionObserver
{
    public function created(Institution $institution)
    {
        // Update analytics when new institution is created
        $institution->updateAnalytics();
    }

    public function updated(Institution $institution)
    {
        // Update analytics when institution data changes
        $relevantFields = [
            'internet_speed', 'student_population', 'staff_population', 'number_of_computers',
            'has_computer_lab', 'has_projected_whiteboard', 'has_smart_tv', 'has_public_address_system',
            'needs_maintenance', 'name', 'type', 'barangay_name', 'latitude', 'longitude'
        ];

        if ($institution->wasChanged($relevantFields)) {
            $institution->updateAnalytics();
        }

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
