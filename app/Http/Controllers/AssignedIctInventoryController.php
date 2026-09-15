<?php

namespace App\Http\Controllers;

use App\Models\IctInventory;
use App\Models\Institution;
use Illuminate\Http\Request;

class AssignedIctInventoryController extends IctInventoryController
{
    public function create(Request $request)
    {
        $user = $request->user();
        $selectedInstitution = null;

        if ($request->filled('institution_id')) {
            $selectedInstitution = Institution::with('barangay')
                ->findOrFail($request->integer('institution_id'));

            $this->authorizeAssignment($request, $selectedInstitution->id);
        }

        $institutions = Institution::with('barangay')
            ->when(
                $user->isEditor(),
                fn ($query) => $query->whereHas(
                    'assignedUsers',
                    fn ($assigned) => $assigned->whereKey($user->id)
                )
            )
            ->orderBy('name')
            ->get();

        return view('ict-inventory.create', [
            'institutions' => $institutions,
            'selectedInstitution' => $selectedInstitution,
            'inventoryConfig' => config('ict_inventory'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAssignment(
            $request,
            $request->integer('institution_id')
        );

        return parent::store($request);
    }

    public function edit(IctInventory $ict_inventory)
    {
        $request = request();

        $this->authorizeAssignment(
            $request,
            (int) $ict_inventory->institution_id
        );

        $ict_inventory->load('institution');

        $user = $request->user();

        $institutions = Institution::with('barangay')
            ->when(
                $user->isEditor(),
                fn ($query) => $query->whereHas(
                    'assignedUsers',
                    fn ($assigned) => $assigned->whereKey($user->id)
                )
            )
            ->orderBy('name')
            ->get();

        return view('ict-inventory.edit', [
            'ictInventory' => $ict_inventory,
            'institutions' => $institutions,
            'inventoryConfig' => config('ict_inventory'),
        ]);
    }

    public function update(Request $request, IctInventory $ict_inventory)
    {
        $this->authorizeAssignment(
            $request,
            (int) $ict_inventory->institution_id
        );

        if ($request->filled('institution_id')) {
            $this->authorizeAssignment(
                $request,
                $request->integer('institution_id')
            );
        }

        return parent::update($request, $ict_inventory);
    }

    private function authorizeAssignment(Request $request, int $institutionId): void
    {
        $user = $request->user();

        if ($user->isEditor() && ! $user->canAccessInstitution($institutionId)) {
            abort(403, 'You are not assigned to this institution.');
        }
    }
}
