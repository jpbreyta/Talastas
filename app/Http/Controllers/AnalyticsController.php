<?php

namespace App\Http\Controllers;

use App\Models\IctInventory;
use App\Models\Institution;
use App\Models\InstitutionAnalytics;
use App\Jobs\ProcessInstitutionAnalytics; // <-- Added our new Job

class AnalyticsController extends Controller
{
    public function index()
    {
        $analytics = InstitutionAnalytics::with('institution')
            ->orderBy('gap_score', 'desc')
            ->paginate(20);

        $averageGapScore = InstitutionAnalytics::avg('gap_score');
        $institutionCount = Institution::count();
        $criticalAreas = InstitutionAnalytics::where('gap_score', '>=', 80)->count();
        $needsAttention = InstitutionAnalytics::where('gap_score', '>=', 50)->where('gap_score', '<', 80)->count();

        $missingCritical = Institution::where(function ($q) {
            $q->whereNull('internet_speed')
                ->orWhereNull('has_computer_lab')
                ->orWhereNull('has_smart_tv')
                ->orWhereNull('has_public_address_system');
        })
            ->where('is_active', true)
            ->count();

        $needsAudit = Institution::where('requires_audit', true)->where('is_active', true)->count();
        $pilotSites = Institution::where('is_pilot_site', true)->where('is_active', true)->count();

        // CHANGED: Now using the method we just added to the Institution model
        $allInstitutions = Institution::all();
        $totalCompleteness = 0;
        foreach ($allInstitutions as $inst) {
            $totalCompleteness += $inst->computeDataCompleteness();
        }
        $averageCompleteness = $allInstitutions->count() > 0 ? $totalCompleteness / $allInstitutions->count() : 0;

        $barangayScores = Institution::join('institution_analytics', 'institutions.id', '=', 'institution_analytics.institution_id')
            ->join('barangays', 'institutions.barangay_id', '=', 'barangays.id')
            ->select('barangays.name')
            ->selectRaw('AVG(institution_analytics.gap_score) as avg_gap_score')
            ->groupBy('barangays.name')
            ->orderBy('avg_gap_score', 'desc')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->name => round($item->avg_gap_score ?? 50, 1)];
            });

        $barangays = \App\Models\Barangay::orderBy('name')->pluck('name')->values();

        return view('analytics', compact(
            'analytics',
            'averageGapScore',
            'institutionCount',
            'criticalAreas',
            'needsAttention',
            'missingCritical',
            'needsAudit',
            'pilotSites',
            'averageCompleteness',
            'barangayScores',
            'barangays'
        ));
    }

    public function getAnalytics()
    {
        $analytics = InstitutionAnalytics::with('institution')
            ->orderBy('gap_score', 'desc')
            ->get();

        $averageGapScore = InstitutionAnalytics::avg('gap_score');
        $institutionCount = Institution::count();
        $criticalAreas = InstitutionAnalytics::where('gap_score', '>=', 80)->count();

        return response()->json([
            'analytics' => $analytics,
            'average_gap_score' => $averageGapScore,
            'institution_count' => $institutionCount,
            'critical_areas' => $criticalAreas,
        ]);
    }

    public function analyzeInstitution(Institution $institution)
    {
        // CHANGED: Dispatch the job to the background queue instead of doing PHP math
        ProcessInstitutionAnalytics::dispatch($institution);

        return response()->json([
            'success' => true,
            'message' => 'Analysis queued for background processing.',
        ]);
    }

    public function analyzeAll()
    {
        $institutions = Institution::all();
        
        // CHANGED: Loop through and dispatch jobs to the background queue
        foreach ($institutions as $institution) {
            ProcessInstitutionAnalytics::dispatch($institution);
        }

        return response()->json([
            'success' => true,
            'message' => 'Analysis queued for '. $institutions->count() .' institutions. Results will update shortly.',
        ]);
    }
}