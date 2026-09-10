<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInstitutionAnalytics;
use App\Models\Barangay;
use App\Models\Institution;
use App\Models\InstitutionAnalytics;
use App\Services\InstitutionInventorySummaryService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $analyticsQuery = $this->activeAnalyticsQuery();

        $this->applyIndexFilters(
            $analyticsQuery,
            $request
        );

        $analytics = $analyticsQuery
            ->orderByRaw(
                'CASE WHEN gap_score IS NULL THEN 1 ELSE 0 END'
            )
            ->orderByDesc('gap_score')
            ->paginate(15)
            ->withQueryString();

        $summary = $this->summaryData();

        $institutionCount =
            $summary['institution_count'];

        $analyzedInstitutionCount =
            $summary['analyzed_institution_count'];

        $pendingAssessments =
            $summary['pending_assessment_count'];

        $analysisCoveragePercentage =
            $summary['analysis_coverage_percentage'];

        $averageGapScore =
            $summary['average_gap_score'];

        $criticalAreas =
            $summary['critical'];

        $needsAttention =
            $summary['warning'];

        $adequateAreas =
            $summary['adequate'];

        $insufficientData =
            $summary['insufficient_data'];

        $infrastructureDeficit =
            $summary['infrastructure_deficit_score'];

        $connectivityDivide =
            $summary['connectivity_divide_score'];

        $technologicalObsolescence =
            $summary['technological_obsolescence_score'];

        $averageCompleteness =
            $summary['data_completeness_score'];

        $latestAnalysisDate =
            $summary['latest_analysis_date'];

        $overallRiskLevel =
            $this->riskLevelFromNullableScore(
                $averageGapScore
            );

        $primaryCityConcern =
            $this->primaryCityConcern(
                $summary
            );

        $barangayScores =
            $this->barangayScores();

        $barangays = Barangay::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        return view('analytics', compact(
            'analytics',
            'institutionCount',
            'analyzedInstitutionCount',
            'pendingAssessments',
            'analysisCoveragePercentage',
            'averageGapScore',
            'criticalAreas',
            'needsAttention',
            'adequateAreas',
            'insufficientData',
            'infrastructureDeficit',
            'connectivityDivide',
            'technologicalObsolescence',
            'averageCompleteness',
            'latestAnalysisDate',
            'overallRiskLevel',
            'primaryCityConcern',
            'barangayScores',
            'barangays'
        ));
    }

    public function getAnalytics(): JsonResponse
    {
        $summary = $this->summaryData();

        $analytics = $this->activeAnalyticsQuery()
            ->orderByRaw(
                'CASE WHEN gap_score IS NULL THEN 1 ELSE 0 END'
            )
            ->orderByDesc('gap_score')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'summary' => $summary,

            /*
             * Keep these API fields for compatibility with any existing
             * clients even though the simplified Blade no longer displays
             * all of them at once.
             */
            'primary_city_concern' =>
                $this->primaryCityConcern(
                    $summary
                ),

            'barangay_scores' =>
                $this->barangayScores(),

            'infrastructure_status' =>
                $this->infrastructureStatus(),

            'priority_needs' =>
                $this->priorityNeeds(),

            'generated_at' =>
                now()->toIso8601String(),
        ]);
    }

    public function analyzeInstitution(
        Request $request,
        Institution $institution,
        InstitutionInventorySummaryService $summaryService
    ): JsonResponse|RedirectResponse {
        $institution->loadMissing(
            'ictInventory'
        );

        if ($institution->ictInventory->isEmpty()) {
            $message =
                'Add at least one inventory item before running infrastructure analytics.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'institution_id' =>
                        $institution->id,
                ], 422);
            }

            return redirect()
                ->route(
                    'institutions.show',
                    $institution
                )
                ->with(
                    'error',
                    $message
                );
        }

        $summaryService->sync(
            $institution
        );

        ProcessInstitutionAnalytics::dispatch(
            $institution->id
        )->afterCommit();

        $message =
            'Infrastructure analytics was added to the processing queue.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'institution_id' =>
                    $institution->id,
            ], 202);
        }

        return redirect()
            ->route(
                'institutions.show',
                $institution
            )
            ->with(
                'success',
                $message
            );
    }

    public function analyzeAll(): JsonResponse
    {
        $institutionIds = Institution::query()
            ->where(
                'is_active',
                true
            )
            ->pluck('id');

        foreach (
            $institutionIds
            as $institutionId
        ) {
            ProcessInstitutionAnalytics::dispatch(
                (int) $institutionId
            )->afterCommit();
        }

        return response()->json([
            'success' => true,
            'message' =>
                'All active institutions were added to the analytics queue.',
            'queued_count' =>
                $institutionIds->count(),
        ]);
    }

    public function export(): StreamedResponse
    {
        $fileName =
            'ict-gap-analytics-'
            . now()->format(
                'Y-m-d-His'
            )
            . '.csv';

        $analytics =
            $this->activeAnalyticsQuery()
                ->orderByRaw(
                    'CASE WHEN gap_score IS NULL THEN 1 ELSE 0 END'
                )
                ->orderByDesc(
                    'gap_score'
                )
                ->get();

        return Response::streamDownload(
            function () use (
                $analytics
            ): void {
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

                foreach (
                    $analytics
                    as $analytic
                ) {
                    fputcsv($file, [
                        $analytic
                            ->institution
                            ?->name,

                        $analytic
                            ->institution
                            ?->barangay
                            ?->name,

                        $analytic
                            ->institution
                            ?->type,

                        $analytic
                            ->gap_score,

                        $analytic
                            ->risk_level,

                        $analytic
                            ->infrastructure_deficit_score,

                        $analytic
                            ->connectivity_divide_score,

                        $analytic
                            ->technological_obsolescence_score,

                        $analytic
                            ->data_completeness_score,

                        $analytic
                            ->infrastructure_narrative,

                        $analytic
                            ->policy_recommendation,
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

    private function activeAnalyticsQuery(): Builder
    {
        return InstitutionAnalytics::query()
            ->with([
                'institution:id,name,type,barangay_id,is_active',
                'institution.barangay:id,name',
            ])
            ->whereHas(
                'institution',
                function (
                    Builder $query
                ): void {
                    $query->where(
                        'is_active',
                        true
                    );
                }
            );
    }

    private function applyIndexFilters(
        Builder $query,
        Request $request
    ): void {
        $search =
            trim(
                (string)
                    $request->query(
                        'q',
                        ''
                    )
            );

        $barangayId =
            $request->query(
                'barangay'
            );

        $type =
            trim(
                (string)
                    $request->query(
                        'type',
                        ''
                    )
            );

        $risk =
            trim(
                (string)
                    $request->query(
                        'risk',
                        ''
                    )
            );

        if ($search !== '') {
            $query->whereHas(
                'institution',
                function (
                    Builder $institutionQuery
                ) use (
                    $search
                ): void {
                    $institutionQuery
                        ->whereRaw(
                            'LOWER(name) LIKE ?',
                            [
                                '%'
                                . mb_strtolower(
                                    $search
                                )
                                . '%',
                            ]
                        );
                }
            );
        }

        if (
            is_numeric($barangayId)
            && (int) $barangayId > 0
        ) {
            $query->whereHas(
                'institution',
                function (
                    Builder $institutionQuery
                ) use (
                    $barangayId
                ): void {
                    $institutionQuery->where(
                        'barangay_id',
                        (int) $barangayId
                    );
                }
            );
        }

        $allowedTypes = [
            'School',
            'Barangay Hall',
            'Local Government Office',
        ];

        if (
            in_array(
                $type,
                $allowedTypes,
                true
            )
        ) {
            $query->whereHas(
                'institution',
                function (
                    Builder $institutionQuery
                ) use (
                    $type
                ): void {
                    $institutionQuery->where(
                        'type',
                        $type
                    );
                }
            );
        }

        $allowedRisks = [
            'critical',
            'warning',
            'adequate',
            'insufficient_data',
        ];

        if (
            in_array(
                $risk,
                $allowedRisks,
                true
            )
        ) {
            $query->where(
                'risk_level',
                $risk
            );
        }
    }

    private function summaryData(): array
    {
        $institutionCount =
            Institution::query()
                ->where(
                    'is_active',
                    true
                )
                ->count();

        /*
         * One aggregate query replaces the previous set of repeated AVG and
         * risk-count queries.
         */
        $aggregate =
            InstitutionAnalytics::query()
                ->whereHas(
                    'institution',
                    function (
                        Builder $query
                    ): void {
                        $query->where(
                            'is_active',
                            true
                        );
                    }
                )
                ->selectRaw(
                    'AVG(gap_score) AS average_gap_score'
                )
                ->selectRaw(
                    'AVG(infrastructure_deficit_score) AS infrastructure_deficit_score'
                )
                ->selectRaw(
                    'AVG(connectivity_divide_score) AS connectivity_divide_score'
                )
                ->selectRaw(
                    'AVG(technological_obsolescence_score) AS technological_obsolescence_score'
                )
                ->selectRaw(
                    'AVG(data_completeness_score) AS data_completeness_score'
                )
                ->selectRaw(
                    'SUM(CASE WHEN gap_score IS NOT NULL THEN 1 ELSE 0 END) AS analyzed_institution_count'
                )
                ->selectRaw(
                    "SUM(CASE WHEN risk_level = 'critical' THEN 1 ELSE 0 END) AS critical_count"
                )
                ->selectRaw(
                    "SUM(CASE WHEN risk_level = 'warning' THEN 1 ELSE 0 END) AS warning_count"
                )
                ->selectRaw(
                    "SUM(CASE WHEN risk_level = 'adequate' THEN 1 ELSE 0 END) AS adequate_count"
                )
                ->selectRaw(
                    "SUM(CASE WHEN risk_level = 'insufficient_data' THEN 1 ELSE 0 END) AS insufficient_data_count"
                )
                ->selectRaw(
                    'MAX(analysis_date) AS latest_analysis_date'
                )
                ->first();

        $analyzedInstitutionCount =
            (int) (
                $aggregate
                    ?->analyzed_institution_count
                ?? 0
            );

        $analysisCoveragePercentage =
            $institutionCount > 0
                ? round(
                    (
                        $analyzedInstitutionCount
                        / $institutionCount
                    ) * 100,
                    1
                )
                : 0.0;

        $latestAnalysisDate =
            $aggregate
                ?->latest_analysis_date;

        return [
            'average_gap_score' =>
                $this->nullableRounded(
                    $aggregate
                        ?->average_gap_score
                ),

            'infrastructure_deficit_score' =>
                $this->nullableRounded(
                    $aggregate
                        ?->infrastructure_deficit_score
                ),

            'connectivity_divide_score' =>
                $this->nullableRounded(
                    $aggregate
                        ?->connectivity_divide_score
                ),

            'technological_obsolescence_score' =>
                $this->nullableRounded(
                    $aggregate
                        ?->technological_obsolescence_score
                ),

            'data_completeness_score' =>
                $this->nullableRounded(
                    $aggregate
                        ?->data_completeness_score
                ),

            'institution_count' =>
                $institutionCount,

            'analyzed_institution_count' =>
                $analyzedInstitutionCount,

            'pending_assessment_count' =>
                max(
                    0,
                    $institutionCount
                    - $analyzedInstitutionCount
                ),

            'analysis_coverage_percentage' =>
                $analysisCoveragePercentage,

            'critical' =>
                (int) (
                    $aggregate
                        ?->critical_count
                    ?? 0
                ),

            'warning' =>
                (int) (
                    $aggregate
                        ?->warning_count
                    ?? 0
                ),

            'adequate' =>
                (int) (
                    $aggregate
                        ?->adequate_count
                    ?? 0
                ),

            'insufficient_data' =>
                (int) (
                    $aggregate
                        ?->insufficient_data_count
                    ?? 0
                ),

            'latest_analysis_date' =>
                $latestAnalysisDate
                    ? Carbon::parse(
                        $latestAnalysisDate
                    )->toIso8601String()
                    : null,
        ];
    }

    private function primaryCityConcern(
        array $summary
    ): ?array {
        $dimensions = [
            [
                'key' =>
                    'infrastructure',
                'label' =>
                    'Infrastructure Deficit',
                'score' =>
                    $summary[
                        'infrastructure_deficit_score'
                    ] ?? null,
            ],
            [
                'key' =>
                    'connectivity',
                'label' =>
                    'Connectivity Divide',
                'score' =>
                    $summary[
                        'connectivity_divide_score'
                    ] ?? null,
            ],
            [
                'key' =>
                    'obsolescence',
                'label' =>
                    'Technological Obsolescence',
                'score' =>
                    $summary[
                        'technological_obsolescence_score'
                    ] ?? null,
            ],
        ];

        $available =
            array_values(
                array_filter(
                    $dimensions,
                    fn (
                        array $dimension
                    ): bool =>
                        is_numeric(
                            $dimension[
                                'score'
                            ]
                        )
                )
            );

        if ($available === []) {
            return null;
        }

        usort(
            $available,
            fn (
                array $a,
                array $b
            ): int =>
                (float) $b['score']
                <=>
                (float) $a['score']
        );

        return $available[0];
    }

    private function barangayScores()
    {
        return Institution::query()
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
            ->where(
                'institutions.is_active',
                true
            )
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
            ->orderByDesc(
                'average_gap_score'
            )
            ->get()
            ->map(
                function (
                    $barangay
                ): array {
                    $score =
                        round(
                            (float)
                                $barangay
                                    ->average_gap_score,
                            2
                        );

                    return [
                        'id' =>
                            (int)
                                $barangay->id,

                        'name' =>
                            $barangay->name,

                        'gap_score' =>
                            $score,

                        'institution_count' =>
                            (int)
                                $barangay
                                    ->institution_count,

                        'risk_level' =>
                            $this
                                ->riskLevelFromScore(
                                    $score
                                ),
                    ];
                }
            )
            ->values();
    }

    /*
     * Retained for API compatibility. The simplified analytics page does not
     * display this section because it duplicated information already conveyed
     * by the three research gap dimensions.
     */
    private function infrastructureStatus(): array
    {
        return [
            'computer_laboratories' =>
                $this
                    ->institutionAvailabilityPercentage(
                        'has_computer_lab'
                    ),

            'internet_connectivity' =>
                $this
                    ->internetAvailabilityPercentage(),

            'smart_tvs' =>
                $this
                    ->institutionAvailabilityPercentage(
                        'has_smart_tv'
                    ),

            'projected_whiteboards' =>
                $this
                    ->institutionAvailabilityPercentage(
                        'has_projected_whiteboard'
                    ),

            'public_address_systems' =>
                $this
                    ->institutionAvailabilityPercentage(
                        'has_public_address_system'
                    ),
        ];
    }

    /*
     * Retained for API compatibility, but aligned with the actual research
     * dimensions instead of inferring interventions from missing fields.
     */
    private function priorityNeeds()
    {
        return collect([
            [
                'key' =>
                    'infrastructure',
                'label' =>
                    'Infrastructure Deficit',
                'count' =>
                    $this->analyticsDeficitCount(
                        'infrastructure_deficit_score'
                    ),
            ],
            [
                'key' =>
                    'connectivity',
                'label' =>
                    'Connectivity Divide',
                'count' =>
                    $this->analyticsDeficitCount(
                        'connectivity_divide_score'
                    ),
            ],
            [
                'key' =>
                    'obsolescence',
                'label' =>
                    'Technological Obsolescence',
                'count' =>
                    $this->analyticsDeficitCount(
                        'technological_obsolescence_score'
                    ),
            ],
        ])
            ->filter(
                fn (
                    array $need
                ): bool =>
                    $need['count'] > 0
            )
            ->sortByDesc(
                'count'
            )
            ->values();
    }

    private function analyticsDeficitCount(
        string $field
    ): int {
        return InstitutionAnalytics::query()
            ->where(
                $field,
                '>=',
                50
            )
            ->whereHas(
                'institution',
                function (
                    Builder $query
                ): void {
                    $query->where(
                        'is_active',
                        true
                    );
                }
            )
            ->count();
    }

    private function institutionAvailabilityPercentage(
        string $field
    ): float {
        $total =
            Institution::query()
                ->where(
                    'is_active',
                    true
                )
                ->count();

        if ($total === 0) {
            return 0.0;
        }

        $available =
            Institution::query()
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    $field,
                    true
                )
                ->count();

        return round(
            (
                $available
                / $total
            ) * 100,
            1
        );
    }

    private function internetAvailabilityPercentage(): float
    {
        $total =
            Institution::query()
                ->where(
                    'is_active',
                    true
                )
                ->count();

        if ($total === 0) {
            return 0.0;
        }

        $connected =
            Institution::query()
                ->where(
                    'is_active',
                    true
                )
                ->whereNotNull(
                    'internet_speed'
                )
                ->where(
                    'internet_speed',
                    '>',
                    0
                )
                ->count();

        return round(
            (
                $connected
                / $total
            ) * 100,
            1
        );
    }

    private function riskLevelFromNullableScore(
        mixed $score
    ): string {
        if (!is_numeric($score)) {
            return 'insufficient_data';
        }

        return $this->riskLevelFromScore(
            (float) $score
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

    private function nullableRounded(
        mixed $value
    ): ?float {
        if (!is_numeric($value)) {
            return null;
        }

        return round(
            (float) $value,
            2
        );
    }
}
