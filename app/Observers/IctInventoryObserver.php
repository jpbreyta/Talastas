<?php

namespace App\Observers;

use App\Jobs\ProcessInstitutionAnalytics;
use App\Models\IctInventory;
use App\Models\Institution;

class IctInventoryObserver
{
    public function created(IctInventory $inventory): void
    {
        $this->refreshInstitutionAnalytics($inventory);
    }

    public function updated(IctInventory $inventory): void
    {
        $this->refreshInstitutionAnalytics($inventory);
    }

    public function deleted(IctInventory $inventory): void
    {
        $this->refreshInstitutionAnalytics($inventory);
    }

    private function refreshInstitutionAnalytics(
        IctInventory $inventory
    ): void {
        $institutionId = (int) $inventory->institution_id;

        $institution = Institution::find($institutionId);

        if (!$institution) {
            return;
        }

        $computerCount = IctInventory::query()
            ->where('institution_id', $institutionId)
            ->whereIn('category', [
                'computer',
                'desktop',
                'laptop',
            ])
            ->whereIn('status', [
                'active',
                'operational',
                'working',
                'in use',
            ])
            ->count();

        $institution->updateQuietly([
            'number_of_computers' => $computerCount,
        ]);

        ProcessInstitutionAnalytics::dispatch(
            $institutionId
        )->afterCommit();
    }
}