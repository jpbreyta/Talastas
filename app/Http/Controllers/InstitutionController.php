<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\Request;

class InstitutionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $institutions = Institution::paginate(20);

        return view('institutions.index', compact('institutions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('institutions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:School,Hospital,Government,Library,Community Center,Other',
            'sector_type' => 'nullable|string|max:255',
            'barangay_name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'student_population' => 'nullable|integer|min:0',
            'staff_population' => 'nullable|integer|min:0',
            'isp_provider' => 'nullable|string|max:255',
            'internet_speed' => 'nullable|numeric|min:0',
            'has_computer_lab' => 'boolean',
            'number_of_computers' => 'nullable|integer|min:0',
            'has_projected_whiteboard' => 'boolean',
            'has_smart_tv' => 'boolean',
            'has_public_address_system' => 'boolean',
            'needs_maintenance' => 'boolean',
            'is_pilot_site' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        Institution::create($validated);

        return redirect()->route('institutions.index')->with('success', 'Institution created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Institution $institution)
    {
        $institution->load('ictInventory');

        return view('institutions.show', compact('institution'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Institution $institution)
    {
        return view('institutions.edit', compact('institution'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Institution $institution)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:School,Hospital,Government,Library,Community Center,Other',
            'sector_type' => 'nullable|string|max:255',
            'barangay_name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'student_population' => 'nullable|integer|min:0',
            'staff_population' => 'nullable|integer|min:0',
            'isp_provider' => 'nullable|string|max:255',
            'internet_speed' => 'nullable|numeric|min:0',
            'has_computer_lab' => 'boolean',
            'number_of_computers' => 'nullable|integer|min:0',
            'has_projected_whiteboard' => 'boolean',
            'has_smart_tv' => 'boolean',
            'has_public_address_system' => 'boolean',
            'needs_maintenance' => 'boolean',
            'is_pilot_site' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $institution->update($validated);

        return redirect()->route('institutions.index')->with('success', 'Institution updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Institution $institution)
    {
        $institution->delete();

        return redirect()->route('institutions.index')->with('success', 'Institution deleted successfully.');
    }
}
