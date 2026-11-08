<?php

namespace App\Http\Controllers;

use App\Models\IctInventory;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PortalIctInventoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['all', 'active', 'attention', 'empty'])],
        ]);
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', 'all');

        $query = Institution::query()
            ->whereIn('type', ['School', 'Barangay Hall'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search)).'%';
                $query->where(function (Builder $matches) use ($pattern): void {
                    $matches->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("LOWER(type) LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereHas('barangay', fn (Builder $barangay) =>
                            $barangay->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$pattern]))
                        ->orWhereHas('ictInventory', function (Builder $inventory) use ($pattern): void {
                            $inventory->whereIn('component_type', IctInventoryController::COMPONENT_TYPES)
                                ->where(fn (Builder $items) => $items
                                    ->whereRaw("LOWER(asset_name) LIKE ? ESCAPE '!'", [$pattern])
                                    ->orWhereRaw("LOWER(component_type) LIKE ? ESCAPE '!'", [$pattern]));
                        });
                });
            })
            ->when(in_array($status, ['active', 'attention', 'empty'], true), function (Builder $query) use ($status): void {
                $column = match ($status) {
                    'active' => 'active_count',
                    'attention' => 'defective_count',
                    default => 'total_count',
                };
                $inventory = fn (Builder $items) => $items
                    ->whereIn('component_type', IctInventoryController::COMPONENT_TYPES)->where($column, '>', 0);
                $status === 'empty'
                    ? $query->whereDoesntHave('ictInventory', $inventory)
                    : $query->whereHas('ictInventory', $inventory);
            })
            ->select(['id', 'name', 'type', 'barangay_id'])
            ->with('barangay:id,name')
            ->orderBy('name');

        $user = $request->user();

        if ($user->isEditor()) {
            $query->whereHas(
                'assignedUsers',
                fn ($assigned) => $assigned->whereKey($user->id)
            );
        }

        $institutions = $query->paginate(20)->withQueryString();

        $institutionIds = $institutions->getCollection()->modelKeys();
        $summaries = collect();

        if ($institutionIds !== []) {
            $summaries = IctInventory::query()
                ->whereIn('institution_id', $institutionIds)
                ->whereIn('component_type', IctInventoryController::COMPONENT_TYPES)
                ->select('institution_id')
                ->selectRaw('COALESCE(SUM(total_count), 0) AS total_assets_count')
                ->selectRaw('CASE WHEN COUNT(*) > 0 AND COUNT(active_count) = COUNT(*) AND MIN(active_count) >= 0 THEN SUM(active_count) ELSE NULL END AS active_assets_count')
                ->selectRaw('CASE WHEN COUNT(*) > 0 AND COUNT(retired_count) = COUNT(*) AND MIN(retired_count) >= 0 THEN SUM(retired_count) ELSE NULL END AS retired_assets_count')
                ->selectRaw('CASE WHEN COUNT(*) > 0 AND COUNT(defective_count) = COUNT(*) AND MIN(defective_count) >= 0 THEN SUM(defective_count) ELSE NULL END AS attention_assets_count')
                ->selectRaw('CASE WHEN COUNT(*) > 0 AND COUNT(CASE WHEN functional_count_recorded = true AND functional_count IS NOT NULL THEN 1 END) = COUNT(*) THEN SUM(functional_count) ELSE NULL END AS functional_assets_count')
                ->groupBy('institution_id')
                ->get()
                ->keyBy('institution_id');
        }

        $institutions->getCollection()->each(function (Institution $institution) use ($summaries): void {
            $summary = $summaries->get($institution->id);
            $institution->setAttribute('total_assets_count', (int) ($summary->total_assets_count ?? 0));
            $institution->setAttribute('active_assets_count', $summary?->active_assets_count);
            $institution->setAttribute('retired_assets_count', $summary?->retired_assets_count);
            $institution->setAttribute('functional_assets_count', $summary?->functional_assets_count);
            $institution->setAttribute('attention_assets_count', $summary?->attention_assets_count);
        });

        return view($request->header('X-Talastas-Fragment') === 'inventory'
            ? 'ict-inventory._table-results' : 'ict-inventory.index', [
            'institutions' => $institutions,
        ]);
    }

    public function show(Request $request, IctInventory $ict_inventory)
    {
        abort_unless(
            in_array(
                $ict_inventory->component_type,
                IctInventoryController::COMPONENT_TYPES,
                true
            ),
            404
        );

        if (
            $request->user()->isEditor()
            && !$request->user()->canAccessInstitution(
                (int) $ict_inventory->institution_id
            )
        ) {
            abort(403, 'You are not assigned to this institution.');
        }

        $ict_inventory->load([
            'institution.barangay',
            'component',
        ]);

        return view('ict-inventory.show', [
            'ictInventory' => $ict_inventory,
        ]);
    }

    public function institutionDetails(Request $request, Institution $institution)
    {
        abort_unless(
            in_array($institution->type, ['School', 'Barangay Hall'], true),
            404
        );

        $user = $request->user();
        if ($user->isEditor() && !$user->canAccessInstitution((int) $institution->id)) {
            abort(403, 'You are not assigned to this institution.');
        }

        $inventory = $institution->ictInventory()
            ->whereIn('component_type', IctInventoryController::COMPONENT_TYPES)
            ->select([
                'asset_id',
                'institution_id',
                'component_type',
                'asset_name',
                'total_count',
                'active_count',
                'retired_count',
                'functional_count',
                'functional_count_recorded',
                'defective_count',
                'defective_active_count',
            ])
            ->latest('asset_id')
            ->get();

        return view('ict-inventory._institution-details', compact('inventory'));
    }
}
