<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
        return redirect()
            ->route('institutions.index')
            ->with('open_institution_modal', true);
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

        $this->validateCoordinateBarangayMatch(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            (int) $validated['barangay_id']
        );

        $this->validateBoundaryContainsInstitutionPoint(
            $validated
        );

        $validated['is_active'] = true;

        if (config('services.rdbma.api_key')) {
            $validated['api_key'] =
                config('services.rdbma.api_key');
        }

        $validated = $this->prepareLocationCapture($request, $validated);
        if (!empty($validated['osm_type']) && !empty($validated['osm_id'])
            && Institution::where('osm_type', $validated['osm_type'])->where('osm_id', $validated['osm_id'])->exists()) {
            throw ValidationException::withMessages([
                'name' => 'This institution already exists in the system. Choose another institution or edit the existing record.',
            ]);
        }
        $institution = Institution::create($validated);

        $this->syncPostgisBoundaryGeometry($institution);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => route('institutions.index')], 201);
        }

        return redirect()
            ->route('institutions.index')
            ->with(
                'success',
                'Institution created successfully.'
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

        $this->validateCoordinateBarangayMatch(
            (float) $validated['latitude'], (float) $validated['longitude'], (int) $validated['barangay_id']
        );
        $this->validateBoundaryContainsInstitutionPoint($validated);

        $validated = $this->prepareLocationCapture($request, $validated, $institution);
        $institution->update($validated);

        $this->syncPostgisBoundaryGeometry($institution);

        return redirect()
            ->route('institutions.index')
            ->with(
                'success',
                'Institution updated successfully.'
            );
    }

    public function destroy(Institution $institution)
    {
        $institution->delete();

        return response()->json([
            'success' => true,
            'message' => 'Institution deleted successfully.',
        ]);
    }

    public function destroyBatch(Request $request)
    {
        abort_unless($request->user()?->canManageInstitutions(), 403);

        $validated = $request->validate([
            'institution_ids' => ['required', 'array', 'min:1', 'max:100'],
            'institution_ids.*' => ['bail', 'required', 'integer', 'min:1', 'distinct', 'exists:institutions,id'],
        ]);

        $ids = array_map('intval', $validated['institution_ids']);

        DB::transaction(function () use ($ids): void {
            $institutions = Institution::query()
                ->whereKey($ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // A record may disappear between validation and acquiring the locks.
            if ($institutions->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    'institution_ids' => 'One or more selected institutions no longer exist. Refresh the table and try again.',
                ]);
            }

            foreach ($institutions as $institution) {
                // Preserve the same model events and database cascades as destroy().
                if (! $institution->delete()) {
                    throw new \RuntimeException('The selected institutions could not be deleted.');
                }
            }
        }, 3);

        return response()->json([
            'success' => true,
            'message' => count($ids).' institution'.(count($ids) === 1 ? '' : 's').' deleted successfully.',
            'deleted_ids' => $ids,
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
                'No matching institution locations were found inside Lipa City for "'
                . $validated['name']
                . '". You may enter the exact coordinates manually.',
        ], 404);
    }

    public function resolveCoordinates(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];

        $barangay = DB::table('barangays')
            ->select('id', 'name')
            ->whereRaw(
                'ST_Covers(
                    geom,
                    ST_SetSRID(ST_Point(?, ?), 4326)
                )',
                [$longitude, $latitude]
            )
            ->first();

        if (!$barangay) {
            return response()->json([
                'message' =>
                    'The coordinates are outside the mapped Lipa City barangay boundaries.',
            ], 422);
        }

        return response()->json([
            'lat' => $latitude,
            'lng' => $longitude,
            'barangay' => $barangay->name,
            'barangay_id' => $barangay->id,
            'city' => 'Lipa',
            'province' => 'Batangas',
            'country' => 'Philippines',
        ]);
    }


    public function resolveOsmBoundary(
        Request $request
    ) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'osm_type' =>
                'nullable|string|in:node,way,relation|max:20',
            'osm_id' => 'nullable|integer|min:1',
        ]);

        $institution = new Institution([
            'name' => $validated['name'],
            'latitude' => (float) $validated['latitude'],
            'longitude' => (float) $validated['longitude'],
            'osm_type' => $validated['osm_type'] ?? null,
            'osm_id' => $validated['osm_id'] ?? null,
        ]);

        $resolution = $this->fetchInstitutionOsmBoundary(
            $institution
        );

        if ($resolution['outcome'] !== 'valid_polygon') {
            return response()->json([
                'outcome' => $resolution['outcome'],
                'message' => $resolution['message'],
                'source' => $resolution['source'] ?? null,
            ], $resolution['http_status']);
        }

        $boundary = $resolution['boundary'];

        return response()->json([
            'outcome' => 'valid_polygon',
            'message' =>
                'Institution boundary found in map data.',
            'source' => $resolution['source'],
            'saved' => false,
            'geojson' =>
                $boundary['geojson'],
            'boundingbox' =>
                $boundary['boundingbox'] ?? null,
            'place_id' =>
                $boundary['place_id'] ?? null,
            'osm_type' =>
                $boundary['osm_type'] ?? null,
            'osm_id' =>
                $boundary['osm_id'] ?? null,
        ]);
    }

    public function fetchOsmBoundary(
        Request $request,
        Institution $institution
    ) {
        if (
            $institution->latitude === null
            || $institution->longitude === null
        ) {
            return response()->json([
                'outcome' => 'invalid_coordinates',
                'message' =>
                    'The institution does not have valid coordinates.',
            ], 422);
        }

        $resolution = $this->fetchInstitutionOsmBoundary(
            $institution
        );

        if ($resolution['outcome'] !== 'valid_polygon') {
            return response()->json([
                'outcome' => $resolution['outcome'],
                'message' => $resolution['message'],
                'source' => $resolution['source'] ?? null,
            ], $resolution['http_status']);
        }

        $boundary = $resolution['boundary'];
        $geometry = $boundary['geojson'];

        $existingBoundary =
            $this->hasStoredBoundary($institution);

        $saved = false;

        if (!$existingBoundary) {
            DB::transaction(
                function () use (
                    $institution,
                    $geometry,
                    $boundary
                ): void {
                    $institution->forceFill([
                        'location_geojson' => $geometry,
                        'location_bounding_box' =>
                            $boundary['boundingbox'] ?? null,
                        'nominatim_place_id' =>
                            $boundary['place_id'] ?? null,
                        'osm_type' =>
                            $boundary['osm_type'] ?? null,
                        'osm_id' =>
                            $boundary['osm_id'] ?? null,
                    ])->save();

                    $this->syncPostgisBoundaryGeometry(
                        $institution
                    );
                }
            );

            $saved = true;
        }

        return response()->json([
            'outcome' => 'valid_polygon',
            'message' =>
                'Institution boundary found in map data.',
            'storage_message' => $saved
                ? 'The map boundary was saved because no institution boundary existed.'
                : 'The existing saved boundary was preserved.',
            'source' => $resolution['source'],
            'saved' => $saved,
            'existing_boundary_preserved' =>
                !$saved && $existingBoundary,
            'geojson' => $geometry,
            'boundingbox' =>
                $boundary['boundingbox'] ?? null,
            'place_id' =>
                $boundary['place_id'] ?? null,
            'osm_type' =>
                $boundary['osm_type'] ?? null,
            'osm_id' =>
                $boundary['osm_id'] ?? null,
        ]);
    }

    protected function prepareLocationCapture(Request $request, array $validated, ?Institution $existing = null): array
    {
        $capture = $request->validate([
            'location_source' => ['nullable', 'in:nominatim,device_gps,manual'],
            'actor_latitude' => ['nullable', 'required_with:actor_longitude,gps_accuracy', 'numeric', 'between:-90,90'],
            'actor_longitude' => ['nullable', 'required_with:actor_latitude,gps_accuracy', 'numeric', 'between:-180,180'],
            'gps_accuracy' => ['nullable', 'required_with:actor_latitude,actor_longitude', 'numeric', 'min:0'],
            'gps_verified' => ['nullable', 'boolean'],
        ]);
        $unchangedGps = $existing && $existing->location_source === 'device_gps'
            && (float) $validated['latitude'] === (float) $existing->latitude
            && (float) $validated['longitude'] === (float) $existing->longitude;
        if (($capture['location_source'] ?? null) === 'device_gps' && !isset($capture['actor_latitude']) && !$unchangedGps) {
            throw ValidationException::withMessages(['actor_latitude' => 'Capture your device location before selecting GPS.']);
        }
        if (isset($capture['actor_latitude']) && !$request->boolean('gps_verified')) {
            throw ValidationException::withMessages(['gps_verified' => 'Verify the institution pin before saving.']);
        }
        if (isset($capture['actor_latitude'])) {
            $this->validateGpsWithinLipa((float) $capture['actor_latitude'], (float) $capture['actor_longitude']);
        }
        $validated['location_source'] = $capture['location_source']
            ?? (isset($validated['nominatim_place_id']) ? 'nominatim' : 'manual');
        $capture['location_source'] = $validated['location_source'];
        $request->attributes->set('institution_location_capture', $capture);
        return $validated;
    }

    protected function validateGpsWithinLipa(float $latitude, float $longitude): void
    {
        $inside = DB::table('barangays')->whereRaw(
            'ST_Covers(geom, ST_SetSRID(ST_Point(?, ?), 4326))',
            [$longitude, $latitude]
        )->exists();

        if (!$inside) {
            throw ValidationException::withMessages([
                'actor_latitude' => 'GPS locations must be within the mapped Lipa City boundaries.',
            ]);
        }
    }

    protected function createRules(): array
    {
        return [
            'name' =>
                'required|string|max:255',

            'barangay_id' =>
                'required|integer|exists:barangays,id',

            'type' =>
                'required|in:School,Barangay Hall',

            'latitude' =>
                'nullable|required_with:longitude|numeric|between:-90,90',

            'longitude' =>
                'nullable|required_with:latitude|numeric|between:-180,180',

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
        return [
            'name' =>
                'required|string|max:255',
            'barangay_id' =>
                'required|integer|exists:barangays,id',
            'type' =>
                'required|in:School,Barangay Hall',
            'latitude' =>
                'nullable|required_with:longitude|numeric|between:-90,90',
            'longitude' =>
                'nullable|required_with:latitude|numeric|between:-180,180',
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
            'is_active' =>
                'nullable|boolean',
        ];
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
            'is_active',
        ];
    }

    protected function normalizeBooleanFields(
        Request $request
    ): void {
        foreach ($this->booleanFields() as $field) {
            $request->validate([$field => ['nullable', 'boolean']]);
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
        $latitudeChanged =
            isset($validated['latitude'])
            && abs(
                (float) $validated['latitude']
                - (float) $institution->latitude
            ) > 0.0000001;

        $longitudeChanged =
            isset($validated['longitude'])
            && abs(
                (float) $validated['longitude']
                - (float) $institution->longitude
            ) > 0.0000001;

        $barangayChanged =
            isset($validated['barangay_id'])
            && (int) $validated['barangay_id']
                !== (int) $institution->barangay_id;

        $locationChanged =
            $latitudeChanged
            || $longitudeChanged
            || $barangayChanged;

        foreach ([
            'location_geojson',
            'location_bounding_box',
            'nominatim_place_id',
            'osm_type',
            'osm_id',
        ] as $field) {
            if ($request->exists($field)) {
                continue;
            }

            /*
             * If the point/barangay moved but the form did not submit new
             * geometry metadata, do not preserve a stale boundary from the
             * previous location.
             */
            $validated[$field] = $locationChanged
                ? null
                : $institution->{$field};
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
                    . 'Select a map result or enter valid coordinates within Lipa City.',
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

            $this->validateGeometryComplexity(
                $geometryType,
                $coordinates,
                'location_geojson'
            );
            $this->validateGeometryStructure($geometryType, $coordinates);
        }

        return $validated;
    }

    protected function validateGeometryStructure(string $type, array $coordinates): void
    {
        $validPosition = static function (mixed $point): bool {
            if (!is_array($point) || !array_is_list($point) || !in_array(count($point), [2, 3], true)) {
                return false;
            }
            foreach ($point as $value) {
                if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value)) {
                    return false;
                }
            }
            return abs($point[0]) <= 180 && abs($point[1]) <= 90;
        };
        $validPolygon = static function (mixed $polygon) use ($validPosition): bool {
            if (!is_array($polygon) || !array_is_list($polygon) || $polygon === []) {
                return false;
            }
            foreach ($polygon as $ring) {
                if (!is_array($ring) || !array_is_list($ring) || count($ring) < 4) {
                    return false;
                }
                foreach ($ring as $point) {
                    if (!$validPosition($point)) {
                        return false;
                    }
                }
                if ($ring[0] != $ring[count($ring) - 1]) {
                    return false;
                }
            }
            return true;
        };
        $valid = match ($type) {
            'Point' => $validPosition($coordinates),
            'Polygon' => $validPolygon($coordinates),
            'MultiPolygon' => array_is_list($coordinates) && $coordinates !== []
                && collect($coordinates)->every($validPolygon),
            default => false,
        };
        if (!$valid) {
            throw ValidationException::withMessages([
                'location_geojson' => 'Location geometry must use valid coordinates and closed polygon rings.',
            ]);
        }
    }

    /**
     * Reject geometries with an excessive vertex count to prevent
     * PostGIS DoS from maliciously large polygons.
     */
    protected function validateGeometryComplexity(
        string $geometryType,
        array $coordinates,
        string $errorKey
    ): void {
        $maxCoordinates = (int) config(
            'services.geometry.max_coordinates',
            10000
        );

        $count = $this->countCoordinates(
            $geometryType,
            $coordinates
        );

        if ($count > $maxCoordinates) {
            throw ValidationException::withMessages([
                $errorKey => [
                    'The selected location geometry is too complex. '
                    . 'Maximum ' . $maxCoordinates
                    . ' coordinates allowed, received ' . $count . '.',
                ],
            ]);
        }
    }

    private function countCoordinates(
        string $geometryType,
        array $coordinates
    ): int {
        if ($geometryType === 'Point') {
            return is_array($coordinates) ? count($coordinates) : 0;
        }

        if ($geometryType === 'Polygon') {
            return $this->countPolygonCoordinates($coordinates);
        }

        if ($geometryType === 'MultiPolygon') {
            $total = 0;
            foreach ($coordinates as $polygon) {
                if (is_array($polygon)) {
                    $total += $this->countPolygonCoordinates($polygon);
                }
            }

            return $total;
        }

        return 0;
    }

    private function countPolygonCoordinates(array $polygon): int
    {
        $total = 0;
        foreach ($polygon as $ring) {
            if (is_array($ring)) {
                $total += count($ring);
            }
        }

        return $total;
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

    protected function validateCoordinateBarangayMatch(
        float $latitude,
        float $longitude,
        int $barangayId
    ): void {
        $matchedBarangayId = DB::table('barangays')
            ->where('id', $barangayId)
            ->whereRaw(
                'ST_Covers(
                    geom,
                    ST_SetSRID(ST_Point(?, ?), 4326)
                )',
                [$longitude, $latitude]
            )
            ->value('id');

        if ((int) $matchedBarangayId !== $barangayId) {
            throw ValidationException::withMessages([
                'location' => [
                    'Choose coordinates within the selected Lipa City barangay. The current point is outside its boundary.',
                ],
            ]);
        }
    }

    protected function validateBoundaryContainsInstitutionPoint(
        array $validated
    ): void {
        $geometry = $validated['location_geojson'] ?? null;

        if (
            !is_array($geometry)
            || !in_array(
                $geometry['type'] ?? null,
                ['Polygon', 'MultiPolygon'],
                true
            )
        ) {
            return;
        }

        $result = DB::selectOne(
            'SELECT CASE
                WHEN ST_Covers(
                    ST_SetSRID(ST_GeomFromGeoJSON(?), 4326),
                    ST_SetSRID(ST_Point(?, ?), 4326)
                )
                THEN 1
                ELSE 0
            END AS contains_point',
            [
                json_encode($geometry, JSON_THROW_ON_ERROR),
                (float) $validated['longitude'],
                (float) $validated['latitude'],
            ]
        );

        if ((int) ($result->contains_point ?? 0) !== 1) {
            throw ValidationException::withMessages([
                'location_geojson' => [
                    'The institution point must be inside the saved institution boundary.',
                ],
            ]);
        }
    }

    protected function lipaCityViewbox(): ?string
    {
        $extent = DB::selectOne(
            'SELECT
                ST_XMin(extent) AS west,
                ST_YMin(extent) AS south,
                ST_XMax(extent) AS east,
                ST_YMax(extent) AS north
             FROM (
                SELECT ST_Extent(geom) AS extent
                FROM barangays
                WHERE geom IS NOT NULL
             ) AS mapped_city'
        );

        if (
            !$extent
            || !is_numeric($extent->west ?? null)
            || !is_numeric($extent->south ?? null)
            || !is_numeric($extent->east ?? null)
            || !is_numeric($extent->north ?? null)
        ) {
            return null;
        }

        /*
         * Nominatim viewbox order:
         * left, top, right, bottom
         * = west, north, east, south.
         */
        return implode(',', [
            (float) $extent->west,
            (float) $extent->north,
            (float) $extent->east,
            (float) $extent->south,
        ]);
    }

    protected function fetchInstitutionOsmBoundary(
        Institution $institution
    ): array {
        $latitude = (float) $institution->latitude;
        $longitude = (float) $institution->longitude;

        $pointSeen = false;
        $rejectedPolygonSeen = false;

        if (
            filled($institution->osm_type)
            && filled($institution->osm_id)
        ) {
            $lookup = $this->lookupOsmFeature(
                $institution->osm_type,
                (int) $institution->osm_id
            );

            $this->logNominatimCandidates(
                $institution,
                'lookup',
                $lookup ? [$lookup] : []
            );

            if ($lookup !== null) {
                $geometryType =
                    $lookup['geometry_type'] ?? null;

                if ($geometryType === 'Point') {
                    $pointSeen = true;
                }

                if (
                    $this->isBoundaryGeometry(
                        $lookup['geojson'] ?? null
                    )
                ) {
                    if (
                        $this->geometryCoversPoint(
                            $lookup['geojson'],
                            $latitude,
                            $longitude
                        )
                    ) {
                        return $this->validBoundaryResolution(
                            $lookup,
                            'lookup'
                        );
                    }

                    $rejectedPolygonSeen = true;
                }
            }
        }

        $searchResults =
            $this->geocodeLocation(
                $institution->name
            ) ?? [];

        $this->logNominatimCandidates(
            $institution,
            'name_search',
            $searchResults
        );

        foreach ($searchResults as $candidate) {
            $geometryType =
                $candidate['geometry_type'] ?? null;

            if ($geometryType === 'Point') {
                if (
                    $this->hasSufficientNameMatch(
                        $institution->name,
                        $candidate
                    )
                ) {
                    $pointSeen = true;
                }

                continue;
            }

            if (
                !$this->isBoundaryGeometry(
                    $candidate['geojson'] ?? null
                )
            ) {
                continue;
            }

            if (
                !$this->hasSufficientNameMatch(
                    $institution->name,
                    $candidate
                )
            ) {
                continue;
            }

            if (
                $this->geometryCoversPoint(
                    $candidate['geojson'],
                    $latitude,
                    $longitude
                )
            ) {
                return $this->validBoundaryResolution(
                    $candidate,
                    'name_search'
                );
            }

            $rejectedPolygonSeen = true;
        }

        $reverse = $this->reverseGeocodeInstitution(
            $latitude,
            $longitude
        );

        $this->logNominatimCandidates(
            $institution,
            'reverse',
            $reverse ? [$reverse] : []
        );

        if ($reverse !== null) {
            $geometryType =
                $reverse['geometry_type'] ?? null;

            if (
                $geometryType === 'Point'
                && $this->isVerifiedInstitutionFeature(
                    $institution,
                    $reverse
                )
            ) {
                $pointSeen = true;
            }

            if (
                $this->isBoundaryGeometry(
                    $reverse['geojson'] ?? null
                )
            ) {
                $verified =
                    $this->geometryCoversPoint(
                        $reverse['geojson'],
                        $latitude,
                        $longitude
                    )
                    && $this->isVerifiedInstitutionFeature(
                        $institution,
                        $reverse
                    );

                if ($verified) {
                    return $this->validBoundaryResolution(
                        $reverse,
                        'reverse'
                    );
                }

                $rejectedPolygonSeen = true;
            }
        }

        if ($pointSeen) {
            return [
                'outcome' => 'point_only',
                'message' =>
                    'OpenStreetMap contains this institution as a point only. No institution boundary is mapped. You may draw a custom boundary.',
                'source' => 'osm',
                'http_status' => 422,
            ];
        }

        if ($rejectedPolygonSeen) {
            return [
                'outcome' =>
                    'nearby_polygon_rejected',
                'message' =>
                    'A nearby mapped area was found, but it could not be confirmed as the institution boundary.',
                'source' => 'reverse',
                'http_status' => 422,
            ];
        }

        return [
            'outcome' => 'no_matching_feature',
            'message' =>
                'No matching OpenStreetMap feature was found near the saved coordinates.',
            'source' => null,
            'http_status' => 404,
        ];
    }

    protected function lookupOsmFeature(
        ?string $osmType,
        ?int $osmId
    ): ?array {
        $prefix = match ($osmType) {
            'node' => 'N',
            'way' => 'W',
            'relation' => 'R',
            default => null,
        };

        if ($prefix === null || !$osmId) {
            return null;
        }

        try {
            $response = $this->nominatimRequest(
                config(
                    'services.nominatim.lookup_url',
                    'https://nominatim.openstreetmap.org/lookup'
                ),
                [
                    'format' => 'jsonv2',
                    'osm_ids' => $prefix . $osmId,
                    'polygon_geojson' => 1,
                    'polygon_threshold' => 0.00001,
                    'addressdetails' => 1,
                    'extratags' => 1,
                    'namedetails' => 1,
                ]
            );

            if (!$response->successful()) {
                Log::warning(
                    'Nominatim lookup returned an error status.',
                    [
                        'status' => $response->status(),
                        'osm_type' => $osmType,
                        'osm_id' => $osmId,
                    ]
                );

                return null;
            }

            $data = $response->json();
            $item = is_array($data)
                ? ($data[0] ?? null)
                : null;

            return is_array($item)
                ? $this->normalizeNominatimCandidate($item)
                : null;
        } catch (\Throwable $exception) {
            Log::warning(
                'Nominatim boundary lookup failed.',
                [
                    'institution_osm_type' => $osmType,
                    'institution_osm_id' => $osmId,
                    'error' => $exception->getMessage(),
                ]
            );

            return null;
        }
    }

    protected function reverseGeocodeInstitution(
        float $latitude,
        float $longitude
    ): ?array {
        try {
            $response = $this->nominatimRequest(
                config(
                    'services.nominatim.reverse_url',
                    'https://nominatim.openstreetmap.org/reverse'
                ),
                [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 18,
                    'polygon_geojson' => 1,
                    'polygon_threshold' => 0.00001,
                    'addressdetails' => 1,
                    'extratags' => 1,
                    'namedetails' => 1,
                ]
            );

            if (!$response->successful()) {
                Log::warning(
                    'Nominatim reverse lookup returned an error status.',
                    [
                        'status' => $response->status(),
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ]
                );

                return null;
            }

            $item = $response->json();

            return is_array($item)
                ? $this->normalizeNominatimCandidate($item)
                : null;
        } catch (\Throwable $exception) {
            Log::warning(
                'Nominatim reverse lookup failed.',
                [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'error' => $exception->getMessage(),
                ]
            );

            return null;
        }
    }

    protected function normalizeNominatimCandidate(
        array $item
    ): array {
        $geometry =
            is_array($item['geojson'] ?? null)
                ? $item['geojson']
                : null;

        $displayName =
            (string) ($item['display_name'] ?? '');

        return [
            'name' =>
                $item['name']
                ?? trim(
                    explode(',', $displayName)[0]
                ),
            'display_name' => $displayName,
            'lat' =>
                is_numeric($item['lat'] ?? null)
                    ? (float) $item['lat']
                    : null,
            'lng' =>
                is_numeric($item['lon'] ?? null)
                    ? (float) $item['lon']
                    : null,
            'address' =>
                is_array($item['address'] ?? null)
                    ? $item['address']
                    : [],
            'place_id' =>
                $item['place_id'] ?? null,
            'osm_type' =>
                $item['osm_type'] ?? null,
            'osm_id' =>
                $item['osm_id'] ?? null,
            'boundingbox' =>
                $item['boundingbox'] ?? null,
            'geojson' => $geometry,
            'geometry_type' =>
                $geometry['type'] ?? null,
            'has_boundary' =>
                $this->isBoundaryGeometry(
                    $geometry
                ),
            'category' =>
                $item['category']
                ?? $item['class']
                ?? null,
            'feature_type' =>
                $item['type'] ?? null,
            'extratags' =>
                is_array($item['extratags'] ?? null)
                    ? $item['extratags']
                    : [],
            'namedetails' =>
                is_array($item['namedetails'] ?? null)
                    ? $item['namedetails']
                    : [],
        ];
    }

    protected function isVerifiedInstitutionFeature(
        Institution $institution,
        array $candidate
    ): bool {
        if ($this->isGeneralAreaFeature($candidate)) {
            return false;
        }

        if ($this->hasInstitutionLikeTags($candidate)) {
            return true;
        }

        return $this->hasSufficientNameMatch(
            $institution->name,
            $candidate
        );
    }

    protected function hasInstitutionLikeTags(
        array $candidate
    ): bool {
        $tags = $candidate['extratags'] ?? [];

        $amenity = Str::lower(
            (string) (
                $tags['amenity']
                ?? (
                    ($candidate['category'] ?? null) === 'amenity'
                        ? $candidate['feature_type'] ?? ''
                        : ''
                )
            )
        );

        $building = Str::lower(
            (string) (
                $tags['building']
                ?? (
                    ($candidate['category'] ?? null) === 'building'
                        ? $candidate['feature_type'] ?? ''
                        : ''
                )
            )
        );

        $office = Str::lower(
            (string) (
                $tags['office']
                ?? (
                    ($candidate['category'] ?? null) === 'office'
                        ? $candidate['feature_type'] ?? ''
                        : ''
                )
            )
        );

        return in_array(
            $amenity,
            [
                'school',
                'college',
                'university',
                'kindergarten',
                'townhall',
            ],
            true
        )
            || in_array(
                $building,
                [
                    'school',
                    'civic',
                    'government',
                    'public',
                ],
                true
            )
            || in_array(
                $office,
                [
                    'government',
                    'administrative',
                ],
                true
            );
    }

    protected function isGeneralAreaFeature(
        array $candidate
    ): bool {
        $tags = $candidate['extratags'] ?? [];

        $category = Str::lower(
            (string) ($candidate['category'] ?? '')
        );

        $featureType = Str::lower(
            (string) ($candidate['feature_type'] ?? '')
        );

        if (
            in_array(
                $category,
                [
                    'boundary',
                    'highway',
                    'place',
                    'landuse',
                    'waterway',
                ],
                true
            )
        ) {
            return true;
        }

        if (
            filled($tags['boundary'] ?? null)
            || filled($tags['highway'] ?? null)
            || in_array(
                Str::lower(
                    (string) ($tags['landuse'] ?? '')
                ),
                [
                    'residential',
                    'commercial',
                    'industrial',
                ],
                true
            )
            || in_array(
                Str::lower(
                    (string) ($tags['place'] ?? '')
                ),
                [
                    'barangay',
                    'village',
                    'suburb',
                    'neighbourhood',
                    'quarter',
                    'city',
                    'town',
                    'municipality',
                ],
                true
            )
        ) {
            return true;
        }

        return in_array(
            $featureType,
            [
                'administrative',
                'residential',
                'road',
                'street',
            ],
            true
        );
    }

    protected function hasSufficientNameMatch(
        string $institutionName,
        array $candidate
    ): bool {
        $expected =
            $this->normalizeInstitutionName(
                $institutionName
            );

        if ($expected === '') {
            return false;
        }

        $candidateNames = collect([
            $candidate['name'] ?? null,
            $candidate['display_name'] ?? null,
            ...array_values(
                $candidate['namedetails'] ?? []
            ),
        ])
            ->filter(
                fn (mixed $value): bool =>
                    is_string($value)
                    && trim($value) !== ''
            )
            ->map(
                fn (string $value): string =>
                    $this->normalizeInstitutionName(
                        $value
                    )
            )
            ->filter()
            ->unique();

        foreach ($candidateNames as $candidateName) {
            if (
                $candidateName === $expected
                || str_contains(
                    $candidateName,
                    $expected
                )
                || str_contains(
                    $expected,
                    $candidateName
                )
            ) {
                return true;
            }

            $expectedTokens =
                $this->meaningfulNameTokens(
                    $expected
                );

            $candidateTokens =
                $this->meaningfulNameTokens(
                    $candidateName
                );

            if (
                empty($expectedTokens)
                || empty($candidateTokens)
            ) {
                continue;
            }

            $intersection = count(
                array_intersect(
                    $expectedTokens,
                    $candidateTokens
                )
            );

            $union = count(
                array_unique([
                    ...$expectedTokens,
                    ...$candidateTokens,
                ])
            );

            if (
                $union > 0
                && ($intersection / $union) >= 0.65
            ) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeInstitutionName(
        string $value
    ): string {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches(
                '/[^a-z0-9]+/',
                ' '
            )
            ->squish()
            ->toString();
    }

    protected function meaningfulNameTokens(
        string $value
    ): array {
        $stopWords = [
            'the',
            'of',
            'ng',
            'barangay',
            'brgy',
            'bahay',
            'pamahalaan',
        ];

        return collect(
            preg_split(
                '/\s+/',
                trim($value)
            ) ?: []
        )
            ->filter(
                fn (string $token): bool =>
                    strlen($token) >= 3
                    && !in_array(
                        $token,
                        $stopWords,
                        true
                    )
            )
            ->unique()
            ->values()
            ->all();
    }

    protected function isBoundaryGeometry(
        mixed $geometry
    ): bool {
        return is_array($geometry)
            && in_array(
                $geometry['type'] ?? null,
                ['Polygon', 'MultiPolygon'],
                true
            );
    }

    protected function validBoundaryResolution(
        array $boundary,
        string $source
    ): array {
        return [
            'outcome' => 'valid_polygon',
            'message' =>
                'Institution boundary found in map data.',
            'source' => $source,
            'http_status' => 200,
            'boundary' => $boundary,
        ];
    }

    protected function logNominatimCandidates(
        Institution $institution,
        string $stage,
        array $candidates
    ): void {
        Log::info(
            'OSM institution boundary candidates.',
            [
                'institution_id' =>
                    $institution->id,
                'institution' =>
                    $institution->name,
                'latitude' =>
                    $institution->latitude,
                'longitude' =>
                    $institution->longitude,
                'stage' => $stage,
                'candidates' => collect(
                    $candidates
                )
                    ->map(
                        fn (array $candidate): array => [
                            'name' =>
                                $candidate['name']
                                ?? null,
                            'display_name' =>
                                $candidate['display_name']
                                ?? null,
                            'osm_type' =>
                                $candidate['osm_type']
                                ?? null,
                            'osm_id' =>
                                $candidate['osm_id']
                                ?? null,
                            'geometry_type' =>
                                $candidate['geometry_type']
                                ?? null,
                            'has_boundary' =>
                                (bool) (
                                    $candidate['has_boundary']
                                    ?? false
                                ),
                        ]
                    )
                    ->values()
                    ->all(),
            ]
        );
    }

    protected function nominatimRequest(
        string $url,
        array $parameters
    ) {
        $parameters['email'] =
            config(
                'services.nominatim.app_email'
            );

        $parameters = array_filter(
            $parameters,
            fn (mixed $value): bool =>
                $value !== null
                && $value !== ''
        );

        return Cache::lock(
            'nominatim-public-api-request',
            10
        )->block(
            10,
            function () use (
                $url,
                $parameters
            ) {
                $lastRequestAt = (float) Cache::get(
                    'nominatim-public-api-last-request',
                    0
                );

                $elapsedMicroseconds = (int) (
                    (
                        microtime(true)
                        - $lastRequestAt
                    )
                    * 1_000_000
                );

                $waitMicroseconds = max(
                    0,
                    1_000_000 - $elapsedMicroseconds
                );

                if ($waitMicroseconds > 0) {
                    usleep($waitMicroseconds);
                }

                $response = Http::withHeaders([
                    'User-Agent' => config(
                        'services.nominatim.user_agent',
                        'LipaICTMapping/1.0'
                    ),
                ])
                    ->timeout(10)
                    ->get(
                        $url,
                        $parameters
                    );

                Cache::put(
                    'nominatim-public-api-last-request',
                    microtime(true),
                    now()->addMinutes(5)
                );

                return $response;
            }
        );
    }

    protected function hasStoredBoundary(
        Institution $institution
    ): bool {
        $hasGeoJsonBoundary =
            is_array($institution->location_geojson)
            && in_array(
                $institution->location_geojson['type']
                    ?? null,
                ['Polygon', 'MultiPolygon'],
                true
            );

        if ($hasGeoJsonBoundary) {
            return true;
        }

        if (
            DB::connection()->getDriverName() !== 'pgsql'
            || !Schema::hasColumn(
                'institutions',
                'location_geometry'
            )
        ) {
            return false;
        }

        return DB::table('institutions')
            ->where('id', $institution->id)
            ->whereNotNull('location_geometry')
            ->exists();
    }

    protected function geometryCoversPoint(
        array $geometry,
        float $latitude,
        float $longitude
    ): bool {
        if (
            !in_array(
                $geometry['type'] ?? null,
                ['Polygon', 'MultiPolygon'],
                true
            )
        ) {
            return false;
        }

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return true;
        }

        $result = DB::selectOne(
            'SELECT ST_Covers(
                ST_SetSRID(
                    ST_GeomFromGeoJSON(?),
                    4326
                ),
                ST_SetSRID(
                    ST_Point(?, ?),
                    4326
                )
            ) AS covers',
            [
                json_encode(
                    $geometry,
                    JSON_THROW_ON_ERROR
                ),
                $longitude,
                $latitude,
            ]
        );

        return (bool) ($result?->covers ?? false);
    }

    protected function syncPostgisBoundaryGeometry(
        Institution $institution
    ): void {
        if (
            DB::connection()->getDriverName() !== 'pgsql'
            || !Schema::hasColumn(
                'institutions',
                'location_geometry'
            )
        ) {
            return;
        }

        $geometry = $institution->location_geojson;

        if (
            !is_array($geometry)
            || !in_array(
                $geometry['type'] ?? null,
                ['Polygon', 'MultiPolygon'],
                true
            )
        ) {
            DB::statement(
                'UPDATE institutions
                 SET location_geometry = NULL
                 WHERE id = ?',
                [$institution->id]
            );

            return;
        }

        DB::statement(
            'UPDATE institutions
             SET location_geometry =
                ST_SetSRID(
                    ST_GeomFromGeoJSON(?),
                    4326
                )
             WHERE id = ?',
            [
                json_encode(
                    $geometry,
                    JSON_THROW_ON_ERROR
                ),
                $institution->id,
            ]
        );
    }

    protected function geocodeLocation(
        string $name
    ): ?array {
        try {
            $query = trim($name);

            $viewbox = $this->lipaCityViewbox();

            $requestParameters = [
                'format' => config(
                    'services.nominatim.output_format',
                    'json'
                ),
                // The viewbox and PostGIS containment enforce the city scope.
                // Appending "Lipa City" can reject POIs indexed under "Lipa".
                'q' => $query,
                'addressdetails' => 1,
                'countrycodes' => config(
                    'services.nominatim.country_code',
                    'ph'
                ),
                'polygon_geojson' => 1,
                'polygon_threshold' => 0.00001,
                'extratags' => 1,
                'namedetails' => 1,
                'viewbox' => $viewbox,
                'bounded' => $viewbox !== null ? 1 : null,
                'limit' => 10,
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
                        'ST_Covers(
                            geom,
                            ST_SetSRID(ST_Point(?, ?), 4326)
                        )',
                        [$longitude, $latitude]
                    )
                    ->first();

                /*
                 * Strict Lipa City geofence.
                 * The barangay polygons collectively define the mapped city
                 * coverage. Results outside those polygons are never returned
                 * to the institution picker.
                 */
                if (!$barangay) {
                    continue;
                }

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

                    'lat' => $latitude,
                    'lng' => $longitude,

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

                    'category' =>
                        $result['category']
                        ?? $result['class']
                        ?? $item['category']
                        ?? $item['class']
                        ?? null,

                    'feature_type' =>
                        $result['type']
                        ?? $item['type']
                        ?? null,

                    'extratags' =>
                        is_array(
                            $result['extratags']
                            ?? $item['extratags']
                            ?? null
                        )
                            ? (
                                $result['extratags']
                                ?? $item['extratags']
                            )
                            : [],

                    'namedetails' =>
                        is_array(
                            $result['namedetails']
                            ?? $item['namedetails']
                            ?? null
                        )
                            ? (
                                $result['namedetails']
                                ?? $item['namedetails']
                            )
                            : [],
                ];
            }

            foreach ($results as &$location) {
                $location['classification'] = \App\Services\InstitutionMapClassification::detect($location);
                $location['already_exists'] = Institution::query()
                    ->where(function ($query) use ($location) {
                        $query->where(function ($identity) use ($location) {
                            $identity->where('barangay_id', $location['barangay_id'])
                                ->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [$location['name']]);
                        });
                        if (!empty($location['osm_type']) && !empty($location['osm_id'])) {
                            $query->orWhere(function ($mapped) use ($location) {
                                $mapped->where('osm_type', $location['osm_type'])->where('osm_id', $location['osm_id']);
                            });
                        }
                    })->exists();
            }
            unset($location);

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
