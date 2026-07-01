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

            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',

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

        // Auto-geocode location if coordinates not provided
        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            $coordinates = $this->geocodeLocation($validated['name'], $validated['barangay_id']);
            if ($coordinates && is_array($coordinates) && count($coordinates) > 0) {
                $validated['latitude'] = $coordinates[0]['lat'];
                $validated['longitude'] = $coordinates[0]['lng'];
            }
        }

        // Set API key from config for RDBMA integration
        if (config('services.rdbma.api_key')) {
            $validated['api_key'] = config('services.rdbma.api_key');
        }

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

            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',

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

        // Auto-geocode location if coordinates not provided
        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            $coordinates = $this->geocodeLocation($validated['name'], $validated['barangay_id']);
            if ($coordinates) {
                $validated['latitude'] = $coordinates['lat'];
                $validated['longitude'] = $coordinates['lng'];
            }
        }

        // Set API key from config for RDBMA integration
        if (config('services.rdbma.api_key')) {
            $validated['api_key'] = config('services.rdbma.api_key');
        }

        $institution->update($validated);

        return redirect()
            ->route('institutions.index')
            ->with('success', 'Institution updated successfully.');
    }

    /**
     * Geocode location using Nominatim based on institution name and barangay.
     */
    protected function geocodeLocation(string $name, int $barangayId): ?array
    {
        try {
            $barangay = \App\Models\Barangay::find($barangayId);
            $query = urlencode($name . ', ' . ($barangay ? $barangay->name . ', Lipa City, Batangas, Philippines' : 'Lipa City, Batangas, Philippines'));
            
            $response = \Illuminate\Support\Facades\Http::get(config('services.nominatim.base_url'), [
                'format' => config('services.nominatim.output_format', 'json'),
                'addressdetails' => 1,
                'q' => $query,
                'limit' => 5,
                'countrycodes' => config('services.nominatim.country_code', 'ph'),
                'email' => config('services.nominatim.app_email'),
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $results = [];
                
                foreach ($data as $item) {
                    $result = config('services.nominatim.output_format') === 'jsonv2' 
                        ? ($item['properties'] ?? $item) 
                        : $item;
                    
                    $results[] = [
                        'lat' => round($result['lat'] ?? $item['lat'], 7),
                        'lng' => round($result['lon'] ?? $result['lng'] ?? $item['lon'], 7),
                        'display_name' => $result['display_name'] ?? $item['display_name'] ?? '',
                    ];
                }
                
                return $results;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Nominatim geocode failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Geocode location for modal search (AJAX endpoint).
     */
    public function geocode(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'barangay_id' => 'required|exists:barangays,id',
        ]);

        $coordinates = $this->geocodeLocation($request->name, $request->barangay_id);

        if ($coordinates) {
            return response()->json($coordinates);
        }

        return response()->json(['message' => 'Location not found'], 404);
    }

    public function storeApi(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:School,Barangay Hall,Local Government Office',
            'barangay_id' => 'required|exists:barangays,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
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

        if ($request->type === 'School') {
            $rules['student_population'] = 'required|integer|min:0';
            $rules['staff_population'] = 'nullable|integer|min:0';
        } else {
            $rules['student_population'] = 'nullable|integer|min:0';
            $rules['staff_population'] = 'required|integer|min:0';
        }

        $validated = $request->validate($rules);
        $validated['has_computer_lab'] = $request->boolean('has_computer_lab');
        $validated['has_projected_whiteboard'] = $request->boolean('has_projected_whiteboard');
        $validated['has_smart_tv'] = $request->boolean('has_smart_tv');
        $validated['has_public_address_system'] = $request->boolean('has_public_address_system');
        $validated['needs_maintenance'] = $request->boolean('needs_maintenance');
        $validated['is_pilot_site'] = $request->boolean('is_pilot_site');
        $validated['requires_audit'] = $request->boolean('requires_audit');
        $validated['is_active'] = $request->boolean('is_active');

        $institution = Institution::create($validated);
        return response()->json($institution->load('barangay'));
    }

    public function updateApi(Request $request, Institution $institution)
    {
        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:School,Barangay Hall,Local Government Office',
            'barangay_id' => 'sometimes|required|exists:barangays,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
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

        $validated = $request->validate($rules);

        foreach (['has_computer_lab', 'has_projected_whiteboard', 'has_smart_tv',
                  'has_public_address_system', 'needs_maintenance', 'is_pilot_site',
                  'requires_audit', 'is_active'] as $field) {
            if ($request->has($field)) {
                $validated[$field] = $request->boolean($field);
            }
        }

        $institution->update($validated);
        return response()->json($institution->load('barangay'));
    }

    public function destroyApi(Institution $institution)
    {
        $institution->delete();
        return response()->json(['message' => 'Institution deleted successfully.']);
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