<?php

namespace App\Services;

use App\Models\IctInventory;
use Illuminate\Database\Eloquent\Builder;

class InventoryListOrdering
{
    public const OPTIONS = ['name', 'latest', 'most_complete'];

    public function apply(Builder $query, string $sort, array $types): Builder
    {
        $rows = IctInventory::query()->whereColumn('ict_inventory.institution_id', 'institutions.id')
            ->whereIn('component_type', $types);
        $validTotal = 'total_count IS NOT NULL AND total_count >= 0';
        $terms = ["CASE WHEN {$validTotal} THEN 1 ELSE 0 END"];
        foreach (InventoryCounts::AVAILABILITY_FIELDS as $field) {
            $recorded = $field === 'functional_count' ? ' AND functional_count_recorded = true' : '';
            $terms[] = "CASE WHEN {$validTotal} AND {$field} IS NOT NULL AND {$field} >= 0 AND {$field} <= total_count{$recorded} THEN 1 ELSE 0 END";
        }
        // A coverage percentage, never an assessment score. Every row stays in the denominator.
        $coverage = 'COALESCE(100.0 * SUM('.implode(' + ', $terms).') / NULLIF('.(1 + count(InventoryCounts::AVAILABILITY_FIELDS)).' * COUNT(*), 0), 0)';
        $query->addSelect([
            'inventory_input_coverage' => (clone $rows)->selectRaw($coverage),
            'latest_inventory_update' => (clone $rows)->selectRaw('MAX(COALESCE(updated_at, created_at))'),
        ]);
        if ($sort === 'most_complete') {
            $query->orderByDesc('inventory_input_coverage');
        }
        if (in_array($sort, ['latest', 'most_complete'], true)) {
            // Explicit NULL ordering works for both PostgreSQL and SQLite.
            $query->orderByRaw('CASE WHEN ('.(clone $rows)->selectRaw('MAX(COALESCE(updated_at, created_at))')->toSql().') IS NULL THEN 1 ELSE 0 END', $rows->getBindings())
                ->orderByDesc('latest_inventory_update');
        }
        return $query->orderBy('name')->orderBy('institutions.id');
    }
}
