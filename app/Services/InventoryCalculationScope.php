<?php

namespace App\Services;

use App\Models\IctInventory;

class InventoryCalculationScope
{
    public function includes(IctInventory $item, bool $includeSoftware = true): bool
    {
        if ($item->component_type === 'software') {
            return $includeSoftware;
        }
        if (! in_array($item->component_type, ['hardware', 'network'], true)) {
            return false;
        }

        return $item->component ? (bool) $item->component->counts_toward_infrastructure : true;
    }

    public function describe(bool $includeSoftware): array
    {
        return [
            'include_software' => $includeSoftware,
            'inventory_types' => $includeSoftware ? ['hardware', 'software', 'network'] : ['hardware', 'network'],
            'label' => $includeSoftware ? 'Hardware + Software + Network' : 'Hardware + Network',
        ];
    }
}
