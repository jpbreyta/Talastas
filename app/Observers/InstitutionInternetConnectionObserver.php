<?php

namespace App\Observers;

use App\Models\InstitutionInternetConnection;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class InstitutionInternetConnectionObserver implements ShouldHandleEventsAfterCommit
{
    public function created(InstitutionInternetConnection $connection): void
    {
        IctInventoryObserver::invalidateForInstitution((int) $connection->institution_id);
    }

    public function updated(InstitutionInternetConnection $connection): void
    {
        if (!$connection->wasChanged($connection->getFillable()) && !$connection->wasChanged('institution_id')) {
            return;
        }
        IctInventoryObserver::invalidateForInstitution((int) $connection->institution_id);
        $previousId = (int) ($connection->getPrevious()['institution_id'] ?? $connection->institution_id);
        if ($previousId !== (int) $connection->institution_id) {
            IctInventoryObserver::invalidateForInstitution($previousId);
        }
    }

    public function deleted(InstitutionInternetConnection $connection): void
    {
        IctInventoryObserver::invalidateForInstitution((int) $connection->institution_id);
    }
}
