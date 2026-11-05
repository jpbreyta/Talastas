<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Institution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InstitutionController extends Controller
{
    /**
     * Return all institutions for the Flutter application.
     *
     * The API key is removed from every record before the response is sent.
     */
    public function index(Request $request): JsonResponse
    {
        $institutions = Institution::query()
            ->when($request->user()->isEditor(), fn ($q) => $q->whereHas('assignedUsers', fn ($assigned) => $assigned->whereKey($request->user()->id)))
            ->with('barangay')
            ->latest()
            ->get()
            ->each(fn (Institution $institution) => $institution->makeHidden('api_key'));

        return response()->json([
            'success' => true,
            'data' => $institutions,
        ]);
    }

    /**
     * Return the complete details of one institution.
     *
     * Related barangay, inventory, and analytics records are loaded while the
     * server-side API key remains hidden from the mobile application.
     */
    public function show(Request $request, Institution $institution): JsonResponse
    {
        abort_unless($request->user()->canAccessInstitution($institution), 403);
        $institution->load([
            'barangay',
            'ictInventory',
            'institutionAnalytics',
        ]);

        $institution->makeHidden('api_key');

        return response()->json([
            'success' => true,
            'data' => $institution,
        ]);
    }

    /**
     * Validate and create a new institution from the Flutter application.
     *
     * A barangay name is converted to its database ID when necessary. Missing
     * coordinates are obtained through Nominatim and checked against the
     * selected barangay before the institution is saved.
     */
    public function store(Request $request): JsonResponse
    {
        $this->resolveBarangayName($request);

        $request->merge([
            'is_active' => $request->has('is_active')
                ? $request->input('is_active')
                : true,
        ]);

        $validated = $request->validate(
            $this->validationRules($request)
        );

        $this->validateCoordinatePair($request);

        $validated = $this->applyBooleanValues(
            $validated,
            $request
        );

        if ($this->coordinatesAreMissing($validated)) {
            $validated = $this->addGeocodedCoordinates($validated);
        } else {
            $this->validateCoordinateBarangayMatch(
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                (int) $validated['barangay_id']
            );
        }

        if (config('services.rdbma.api_key')) {
            $validated['api_key'] = config('services.rdbma.api_key');
        }

        $institution = DB::transaction(
            fn () => Institution::create($validated)
        );

        $institution->load('barangay');
        $institution->makeHidden('api_key');

        return response()->json([
            'success' => true,
            'message' => 'Institution created successfully.',
            'data' => $institution,
        ], 201);
    }

    /**
     * Validate and update an existing institution from the Flutter application.
     *
     * Partial updates are supported. Coordinates are searched again when the
     * name or barangay changes without new coordinates being supplied.
     */
    public function update(
        Request $request,
        Institution $institution
    ): JsonResponse {
        $this->resolveBarangayName($request);

        $validated = $request->validate(
            $this->validationRules($request, true)
        );

        $this->validateCoordinatePair($request);

        $validated = $this->applyBooleanValues(
            $validated,
            $request,
            true
        );

        $finalData = array_merge(
            $institution->only([
                'name',
                'type',
                'barangay_id',
                'latitude',
                'longitude',
            ]),
            $validated
        );

        if ($this->shouldRefreshCoordinates($request, $finalData)) {
            $geocodedData = $this->addGeocodedCoordinates($finalData);

            $validated['latitude'] = $geocodedData['latitude'];
            $validated['longitude'] = $geocodedData['longitude'];
        } elseif (
            array_key_exists('latitude', $validated)
            || array_key_exists('longitude', $validated)
            || array_key_exists('barangay_id', $validated)
        ) {
            $this->validateCoordinateBarangayMatch(
                (float) $finalData['latitude'],
                (float) $finalData['longitude'],
                (int) $finalData['barangay_id']
            );
        }

        if (config('services.rdbma.api_key')) {
            $validated['api_key'] = config('services.rdbma.api_key');
        }

        if (array_key_exists('download_speed_mbps', $validated)) {
            $validated['internet_speed'] =
                $validated['download_speed_mbps'];
        }

        DB::transaction(function () use ($institution, $validated): void {
            $institution->update($validated);
        });

        $institution = $institution->fresh()->load('barangay');
        $institution->makeHidden('api_key');

        return response()->json([
            'success' => true,
            'message' => 'Institution updated successfully.',
            'data' => $institution,
        ]);
    }

    /**
     * Permanently delete an institution through the API.
     *
     * This endpoint must remain protected by authentication and administrator
     * role middleware in routes/api.php.
     */
    public function destroy(Institution $institution): JsonResponse
    {
        DB::transaction(function () use ($institution): void {
            $institution->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Institution deleted successfully.',
        ]);
    }

    /**
     * Convert the optional barangay name sent by Flutter into a barangay ID.
     *
     * The request is left unchanged when barangay_id is already provided.
     */
    protected function resolveBarangayName(Request $request): void
    {
        if ($request->filled('barangay_id') || !$request->filled('barangay')) {
            return;
        }

        $request->validate(['barangay' => ['required', 'string', 'max:255']]);

        $barangayName = trim((string) $request->input('barangay'));

        $barangay = Barangay::query()
            ->whereRaw(
                'LOWER(name) = ?',
                [mb_strtolower($barangayName)]
            )
            ->first();

        if (!$barangay) {
            throw ValidationException::withMessages([
                'barangay' => [
                    'The barangay "' . $barangayName . '" does not exist in the barangays table.',
                ],
            ]);
        }

        $request->merge([
            'barangay_id' => $barangay->id,
        ]);
    }

    /**
     * Return the fields accepted by the institution API.
     *
     * Only institution identity, map coordinates, active status, and the
     * connectivity inputs used by the finalized ICT Gap baseline are accepted.
     */
    protected function validationRules(
        Request $request,
        bool $partial = false
    ): array {
        $prefix = $partial ? 'sometimes|' : '';

        return [
            'name' => $prefix . 'required|string|max:255',
            'barangay_id' => $prefix . 'required|integer|exists:barangays,id',
            'type' => $prefix . 'required|in:School,Barangay Hall',
            'latitude' => $prefix . 'nullable|numeric|between:-90,90',
            'longitude' => $prefix . 'nullable|numeric|between:-180,180',
            'has_internet_connection' => $prefix . 'nullable|boolean',
            'bandwidth_allocation_mbps' => $prefix . 'nullable|numeric|min:0|max:1000000',
            'download_speed_mbps' => $prefix . 'nullable|numeric|min:0|max:1000000',

            'is_active' => $prefix . 'nullable|boolean',
        ];
    }

    /**
     * Require latitude and longitude to be submitted as one complete pair.
     *
     * This prevents a partial coordinate update from combining a new value with
     * an unrelated coordinate already stored in the database.
     */
    protected function validateCoordinatePair(Request $request): void
    {
        $latitudeProvided = $request->filled('latitude');
        $longitudeProvided = $request->filled('longitude');

        if ($latitudeProvided !== $longitudeProvided) {
            throw ValidationException::withMessages([
                'location' => [
                    'Latitude and longitude must be provided together.',
                ],
            ]);
        }
    }

    /**
     * Convert checkbox-like API values into real boolean values.
     *
     * During partial updates, only boolean fields included in the request are
     * changed so omitted values keep their current database state.
     */
    protected function applyBooleanValues(
        array $validated,
        Request $request,
        bool $partial = false
    ): array {
        foreach ($this->booleanFields() as $field) {
            if (!$partial || $request->has($field)) {
                $validated[$field] = $request->boolean($field);
            }
        }

        foreach ([
            'has_internet_connection',
        ] as $field) {
            if ($request->has($field)) {
                $validated[$field] =
                    in_array($request->input($field), [null, ''], true)
                        ? null
                        : $request->boolean($field);
            }
        }

        return $validated;
    }

    /**
     * Return the institution fields that Laravel must normalize as booleans.
     */
    protected function booleanFields(): array
    {
        return [
            'is_active',
        ];
    }

    /**
     * Determine whether either latitude or longitude is unavailable.
     */
    protected function coordinatesAreMissing(array $data): bool
    {
        return !isset($data['latitude'], $data['longitude'])
            || $data['latitude'] === null
            || $data['longitude'] === null
            || $data['latitude'] === ''
            || $data['longitude'] === '';
    }

    /**
     * Determine whether an update requires a new geocoding search.
     *
     * Geocoding is repeated when coordinates are missing or when the name or
     * barangay changes without a complete new coordinate pair.
     */
    protected function shouldRefreshCoordinates(
        Request $request,
        array $finalData
    ): bool {
        if ($this->coordinatesAreMissing($finalData)) {
            return true;
        }

        $locationIdentityChanged = $request->hasAny([
            'name',
            'barangay_id',
            'barangay',
        ]);

        $completeCoordinatesProvided = $request->filled('latitude')
            && $request->filled('longitude');

        return $locationIdentityChanged && !$completeCoordinatesProvided;
    }

    /**
     * Search for coordinates and add the best barangay-matching result.
     *
     * A geocoding result is accepted only when its point belongs to the selected
     * barangay polygon. This prevents a similarly named location outside the
     * intended barangay from being saved.
     */
    protected function addGeocodedCoordinates(array $data): array
    {
        $barangay = Barangay::find($data['barangay_id']);

        $searchQuery = implode(', ', array_filter([
            $data['name'] ?? null,
            $barangay?->name,
            'Lipa City',
            'Batangas',
            'Philippines',
        ]));

        $locations = $this->geocodeLocation($searchQuery);

        if (empty($locations)) {
            throw ValidationException::withMessages([
                'location' => [
                    'No location was found for "' . $searchQuery . '".',
                ],
            ]);
        }

        $matchingLocation = collect($locations)->first(
            fn (array $location) => (int) ($location['barangay_id'] ?? 0)
                === (int) $data['barangay_id']
        );

        if (!$matchingLocation) {
            throw ValidationException::withMessages([
                'location' => [
                    'The searched location does not belong to the selected barangay.',
                ],
            ]);
        }

        $data['latitude'] = $matchingLocation['lat'];
        $data['longitude'] = $matchingLocation['lng'];

        return $data;
    }

    /**
     * Confirm that manually supplied coordinates belong to the selected barangay.
     */
    protected function validateCoordinateBarangayMatch(
        float $latitude,
        float $longitude,
        int $barangayId
    ): void {
        $matchedBarangayId = DB::table('barangays')
            ->whereRaw(
                'ST_Contains(
                    geom,
                    ST_SetSRID(ST_Point(?, ?), 4326)
                )',
                [$longitude, $latitude]
            )
            ->value('id');

        if ((int) $matchedBarangayId !== $barangayId) {
            throw ValidationException::withMessages([
                'location' => [
                    'The provided coordinates do not belong to the selected barangay.',
                ],
            ]);
        }
    }

    /**
     * Search Nominatim and attach the containing barangay to every valid result.
     *
     * Invalid coordinates and unsuccessful external responses are ignored and
     * recorded in the Laravel log instead of causing an unhandled API error.
     */
    protected function geocodeLocation(string $name): ?array
    {
        try {
            $query = trim($name);

            $response = Http::withHeaders([
                'User-Agent' => config(
                    'services.nominatim.user_agent',
                    'LipaGisMapping/1.0'
                ),
            ])
                ->timeout(10)
                ->get(
                    config(
                        'services.nominatim.base_url',
                        'https://nominatim.openstreetmap.org/search'
                    ),
                    [
                        'format' => config(
                            'services.nominatim.output_format',
                            'json'
                        ),
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

            $outputFormat = config(
                'services.nominatim.output_format',
                'json'
            );

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
                    'lat' => $latitude,
                    'lng' => $longitude,
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
