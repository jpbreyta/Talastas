<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InstitutionController extends Controller
{
    public function index()
    {
        $institutions = Institution::query()
            ->with('barangay:id,name')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('institutions.index', compact('institutions'));
    }

    public function create()
    {
        $barangays = Barangay::orderBy('name')->get();

        return view('institutions.create', compact('barangays'));
    }

    public function store(Request $request)
    {
        /*
         * Creation is intentionally minimal:
         * identity + institution type + mapped location only.
         */
        $validated = $request->validate(
            $this->createRules()
        );

        if (
            !isset($validated['latitude'])
            || !isset($validated['longitude'])
        ) {
            $locations = $this->geocodeLocation(
                $validated['name']
            );

            if (!empty($locations)) {
                $validated = $this->applyGeocodedLocation(
                    $validated,
                    $locations[0]
                );
            }
        }

        $this->ensureCoordinatesExist($validated);

        $validated = $this->normalizeLocationMetadata(
            $validated
        );

        /*
         * Safe defaults for a newly registered institution.
         * These can be reviewed from the edit page afterward.
         */
        $validated['has_computer_lab'] = false;
        $validated['is_pilot_site'] = false;
        $validated['requires_audit'] = true;
        $validated['is_active'] = true;

        if (config('services.rdbma.api_key')) {
            $validated['api_key'] =
                config('services.rdbma.api_key');
        }

        $institution = Institution::create($validated);

        return redirect()
            ->route('institutions.edit', $institution)
            ->with(
                'success',
                'Institution created successfully. Complete the remaining profile information below.'
            );
    }

    public function show(Institution $institution)
    {
        $institution->load([
            'barangay',
            'ictInventory' => function ($query): void {
                $query->latest('asset_id');
            },
            'institutionAnalytics',
            'auditor',
            'auditRecords' => function ($query): void {
                $query->latest('audit_date');
            },
        ]);

        return view(
            'institutions.show',
            compact('institution')
        );
    }

    public function edit(Institution $institution)
    {
        $barangays = Barangay::orderBy('name')->get();

        return view(
            'institutions.edit',
            compact('institution', 'barangays')
        );
    }

    public function update(
        Request $request,
        Institution $institution
    ) {
        if ($request->boolean('notes_only')) {
            return $this->updateNotes(
                $request,
                $institution
            );
        }

        /*
         * Normalize HTML checkbox values before boolean validation.
         */
        $this->normalizeBooleanFields($request);

        $validated = $request->validate(
            $this->updateRules($request)
        );

        /*
         * If an edit form does not submit coordinates, preserve the current
         * location rather than accidentally replacing a valid GIS position.
         */
        if (
            !isset($validated['latitude'])
            || !isset($validated['longitude'])
        ) {
            $validated['latitude'] = $institution->latitude;
            $validated['longitude'] = $institution->longitude;
        }

        $validated = $this->preserveExistingLocationMetadata(
            $validated,
            $request,
            $institution
        );

        $this->ensureCoordinatesExist($validated);

        $validated = $this->normalizeLocationMetadata(
            $validated
        );

        if (config('services.rdbma.api_key')) {
            $validated['api_key'] =
                config('services.rdbma.api_key');
        }

        $institution->update($validated);

        return redirect()
            ->route('institutions.index')
            ->with(
                'success',
                'Institution updated successfully.'
            );
    }

    protected function updateNotes(
        Request $request,
        Institution $institution
    ) {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:5000',
        ]);

        $notes = isset($validated['notes'])
            ? trim((string) $validated['notes'])
            : null;

        if ($notes === '') {
            $notes = null;
        }

        $institution->update([
            'notes' => $notes,
        ]);

        $message = $notes === null
            ? 'Institution notes cleared.'
            : 'Institution notes updated successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'notes' => $institution->notes,
            ]);
        }

        return redirect()
            ->route('institutions.show', $institution)
            ->with('success', $message);
    }

    public function destroy(Institution $institution)
    {
        $institution->delete();

        return response()->json([
            'success' => true,
            'message' => 'Institution deleted successfully.',
        ]);
    }

    public function geocode(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $locations = $this->geocodeLocation(
            $validated['name']
        );

        if (!empty($locations)) {
            return response()->json($locations);
        }

        return response()->json([
            'message' =>
                'No locations found for "'
                . $validated['name']
                . '". Try a shorter or more general name.',
        ], 404);
    }

    protected function createRules(): array
    {
        return [
            'name' =>
                'required|string|max:255',

            'barangay_id' =>
                'required|exists:barangays,id',

            'type' =>
                'required|in:School,Barangay Hall,Local Government Office',

            'latitude' =>
                'nullable|numeric|between:-90,90',

            'longitude' =>
                'nullable|numeric|between:-180,180',

            'location_geojson' =>
                'nullable|json',

            'location_bounding_box' =>
                'nullable|json',

            'nominatim_place_id' =>
                'nullable|integer|min:1',

            'osm_type' =>
                'nullable|string|in:node,way,relation|max:20',

            'osm_id' =>
                'nullable|integer|min:1',
        ];
    }

    protected function updateRules(Request $request): array
    {
        $rules = [
            'name' =>
                'required|string|max:255',

            'barangay_id' =>
                'required|exists:barangays,id',

            'type' =>
                'required|in:School,Barangay Hall,Local Government Office',

            'latitude' =>
                'nullable|numeric|between:-90,90',

            'longitude' =>
                'nullable|numeric|between:-180,180',

            'location_geojson' =>
                'nullable|json',

            'location_bounding_box' =>
                'nullable|json',

            'nominatim_place_id' =>
                'nullable|integer|min:1',

            'osm_type' =>
                'nullable|string|in:node,way,relation|max:20',

            'osm_id' =>
                'nullable|integer|min:1',

            'isp_provider' =>
                'nullable|string|max:255',

            'internet_speed' =>
                'nullable|numeric|min:0|max:1000000',

            'has_computer_lab' =>
                'nullable|boolean',

            'notes' =>
                'nullable|string|max:5000',

            'is_pilot_site' =>
                'nullable|boolean',

            'data_completeness_score' =>
                'nullable|numeric|min:0|max:100',

            'requires_audit' =>
                'nullable|boolean',

            'last_audited_at' =>
                'nullable|date',

            'audited_by' =>
                'nullable|exists:users,id',

            'audit_notes' =>
                'nullable|string|max:5000',

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
     * Only user-managed booleans belong here.
     *
     * Inventory-derived fields such as number_of_computers,
     * has_smart_tv, has_projected_whiteboard,
     * has_public_address_system, and needs_maintenance are deliberately absent.
     */
    protected function booleanFields(): array
    {
        return [
            'has_computer_lab',
            'is_pilot_site',
            'requires_audit',
            'is_active',
        ];
    }

    protected function normalizeBooleanFields(
        Request $request
    ): void {
        foreach ($this->booleanFields() as $field) {
            $request->merge([
                $field => $request->boolean($field),
            ]);
        }
    }

    protected function applyGeocodedLocation(
        array $validated,
        array $location
    ): array {
        $validated['latitude'] = $location['lat'];
        $validated['longitude'] = $location['lng'];

        if (!empty($location['barangay_id'])) {
            $validated['barangay_id'] =
                $location['barangay_id'];
        }

        $validated['location_geojson'] =
            $location['geojson'] ?? null;

        $validated['location_bounding_box'] =
            $location['boundingbox'] ?? null;

        $validated['nominatim_place_id'] =
            $location['place_id'] ?? null;

        $validated['osm_type'] =
            $location['osm_type'] ?? null;

        $validated['osm_id'] =
            $location['osm_id'] ?? null;

        return $validated;
    }

    protected function preserveExistingLocationMetadata(
        array $validated,
        Request $request,
        Institution $institution
    ): array {
        foreach ([
            'location_geojson',
            'location_bounding_box',
            'nominatim_place_id',
            'osm_type',
            'osm_id',
        ] as $field) {
            if (!$request->exists($field)) {
                $validated[$field] =
                    $institution->{$field};
            }
        }

        return $validated;
    }

    protected function ensureCoordinatesExist(
        array $validated
    ): void {
        if (
            !isset($validated['latitude'])
            || !isset($validated['longitude'])
        ) {
            throw ValidationException::withMessages([
                'name' => [
                    'The institution location could not be resolved. '
                    . 'Select a valid geocoded result or provide coordinates.',
                ],
            ]);
        }
    }

    protected function normalizeLocationMetadata(
        array $validated
    ): array {
        $validated['location_geojson'] =
            $this->decodeJsonArray(
                $validated['location_geojson'] ?? null,
                'location_geojson'
            );

        $validated['location_bounding_box'] =
            $this->decodeJsonArray(
                $validated[
                    'location_bounding_box'
                ] ?? null,
                'location_bounding_box'
            );

        $geometry = $validated['location_geojson'];

        if ($geometry !== null) {
            $geometryType =
                $geometry['type'] ?? null;

            $coordinates =
                $geometry['coordinates'] ?? null;

            if (
                !in_array(
                    $geometryType,
                    ['Point', 'Polygon', 'MultiPolygon'],
                    true
                )
                || !is_array($coordinates)
            ) {
                throw ValidationException::withMessages([
                    'location_geojson' => [
                        'The selected location geometry is invalid.',
                    ],
                ]);
            }
        }

        return $validated;
    }

    protected function decodeJsonArray(
        mixed $value,
        string $field
    ): ?array {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        try {
            $decoded = json_decode(
                (string) $value,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            throw ValidationException::withMessages([
                $field => [
                    'The selected location data is invalid.',
                ],
            ]);
        }

        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                $field => [
                    'The selected location data is invalid.',
                ],
            ]);
        }

        return $decoded;
    }

    protected function geocodeLocation(
        string $name
    ): ?array {
        try {
            $query = trim($name);

            $requestParameters = [
                'format' => config(
                    'services.nominatim.output_format',
                    'json'
                ),
                'q' => $query,
                'addressdetails' => 1,
                'countrycodes' => config(
                    'services.nominatim.country_code',
                    'ph'
                ),
                'polygon_geojson' => 1,
                'polygon_threshold' => 0.00001,
                'limit' => 5,
                'email' => config(
                    'services.nominatim.app_email'
                ),
            ];

            $requestParameters = array_filter(
                $requestParameters,
                fn (mixed $value): bool =>
                    $value !== null && $value !== ''
            );

            $response = Http::withHeaders([
                'User-Agent' => config(
                    'services.nominatim.user_agent',
                    'LipaICTMapping/1.0'
                ),
            ])
                ->timeout(10)
                ->get(
                    config(
                        'services.nominatim.base_url',
                        'https://nominatim.openstreetmap.org/search'
                    ),
                    $requestParameters
                );

            if (!$response->successful()) {
                Log::error(
                    'Nominatim returned an error status.',
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'query' => $query,
                    ]
                );

                return null;
            }

            $data = $response->json();

            if (!is_array($data) || empty($data)) {
                Log::info(
                    'Nominatim returned no results.',
                    ['query' => $query]
                );

                return null;
            }

            $outputFormat = config(
                'services.nominatim.output_format',
                'json'
            );

            $results = [];

            foreach ($data as $item) {
                $result = $outputFormat === 'jsonv2'
                    ? ($item['properties'] ?? $item)
                    : $item;

                $latitude =
                    $result['lat']
                    ?? $item['lat']
                    ?? null;

                $longitude =
                    $result['lon']
                    ?? $result['lng']
                    ?? $item['lon']
                    ?? null;

                if (
                    !is_numeric($latitude)
                    || !is_numeric($longitude)
                ) {
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

                $address =
                    $item['address']
                    ?? $result['address']
                    ?? [];

                $geojson =
                    $item['geojson']
                    ?? $result['geojson']
                    ?? null;

                if (is_array($geojson)) {
                    $geometryType =
                        $geojson['type'] ?? null;

                    if (
                        !in_array(
                            $geometryType,
                            ['Point', 'Polygon', 'MultiPolygon'],
                            true
                        )
                    ) {
                        $geojson = null;
                    }
                } else {
                    $geojson = null;
                }

                $displayName =
                    $result['display_name']
                    ?? $item['display_name']
                    ?? '';

                $results[] = [
                    'name' =>
                        $result['name']
                        ?? $item['name']
                        ?? trim(
                            explode(',', $displayName)[0]
                        ),

                    'lat' => round($latitude, 7),
                    'lng' => round($longitude, 7),

                    'display_name' => $displayName,
                    'address' => $address,

                    'barangay' => $barangay?->name,
                    'barangay_id' => $barangay?->id,

                    'city' =>
                        $address['city']
                        ?? $address['town']
                        ?? $address['municipality']
                        ?? '',

                    'province' =>
                        $address['state'] ?? '',

                    'country' =>
                        $address['country'] ?? '',

                    'place_id' =>
                        $result['place_id']
                        ?? $item['place_id']
                        ?? null,

                    'osm_type' =>
                        $result['osm_type']
                        ?? $item['osm_type']
                        ?? null,

                    'osm_id' =>
                        $result['osm_id']
                        ?? $item['osm_id']
                        ?? null,

                    'boundingbox' =>
                        $result['boundingbox']
                        ?? $item['boundingbox']
                        ?? null,

                    'geojson' => $geojson,

                    'geometry_type' =>
                        $geojson['type'] ?? null,

                    'has_boundary' => in_array(
                        $geojson['type'] ?? null,
                        ['Polygon', 'MultiPolygon'],
                        true
                    ),
                ];
            }

            return !empty($results)
                ? $results
                : null;
        } catch (\Throwable $exception) {
            Log::error(
                'Nominatim geocoding failed.',
                [
                    'error' => $exception->getMessage(),
                    'name' => $name,
                ]
            );

            return null;
        }
    }
}
