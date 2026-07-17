<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Barangay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
        $barangays = Barangay::orderBy('name')->get();
        return view('institutions.create', compact('barangays'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Convert checkbox values before validation
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

        $validated = Validator::make($request->all(), $this->rules($request))->validate();

        // Auto-geocode location if coordinates not provided
        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            $coordinates = $this->geocodeLocation($validated['name']);

            if (!empty($coordinates)) {
                $validated['latitude'] = $coordinates[0]['lat'];
                $validated['longitude'] = $coordinates[0]['lng'];
                $validated['barangay_id'] = $coordinates[0]['barangay_id'];
            }
        }

        // Set API key from config
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
        $barangays = Barangay::orderBy('name')->get();
        return view('institutions.edit', compact('institution', 'barangays'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Institution $institution)
    {
        $validated = $request->validate($this->rules($request));
        $validated = $this->applyCheckboxDefaults($validated, $request);

        // Auto-geocode location if coordinates not provided
        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            $coordinates = $this->geocodeLocation($validated['name']);
            if (!empty($coordinates)) {
                // FIX: geocodeLocation() returns an array of results (like store() expects),
                // not a single ['lat' => ..., 'lng' => ...] array. Previously this used
                // $coordinates['lat'] / $coordinates['lng'] directly, which silently saved
                // null coordinates on every edit that didn't set lat/lng manually.
                $validated['latitude'] = $coordinates[0]['lat'];
                $validated['longitude'] = $coordinates[0]['lng'];
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
     * Shared validation rules for store() and update().
     */
    protected function rules(Request $request): array
    {
        $rules = [
            'name' => 'required|string|max:255',

            'barangay_id' =>
            'required|exists:barangays,id',

            'type' =>
            'required|in:School,Barangay Hall,Local Government Office',

            'latitude' =>
            'nullable|numeric|between:-90,90',

            'longitude' =>
            'nullable|numeric|between:-180,180',

            'isp_provider' =>
            'nullable|string|max:255',

            'internet_speed' =>
            'nullable|numeric|min:0',

            'has_computer_lab' =>
            'nullable|boolean',

            'number_of_computers' =>
            'nullable|integer|min:0',

            'has_projected_whiteboard' =>
            'nullable|boolean',

            'has_smart_tv' =>
            'nullable|boolean',

            'has_public_address_system' =>
            'nullable|boolean',

            'needs_maintenance' =>
            'nullable|boolean',

            'notes' =>
            'nullable|string',

            'is_pilot_site' =>
            'nullable|boolean',

            'data_completeness_score' =>
            'nullable|integer|min:0|max:100',

            'requires_audit' =>
            'nullable|boolean',

            'last_audited_at' =>
            'nullable|date',

            'audited_by' =>
            'nullable|exists:users,id',

            'audit_notes' =>
            'nullable|string',

            'is_active' =>
            'nullable|boolean',
        ];

        if ($request->input('type') === 'School') {
            $rules['student_population'] =
                'required|integer|min:0';

            $rules['staff_population'] =
                'nullable|integer|min:0';
        } else {
            $rules['student_population'] =
                'nullable|integer|min:0';

            $rules['staff_population'] =
                'required|integer|min:0';
        }

        return $rules;
    }

    /**
     * Normalize checkbox/boolean fields (unchecked checkboxes are omitted
     * from the request entirely, so we must explicitly resolve them).
     */
    protected function applyCheckboxDefaults(array $validated, Request $request): array
    {
        $booleanFields = [
            'has_computer_lab',
            'has_projected_whiteboard',
            'has_smart_tv',
            'has_public_address_system',
            'needs_maintenance',
            'is_pilot_site',
            'requires_audit',
            'is_active',
        ];

        foreach ($booleanFields as $field) {
            $validated[$field] = $request->boolean($field);
        }

        return $validated;
    }

    /**
     * Geocode location using Nominatim based on institution name and barangay.
     *
     * @return array<int, array{lat: float, lng: float, display_name: string}>|null
     */
    protected function geocodeLocation(string $name): ?array
    {
        try {
            // Note: Nominatim usually prefers un-encoded strings for the 'q' parameter
            // when using an HTTP client because the client URL-encodes it automatically.
            $query = trim($name);

            $response = Http::withHeaders([
                // REQUIRED: Nominatim blocks requests without a custom User-Agent
                'User-Agent' => config('services.nominatim.user_agent', 'LipaGisMapping/1.0 (jpreytapogi@gmail.com)'),
            ])
                ->timeout(10)
                ->get(config('services.nominatim.base_url', 'https://nominatim.openstreetmap.org/search'), [
                    'format' => config('services.nominatim.output_format', 'json'),
                    'q' => $query,
                    'addressdetails' => 1,
                    'limit' => 5,
                    'email' => config('services.nominatim.app_email'),
                ]);

            if (!$response->successful()) {
                Log::error('Nominatim returned error status', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'query' => $query,
                ]);
                return null;
            }

            $data = $response->json();

            if (!is_array($data) || empty($data)) {
                Log::info('Nominatim returned no results', ['query' => $query]);
                return null;
            }

            $outputFormat = config('services.nominatim.output_format', 'json');

            $results = [];

            foreach ($data as $item) {
                $result = $outputFormat === 'jsonv2'
                    ? ($item['properties'] ?? $item)
                    : $item;

                $lat = $result['lat'] ?? $item['lat'] ?? null;
                $lng = $result['lon'] ?? $result['lng'] ?? $item['lon'] ?? null;

                // Skip malformed entries instead of throwing on round(null, 7)
                if (!is_numeric($lat) || !is_numeric($lng)) {
                    continue;
                }

                $lat = (float) $lat;
                $lng = (float) $lng;

                $barangay = DB::table('barangays')
                    ->select('id', 'name')
                    ->whereRaw(
                        "ST_Contains(
                            geom,
                            ST_SetSRID(ST_Point(?, ?), 4326)
                        )",
                        [$lng, $lat] // longitude first, latitude second
                    )
                    ->first();

                $results[] = [
                    'lat' => round((float) $lat, 7),
                    'lng' => round((float) $lng, 7),

                    'display_name' => $result['display_name'] ?? $item['display_name'] ?? '',

                    'address' => $item['address'] ?? [],

                    'barangay' => $barangay?->name,
                    'barangay_id' => $barangay?->id,

                    'city' =>
                    $item['address']['city']
                        ?? $item['address']['town']
                        ?? $item['address']['municipality']
                        ?? '',

                    'province' =>
                    $item['address']['state']
                        ?? '',

                    'country' =>
                    $item['address']['country']
                        ?? '',
                ];
            }

            return !empty($results) ? $results : null;
        } catch (\Exception $e) {
            Log::error('Nominatim geocode failed', [
                'error' => $e->getMessage(),
                'name' => $name,
            ]);
        }

        return null;
    }

    /**
     * Geocode location for modal search (AJAX endpoint).
     */
    public function geocode(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $coordinates = $this->geocodeLocation(
            $validated['name']
        );

        if (!empty($coordinates)) {
            return response()->json($coordinates);
        }

        return response()->json([
            'message' => 'No locations found for "' . $validated['name'] . '". Try a shorter or more general name.',
        ], 404);
    }


    public function storeApi(Request $request)
    {
        /*
     * Flutter currently sends the barangay name using the "barangay" field.
     * Convert that name into the barangay ID required by the database.
     */
        if (
            !$request->filled('barangay_id') &&
            $request->filled('barangay')
        ) {
            $barangayName = trim((string) $request->input('barangay'));

            $barangay = Barangay::query()
                ->whereRaw(
                    'LOWER(name) = ?',
                    [mb_strtolower($barangayName)]
                )
                ->first();

            if (!$barangay) {
                return response()->json([
                    'message' => 'The selected barangay was not found.',
                    'errors' => [
                        'barangay' => [
                            'The barangay "' . $barangayName .
                                '" does not exist in the barangays table.',
                        ],
                    ],
                ], 422);
            }

            $request->merge([
                'barangay_id' => $barangay->id,
            ]);
        }

        /*
     * Newly created institutions should be active unless the application
     * explicitly sends another value.
     */
        if (!$request->has('is_active')) {
            $request->merge([
                'is_active' => true,
            ]);
        }

        /*
     * Validate the request after resolving the barangay ID.
     */
        $validated = $request->validate(
            $this->rules($request)
        );

        $validated = $this->applyCheckboxDefaults(
            $validated,
            $request
        );

        /*
     * Use Nominatim when Flutter did not provide coordinates.
     */
        if (
            empty($validated['latitude']) ||
            empty($validated['longitude'])
        ) {
            $barangay = Barangay::find(
                $validated['barangay_id']
            );

            $searchQuery = implode(', ', array_filter([
                $validated['name'],
                $barangay?->name,
                'Lipa City',
                'Batangas',
                'Philippines',
            ]));

            $locations = $this->geocodeLocation(
                $searchQuery
            );

            if (empty($locations)) {
                return response()->json([
                    'message' =>
                    'The institution was not saved because its location could not be found.',
                    'errors' => [
                        'location' => [
                            'No Nominatim result was found for "' .
                                $searchQuery . '".',
                        ],
                    ],
                ], 422);
            }

            $validated['latitude'] =
                $locations[0]['lat'];

            $validated['longitude'] =
                $locations[0]['lng'];
        }

        if (config('services.rdbma.api_key')) {
            $validated['api_key'] =
                config('services.rdbma.api_key');
        }

        $institution = Institution::create(
            $validated
        );

        return response()->json([
            'message' => 'Institution created successfully.',
            'data' => $institution->load('barangay'),
        ], 201);
    }

    public function updateApi(Request $request, Institution $institution)
    {
        $rules = $this->rules($request);
        // API update supports partial updates
        foreach (['name', 'type'] as $field) {
            $rules[$field] = 'sometimes|' . $rules[$field];
        }

        if (isset($rules['student_population'])) {
            $rules['student_population'] = 'sometimes|' . $rules['student_population'];
        }

        if (isset($rules['staff_population'])) {
            $rules['staff_population'] = 'sometimes|' . $rules['staff_population'];
        }

        $validated = $request->validate($rules);

        $booleanFields = [
            'has_computer_lab',
            'has_projected_whiteboard',
            'has_smart_tv',
            'has_public_address_system',
            'needs_maintenance',
            'is_pilot_site',
            'requires_audit',
            'is_active',
        ];

        foreach ($booleanFields as $field) {
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
        return response()->json([
            'success' => true,
            'message' => 'Institution deleted successfully.'
        ]);
    }

    public function indexApi()
    {
        return response()->json([
            'success' => true,
            'data' => Institution::with('barangay')->get(),
        ]);
    }

    public function showApi(Institution $institution)
    {
        return response()->json([
            'success' => true,
            'data' => $institution->load([
                'barangay',
                'ictInventory',
                'institutionAnalytics',
            ]),
        ]);
    }
}
