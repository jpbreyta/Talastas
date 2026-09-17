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
            'map.institutions.geojson.v2.' . $scopeKey,
            now()->addSeconds(45),
            function () use ($user): array {
                $query = Institution::query()
                    ->select([
                        'id',
                        'name',
                        'type',
                        'barangay_id',
                        'latitude',
                        'longitude',
                        'location_geojson',
                        'internet_speed',
                        'has_computer_lab',
                        'number_of_computers',
                        'is_pilot_site',
                        'requires_audit',
                        'data_completeness_score',
                        'last_audited_at',
                        'is_active',
                    ])
                    ->with([
                        'institutionAnalytics:id,institution_id,gap_score,data_completeness_score,infrastructure_narrative,policy_recommendation,recommendations_detail',
                        'barangay:id,name',
                    ]);

                if ($user->isEditor()) {
                    $query->whereHas(
                        'assignedUsers',
                        fn ($assigned) => $assigned->whereKey($user->id)
                    );
                }

                $features = $query
                    ->where('is_active', true)
                    ->get()
                    ->map(function (Institution $inst): array {
                        $analytics = $inst->institutionAnalytics;
                        $gapScore = $analytics?->gap_score;
                        $completeness = $analytics?->data_completeness_score
                            ?? $inst->data_completeness_score
                            ?? 0;

                        $qualityLevel = match (true) {
                            $completeness >= 80 => 'good',
                            $completeness >= 50 => 'partial',
                            default => 'incomplete',
                        };

                        $gapLevel = match (true) {
                            ! is_numeric($gapScore) => 'insufficient_data',
                            $gapScore >= 75 => 'high_gap',
                            $gapScore >= 40 => 'moderate_gap',
                            default => 'low_gap',
                        };

                        $geometry = $inst->location_geojson;

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
                                'barangay' => $inst->barangay?->name ?? 'Unknown',
                                'latitude' => (float) $inst->latitude,
                                'longitude' => (float) $inst->longitude,
                                'location_geojson' => $geometry,
                                'has_location_boundary' => in_array(
                                    $geometry['type'] ?? null,
                                    ['Polygon', 'MultiPolygon'],
                                    true
                                ),
                                'internet_speed' => $inst->internet_speed,
                                'has_computer_lab' => (bool) $inst->has_computer_lab,
                                'number_of_computers' => (int) $inst->number_of_computers,
                                'is_pilot' => (bool) $inst->is_pilot_site,
                                'requires_audit' => (bool) $inst->requires_audit,
                                'data_completeness' => (float) $completeness,
                                'data_quality' => $qualityLevel,
                                'last_audited' => $inst->last_audited_at?->format('Y-m-d'),
                                'gap_score' => is_numeric($gapScore)
                                    ? round((float) $gapScore, 2)
                                    : null,
                                'gap_level' => $gapLevel,
                                'infrastructure_narrative' => $analytics?->infrastructure_narrative,
                                'policy_recommendation' => $analytics?->policy_recommendation,
                                'analysis_method' => $analytics?->recommendations_detail['method'] ?? null,
                            ],
                        ];
                    })
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
            ->header('Cache-Control', 'private, max-age=30, stale-while-revalidate=15');
    }
}
