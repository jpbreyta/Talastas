<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInstitutionAnalytics;
use App\Models\Barangay;
use App\Models\Institution;
use App\Models\InstitutionAnalytics;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        /*
        |--------------------------------------------------------------------------
        | Institution Analytics
        |--------------------------------------------------------------------------
        */

        $analytics = InstitutionAnalytics::query()
            ->with([
                'institution:id,name,type,barangay_id,is_active',
                'institution.barangay:id,name',
            ])
            ->whereHas('institution', function ($query) {
                $query->where('is_active', true);
            })
            ->orderByRaw(
                'CASE WHEN gap_score IS NULL THEN 1 ELSE 0 END'
            )
            ->orderByDesc('gap_score')
            ->paginate(15);

        /*
        |--------------------------------------------------------------------------
        | Executive Summary
        |--------------------------------------------------------------------------
        */

        $institutionCount = Institution::query()
            ->where('is_active', true)
            ->count();

        $analyzedInstitutionCount = InstitutionAnalytics::query()
            ->whereNotNull('gap_score')
            ->whereHas('institution', function ($query) {
                $query->where('is_active', true);
            })
            ->count();

        $averageGapScore = $this->averageAnalyticsField(
            'gap_score'
        );

        $criticalAreas = $this->riskCount('critical');

        $needsAttention = $this->riskCount('warning');

        $adequateAreas = $this->riskCount('adequate');

        $insufficientData = $this->riskCount(
            'insufficient_data'
        );

        /*
        |--------------------------------------------------------------------------
        | Gap Category Breakdown
        |--------------------------------------------------------------------------
        */

        $infrastructureDeficit = $this->averageAnalyticsField(
            'infrastructure_deficit_score'
        );

        $connectivityDivide = $this->averageAnalyticsField(
            'connectivity_divide_score'
        );

        $technologicalObsolescence =
            $this->averageAnalyticsField(
                'technological_obsolescence_score'
            );

        $averageCompleteness = $this->averageAnalyticsField(
            'data_completeness_score'
        );

        /*
        |--------------------------------------------------------------------------
        | Barangay Priority Ranking
        |--------------------------------------------------------------------------
        */

        $barangayScores = Institution::query()
            ->join(
                'institution_analytics',
                'institutions.id',
                '=',
                'institution_analytics.institution_id'
            )
            ->join(
                'barangays',
                'institutions.barangay_id',
                '=',
                'barangays.id'
            )
            ->where('institutions.is_active', true)
            ->whereNotNull(
                'institution_analytics.gap_score'
            )
            ->select([
                'barangays.id',
                'barangays.name',
            ])
            ->selectRaw(
                'AVG(
                    institution_analytics.gap_score
                ) AS average_gap_score'
            )
            ->selectRaw(
                'COUNT(
                    institutions.id
                ) AS institution_count'
            )
            ->groupBy(
                'barangays.id',
                'barangays.name'
            )
            ->orderByDesc('average_gap_score')
            ->get()
            ->map(function ($barangay) {
                $score = round(
                    (float) $barangay->average_gap_score,
                    2
                );

                return [
                    'id' => $barangay->id,
                    'name' => $barangay->name,
                    'gap_score' => $score,
                    'institution_count' =>
                        (int) $barangay->institution_count,
                    'risk_level' =>
                        $this->riskLevelFromScore($score),
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | ICT Infrastructure Availability
        |--------------------------------------------------------------------------
        */

        $infrastructureStatus = [
            'computer_laboratories' =>
                $this->institutionAvailabilityPercentage(
                    'has_computer_lab'
                ),

            'internet_connectivity' =>
                $this->internetAvailabilityPercentage(),

            'smart_tvs' =>
                $this->institutionAvailabilityPercentage(
                    'has_smart_tv'
                ),

            'projected_whiteboards' =>
                $this->institutionAvailabilityPercentage(
                    'has_projected_whiteboard'
                ),

            'public_address_systems' =>
                $this->institutionAvailabilityPercentage(
                    'has_public_address_system'
                ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Priority ICT Needs
        |--------------------------------------------------------------------------
        */

        $priorityNeeds = collect([
            [
                'label' => 'Improve internet connectivity',
                'count' => Institution::query()
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query
                            ->whereNull('internet_speed')
                            ->orWhere(
                                'internet_speed',
                                '<=',
                                0
                            );
                    })
                    ->count(),
            ],
            [
                'label' => 'Provide computer laboratories',
                'count' => Institution::query()
                    ->where('is_active', true)
                    ->where(
                        'has_computer_lab',
                        false
                    )
                    ->count(),
            ],
            [
                'label' => 'Replace or repair aging equipment',
                'count' => InstitutionAnalytics::query()
                    ->where(
                        'technological_obsolescence_score',
                        '>=',
                        50
                    )
                    ->whereHas(
                        'institution',
                        function ($query) {
                            $query->where(
                                'is_active',
                                true
                            );
                        }
                    )
                    ->count(),
            ],
            [
                'label' => 'Perform ICT maintenance',
                'count' => Institution::query()
                    ->where('is_active', true)
                    ->where(
                        'needs_maintenance',
                        true
                    )
                    ->count(),
            ],
            [
                'label' => 'Provide display technology',
                'count' => Institution::query()
                    ->where('is_active', true)
                    ->where(
                        'has_smart_tv',
                        false
                    )
                    ->where(
                        'has_projected_whiteboard',
                        false
                    )
                    ->count(),
            ],
        ])
            ->filter(function ($need) {
                return $need['count'] > 0;
            })
            ->sortByDesc('count')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        $barangays = Barangay::query()
            ->orderBy('name')
            ->pluck('name')
            ->values();

        return view('analytics', compact(
            'analytics',
            'institutionCount',
            'analyzedInstitutionCount',
            'averageGapScore',
            'criticalAreas',
            'needsAttention',
            'adequateAreas',
            'insufficientData',
            'infrastructureDeficit',
            'connectivityDivide',
            'technologicalObsolescence',
            'averageCompleteness',
            'barangayScores',
            'infrastructureStatus',
            'priorityNeeds',
            'barangays'
        ));
    }

    public function getAnalytics(): JsonResponse
    {
        $analytics = InstitutionAnalytics::query()
            ->with([
                'institution:id,name,type,barangay_id',
                'institution.barangay:id,name',
            ])
            ->whereHas('institution', function ($query) {
                $query->where('is_active', true);
            })
            ->orderByRaw(
                'CASE WHEN gap_score IS NULL THEN 1 ELSE 0 END'
            )
            ->orderByDesc('gap_score')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'summary' => [
                'average_gap_score' =>
                    $this->averageAnalyticsField(
                        'gap_score'
                    ),

                'infrastructure_deficit_score' =>
                    $this->averageAnalyticsField(
                        'infrastructure_deficit_score'
                    ),

                'connectivity_divide_score' =>
                    $this->averageAnalyticsField(
                        'connectivity_divide_score'
                    ),

                'technological_obsolescence_score' =>
                    $this->averageAnalyticsField(
                        'technological_obsolescence_score'
                    ),

                'data_completeness_score' =>
                    $this->averageAnalyticsField(
                        'data_completeness_score'
                    ),

                'institution_count' =>
                    Institution::query()
                        ->where('is_active', true)
                        ->count(),

                'critical' =>
                    $this->riskCount('critical'),

                'warning' =>
                    $this->riskCount('warning'),

                'adequate' =>
                    $this->riskCount('adequate'),

                'insufficient_data' =>
                    $this->riskCount(
                        'insufficient_data'
                    ),
            ],
        ]);
    }

    public function analyzeInstitution(
        Institution $institution
    ): JsonResponse {
        ProcessInstitutionAnalytics::dispatch(
            $institution->id
        )->afterCommit();

        return response()->json([
            'success' => true,
            'message' =>
                'Institution analysis was added to the queue.',
            'institution_id' => $institution->id,
        ]);
    }

    public function analyzeAll(): JsonResponse
    {
        $institutionIds = Institution::query()
            ->where('is_active', true)
            ->pluck('id');

        foreach ($institutionIds as $institutionId) {
            ProcessInstitutionAnalytics::dispatch(
                (int) $institutionId
            )->afterCommit();
        }

        return response()->json([
            'success' => true,
            'message' =>
                'All active institutions were added to the analytics queue.',
            'queued_count' => $institutionIds->count(),
        ]);
    }

    public function export(): StreamedResponse
    {
        $fileName = 'ict-gap-analytics-'
            . now()->format('Y-m-d-His')
            . '.csv';

        $analytics = InstitutionAnalytics::query()
            ->with([
                'institution:id,name,type,barangay_id',
                'institution.barangay:id,name',
            ])
            ->whereHas('institution', function ($query) {
                $query->where('is_active', true);
            })
            ->orderByDesc('gap_score')
            ->get();

        return Response::streamDownload(
            function () use ($analytics) {
                $file = fopen(
                    'php://output',
                    'w'
                );

                fputcsv($file, [
                    'Institution',
                    'Barangay',
                    'Type',
                    'Overall Gap Score',
                    'Risk Level',
                    'Infrastructure Deficit',
                    'Connectivity Divide',
                    'Technological Obsolescence',
                    'Data Completeness',
                    'Analysis',
                    'Recommendation',
                ]);

                foreach ($analytics as $analytic) {
                    fputcsv($file, [
                        $analytic->institution?->name,
                        $analytic->institution?->barangay?->name,
                        $analytic->institution?->type,
                        $analytic->gap_score,
                        $analytic->risk_level,
                        $analytic->infrastructure_deficit_score,
                        $analytic->connectivity_divide_score,
                        $analytic->technological_obsolescence_score,
                        $analytic->data_completeness_score,
                        $analytic->infrastructure_narrative,
                        $analytic->policy_recommendation,
                    ]);
                }

                fclose($file);
            },
            $fileName,
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
            ]
        );
    }

    private function averageAnalyticsField(
        string $field
    ): float {
        $average = InstitutionAnalytics::query()
            ->whereNotNull($field)
            ->whereHas('institution', function ($query) {
                $query->where('is_active', true);
            })
            ->avg($field);

        return round(
            (float) ($average ?? 0),
            2
        );
    }

    private function riskCount(
        string $riskLevel
    ): int {
        return InstitutionAnalytics::query()
            ->where('risk_level', $riskLevel)
            ->whereHas('institution', function ($query) {
                $query->where('is_active', true);
            })
            ->count();
    }

    private function institutionAvailabilityPercentage(
        string $field
    ): float {
        $total = Institution::query()
            ->where('is_active', true)
            ->count();

        if ($total === 0) {
            return 0;
        }

        $available = Institution::query()
            ->where('is_active', true)
            ->where($field, true)
            ->count();

        return round(
            ($available / $total) * 100,
            1
        );
    }

    private function internetAvailabilityPercentage(): float
    {
        $total = Institution::query()
            ->where('is_active', true)
            ->count();

        if ($total === 0) {
            return 0;
        }

        $connected = Institution::query()
            ->where('is_active', true)
            ->whereNotNull('internet_speed')
            ->where('internet_speed', '>', 0)
            ->count();

        return round(
            ($connected / $total) * 100,
            1
        );
    }

    private function riskLevelFromScore(
        float $score
    ): string {
        if ($score >= 80) {
            return 'critical';
        }

        if ($score >= 50) {
            return 'warning';
        }

        return 'adequate';
    }
}