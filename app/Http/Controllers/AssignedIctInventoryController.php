<?php

namespace App\Http\Controllers;

use App\Models\IctInventory;
use App\Models\Institution;
use App\Models\InstitutionInternetConnection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;

class AssignedIctInventoryController extends IctInventoryController
{ 
    public function create(Request $request)
    {
        $request->validate(['institution_id' => ['nullable', 'integer', 'min:1']]);
        $user = $request->user();
        $selectedInstitution = null;

        $requestedInstitutionId =
            $request->integer('institution_id')
            ?: (int) old('institution_id');

        if ($requestedInstitutionId > 0) {
            $selectedInstitution = Institution::with(['barangay', 'internetConnections', 'institutionAnalytics'])
                ->whereIn('type', ['School', 'Barangay Hall'])
                ->findOrFail(
                    $requestedInstitutionId
                );

            $this->restoreLegacyInternetConnectionForManagement($selectedInstitution);

            $this->authorizeAssignment(
                $request,
                $selectedInstitution->id
            );
        }

        $institutions = Institution::with('barangay')
            ->whereIn('type', ['School', 'Barangay Hall'])
            ->when(
                $user->isEditor(),
                fn ($query) => $query->whereHas(
                    'assignedUsers',
                    fn ($assigned) =>
                        $assigned->whereKey($user->id)
                )
            )
            ->orderBy('name')
            ->get();

        $sections = [];

        foreach (self::COMPONENT_TYPES as $componentType) {
            $components = $this->componentsForSection(
                $componentType
            );

            $sections[$componentType] = [
                'meta' => $this->sectionMeta($componentType),
                'components' => $components,
                'items' => $this->initialItemsForSection(
                    $componentType,
                    $components,
                    $selectedInstitution
                ),
            ];
        }

        return view('ict-inventory.create', [
            'institutions' => $institutions,
            'selectedInstitution' => $selectedInstitution,
            'sections' => $sections,
        ]);
    }

    private function restoreLegacyInternetConnectionForManagement(Institution $institution): void
    {
        if ($institution->internetConnections->isNotEmpty()
            || $institution->has_internet_connection === false
            || $institution->q10_number_of_connections === 0) {
            return;
        }

        $legacyValues = [
            'isp' => $institution->isp_provider,
            'plan' => $institution->isp_connection_plan,
            'advertised_download_mbps' => $institution->bandwidth_allocation_mbps,
            'download_speed_mbps' => $institution->download_speed_mbps ?? $institution->internet_speed,
            'upload_speed_mbps' => $institution->upload_speed_mbps,
            'latency_ms' => $institution->latency_ms,
            'throughput_mbps' => $institution->throughput_mbps,
        ];

        $hasLegacyValues = collect($legacyValues)->contains(
            fn ($value) => $value !== null && $value !== ''
        );

        if (!$hasLegacyValues) {
            return;
        }

        $connection = new InstitutionInternetConnection([
            ...$legacyValues,
            'connection_number' => 1,
        ]);

        $institution->setRelation('internetConnections', new EloquentCollection([$connection]));
        $institution->has_internet_connection ??= true;
        $institution->q10_number_of_connections ??= 1;
    }

    public function store(Request $request)
    {
        $request->validate(['institution_id' => ['required', 'integer', 'min:1']]);
        $this->authorizeAssignment(
            $request,
            $request->integer('institution_id')
        );

        return parent::store($request);
    }

    public function edit(IctInventory $ict_inventory)
    {
        abort_unless(
            in_array(
                $ict_inventory->component_type,
                self::COMPONENT_TYPES,
                true
            ),
            404
        );

        $request = request();

        $this->authorizeAssignment(
            $request,
            (int) $ict_inventory->institution_id
        );

        $ict_inventory->load([
            'institution',
            'component',
        ]);

        $user = $request->user();

        $institutions = Institution::with('barangay')
            ->whereIn('type', ['School', 'Barangay Hall'])
            ->when(
                $user->isEditor(),
                fn ($query) => $query->whereHas(
                    'assignedUsers',
                    fn ($assigned) =>
                        $assigned->whereKey($user->id)
                )
            )
            ->orderBy('name')
            ->get();

        return view('ict-inventory.edit', [
            'ictInventory' => $ict_inventory,
            'institutions' => $institutions,
            'componentType' => $ict_inventory->component_type,
            'sectionMeta' => $this->sectionMeta(
                $ict_inventory->component_type
            ),
            'components' => $this->componentsForSection(
                $ict_inventory->component_type
            ),
        ]);
    }

    public function update(
        Request $request,
        IctInventory $ict_inventory
    ) {
        abort_unless(
            in_array(
                $ict_inventory->component_type,
                self::COMPONENT_TYPES,
                true
            ),
            404
        );

        $this->authorizeAssignment(
            $request,
            (int) $ict_inventory->institution_id
        );

        $request->validate(['institution_id' => ['nullable', 'integer', 'min:1']]);

        if ($request->filled('institution_id')) {
            $this->authorizeAssignment(
                $request,
                $request->integer('institution_id')
            );
        }

        return parent::update(
            $request,
            $ict_inventory
        );
    }

    private function authorizeAssignment(
        Request $request,
        int $institutionId
    ): void {
        $user = $request->user();

        if (
            $user->isEditor()
            && !$user->canAccessInstitution($institutionId)
        ) {
            abort(
                403,
                'You are not assigned to this institution.'
            );
        }
    }
}
