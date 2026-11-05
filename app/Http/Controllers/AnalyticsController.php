<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInstitutionAnalytics;
use App\Models\Barangay;
use App\Models\Institution;
use App\Models\InstitutionAnalytics;
use App\Services\AnalyticsPrescription;
use Dompdf\Dompdf;
use Dompdf\Options;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Editors see only institutions they are assigned to.
        // Viewers see city-wide aggregate only (no per-institution rows).
        $analyticsQuery = $this->activeAnalyticsQuery();

        if ($user->isEditor()) {
            $analyticsQuery->whereHas('institution', function (Builder $q) use ($user): void {
                $q->whereHas('assignedUsers', fn ($a) => $a->whereKey($user->id));
            });
        }

        $this->applyIndexFilters($analyticsQuery, $request);

        $prescription = app(AnalyticsPrescription::class)->build((clone $analyticsQuery)->get());

        if ($user->isViewer()) {
            $analyticsQuery->whereRaw('1 = 0');
        }

        $direction = $request->query('order') === 'asc' ? 'asc' : 'desc';
        if ($request->query('sort') === 'name') {
            $analyticsQuery->orderBy(
                Institution::query()->select('name')->whereColumn('institutions.id', 'institution_analytics.institution_id'),
                $direction
            );
        } else {
            $analyticsQuery->orderByRaw('CASE WHEN gap_score IS NULL THEN 1 ELSE 0 END')->orderBy('gap_score', $direction);
        }

        $analytics = $analyticsQuery
            ->orderBy('institution_analytics.id')
            ->paginate(15)
            ->withQueryString();

        $summary = $this->summaryData($request);

        $averageGapScore = $summary['average_gap_score'];

        $infrastructureDeficit = $summary['infrastructure_deficit_score'];
        $connectivityDivide = $summary['connectivity_divide_score'];
        $technologicalObsolescence = $summary['technological_obsolescence_score'];
        $latestAnalysisDate = $summary['latest_analysis_date'];

        $primaryCityConcern = $this->primaryCityConcern($summary);
        $barangayScores = $this->barangayScores($request);

        $barangays = Barangay::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('analytics', compact(
            'analytics',
            'averageGapScore',
            'infrastructureDeficit',
            'connectivityDivide',
            'technologicalObsolescence',
            'latestAnalysisDate',
            'primaryCityConcern',
            'barangayScores',
            'barangays',
            'prescription'
        ));
    }

    public function getAnalytics(Request $request): JsonResponse
    {
        $summary = $this->summaryData($request);

        $query = $this->activeAnalyticsQuery();
        if ($request->user()->isEditor()) {
            $query->whereHas('institution.assignedUsers', fn ($q) => $q->whereKey($request->user()->id));
        }
        $this->applyIndexFilters($query, $request);
        $prescription = app(AnalyticsPrescription::class)->build((clone $query)->get());
        if ($request->user()->isViewer()) {
            $query->whereRaw('1 = 0');
        }
        $analytics = $query
            ->orderByRaw('CASE WHEN gap_score IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('gap_score')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'summary' => $summary,
            'prescription' => $prescription,
            'primary_city_concern' => $this->primaryCityConcern($summary),
            'barangay_scores' => $this->barangayScores($request),
            'priority_needs' => $this->priorityNeeds(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function analyzeInstitution(
        Request $request,
        Institution $institution
    ): JsonResponse|RedirectResponse {
        if (!in_array($institution->type, ['School', 'Barangay Hall'], true)) {
            $message = 'This institution type is outside the approved TALASTAS capstone scope.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : redirect()->route('institutions.show', $institution)->with('error', $message);
        }

        $request->validate(['include_software' => ['sometimes', 'boolean']]);
        $includeSoftware = $request->boolean('include_software', true);
        $institution->loadMissing('ictInventory.component');

        $existingAnalytics = InstitutionAnalytics::query()
            ->where('institution_id', $institution->id)
            ->first();

        if (
            $existingAnalytics
            && in_array(
                $existingAnalytics->processing_status,
                ['queued', 'processing'],
                true
            )
            && filled($existingAnalytics->processing_token)
        ) {
            $processingToken =
                (string) $existingAnalytics->processing_token;

            $message =
                'ICT analytics is already processing.';

            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                    'message' => $message,
                    'institution_id' => $institution->id,
                    'processing_token' => $processingToken,
                    'stream_url' => route(
                        'institutions.analytics.stream',
                        [
                            'institution' => $institution->id,
                            'token' => $processingToken,
                        ]
                    ),
                    'state_url' => route(
                        'institutions.analytics.state',
                        [
                            'institution' => $institution->id,
                            'token' => $processingToken,
                        ]
                    ),
                ], 202)
                : redirect()
                    ->route(
                        'institutions.show',
                        $institution
                    )
                    ->with('success', $message);
        }

        $processingToken = (string) Str::uuid();

        InstitutionAnalytics::query()->updateOrCreate(
            ['institution_id' => $institution->id],
            [
                'processing_token' => $processingToken,
                'processing_status' => 'queued',
                'processing_stage' => 'queued',
                'processing_started_at' => now(),
                'processing_completed_at' => null,
                'processing_error' => null,
            ]
        );

        ProcessInstitutionAnalytics::dispatch(
            $institution->id,
            $processingToken,
            $includeSoftware
        )->afterCommit();

        $message =
            'ICT analytics is processing in the background.';

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'message' => $message,
                'institution_id' => $institution->id,
                'processing_token' => $processingToken,
                'stream_url' => route(
                    'institutions.analytics.stream',
                    [
                        'institution' => $institution->id,
                        'token' => $processingToken,
                    ]
                ),
                'state_url' => route(
                    'institutions.analytics.state',
                    [
                        'institution' => $institution->id,
                        'token' => $processingToken,
                    ]
                ),
            ], 202)
            : redirect()
                ->route('institutions.show', $institution)
                ->with('success', $message);
    }

    public function analyzeAll(): JsonResponse
    {
        $institutionIds = Institution::query()
            ->where('is_active', true)
            ->whereIn('type', ['School', 'Barangay Hall'])
            ->pluck('id');

        $queuedCount = 0;

        foreach ($institutionIds as $institutionId) {
            $existing = InstitutionAnalytics::query()
                ->where(
                    'institution_id',
                    (int) $institutionId
                )
                ->first();

            if (
                $existing
                && in_array(
                    $existing->processing_status,
                    ['queued', 'processing'],
                    true
                )
            ) {
                continue;
            }

            $processingToken = (string) Str::uuid();

            InstitutionAnalytics::query()->updateOrCreate(
                ['institution_id' => (int) $institutionId],
                [
                    'processing_token' => $processingToken,
                    'processing_status' => 'queued',
                    'processing_started_at' => now(),
                    'processing_completed_at' => null,
                    'processing_error' => null,
                ]
            );

            ProcessInstitutionAnalytics::dispatch(
                (int) $institutionId,
                $processingToken
            )->afterCommit();

            $queuedCount++;
        }

        return response()->json([
            'success' => true,
            'message' =>
                'Active public schools and barangay halls that were not already processing were added to the analytics queue.',
            'queued_count' => $queuedCount,
        ]);
    }

    public function institutionAnalyticsState(
        Request $request,
        Institution $institution
    ): JsonResponse {
        $request->validate(['token' => ['nullable', 'string', 'max:255']]);
        if (
            $request->user()->isEditor()
            && !$request->user()->canAccessInstitution(
                $institution->id
            )
        ) {
            abort(
                403,
                'You are not assigned to this institution.'
            );
        }

        $analytics = InstitutionAnalytics::query()
            ->where('institution_id', $institution->id)
            ->first();

        if (!$analytics) {
            return response()->json([
                'success' => true,
                'state' => [
                    'status' => 'idle',
                    'processing_token' => null,
                    'analysis_date' => null,
                    'error' => null,
                ],
            ]);
        }

        $requestedToken = trim(
            (string) $request->query('token', '')
        );

        $state = $this->analyticsProcessingState(
            $analytics,
            $requestedToken
        );

        return response()->json([
            'success' => true,
            'state' => $state,
        ]);
    }

    public function streamInstitutionAnalytics(
        Request $request,
        Institution $institution
    ): StreamedResponse {
        $request->validate(['token' => ['nullable', 'string', 'max:255']]);
        if (
            $request->user()->isEditor()
            && !$request->user()->canAccessInstitution(
                $institution->id
            )
        ) {
            abort(
                403,
                'You are not assigned to this institution.'
            );
        }

        $requestedToken = trim(
            (string) $request->query('token', '')
        );

        return response()->stream(
            function () use (
                $institution,
                $requestedToken
            ): void {
                @set_time_limit(0);
                echo "retry: 1500\n\n";

                $lastPayload = null;
                $startedAt = microtime(true);

                while (
                    connection_aborted() === 0
                    && (microtime(true) - $startedAt) < 55
                ) {
                    $analytics = InstitutionAnalytics::query()
                        ->where(
                            'institution_id',
                            $institution->id
                        )
                        ->first();

                    if (!$analytics) {
                        $payload = [
                            'status' => 'idle',
                            'processing_token' => null,
                            'analysis_date' => null,
                            'error' => null,
                        ];
                    } else {
                        $payload =
                            $this->analyticsProcessingState(
                                $analytics,
                                $requestedToken
                            );
                    }

                    $encoded = json_encode(
                        $payload,
                        JSON_UNESCAPED_SLASHES
                        | JSON_UNESCAPED_UNICODE
                    );

                    if ($encoded !== $lastPayload) {
                        echo "event: analytics.status\n";
                        echo "data: {$encoded}\n\n";

                        $lastPayload = $encoded;
                    } else {
                        echo ": keep-alive\n\n";
                    }

                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }

                    flush();

                    if (
                        in_array(
                            $payload['status'] ?? null,
                            [
                                'completed',
                                'failed',
                                'superseded',
                            ],
                            true
                        )
                    ) {
                        break;
                    }

                    usleep(1000000);
                }
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }

    private function analyticsProcessingState(
        InstitutionAnalytics $analytics,
        string $requestedToken = ''
    ): array {
        $currentToken =
            (string) ($analytics->processing_token ?? '');

        if (
            $requestedToken !== ''
            && $currentToken !== ''
            && !hash_equals(
                $currentToken,
                $requestedToken
            )
        ) {
            return [
                'status' => 'superseded',
                'processing_token' => $currentToken,
                'analysis_date' =>
                    $analytics->analysis_date?->toIso8601String(),
                'error' => null,
            ];
        }

        return [
            'status' =>
                $analytics->processing_status
                ?? (
                    $analytics->analysis_date
                        ? 'completed'
                        : 'idle'
                ),
            'processing_token' =>
                $analytics->processing_token,
            'stage' =>
                $analytics->processing_stage,
            'analysis_date' =>
                $analytics->analysis_date?->toIso8601String(),
            'started_at' =>
                $analytics->processing_started_at?->toIso8601String(),
            'completed_at' =>
                $analytics->processing_completed_at?->toIso8601String(),
            'assessment_status' => $analytics->risk_level,
            'assessment' => data_get($analytics->recommendations_detail, 'assessment'),
            'metrics' => [
                'gap_score' =>
                    $analytics->gap_score,
                'infrastructure_deficit_score' =>
                    $analytics->infrastructure_deficit_score,
                'connectivity_divide_score' =>
                    $analytics->connectivity_divide_score,
                'technological_obsolescence_score' =>
                    $analytics->technological_obsolescence_score,
            ],
            'error' =>
                $analytics->processing_status === 'failed'
                    ? 'Analytics processing failed. Review the application and queue logs, then try again.'
                    : null,
        ];
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isViewer(), 403);
        $fileName =
            'ict-gap-analytics-' . now()->format('Y-m-d-His') . '.csv';

        $analyticsQuery = $this->activeAnalyticsQuery()
            ->orderByRaw('CASE WHEN gap_score IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('gap_score');

        $this->applyIndexFilters($analyticsQuery, $request);

        $analytics = $analyticsQuery->get();

        return Response::streamDownload(
            function () use ($analytics): void {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'Institution',
                    'Barangay',
                    'Type',
                    'Overall ICT Gap (%)',
                    'Assessment Status',
                    'Infrastructure Deficit (%)',
                    'Connectivity Divide (%)',
                    'Technological Obsolescence Proxy (%)',
                    'Infrastructure Narrative',
                    'Policy Recommendation',
                    'Reported Internet Connections',
                    'Scored Internet Connections',
                    'Plans With Identifiable Speeds',
                    'Price-Only Plans',
                    'Ambiguous Plans',
                    'Unrecorded Plans',
                    'Connections Missing Details',
                    'Connectivity Unavailable Reasons',
                ]);

                foreach ($analytics as $analytic) {
                    $connectivity = data_get($analytic->recommendations_detail, 'connectivity_assessment', []);
                    fputcsv($file, array_map([$this, 'safeCsvValue'], [
                        $analytic->institution?->name,
                        $analytic->institution?->barangay?->name,
                        $analytic->institution?->type,
                        $analytic->gap_score,
                        $analytic->risk_level,
                        $analytic->infrastructure_deficit_score,
                        $analytic->connectivity_divide_score,
                        $analytic->technological_obsolescence_score,
                        $analytic->infrastructure_narrative,
                        $analytic->policy_recommendation,
                        $connectivity['reported_connection_count'] ?? null,
                        $connectivity['valid_connection_count'] ?? null,
                        data_get($connectivity, 'plan_counts.identifiable'),
                        data_get($connectivity, 'plan_counts.price_only'),
                        data_get($connectivity, 'plan_counts.ambiguous'),
                        data_get($connectivity, 'plan_counts.not_recorded'),
                        $connectivity['missing_connection_count'] ?? null,
                        implode(' ', array_map([\App\Services\InternetConnectivityAssessment::class, 'reasonLabel'], $connectivity['reason_codes'] ?? [])),
                    ]));
                }

                fclose($file);
            },
            $fileName,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    private function safeCsvValue(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[\x00-\x20]*[=+@-]/', $value)) {
            return "'".$value;
        }

        return $value;
    }

    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isViewer(), 403);
        $query = $this->activeAnalyticsQuery();
        $this->applyIndexFilters($query, $request);
        $records = $query->orderByDesc('gap_score')->get();
        $prescription = app(AnalyticsPrescription::class)->build($records);
        $summary = $this->summaryData();
        $generatedAt = now()->timezone('Asia/Manila');
        $preparedBy = $request->user()->name;
        $filters = $request->only(['q', 'barangay', 'type', 'risk', 'date_from', 'date_to']);
        $options = new Options(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'defaultFont' => 'DejaVu Sans', 'chroot' => public_path()]);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('reports.lgu-analytics', compact('records', 'prescription', 'summary', 'generatedAt', 'preparedBy', 'filters'))->render());
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        $pdf->getCanvas()->page_text(42, 805, 'TALASTAS | Lipa City ICT Planning | Page {PAGE_NUM} of {PAGE_COUNT}', 'Helvetica', 8, [0.35, 0.35, 0.35]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="lipa-city-ict-planning-'.$generatedAt->format('Y-m-d-His').'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function pythonHealth(): JsonResponse
    {
        $baseUrl = rtrim(
            (string) config(
                'services.python_analytics.url'
            ),
            '/'
        );

        $timeout = (int) config(
            'services.python_analytics.health_check_timeout',
            3
        );

        try {
            $response = Http::acceptJson()
                ->connectTimeout($timeout)
                ->timeout($timeout)
                ->get($baseUrl . '/health');

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'status' => 'unavailable',
                    'url' => $baseUrl,
                    'http_status' => $response->status(),
                    'message' => 'Python analytics service returned an error status.',
                ], 503);
            }

            $data = $response->json();

            return response()->json([
                'success' => true,
                'status' => 'available',
                'url' => $baseUrl,
                'http_status' => $response->status(),
                'data' => is_array($data) ? $data : ['raw' => $data],
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'status' => 'unreachable',
                'url' => $baseUrl,
                'message' => $exception->getMessage(),
            ], 503);
        }
    }

    private function activeAnalyticsQuery(): Builder
    {
        return InstitutionAnalytics::query()
            ->with([
                'institution:id,name,type,barangay_id,is_active',
                'institution.barangay:id,name',
            ])
            ->whereHas('institution', function (Builder $query): void {
                $query
                    ->where('is_active', true)
                    ->whereIn('type', ['School', 'Barangay Hall']);
            });
    }

    private function applyIndexFilters(
        Builder $query,
        Request $request
    ): void {
        $request->validate([
            'q' => 'nullable|string|max:255',
            'barangay' => 'nullable|integer|min:1',
            'type' => 'nullable|string|max:50',
            'risk' => 'nullable|string|max:50',
            'sort' => 'nullable|string|max:50',
            'order' => 'nullable|string|max:10',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d'.($request->filled('date_from') ? '|after_or_equal:date_from' : ''),
        ]);
        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $operator) {
            if ($request->filled($key)) {
                $date = Carbon::parse($request->query($key));
                $query->where('analysis_date', $operator, $key === 'date_from' ? $date->startOfDay() : $date->endOfDay());
            }
        }
        $search = trim((string) $request->query('q', ''));
        $barangayId = $request->query('barangay');
        $type = trim((string) $request->query('type', ''));
        $status = trim((string) $request->query('risk', ''));

        if ($search !== '') {
            $query->whereHas('institution', function (Builder $institutionQuery) use ($search): void {
                $institutionQuery->whereRaw(
                    'LOWER(name) LIKE ?',
                    ['%' . mb_strtolower($search) . '%']
                );
            });
        }

        if (is_numeric($barangayId) && (int) $barangayId > 0) {
            $query->whereHas('institution', function (Builder $institutionQuery) use ($barangayId): void {
                $institutionQuery->where('barangay_id', (int) $barangayId);
            });
        }

        if (in_array($type, ['School', 'Barangay Hall'], true)) {
            $query->whereHas('institution', function (Builder $institutionQuery) use ($type): void {
                $institutionQuery->where('type', $type);
            });
        }

        if (in_array($status, ['assessed', 'insufficient_data'], true)) {
            $query->where('risk_level', $status);
        }
    }

    private function summaryData(?Request $request = null): array
    { 
        $institutionCount = Institution::query()
            ->where('is_active', true)
            ->whereIn('type', ['School', 'Barangay Hall'])
            ->count(); 

        $aggregateQuery = InstitutionAnalytics::query()
            ->whereHas('institution', function (Builder $query): void {
                $query
                    ->where('is_active', true)
                    ->whereIn('type', ['School', 'Barangay Hall']);
            });

        if ($request) {
            $this->applyIndexFilters($aggregateQuery, $request);
        }

        $aggregate = $aggregateQuery
            ->selectRaw('AVG(gap_score) AS average_gap_score')
            ->selectRaw('AVG(infrastructure_deficit_score) AS infrastructure_deficit_score')
            ->selectRaw('AVG(connectivity_divide_score) AS connectivity_divide_score')
            ->selectRaw('AVG(technological_obsolescence_score) AS technological_obsolescence_score')
            ->selectRaw('SUM(CASE WHEN gap_score IS NOT NULL THEN 1 ELSE 0 END) AS analyzed_institution_count')
            ->selectRaw("SUM(CASE WHEN risk_level = 'assessed' THEN 1 ELSE 0 END) AS assessed_count")
            ->selectRaw("SUM(CASE WHEN risk_level = 'insufficient_data' THEN 1 ELSE 0 END) AS insufficient_data_count")
            ->selectRaw('MAX(analysis_date) AS latest_analysis_date')
            ->first();

        $analyzedInstitutionCount =
            (int) ($aggregate?->analyzed_institution_count ?? 0);

        $analysisCoveragePercentage = $institutionCount > 0
            ? round(($analyzedInstitutionCount / $institutionCount) * 100, 1)
            : 0.0;

        return [
            'average_gap_score' =>
                $this->nullableRounded($aggregate?->average_gap_score),
            'infrastructure_deficit_score' =>
                $this->nullableRounded($aggregate?->infrastructure_deficit_score),
            'connectivity_divide_score' =>
                $this->nullableRounded($aggregate?->connectivity_divide_score),
            'technological_obsolescence_score' =>
                $this->nullableRounded($aggregate?->technological_obsolescence_score),
            'institution_count' => $institutionCount,
            'analyzed_institution_count' => $analyzedInstitutionCount,
            'pending_assessment_count' =>
                max(0, $institutionCount - $analyzedInstitutionCount),
            'analysis_coverage_percentage' =>
                $analysisCoveragePercentage,

            'assessed' => (int) ($aggregate?->assessed_count ?? 0),
            'insufficient_data' =>
                (int) ($aggregate?->insufficient_data_count ?? 0),

            'latest_analysis_date' => $aggregate?->latest_analysis_date
                ? Carbon::parse($aggregate->latest_analysis_date)->toIso8601String()
                : null,
        ];
    }

    private function primaryCityConcern(array $summary): ?array
    {
        $dimensions = collect([
            [
                'key' => 'infrastructure',
                'label' => 'Infrastructure Deficit',
                'score' => $summary['infrastructure_deficit_score'] ?? null,
            ],
            [
                'key' => 'connectivity',
                'label' => 'Connectivity Divide',
                'score' => $summary['connectivity_divide_score'] ?? null,
            ],
            [
                'key' => 'obsolescence',
                'label' => 'Technological Obsolescence Proxy',
                'score' => $summary['technological_obsolescence_score'] ?? null,
            ],
        ])->filter(fn (array $dimension): bool => is_numeric($dimension['score']))
          ->sortByDesc('score')
          ->values();

        return $dimensions->first();
    }

    /**
     * Barangay value is a descriptive mean of complete institution composite
     * scores. Institution type remains available in the detailed table/filter;
     * the score is not presented as a standards-based barangay compliance rate.
     */
    private function barangayScores(?Request $request = null)
    {
        $query = Institution::query()
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
            ->whereIn('institutions.type', ['School', 'Barangay Hall'])
            ->whereNotNull('institution_analytics.gap_score');

        if ($request) {
            $selected = $this->activeAnalyticsQuery();
            $this->applyIndexFilters($selected, $request);
            $query->whereIn('institution_analytics.id', $selected->select('institution_analytics.id'));
        }

        return $query
            ->select(['barangays.id', 'barangays.name'])
            ->selectRaw('AVG(institution_analytics.gap_score) AS average_gap_score')
            ->selectRaw('COUNT(institutions.id) AS institution_count')
            ->groupBy('barangays.id', 'barangays.name')
            ->orderByDesc('average_gap_score')
            ->get()
            ->map(function ($barangay): array {
                return [
                    'id' => (int) $barangay->id,
                    'name' => $barangay->name,
                    'gap_score' => round((float) $barangay->average_gap_score, 2),
                    'institution_count' => (int) $barangay->institution_count,
                    'risk_level' => 'assessed',
                ];
            })
            ->values();
    }

    /**
     * Prevalence of any measured non-zero gap. No arbitrary 50% threshold is
     * used to define a priority institution.
     */
    private function priorityNeeds()
    {
        return collect([
            [
                'key' => 'infrastructure',
                'label' => 'Infrastructure Deficit',
                'count' => $this->analyticsGapPrevalenceCount(
                    'infrastructure_deficit_score'
                ),
            ],
            [
                'key' => 'connectivity',
                'label' => 'Connectivity Divide',
                'count' => $this->analyticsGapPrevalenceCount(
                    'connectivity_divide_score'
                ),
            ],
            [
                'key' => 'obsolescence',
                'label' => 'Technological Obsolescence Proxy',
                'count' => $this->analyticsGapPrevalenceCount(
                    'technological_obsolescence_score'
                ),
            ],
        ])
            ->filter(fn (array $need): bool => $need['count'] > 0)
            ->sortByDesc('count')
            ->values();
    }

    private function analyticsGapPrevalenceCount(string $field): int
    {
        return InstitutionAnalytics::query()
            ->where($field, '>', 0)
            ->whereHas('institution', function (Builder $query): void {
                $query
                    ->where('is_active', true)
                    ->whereIn('type', ['School', 'Barangay Hall']);
            })
            ->count();
    }

    private function nullableRounded(mixed $value): ?float
    {
        return is_numeric($value)
            ? round((float) $value, 2)
            : null;
    }
}
