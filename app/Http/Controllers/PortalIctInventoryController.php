<?php

namespace App\Http\Controllers;

use App\Models\IctInventory;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PortalIctInventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Institution::query()
            ->with([
                'barangay:id,name',
                'ictInventory' => function ($query): void {
                    $query->latest('asset_id');
                },
            ])
            ->withSum('ictInventory as total_assets_count', 'quantity')
            ->withSum([
                'ictInventory as active_assets_count' => function (Builder $query): void {
                    $query->whereRaw('LOWER(status) = ?', [IctInventory::STATUS_ACTIVE]);
                },
            ], 'quantity')
            ->withSum([
                'ictInventory as attention_assets_count' => function (Builder $query): void {
                    $query->where(function (Builder $query): void {
                        $query
                            ->whereIn('status', ['under_repair', 'inactive'])
                            ->orWhereIn('condition', ['poor', 'needs_repair'])
                            ->orWhere('needs_maintenance', true);
                    });
                },
            ], 'quantity')
            ->orderBy('name');

        $user = $request->user();

        if ($user->isEditor()) {
            $query->whereHas(
                'assignedUsers',
                fn ($assigned) => $assigned->whereKey($user->id)
            );
        }

        $institutions = $query
            ->paginate(20)
            ->withQueryString();

        $institutions->getCollection()->transform(
            function (Institution $institution): Institution {
                $institution->setAttribute(
                    'total_assets_count',
                    (int) ($institution->total_assets_count ?? 0)
                );
                $institution->setAttribute(
                    'active_assets_count',
                    (int) ($institution->active_assets_count ?? 0)
                );
                $institution->setAttribute(
                    'attention_assets_count',
                    (int) ($institution->attention_assets_count ?? 0)
                );

                return $institution;
            }
        );

        return view('ict-inventory.index', [
            'institutions' => $institutions,
            'inventoryConfig' => config('ict_inventory'),
        ]);
    }

    public function show(Request $request, IctInventory $ict_inventory)
    {
        if (
            $request->user()->isEditor()
            && ! $request->user()->canAccessInstitution((int) $ict_inventory->institution_id)
        ) {
            abort(403, 'You are not assigned to this institution.');
        }

        $ict_inventory->load(['institution.barangay']);

        return view('ict-inventory.show', [
            'ictInventory' => $ict_inventory,
            'inventoryConfig' => config('ict_inventory'),
        ]);
    }
}
