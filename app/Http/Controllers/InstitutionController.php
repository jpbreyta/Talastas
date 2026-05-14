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
        $institutions = Institution::latest()->paginate(20);

        return view('institutions.index', compact('institutions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $barangays = \App\Models\Barangay::orderBy('name')->get();
        return view('institutions.create', compact('barangays'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',

            'type' => 'required|in:School,Barangay Hall,Local Government Office',

            'barangay_id' => 'required|exists:barangays,id',

            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',

            'isp_provider' => 'nullable|string|max:255',
            'internet_speed' => 'nullable|numeric|min:0',

            'has_computer_lab' => 'nullable|boolean',
            'number_of_computers' => 'nullable|integer|min:0',

            'has_projected_whiteboard' => 'nullable|boolean',
            'has_smart_tv' => 'nullable|boolean',
            'has_public_address_system' => 'nullable|boolean',

            'needs_maintenance' => 'nullable|boolean',

            'notes' => 'nullable|string',

            'is_pilot_site' => 'nullable|boolean',
            'data_completeness_score' => 'nullable|integer|min:0|max:100',
            'requires_audit' => 'nullable|boolean',

            'last_audited_at' => 'nullable|date',
            'audited_by' => 'nullable|exists:users,id',
            'audit_notes' => 'nullable|string',

            'is_active' => 'nullable|boolean',
        ];

        // Conditional validation for population fields
        if ($request->type === 'School') {
            $rules['student_population'] = 'required|integer|min:0';
            $rules['staff_population'] = 'nullable|integer|min:0';
        } else {
            $rules['student_population'] = 'nullable|integer|min:0';
            $rules['staff_population'] = 'required|integer|min:0';
        }

        $validated = $request->validate($rules);

        // Handle unchecked checkboxes
        $validated['has_computer_lab'] = $request->boolean('has_computer_lab');
        $validated['has_projected_whiteboard'] = $request->boolean('has_projected_whiteboard');
        $validated['has_smart_tv'] = $request->boolean('has_smart_tv');
        $validated['has_public_address_system'] = $request->boolean('has_public_address_system');
        $validated['needs_maintenance'] = $request->boolean('needs_maintenance');
        $validated['is_pilot_site'] = $request->boolean('is_pilot_site');
        $validated['requires_audit'] = $request->boolean('requires_audit');
        $validated['is_active'] = $request->boolean('is_active');

        Institution::create($validated);

        return redirect()
            ->route('institutions.index')
            ->with('success', 'Institution created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Institution $institution)
    {
        $institution->load([
            'ictInventory',
            'institutionAnalytics',
            'auditor',
        ]);

        return view('institutions.show', compact('institution'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Institution $institution)
    {
        $barangays = \App\Models\Barangay::orderBy('name')->get();
        return view('institutions.edit', compact('institution', 'barangays'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Institution $institution)
    {
        $rules = [
            'name' => 'required|string|max:255',

            'type' => 'required|in:School,Barangay Hall,Local Government Office',

            'barangay_id' => 'required|exists:barangays,id',

            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',

            'isp_provider' => 'nullable|string|max:255',
            'internet_speed' => 'nullable|numeric|min:0',

            'has_computer_lab' => 'nullable|boolean',
            'number_of_computers' => 'nullable|integer|min:0',

            'has_projected_whiteboard' => 'nullable|boolean',
            'has_smart_tv' => 'nullable|boolean',
            'has_public_address_system' => 'nullable|boolean',

            'needs_maintenance' => 'nullable|boolean',

            'notes' => 'nullable|string',

            'is_pilot_site' => 'nullable|boolean',
            'data_completeness_score' => 'nullable|integer|min:0|max:100',
            'requires_audit' => 'nullable|boolean',

            'last_audited_at' => 'nullable|date',
            'audited_by' => 'nullable|exists:users,id',
            'audit_notes' => 'nullable|string',

            'is_active' => 'nullable|boolean',
        ];

        // Conditional validation for population fields
        if ($request->type === 'School') {
            $rules['student_population'] = 'required|integer|min:0';
            $rules['staff_population'] = 'nullable|integer|min:0';
        } else {
            $rules['student_population'] = 'nullable|integer|min:0';
            $rules['staff_population'] = 'required|integer|min:0';
        }

        $validated = $request->validate($rules);

        // Handle unchecked checkboxes
        $validated['has_computer_lab'] = $request->boolean('has_computer_lab');
        $validated['has_projected_whiteboard'] = $request->boolean('has_projected_whiteboard');
        $validated['has_smart_tv'] = $request->boolean('has_smart_tv');
        $validated['has_public_address_system'] = $request->boolean('has_public_address_system');
        $validated['needs_maintenance'] = $request->boolean('needs_maintenance');
        $validated['is_pilot_site'] = $request->boolean('is_pilot_site');
        $validated['requires_audit'] = $request->boolean('requires_audit');
        $validated['is_active'] = $request->boolean('is_active');

        $institution->update($validated);

        return redirect()
            ->route('institutions.index')
            ->with('success', 'Institution updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Institution $institution)
    {
        $institution->delete();

        return redirect()
            ->route('institutions.index')
            ->with('success', 'Institution deleted successfully.');
    }
}