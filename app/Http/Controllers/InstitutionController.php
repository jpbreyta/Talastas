<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InstitutionController extends Controller
{
    /**
     * Display the institution list.
     */
    public function index()
    {
        $institutions = Institution::latest()->paginate(20);

        return view('institutions.index', compact('institutions'));
    }

    /**
     * Display the institution creation page.
     */
    public function create()
    {
        $barangays = Barangay::orderBy('name')->get();

        return view('institutions.create', compact('barangays'));
    }

    /**
     * Store an institution from the web form.
     */
    public function store(Request $request)
    {
        $request->merge([
            'has_computer_lab' => $request->boolean('has_computer_lab'),
            'has_projected_whiteboard' => $request->boolean('has_projected_whiteboard'),
            'has_smart_tv' => $request->boolean('has_smart_tv'),
            'has_public_address_system' => $request->boolean('has_public_address_system'),
            'needs_maintenance' => $request->boolean('needs_maintenance'),
            'is_pilot_site' => $request->boolean('is_pilot_site'),
            'requires_audit' => $request->boolean('requires_audit'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $validated = Validator::make(
            $request->all(),
            $this->rules($request)
        )->validate();

        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            $locations = $this->geocodeLocation($validated['name']);

            if (!empty($locations)) {
                $validated['latitude'] = $locations[0]['lat'];
                $validated['longitude'] = $locations[0]['lng'];

                if (!empty($locations[0]['barangay_id'])) {
                    $validated['barangay_id'] = $locations[0]['barangay_id'];
                }
            }
        }

        if (config('services.rdbma.api_key')) {
            $validated['api_key'] = config('services.rdbma.api_key');
        }

        Institution::create($validated);

        return redirect()
            ->route('institutions.index')
            ->with('success', 'Institution created successfully.');
    }

    /**
     * Display one institution.
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
     * Display the institution edit page.
     */
    public function edit(Institution $institution)
    {
        $barangays = Barangay::orderBy('name')->get();

        return view('institutions.edit', compact('institution', 'barangays'));
    }

    /**
     * Update an institution from the web form.
     */
    public function update(Request $request, Institution $institution)
    {
        $validated = $request->validate($this->rules($request));
        $validated = $this->applyCheckboxDefaults($validated, $request);

        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            $locations = $this->geocodeLocation($validated['name']);

            if (!empty($locations)) {
                $validated['latitude'] = $locations[0]['lat'];
                $validated['longitude'] = $locations[0]['lng'];

                if (!empty($locations[0]['barangay_id'])) {
                    $validated['barangay_id'] = $locations[0]['barangay_id'];
                }
            }
        }

        if (config('services.rdbma.api_key')) {
            $validated['api_key'] = config('services.rdbma.api_key');
        }

        $institution->update($validated);

        return redirect()
            ->route('institutions.index')
            ->with('success', 'Institution updated successfully.');
    }

    /**
     * Delete an institution from the web interface.
     */
    public function destroy(Institution $institution)
    {
        $institution->delete();

        return response()->json([
            'success' => true,
            'message' => 'Institution deleted successfully.',
        ]);
    }

    /**
     * Search institution locations for the web modal.
     */
    public function geocode(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $locations = $this->geocodeLocation($validated['name']);

        if (!empty($locations)) {
            return response()->json($locations);
        }

        return response()->json([
            'message' => 'No locations found for "' . $validated['name'] . '". Try a shorter or more general name.',
        ], 404);
    }

    /**
     * Return validation rules for web requests.
     */
    protected function rules(Request $request): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'barangay_id' => 'required|exists:barangays,id',
            'type' => 'required|in:School,Barangay Hall,Local Government Office',
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

        if ($request->input('type') === 'School') {
            $rules['student_population'] = 'required|integer|min:0';
            $rules['staff_population'] = 'nullable|integer|min:0';
        } else {
            $rules['student_population'] = 'nullable|integer|min:0';
            $rules['staff_population'] = 'required|integer|min:0';
        }

        return $rules;
    }

    /**
     * Resolve omitted checkbox fields as false.
     */
    protected function applyCheckboxDefaults(array $validated, Request $request): array
    {
        foreach ($this->booleanFields() as $field) {
            $validated[$field] = $request->boolean($field);
        }

        return $validated;
    }

    /**
     * Return institution boolean fields.
     */
    protected function booleanFields(): array
    {
        return [
            'has_computer_lab',
            'has_projected_whiteboard',
            'has_smart_tv',
            'has_public_address_system',
            'needs_maintenance',
            'is_pilot_site',
            'requires_audit',
            'is_active',
        ];
    }

    /**
     * Search Nominatim and match results to a barangay polygon.
     */
    protected function geocodeLocation(string $name): ?array
    {
        try {
            $query = trim($name);

            $response = Http::withHeaders([
                'User-Agent' => config(
                    'services.nominatim.user_agent',
                    'LipaGisMapping/1.0 (jpreytapogi@gmail.com)'
                ),
            ])
                ->timeout(10)
                ->get(
                    config(
                        'services.nominatim.base_url',
                        'https://nominatim.openstreetmap.org/search'
                    ),
                    [
                        'format' => config('services.nominatim.output_format', 'json'),
                        'q' => $query,
                        'addressdetails' => 1,
                        'limit' => 5,
                        'email' => config('services.nominatim.app_email'),
                    ]
                );

            if (!$response->successful()) {
                Log::error('Nominatim returned an error status.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'query' => $query,
                ]);

                return null;
            }

            $data = $response->json();

            if (!is_array($data) || empty($data)) {
                Log::info('Nominatim returned no results.', [
                    'query' => $query,
                ]);

                return null;
            }

            $outputFormat = config('services.nominatim.output_format', 'json');
            $results = [];

            foreach ($data as $item) {
                $result = $outputFormat === 'jsonv2'
                    ? ($item['properties'] ?? $item)
                    : $item;

                $latitude = $result['lat'] ?? $item['lat'] ?? null;
                $longitude = $result['lon']
                    ?? $result['lng']
                    ?? $item['lon']
                    ?? null;

                if (!is_numeric($latitude) || !is_numeric($longitude)) {
                    continue;
                }

                $latitude = (float) $latitude;
                $longitude = (float) $longitude;

                $barangay = DB::table('barangays')
                    ->select('id', 'name')
                    ->whereRaw(
                        'ST_Contains(
                            geom,
                            ST_SetSRID(ST_Point(?, ?), 4326)
                        )',
                        [$longitude, $latitude]
                    )
                    ->first();

                $address = $item['address'] ?? [];

                $results[] = [
                    'lat' => round($latitude, 7),
                    'lng' => round($longitude, 7),
                    'display_name' => $result['display_name']
                        ?? $item['display_name']
                        ?? '',
                    'address' => $address,
                    'barangay' => $barangay?->name,
                    'barangay_id' => $barangay?->id,
                    'city' => $address['city']
                        ?? $address['town']
                        ?? $address['municipality']
                        ?? '',
                    'province' => $address['state'] ?? '',
                    'country' => $address['country'] ?? '',
                ];
            }

            return !empty($results) ? $results : null;
        } catch (\Throwable $exception) {
            Log::error('Nominatim geocoding failed.', [
                'error' => $exception->getMessage(),
                'name' => $name,
            ]);

            return null;
        }
    }
}