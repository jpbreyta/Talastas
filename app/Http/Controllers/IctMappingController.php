<?php

namespace App\Http\Controllers;

use App\Helpers\GapScoreCalculator;
use App\Models\Institution;

class IctMappingController extends Controller
{
    public function index()
    {
        $institutions = Institution::all();

        $heatPoints = $institutions->map(function ($inst) {
            // Use precomputed values if available
            if (!$inst->institutionAnalytics || $inst->institutionAnalytics->gap_score === null) {
                $inst->updateAnalytics();
                $inst->load('institutionAnalytics');
            }

            $gapScore = $inst->institutionAnalytics->gap_score ?? 0;

            return [
                (float) $inst->latitude,
                (float) $inst->longitude,
                $gapScore,
            ];
        });

        return view('map', compact('heatPoints'));
    }

    public function getInstitutionsGeoJSON()
    {
        $institutions = Institution::with([
            'institutionAnalytics',
            'auditor'
        ])->get();

        $features = $institutions->map(function ($inst) {

            // Use precomputed values if available, otherwise compute and save
            if (!$inst->institutionAnalytics || $inst->institutionAnalytics->gap_score === null) {
                $inst->updateAnalytics();
                $inst->load('institutionAnalytics');
            }

            $gapScore = $inst->institutionAnalytics->gap_score ?? 0;
            $completeness = $inst->institutionAnalytics->data_completeness_score ?? $inst->data_completeness_score ?? 0;

            $qualityLevel = match (true) {
                $completeness >= 80 => 'good',
                $completeness >= 50 => 'partial',
                default => 'incomplete',
            };

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
                    'barangay' => $inst->barangay->name,

                    'latitude' => (float) $inst->latitude,
                    'longitude' => (float) $inst->longitude,

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
                    'gap_level' => GapScoreCalculator::getLevel($gapScore),

                    // Include recommendations for map display
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

    public function getBarangaysGeoJSON()
    {
        $barangays = \App\Models\Barangay::whereNotNull('geometry')->get();

        if ($barangays->isNotEmpty()) {
            $features = $barangays->map(function ($barangay) {
                return [
                    'type' => 'Feature',
                    'geometry' => json_decode($barangay->geometry, true),
                    'properties' => [
                        'id' => $barangay->id,
                        'code' => $barangay->code,
                        'name' => $barangay->name,
                        'district' => $barangay->district,
                        'population' => $barangay->population,
                        'total_institutions' => $barangay->total_institutions,
                    ],
                ];
            });

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features,
            ]);
        }

        // fallback using institution data
        $barangayNames = Institution::with('barangay')
            ->distinct()
            ->get()
            ->pluck('barangay.name');

        $features = $barangayNames->map(function ($name) {
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [121.1644, 13.9413],
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
            'message' => 'Barangay GIS data not found. Using fallback placeholders.',
        ]);
    }
}