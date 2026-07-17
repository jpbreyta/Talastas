<?php

namespace App\Http\Controllers;

use App\Models\IctInventory;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IctInventoryController extends Controller
{
    public function index()
    {
        $inventories = IctInventory::with(['institution.barangay'])
            ->latest('asset_id')
            ->paginate(20);

        return view('ict-inventory.index', compact('inventories'));
    }

    public function create(Request $request)
    {
        $selectedInstitution = null;

        if ($request->filled('institution_id')) {
            $selectedInstitution = Institution::with('barangay')
                ->findOrFail($request->integer('institution_id'));
        }

        $institutions = Institution::with('barangay')
            ->orderBy('name')
            ->get();

        return view(
            'ict-inventory.create',
            compact('institutions', 'selectedInstitution')
        );
    }

    public function store(Request $request)
    {
        $validated = $this->validateInventory($request);

        $inventory = IctInventory::create($validated);

        if ($request->input('return_to') === 'institution') {
            return redirect()
                ->route('institutions.show', $inventory->institution_id)
                ->with('success', 'ICT inventory item added successfully.');
        }

        return redirect()
            ->route('ict-inventory.index')
            ->with('success', 'ICT inventory item added successfully.');
    }

    public function show(IctInventory $ictInventory)
    {
        $ictInventory->load(['institution.barangay']);

        return view('ict-inventory.show', compact('ictInventory'));
    }

    public function edit(IctInventory $ictInventory)
    {
        $ictInventory->load('institution');

        $institutions = Institution::with('barangay')
            ->orderBy('name')
            ->get();

        return view(
            'ict-inventory.edit',
            compact('ictInventory', 'institutions')
        );
    }

    public function update(Request $request, IctInventory $ictInventory)
    {
        $validated = $this->validateInventory($request);

        $ictInventory->update($validated);

        return redirect()
            ->route('ict-inventory.index')
            ->with('success', 'ICT inventory item updated successfully.');
    }

    public function destroy(IctInventory $ictInventory)
    {
        $institutionId = $ictInventory->institution_id;
        $returnTo = request('return_to');

        $ictInventory->delete();

        if ($returnTo === 'institution') {
            return redirect()
                ->route('institutions.show', $institutionId)
                ->with('success', 'ICT inventory item deleted successfully.');
        }

        return redirect()
            ->route('ict-inventory.index')
            ->with('success', 'ICT inventory item deleted successfully.');
    }

    public function storeApi(Request $request)
    {
        $validated = $this->validateInventory($request);

        $inventory = IctInventory::create($validated);

        return response()->json(
            $inventory->load(['institution.barangay']),
            201
        );
    }

    public function updateApi(Request $request, IctInventory $ictInventory)
    {
        $validated = $request->validate([
            'institution_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('institutions', 'id'),
            ],
            'category' => [
                'sometimes',
                'required',
                Rule::in([
                    'computer',
                    'laptop',
                    'printer',
                    'router',
                    'smart_tv',
                    'projector',
                    'pa_system',
                    'other',
                ]),
            ],
            'condition' => [
                'sometimes',
                'required',
                Rule::in([
                    'new',
                    'excellent',
                    'good',
                    'fair',
                    'poor',
                    'needs_repair',
                ]),
            ],
            'acquisition_year' => [
                'sometimes',
                'required',
                'integer',
                'min:1900',
                'max:' . (date('Y') + 1),
            ],
            'status' => [
                'sometimes',
                'required',
                Rule::in(['active', 'under_repair', 'inactive', 'retired']),
            ],
        ]);

        $ictInventory->update($validated);

        return response()->json(
            $ictInventory->fresh()->load(['institution.barangay'])
        );
    }

    public function destroyApi(IctInventory $ictInventory)
    {
        $ictInventory->delete();

        return response()->json([
            'message' => 'ICT inventory item deleted successfully.',
        ]);
    }

    private function validateInventory(Request $request): array
    {
        return $request->validate([
            'institution_id' => [
                'required',
                'integer',
                Rule::exists('institutions', 'id'),
            ],
            'category' => [
                'required',
                Rule::in([
                    'computer',
                    'laptop',
                    'printer',
                    'router',
                    'smart_tv',
                    'projector',
                    'pa_system',
                    'other',
                ]),
            ],
            'condition' => [
                'required',
                Rule::in([
                    'new',
                    'excellent',
                    'good',
                    'fair',
                    'poor',
                    'needs_repair',
                ]),
            ],
            'acquisition_year' => [
                'required',
                'integer',
                'min:1900',
                'max:' . (date('Y') + 1),
            ],
            'status' => [
                'required',
                Rule::in(['active', 'under_repair', 'inactive', 'retired']),
            ],
        ]);
    }
}