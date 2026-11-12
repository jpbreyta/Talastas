<?php

namespace App\Services;

use App\Models\IctInventory;
use Illuminate\Support\Collection;

class ConnectivityInventory
{
    public const COMPONENTS = [
        'isp_connection_plan' => ['item_number' => '4.1.1', 'name' => 'ISP Connection Plan'],
        'backup_internet_link' => ['item_number' => '4.1.2', 'name' => 'Backup Internet Link'],
        'public_access_network' => ['item_number' => '4.1.3', 'name' => 'Public Access Network'],
    ];

    public const FIELDS = ['total_count', 'active_count', 'retired_count', 'functional_count', 'defective_count', 'defective_active_count'];

    /** Present questionnaire rows without creating inventory or filling unknown counts. */
    public function rows(iterable $items): Collection
    {
        $remaining = collect($items)->map(function ($item): array {
            if (!$item instanceof IctInventory) {
                return $item;
            }
            return [
                'asset_id' => $item->asset_id, 'component_id' => $item->component_id,
                'asset_name' => $item->asset_name, 'category' => $item->category,
                'subsection' => $item->specifications['inventory_subsection'] ?? 'Connectivity Capacity',
                ...collect(self::FIELDS)->mapWithKeys(fn ($field) => [$field => $item->{$field}])->all(),
            ];
        })->values();
        // A legacy record may also have matched a standard template. Show its asset once.
        $seen = [];
        $remaining = $remaining->filter(function (array $item) use (&$seen): bool {
            $id = (int) ($item['asset_id'] ?? 0);
            if ($id && isset($seen[$id])) return false;
            if ($id) $seen[$id] = true;
            return true;
        });

        $rows = collect();
        foreach (self::COMPONENTS as $key => $definition) {
            $matches = $remaining->filter(fn ($item) => ($item['category'] ?? null) === $key
                || mb_strtolower(trim($item['asset_name'] ?? '')) === mb_strtolower($definition['name']));
            $match = $matches->first(fn ($item) => !empty($item['asset_id'])) ?? $matches->first();
            $row = array_replace(array_fill_keys(self::FIELDS, null), $match ?? []);
            $row['asset_name'] = $row['asset_name'] ?? $definition['name'];
            $row['component_label'] = $definition['name'];
            $row['item_number'] = $definition['item_number'];
            $row['subsection'] = 'Connectivity Capacity';
            $rows->push($row);
            $removedMatch = false;
            $remaining = $remaining->reject(function ($item, $index) use ($matches, $match, &$removedMatch): bool {
                if (!$matches->has($index)) return false;
                if (!$removedMatch && $item === $match) {
                    $removedMatch = true;
                    return true;
                }
                // Discard only unused blank templates, never a different saved record.
                return empty($item['asset_id']) && !$this->hasCounts($item);
            });
        }
        return $rows->concat($remaining->map(fn ($item) => array_replace([
            'item_number' => '', 'component_label' => $item['asset_name'] ?? '', 'subsection' => 'Connectivity Capacity',
        ], $item)))->values();
    }

    public function hasCounts(array $row): bool
    {
        return collect(self::FIELDS)->contains(fn ($field) => isset($row[$field]) && $row[$field] !== '');
    }

    public function subtotals(Collection $rows): array
    {
        $recorded = $rows->filter(fn ($row) => !empty($row['asset_id']) || $this->hasCounts($row))
            ->map(fn ($row) => collect(self::FIELDS)->mapWithKeys(fn ($field) => [
                $field => isset($row[$field]) && preg_match('/^[0-9]+$/D', (string) $row[$field]) && (float) $row[$field] <= 1000000
                    ? (int) $row[$field] : null,
            ])->all());
        return collect(self::FIELDS)->mapWithKeys(fn ($field) => [$field => InventoryCounts::sum($recorded, $field)])->all();
    }
}
