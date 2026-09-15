<?php

namespace App\Http\Controllers;

use App\Models\Institution;

class PortalMappingController extends IctMappingController
{
    public function getInstitutionsGeoJSON()
    {
        $request = request();

        $query = Institution::query()->with([
            'institutionAnalytics',
            'auditor',
            'barangay',
        ]);

        $user = $request->user();

        if ($user->isEditor()) {
            $query->whereHas(
                'assignedUsers',
                fn ($assigned) => $assigned->whereKey($user->id)
            );
        }

        $institutions = $query->get();

        $features = $institutions->map(function ($inst) {
            $gapScore = $inst->institutionAnalytics->gap_score ?? 0;
            $completeness = $inst->institutionAnalytics->data_completeness_score
                ?? $inst->data_completeness_score
                ?? 0;

            $qualityLevel = match (true) {
                $completeness >= 80 => 'good',
                $completeness >= 50 => 'partial',
                default => 'incomplete',
            };

            $gapLevel = match (true) {
                $gapScore >= 75 => 'high_gap',
                $gapScore >= 40 => 'moderate_gap',
                default => 'low_gap',
            };

            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float) ($inst->longitude ?? 0),
                        (float) ($inst->latitude ?? 0),
                    ],
                ],
                'properties' => [
                    'id' => $inst->id,
                    'name' => $inst->name,
                    'type' => $inst->type,
                    'barangay' => $inst->barangay?->name ?? 'Unknown',
                    'latitude' => (float) $inst->latitude,
                    'longitude' => (float) $inst->longitude,
                    'location_geojson' => $inst->location_geojson,
                    'location_bounding_box' => $inst->location_bounding_box,
                    'has_location_boundary' => in_array(
                        $inst->location_geojson['type'] ?? null,
                        ['Polygon', 'MultiPolygon'],
                        true
                    ),
                    'nominatim_place_id' => $inst->nominatim_place_id,
                    'osm_type' => $inst->osm_type,
                    'osm_id' => $inst->osm_id,
                    'internet_speed' => $inst->internet_speed,
                    'isp_provider' => $inst->isp_provider,
                    'has_computer_lab' => (bool) $inst->has_computer_lab,
                    'number_of_computers' => $inst->number_of_computers,
                    'has_projected_whiteboard' => (bool) $inst->has_projected_whiteboard,
                    'has_smart_tv' => (bool) $inst->has_smart_tv,
                    'has_public_address_system' => (bool) $inst->has_public_address_system,
                    'student_population' => $inst->student_population,
                    'staff_population' => $inst->staff_population,
                    'needs_maintenance' => (bool) $inst->needs_maintenance,
                    'is_pilot_site' => (bool) $inst->is_pilot_site,
                    'is_active' => (bool) $inst->is_active,
                    'requires_audit' => (bool) $inst->requires_audit,
                    'data_completeness' => $completeness,
                    'data_quality' => $qualityLevel,
                    'last_audited' => $inst->last_audited_at?->format('Y-m-d'),
                    'audited_by' => $inst->auditor?->name,
                    'audit_notes' => $inst->audit_notes,
                    'gap_score' => round($gapScore, 2),
                    'gap_level' => $gapLevel,
                    'infrastructure_narrative' => $inst->institutionAnalytics->infrastructure_narrative ?? null,
                    'policy_recommendation' => $inst->institutionAnalytics->policy_recommendation ?? null,
                    'analysis_method' => $inst->institutionAnalytics->recommendations_detail['method'] ?? null,
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}
