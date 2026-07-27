<?php

namespace App\Services;

use App\Models\IctInventory;
use App\Models\Institution;

class InstitutionInventorySummaryService
{
    public function sync(int|Institution $institution): void
    {
        $institutionModel = $institution instanceof Institution
            ? $institution
            : Institution::find($institution);

        if (!$institutionModel) {
            return;
        }

        $activeCounts = IctInventory::query()
            ->where('institution_id', $institutionModel->id)
            ->where('status', 'active')
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $computerCount = (int) $activeCounts->get(
            'computer',
            0
        );

        $institutionModel->applyInventorySummary([
            'number_of_computers' => $computerCount,
            'has_computer_lab' => $computerCount > 0,
            'has_smart_tv' =>
                (int) $activeCounts->get('smart_tv', 0) > 0,
            'has_projected_whiteboard' =>
                (int) $activeCounts->get('projector', 0) > 0,
            'has_public_address_system' =>
                (int) $activeCounts->get('pa_system', 0) > 0,
        ]);
    }
}
