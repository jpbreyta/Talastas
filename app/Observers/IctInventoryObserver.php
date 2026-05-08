<?php

namespace App\Observers;

use App\Models\IctInventory;
use App\Models\Institution;

class IctInventoryObserver
{
    public function saved(IctInventory $inventory)
    {
        $this->updateComputerCount($inventory->institution_id);
    }

    public function deleted(IctInventory $inventory)
    {
        $this->updateComputerCount($inventory->institution_id);
    }

    private function updateComputerCount($institutionId)
    {
        $count = IctInventory::where('institution_id', $institutionId)
            ->where('category', 'computer')
            ->where('status', 'active')
            ->count();

        Institution::where('id', $institutionId)->update(['number_of_computers' => $count]);
    }
}
