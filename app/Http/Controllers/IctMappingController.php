<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\Institution;

class IctMappingController extends Controller
{
    public function index()
    {
        return view('map');
    }

    /**
     * Mobile/base GeoJSON endpoint aligned with the finalized ICT Gap baseline.
     */
    public function getInstitutionsGeoJSON()
    {
        $institutions = Institution::query()
            ->where('is_active', true)
            ->whereIn('type', ['School', 'Barangay Hall'])
            ->with([
                'institutionAnalytics',
                'barangay',
            ])
            ->get();

        $features = $institutions->map(
            function (Institution $inst): array {
                $analytics = $inst->institutionAnalytics;
                $gapScore = $analytics?->gap_score;
                $geometry = $inst->location_geojson;

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
                        'barangay' =>
                            $inst->barangay?->name
                            ?? 'Unknown',

                        'latitude' => (float) $inst->latitude,
                        'longitude' => (float) $inst->longitude,
                        'location_geojson' => $geometry,
                        'has_location_boundary' => in_array(
                            $geometry['type'] ?? null,
                            ['Polygon', 'MultiPolygon'],
                            true
                        ),

                        'has_internet_connection' =>
                            $inst->has_internet_connection,
                        'bandwidth_allocation_mbps' =>
                            $inst->bandwidth_allocation_mbps,
                        'download_speed_mbps' =>
                            $inst->download_speed_mbps,

                        'gap_score' => is_numeric($gapScore)
                            ? round((float) $gapScore, 2)
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
        )->values();

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function getBarangaysGeoJSON()
    {
        $barangays = Barangay::whereNotNull('geometry')->get();

        if ($barangays->isNotEmpty()) {
            $features = $barangays->map(
                function ($barangay): array {
                    return [
                        'type' => 'Feature',
                        'geometry' => $barangay->geometry,
                        'properties' => [
                            'id' => $barangay->id,
                            'code' => $barangay->code,
                            'name' => $barangay->name,
                            'district' => $barangay->district,
                            'population' => $barangay->population,
                            'total_institutions' =>
                                $barangay->total_institutions,
                        ],
                    ];
                }
            );

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features,
            ]);
        }

        $barangayNames = Institution::query()
            ->whereIn('type', ['School', 'Barangay Hall'])
            ->with('barangay')
            ->distinct()
            ->get()
            ->pluck('barangay.name')
            ->filter()
            ->unique()
            ->values();

        $features = $barangayNames->map(
            function ($name): array {
                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [121.1644, 13.9413],
                    ],
                    'properties' => [
                        'name' => $name,
                        'type' => 'barangay',
                        'note' =>
                            'Boundary data pending GIS import',
                    ],
                ];
            }
        );

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'message' =>
                'Barangay GIS data not found. Using fallback placeholders.',
        ]);
    }
}
