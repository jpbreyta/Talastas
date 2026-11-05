<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Support\Facades\Cache;

class PortalMappingController extends IctMappingController
{
    public function getInstitutionsGeoJSON()
    {
        $request = request();
        $user = $request->user();

        $scopeKey = $user->isEditor()
            ? 'editor:' . $user->id
            : 'citywide';

        $payload = Cache::remember(
            'map.institutions.geojson.v4.baseline.' . $scopeKey,
            now()->addSeconds(45),
            function () use ($user): array {
                $query = Institution::query()
                    ->whereIn(
                        'type',
                        ['School', 'Barangay Hall']
                    )
                    ->select([
                        'id',
                        'name',
                        'type',
                        'barangay_id',
                        'latitude',
                        'longitude',
                        'location_geojson',
                        'has_internet_connection',
                        'bandwidth_allocation_mbps',
                        'download_speed_mbps',
                        'is_active',
                    ])
                    ->with([
                        'institutionAnalytics:id,institution_id,gap_score,risk_level,infrastructure_deficit_score,connectivity_divide_score,technological_obsolescence_score,infrastructure_narrative,policy_recommendation,recommendations_detail',
                        'barangay:id,name',
                    ]);

                if ($user->isEditor()) {
                    $query->whereHas(
                        'assignedUsers',
                        fn ($assigned) =>
                            $assigned->whereKey($user->id)
                    );
                }

                $features = $query
                    ->where('is_active', true)
                    ->get()
                    ->map(
                        function (Institution $inst): array {
                            $analytics =
                                $inst->institutionAnalytics;
                            $gapScore =
                                $analytics?->gap_score;
                            $geometry =
                                $inst->location_geojson;

                            return [
                                'type' => 'Feature',
                                'geometry' => [
                                    'type' => 'Point',
                                    'coordinates' => [
                                        (float) $inst->longitude,
                                        (float) $inst->latitude,
                                    ],
                                ],
                                'properties' => [
                                    'id' => $inst->id,
                                    'name' => $inst->name,
                                    'type' => $inst->type,
                                    'barangay' =>
                                        $inst->barangay?->name
                                        ?? 'Unknown',
                                    'latitude' =>
                                        (float) $inst->latitude,
                                    'longitude' =>
                                        (float) $inst->longitude,
                                    'location_geojson' =>
                                        $geometry,
                                    'has_location_boundary' =>
                                        in_array(
                                            $geometry['type']
                                                ?? null,
                                            [
                                                'Polygon',
                                                'MultiPolygon',
                                            ],
                                            true
                                        ),

                                    'has_internet_connection' =>
                                        $inst->has_internet_connection,
                                    'bandwidth_allocation_mbps' =>
                                        $inst->bandwidth_allocation_mbps,
                                    'download_speed_mbps' =>
                                        $inst->download_speed_mbps,

                                    'gap_score' =>
                                        is_numeric($gapScore)
                                            ? round(
                                                (float) $gapScore,
                                                2
                                            )
                                            : null,
                                    'assessment_status' =>
                                        $analytics?->risk_level
                                        ?? 'insufficient_data',
                                    'infrastructure_deficit_score' =>
                                        $analytics?->infrastructure_deficit_score,
                                    'connectivity_divide_score' =>
                                        $analytics?->connectivity_divide_score,
                                    'technological_obsolescence_score' =>
                                        $analytics?->technological_obsolescence_score,

                                    'infrastructure_narrative' =>
                                        $analytics?->infrastructure_narrative,
                                    'policy_recommendation' =>
                                        $analytics?->policy_recommendation,
                                    'analysis_method' =>
                                        $analytics?->recommendations_detail['method']
                                        ?? null,
                                ],
                            ];
                        }
                    )
                    ->values()
                    ->all();

                return [
                    'type' => 'FeatureCollection',
                    'features' => $features,
                ];
            }
        );

        return response()
            ->json($payload)
            ->header(
                'Cache-Control',
                'private, max-age=30, stale-while-revalidate=15'
            );
    }
}
