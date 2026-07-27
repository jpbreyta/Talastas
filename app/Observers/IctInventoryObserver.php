<?php

namespace App\Observers;

use App\Models\IctInventory;
use App\Services\InstitutionInventorySummaryService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class IctInventoryObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly InstitutionInventorySummaryService $summaryService
    ) {
    }

    public function created(IctInventory $inventory): void
    {
        $this->summaryService->sync((int) $inventory->institution_id);
    }

    public function updated(IctInventory $inventory): void
    {
        $currentInstitutionId = (int) $inventory->institution_id;
        $originalInstitutionId = (int) $inventory->getOriginal('institution_id');

        $this->summaryService->sync($currentInstitutionId);

        if (
            $originalInstitutionId > 0
            && $originalInstitutionId !== $currentInstitutionId
        ) {
            $this->summaryService->sync($originalInstitutionId);
        }
    }

    public function deleted(IctInventory $inventory): void
    {
        $this->summaryService->sync((int) $inventory->institution_id);
    }

    public function restored(IctInventory $inventory): void
    {
        $this->summaryService->sync((int) $inventory->institution_id);
    }

    public function forceDeleted(IctInventory $inventory): void
    {
        $this->summaryService->sync((int) $inventory->institution_id);
    }
}
