<?php

namespace App\Http\Controllers;

use App\Models\IctInventory;
use App\Models\Institution;
use Illuminate\Http\Request;

class IctInventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inventories = IctInventory::with('institution')->paginate(20);

        return view('ict-inventory.index', compact('inventories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $institutions = Institution::all();

        return view('ict-inventory.create', compact('institutions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'category' => 'required|string|max:255',
            'condition' => 'required|string|max:255',
            'acquisition_year' => 'required|integer|min:1900|max:'.(date('Y') + 1),
            'status' => 'required|in:active,retired',
        ]);

        IctInventory::create($validated);

        return redirect()->route('ict-inventory.index')->with('success', 'ICT Inventory item created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(IctInventory $ictInventory)
    {
        $ictInventory->load('institution');

        return view('ict-inventory.show', compact('ictInventory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(IctInventory $ictInventory)
    {
        $institutions = Institution::all();

        return view('ict-inventory.edit', compact('ictInventory', 'institutions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IctInventory $ictInventory)
    {
        $validated = $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'category' => 'required|string|max:255',
            'condition' => 'required|string|max:255',
            'acquisition_year' => 'required|integer|min:1900|max:'.(date('Y') + 1),
            'status' => 'required|in:active,retired',
        ]);

        $ictInventory->update($validated);

        return redirect()->route('ict-inventory.index')->with('success', 'ICT Inventory item updated successfully.');
    }

    public function storeApi(Request $request)
    {
        $validated = $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'category' => 'required|string|max:255',
            'condition' => 'required|string|max:255',
            'acquisition_year' => 'required|integer|min:1900|max:'.(date('Y') + 1),
            'status' => 'required|in:active,retired',
        ]);

        $inventory = IctInventory::create($validated);
        return response()->json($inventory->load('institution'));
    }

    public function updateApi(Request $request, IctInventory $ictInventory)
    {
        $validated = $request->validate([
            'institution_id' => 'sometimes|required|exists:institutions,id',
            'category' => 'sometimes|required|string|max:255',
            'condition' => 'sometimes|required|string|max:255',
            'acquisition_year' => 'sometimes|required|integer|min:1900|max:'.(date('Y') + 1),
            'status' => 'sometimes|required|in:active,retired',
        ]);

        $ictInventory->update($validated);
        return response()->json($ictInventory->load('institution'));
    }

    public function destroyApi(IctInventory $ictInventory)
    {
        $ictInventory->delete();
        return response()->json(['message' => 'ICT Inventory item deleted successfully.']);
    }
}
