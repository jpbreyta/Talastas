<?php

namespace App\Http\Controllers;

use App\Helpers\GapScoreCalculator;
use App\Models\Barangay;
use App\Models\Institution;

class IctMappingController extends Controller
{
    public function index()
    {
        $institutions = Institution::all();

        $heatPoints = $institutions->map(function ($inst) {
            $gapScore = GapScoreCalculator::computeScore($inst);

            return [
                $inst->latitude,
                $inst->longitude,
                $gapScore,
            ];
        });

        return view('map', compact('heatPoints'));
    }

    public function getInstitutionsGeoJSON()
    {
        $institutions = Institution::with('aiGapAnalytics')->get();

        $features = $institutions->map(function ($inst) {
            $gapScore = $inst->aiGapAnalytics
                ? $inst->aiGapAnalytics->gap_score
                : GapScoreCalculator::computeScore($inst);

            // Compute data completeness for quality indicator
            $completeness = GapScoreCalculator::computeDataCompleteness($inst);

            // Determine quality level from completeness
            $qualityLevel = $completeness >= 80 ? 'good' : ($completeness >= 50 ? 'partial' : 'incomplete');

            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $inst->longitude, (float) $inst->latitude],
                ],
                'properties' => [
                    'id' => $inst->id,
                    'name' => $inst->name,
                    'type' => $inst->type,
                    'sector_type' => $inst->sector_type,
                    'barangay' => $inst->barangay_name,
                    'latitude' => (float) $inst->latitude,
                    'longitude' => (float) $inst->longitude,
                    'internet_speed' => $inst->internet_speed,
                    'has_lab' => $inst->has_computer_lab,
                    'computers' => $inst->number_of_computers,
                    'has_smart_tv' => $inst->has_smart_tv,
                    'has_pa' => $inst->has_public_address_system,
                    'student_population' => $inst->student_population,
                    'is_pilot' => $inst->is_pilot_site,
                    'requires_audit' => $inst->requires_audit,
                    'data_completeness' => $completeness,
                    'data_quality' => $qualityLevel,
                    'last_audited' => $inst->last_audited_at?->format('Y-m-d'),
                    'gap_score' => round($gapScore, 2),
                    'gap_level' => GapScoreCalculator::getLevel($gapScore),
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function getBarangaysGeoJSON()
    {
        // Load barangay boundaries from static GeoJSON file
        $geojsonPath = public_path('data/lipa-city-barangays.geojson');

        if (file_exists($geojsonPath)) {
            $geojsonData = json_decode(file_get_contents($geojsonPath), true);

            return response()->json($geojsonData);
        }

        // Fallback: Load barangay boundaries from database (GeoJSON format)
        $barangays = Barangay::all();

        if ($barangays->isEmpty()) {
            // Placeholder points for each distinct barangay from institutions
            $barangayNames = Institution::distinct('barangay_name')->pluck('barangay_name');

            $features = $barangayNames->map(function ($name) {
                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [121.1644, 13.9413], // Lipa City center
                    ],
                    'properties' => [
                        'name' => $name,
                        'type' => 'barangay',
                        'note' => 'Boundary data pending GIS import',
                    ],
                ];
            });

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features,
                'message' => 'Barangay polygon boundaries not yet configured. Upload GIS data to barangays table.',
            ]);
        }

        $features = $barangays->map(function ($barangay) {
            return [
                'type' => 'Feature',
                'geometry' => $barangay->geometry ?: [
                    'type' => 'Point',
                    'coordinates' => [121.1644, 13.9413],
                ],
                'properties' => [
                    'name' => $barangay->name,
                    'code' => $barangay->code,
                    'population' => $barangay->population,
                    'institutions' => $barangay->total_institutions,
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}
