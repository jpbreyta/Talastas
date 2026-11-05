<?php

namespace App\Http\Controllers;

use App\Models\IctInventory;
use App\Models\Institution;
use App\Services\InventoryCounts;
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
            ->with([
                'barangay:id,name',
                'ictInventory' => function ($query): void {
                    $query
                        ->whereIn(
                            'component_type',
                            IctInventoryController::COMPONENT_TYPES
                        )
                        ->with('component')
                        ->select([
                            'asset_id',
                            'institution_id',
                            'component_id',
                            'category',
                            'component_type',
                            'asset_name',
                            'total_count',
                            'active_count',
                            'retired_count',
                            'functional_count',
                            'defective_count',
                            'defective_active_count',
                            'functional_count_recorded',
                        ])
                        ->latest('asset_id');
                },
            ])
            ->withSum(
                [
                    'ictInventory as total_assets_count' =>
                        fn ($query) => $query->whereIn(
                            'component_type',
                            IctInventoryController::COMPONENT_TYPES
                        ),
                ],
                'total_count'
            )
            ->withSum(
                [
                    'ictInventory as active_assets_count' =>
                        fn ($query) => $query->whereIn(
                            'component_type',
                            IctInventoryController::COMPONENT_TYPES
                        ),
                ],
                'active_count'
            )
            ->withSum(
                [
                    'ictInventory as attention_assets_count' =>
                        fn ($query) => $query->whereIn(
                            'component_type',
                            IctInventoryController::COMPONENT_TYPES
                        ),
                ],
                'defective_count'
            )
            ->orderBy('name');

        $user = $request->user();

        if ($user->isEditor()) {
            $query->whereHas(
                'assignedUsers',
                fn ($assigned) => $assigned->whereKey($user->id)
            );
        }

        $institutions = $query->paginate(20)->withQueryString();

        $institutions->getCollection()->transform(
            function (Institution $institution): Institution {
                $institution->setAttribute(
                    'total_assets_count',
                    (int) ($institution->total_assets_count ?? 0)
                );
                $institution->setAttribute(
                    'active_assets_count',
                    InventoryCounts::sum($institution->ictInventory, 'active_count')
                );
                $institution->setAttribute(
                    'attention_assets_count',
                    InventoryCounts::sum($institution->ictInventory, 'defective_count')
                );

                return $institution;
            }
        );

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
}
