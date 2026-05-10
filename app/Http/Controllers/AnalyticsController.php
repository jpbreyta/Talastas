<?php

namespace App\Http\Controllers;

use App\Helpers\GapScoreCalculator;
use App\Models\IctInventory;
use App\Models\Institution;
use App\Models\InstitutionAnalytics;

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

        // Scope: Data integrity monitoring — count institutions with missing critical fields
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

        // Average data completeness across all institutions
        $allInstitutions = Institution::all();
        $totalCompleteness = 0;
        foreach ($allInstitutions as $inst) {
            $totalCompleteness += GapScoreCalculator::computeDataCompleteness($inst);
        }
        $averageCompleteness = $allInstitutions->count() > 0 ? $totalCompleteness / $allInstitutions->count() : 0;

        // Objective 3.2: Barangay-level heat map data (aggregated gap scores)
        $barangayScores = Institution::join('institution_analytics', 'institutions.id', '=', 'institution_analytics.institution_id')
            ->select('barangay_name')
            ->selectRaw('AVG(institution_analytics.gap_score) as avg_gap_score')
            ->groupBy('barangay_name')
            ->orderBy('avg_gap_score', 'desc')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->barangay_name => round($item->avg_gap_score ?? 50, 1)];
            });

        // For filters (Objective 1.2 dynamic dashboard)
        $barangays = Institution::distinct('barangay_name')->pluck('barangay_name')->sort()->values();

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
        $analysis = $this->computeGapAnalysis($institution);

        $gapAnalytics = InstitutionAnalytics::updateOrCreate(
            ['institution_id' => $institution->id],
            [
                'gap_score' => $analysis['gap_score'],
                'infrastructure_narrative' => $analysis['infrastructure_narrative'],
                'policy_recommendation' => $analysis['policy_recommendation'],
                'recommendations_detail' => $analysis['recommendations_detail'],
                'analysis_date' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $gapAnalytics,
        ]);
    }

    public function analyzeAll()
    {
        $institutions = Institution::all();
        $results = [];

        foreach ($institutions as $institution) {
            $analysis = $this->computeGapAnalysis($institution);

            $gapAnalytics = InstitutionAnalytics::updateOrCreate(
                ['institution_id' => $institution->id],
                [
                    'gap_score' => $analysis['gap_score'],
                    'infrastructure_narrative' => $analysis['infrastructure_narrative'],
                    'policy_recommendation' => $analysis['policy_recommendation'],
                    'recommendations_detail' => $analysis['recommendations_detail'],
                    'analysis_date' => now(),
                ]
            );

            $results[] = [
                'institution' => $institution->name,
                'gap_score' => $analysis['gap_score'],
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Analysis complete for '.count($results).' institutions',
            'results' => $results,
        ]);
    }

    /**
     * Compute gap analysis based on standardized scoring algorithm
     * (Objective 3.1: Gap-scoring algorithm)
     */
    private function computeGapAnalysis(Institution $inst): array
    {
        $score = GapScoreCalculator::computeScore($inst);
        $computers = IctInventory::where('institution_id', $inst->id)
            ->where('category', 'computer')
            ->where('status', 'active')
            ->count();
        $ratio = $inst->student_population > 0 ? $computers / $inst->student_population : null;
        $riskLevel = $score >= 80 ? 'critical' : ($score >= 50 ? 'warning' : 'adequate');

        return [
            'gap_score' => $score,
            'infrastructure_narrative' => $this->buildInfrastructureNarrative($inst, $score),
            'policy_recommendation' => $this->generatePolicyRecommendations($inst, $score),
            'recommendations_detail' => [
                'factors' => $this->getScoreFactors($inst),
                'method' => 'heuristic_algorithm_v1',
                'computed_at' => now()->toISOString(),
            ],
            'asset_to_user_ratio' => $ratio,
            'risk_level' => $riskLevel,
        ];
    }

    /**
     * Get individual scoring factors for detailed breakdown
     */
    private function getScoreFactors(Institution $inst): array
    {
        $factors = [];

        // Internet scoring
        if (! $inst->internet_speed || $inst->internet_speed < 25) {
            $factors['internet'] = 'critical';
        } elseif ($inst->internet_speed < 50) {
            $factors['internet'] = 'moderate';
        } else {
            $factors['internet'] = 'adequate';
        }

        $factors['computer_lab'] = $inst->has_computer_lab ? 'available' : 'missing';
        $factors['smart_tv'] = $inst->has_smart_tv ? 'available' : 'missing';
        $factors['projector'] = $inst->has_projected_whiteboard ? 'available' : 'missing';
        $factors['pa_system'] = $inst->has_public_address_system ? 'available' : 'missing';
        $factors['maintenance'] = $inst->needs_maintenance ? 'required' : 'good';

        return $factors;
    }

    /**
     * Build narrative summary based on gap factors
     */
    private function buildInfrastructureNarrative(Institution $inst, float $score): string
    {
        $gaps = [];

        if (! $inst->internet_speed || $inst->internet_speed < 25) {
            $gaps[] = 'insufficient internet connectivity (<25 Mbps)';
        }

        if (! $inst->has_computer_lab) {
            $gaps[] = 'no dedicated computer laboratory';
        } elseif (($inst->number_of_computers ?? 0) < 20) {
            $gaps[] = 'insufficient computer units for student population';
        }

        if (! $inst->has_smart_tv) {
            $gaps[] = 'lack of interactive display technology';
        }

        if (! $inst->has_public_address_system) {
            $gaps[] = 'no emergency broadcast system';
        }

        if ($inst->needs_maintenance) {
            $gaps[] = 'existing equipment requires maintenance';
        }

        if (empty($gaps)) {
            return 'Infrastructure adequately equipped with minimal gaps identified.';
        }

        $sentence = "Primary ICT deficiencies at {$inst->name}: ";
        $count = count($gaps);
        $i = 0;
        foreach ($gaps as $gap) {
            $sentence .= $gap;
            $i++;
            if ($i < $count - 1) {
                $sentence .= ', ';
            } elseif ($i == $count - 1) {
                $sentence .= ', and ';
            }
        }
        $sentence .= '.';

        return $sentence;
    }

    /**
     * Generate policy-ready recommendations (Objective 3.3)
     */
    private function generatePolicyRecommendations(Institution $inst, float $score): string
    {
        $recs = [];

        if ($score >= 70) {
            $recs[] = 'URGENT: Allocate budget for internet infrastructure upgrade to minimum 50 Mbps';
            $recs[] = 'Priority action: Establish computer laboratory with at least 20 functional units';
        } elseif ($score >= 40) {
            $recs[] = 'ENHANCE: Increase internet bandwidth to 25-50 Mbps range';
            $recs[] = 'EXPAND: Add 10-15 computer units to improve student access ratio';
        } else {
            $recs[] = 'MAINTAIN: Continue routine maintenance while planning incremental upgrades';
        }

        if (! $inst->has_smart_tv) {
            $recs[] = 'EQUIP: Procure smart TV displays for digital learning modules';
        }

        if (! $inst->has_public_address_system) {
            $recs[] = 'INSTALL: Campus-wide public address and emergency alert system';
        }

        if ($inst->needs_maintenance) {
            $recs[] = 'SERVICE: Conduct preventive maintenance on existing ICT equipment';
        }

        return implode('. ', $recs).'.';
    }
}
