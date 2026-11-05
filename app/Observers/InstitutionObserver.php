<?php

namespace App\Observers;

use App\Models\Institution;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class InstitutionObserver implements ShouldHandleEventsAfterCommit
{
    private const ANALYTICS_RELEVANT_FIELDS = [
        'name',
        'type',
        'barangay_id',
        'has_internet_connection',
        'bandwidth_allocation_mbps',
        'download_speed_mbps',
        'is_active',
        'q10_number_of_connections',
    ];

    public function updated(
        Institution $institution
    ): void {
        if (
            !$institution->wasChanged(
                self::ANALYTICS_RELEVANT_FIELDS
            )
        ) {
            return;
        }

        IctInventoryObserver::invalidateForInstitution(
            (int) $institution->id
        );
    }
}
