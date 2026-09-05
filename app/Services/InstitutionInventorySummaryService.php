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

        /**
         * A row can now represent a grouped asset, so SUM(quantity) must be
         * used instead of COUNT(*).
         */
        $activeCounts = IctInventory::query()
            ->where('institution_id', $institutionModel->id)
            ->active()
            ->selectRaw(
                "LOWER(category) AS category_key, COALESCE(SUM(quantity), 0) AS total"
            )
            ->groupByRaw('LOWER(category)')
            ->pluck('total', 'category_key');

        $computerCount = collect(IctInventory::COMPUTER_CATEGORIES)
            ->sum(fn (string $category): int => (int) $activeCounts->get($category, 0));

        /**
         * projected_whiteboard is the canonical category.
         * projector is also recognized temporarily so existing development
         * records continue to populate the old institution summary field.
         */
        $projectedWhiteboardCount =
            (int) $activeCounts->get(IctInventory::CATEGORY_PROJECTED_WHITEBOARD, 0)
            + (int) $activeCounts->get(IctInventory::CATEGORY_PROJECTOR, 0);

        $requiresMaintenance = IctInventory::query()
            ->where('institution_id', $institutionModel->id)
            ->active()
            ->where('needs_maintenance', true)
            ->exists();

        $institutionModel->applyInventorySummary([
            'number_of_computers' => $computerCount,

            'has_smart_tv' =>
                (int) $activeCounts->get(IctInventory::CATEGORY_SMART_TV, 0) > 0,

            'has_projected_whiteboard' =>
                $projectedWhiteboardCount > 0,

            'has_public_address_system' =>
                (int) $activeCounts->get(IctInventory::CATEGORY_PA_SYSTEM, 0) > 0,

            'needs_maintenance' => $requiresMaintenance,
        ]);
    }
}
