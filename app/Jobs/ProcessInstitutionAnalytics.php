<?php

namespace App\Jobs;

use App\Models\IctInventory;
use App\Models\Institution;
use App\Models\InstitutionAnalytics;
use App\Services\GeminiService;
use App\Services\IctGapBaselineCalculator;
use App\Services\InternetConnectivityAssessment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class ProcessInstitutionAnalytics implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [10, 30, 60];
    protected string $processingStage = 'loading_data';

    public function __construct(
        public int $institutionId,
        public ?string $processingToken = null,
        public bool $includeSoftware = true
    ) {
    }

    public function handle(
        GeminiService $geminiService,
        IctGapBaselineCalculator $baselineCalculator
    ): void {
        $missingSchema = false;
        try {
            $missingSchema = !Schema::hasColumn('institution_analytics', 'ai_priorities');
            if ($missingSchema) {
                throw new RuntimeException('Analytics schema is out of date. Apply migration 2026_10_13_000000_add_ai_priorities_to_institution_analytics before retrying.');
            }
            $this->process($geminiService, $baselineCalculator);
        } catch (Throwable $exception) {
            Log::error('Institution analytics attempt failed.', [
                'institution_id' => $this->institutionId,
                'processing_token' => $this->processingToken,
                'stage' => $this->processingStage,
                'exception' => $exception,
            ]);
            // A missing migration cannot be corrected by another queue attempt.
            if ($missingSchema && $this->job) {
                $this->fail($exception);
                return;
            }
            throw $exception;
        }
    }

    protected function process(
        GeminiService $geminiService,
        IctGapBaselineCalculator $baselineCalculator
    ): void {
        $institution = Institution::query()
            ->with([
                'barangay',
                'ictInventory.component',
                'institutionAnalytics',
                'internetConnections',
            ])
            ->find($this->institutionId);

        if (!$institution) {
            Log::warning(
                'Institution analytics job skipped because the institution was not found.',
                ['institution_id' => $this->institutionId]
            );
            return;
        }

        if (!in_array($institution->type, ['School', 'Barangay Hall'], true)) {
            Log::info(
                'Institution analytics skipped because the institution type is outside the approved capstone scope.',
                [
                    'institution_id' => $institution->id,
                    'institution_type' => $institution->type,
                ]
            );
            return;
        }

        $processingQuery = InstitutionAnalytics::query()
            ->where('institution_id', $institution->id);

        if ($this->processingToken !== null) {
            $processingQuery->where(
                'processing_token',
                $this->processingToken
            );
        }

        $processingQuery->update([
            'processing_status' => 'processing',
            'processing_stage' => 'loading_data',
            'processing_error' => null,
        ]);

        $payload = $this->buildPythonPayload($institution);

        Log::info('Institution analytics payload prepared.', [
            'institution_id' => $institution->id,
            'processing_token' => $this->processingToken,
            'payload' => $this->diagnosticPayload($payload),
        ]);

        $this->updateProcessingStage(
            $institution->id,
            'python_scoring'
        );

        $pythonResult = $this->requestPythonAnalysis(
            $payload,
            $institution
        );

        $scoreSource = $pythonResult !== null ? 'python' : 'laravel_fallback';
        try {
            $scores = $pythonResult !== null
                ? $baselineCalculator->fromPythonResult($pythonResult, $payload)
                : $baselineCalculator->calculate($payload);
        } catch (\UnexpectedValueException $exception) {
            Log::error('Python analytics result failed local parity validation.', [
                'institution_id' => $institution->id,
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        Log::info('Deterministic analytics scores ready.', [
            'institution_id' => $institution->id,
            'score_source' => $scoreSource,
            'scores' => $scores,
        ]);

        $planningEvidence = $this->buildAiPlanningEvidence(
            $institution,
            $payload,
            $scores
        );

        $this->updateProcessingStage(
            $institution->id,
            'ai_prescriptive'
        );

        Log::info('AI prescriptive analytics input prepared.', [
            'institution_id' => $institution->id,
            'calculated_scores' => $scores,
            'planning_evidence' => $planningEvidence,
        ]);

        try {
            $aiResult = $geminiService->analyzeInfrastructure($institution, $scores, $planningEvidence);
        } catch (Throwable $exception) {
            Log::warning('AI interpretation unavailable; preserving deterministic results.', ['institution_id' => $institution->id]);
            $aiResult = ['infrastructure_narrative' => null, 'policy_recommendation' => null,
                'recommendations_detail' => ['ai_status' => 'unavailable']];
        }

        Log::info('AI prescriptive analytics result received.', [
            'institution_id' => $institution->id,
            'provider' =>
                data_get(
                    $aiResult,
                    'recommendations_detail.provider'
                ),
            'ai_status' =>
                data_get(
                    $aiResult,
                    'recommendations_detail.ai_status'
                ),
            'method' =>
                data_get(
                    $aiResult,
                    'recommendations_detail.method'
                ),
        ]);

        $recommendationDetails = array_merge(
            $aiResult['recommendations_detail'] ?? [],
            [
                'score_source' => $scoreSource,
                'python_service_used' => $scoreSource === 'python',
                'assessment' => $scores,
                'decision_support_version' => '4.2-explicit-analytics-inputs',
                'connectivity_assessment' => app(InternetConnectivityAssessment::class)->assess($payload),
                'network_measurement_reference' => [
                    'standard' => 'ITU-T Y.1540',
                    'url' => 'https://www.itu.int/rec/T-REC-Y.1540/en',
                    'role' => 'Network performance measurement guidance; the shortfall formula and aggregation are TALASTAS methodology.',
                ],
                'calculation_scope' => app(\App\Services\InventoryCalculationScope::class)->describe($this->includeSoftware ?? true),
                'methodology' => [
                    'infrastructure_deficit' =>
                        'Explicitly recorded Defective within Active / Active applicable units x 100. Active means deployed/in-service, including defective units; unknown values are not inferred.',
                    'connectivity_divide' =>
                        'Treat the numeric acquired speed recorded in Plan as Mbps. For each connection, calculate download and upload shortfall as max(acquired Mbps - measured Mbps, 0) / acquired Mbps x 100, then average the two. Connectivity Divide is the mean of connection scores only when every reported connection has an acquired speed and both measured speeds; explicit no Internet service = 100%.',
                    'technological_obsolescence_proxy' =>
                        'Retired applicable units / Total applicable units x 100.',
                    'overall_ict_gap' =>
                        'Arithmetic mean of Infrastructure Deficit, Connectivity Divide, and Technological Obsolescence Proxy when all three are available. Missing values are not converted to zero.',
                ],
            ]
        );

        if ($this->processingToken !== null) {
            $isCurrentRun = InstitutionAnalytics::query()
                ->where('institution_id', $institution->id)
                ->where(
                    'processing_token',
                    $this->processingToken
                )
                ->exists();

            if (!$isCurrentRun) {
                Log::info(
                    'Institution analytics result discarded because a newer run superseded it.',
                    [
                        'institution_id' => $institution->id,
                        'processing_token' => $this->processingToken,
                    ]
                );

                return;
            }
        }

        $this->updateProcessingStage(
            $institution->id,
            'saving_results'
        );

        $published = DB::transaction(function () use (
            $institution,
            $scores,
            $aiResult,
            $recommendationDetails,
            $payload,
            $scoreSource
        ): bool {
            // Serialize result publication with inventory edits and invalidation.
            $currentInstitution = Institution::whereKey($institution->id)->lockForUpdate()->firstOrFail();
            $currentInstitution->load(['ictInventory.component', 'internetConnections']);
            if ($this->buildPythonPayload($currentInstitution) !== $payload) {
                throw new RuntimeException('Source data changed during calculation; request a new calculation.');
            }
            if ($this->processingToken !== null && !InstitutionAnalytics::where('institution_id', $institution->id)
                ->where('processing_token', $this->processingToken)->exists()) {
                return false;
            }
            InstitutionAnalytics::query()->updateOrCreate(
                ['institution_id' => $institution->id],
                [
                    'gap_score' => $scores['gap_score'],
                    'risk_level' => $scores['risk_level'],

                    'infrastructure_deficit_score' => $scores['infrastructure_deficit_score'],
                    'connectivity_divide_score' => $scores['connectivity_divide_score'],
                    'technological_obsolescence_score' => $scores['technological_obsolescence_score'],

                    'infrastructure_narrative' => $aiResult['infrastructure_narrative'],
                    'policy_recommendation' => $aiResult['policy_recommendation'],
                    'recommendations_detail' => $recommendationDetails,
                    'ai_priorities' => $recommendationDetails['priorities'] ?? [],
                    'analysis_date' => now(),

                    'processing_token' =>
                        $this->processingToken,
                    'processing_status' => 'completed',
                    'processing_stage' => 'completed',
                    'processing_completed_at' => now(),
                    'processing_error' => null,
                ]
            );
            return true;
        });
        if (!$published) {
            return;
        }
        Log::info('Institution analytics results persisted.', [
            'institution_id' => $institution->id,
            'processing_token' => $this->processingToken,
            'stage' => 'completed',
        ]);
    }

    protected function updateProcessingStage(
        int $institutionId,
        string $stage
    ): void {
        $this->processingStage = $stage;
        $query = InstitutionAnalytics::query()
            ->where(
                'institution_id',
                $institutionId
            );

        if ($this->processingToken !== null) {
            $query->where(
                'processing_token',
                $this->processingToken
            );
        }

        $query->update([
            'processing_stage' => $stage,
        ]);
    }

    protected function pythonServicePayload(
        array $payload
    ): array {
        $servicePayload = collect($payload)
            ->only([
                'id',
                'has_internet_connection',
                'bandwidth_allocation_mbps',
                'download_speed_mbps',
                'internet_connections',
                'reported_internet_connection_count',
                'total_applicable_inventory_count',
                'active_applicable_inventory_count',
                'retired_applicable_inventory_count',
                'defective_applicable_inventory_count',
                'functional_applicable_inventory_count',
                'inventory_invalid_inputs',
                'inventory',
                'inventory_coverage',
                'missing_expected_inventory',
                'defective_active_applicable_inventory_count',
            ])
            ->all();
        if (($servicePayload['inventory_invalid_inputs'] ?? []) === []) {
            unset($servicePayload['inventory_invalid_inputs']);
        }
        return $servicePayload;
    }

    protected function diagnosticPayload(
        array $payload
    ): array {
        return [
            ...$this->pythonServicePayload($payload),
            'inventory' => collect(
                $payload['inventory'] ?? []
            )
                ->map(fn ($item) => is_array($item)
                    ? collect($item)
                        ->only([
                            'label',
                            'total_count',
                            'active_count',
                            'retired_count',
                            'functional_count',
                            'defective_count',
                        ])
                        ->all()
                    : $item
                )
                ->values()
                ->all(),
        ];
    }

    protected function requestPythonAnalysis(
        array $payload,
        Institution $institution
    ): ?array {
        $requestPayload =
            $this->pythonServicePayload($payload);

        $baseUrl = rtrim(
            (string) config(
                'services.python_analytics.url'
            ),
            '/'
        );

        Log::info('Python analytics request starting.', [
            'institution_id' => $institution->id,
            'url' => $baseUrl . '/analyze',
            'payload' => $requestPayload,
        ]);

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->connectTimeout(3)
                ->timeout((int) config('services.python_analytics.timeout', 15))
                ->retry(2, 250, null, false)
                ->post(
                    $baseUrl . '/analyze',
                    $requestPayload
                );

            if (!$response->successful()) {
                if ($response->status() >= 400 && $response->status() < 500) {
                    throw new \UnexpectedValueException('Python rejected calculation inputs; local fallback is prohibited.');
                }
                $this->logPythonFailure($response, $institution);
                return null;
            }

            $data = $response->json();

            Log::info('Python analytics request completed.', [
                'institution_id' => $institution->id,
                'status' => $response->status(),
                'response_type' => get_debug_type($data),
                'response_keys' => is_array($data) ? array_keys($data) : [],
            ]);

            if (!is_array($data)) {
                Log::warning(
                    'Python analytics returned an invalid successful response.',
                    ['institution_id' => $institution->id, 'status' => $response->status(), 'response_type' => get_debug_type($data)]
                );
                throw new \UnexpectedValueException('Python analytics returned an invalid successful response.');
            }

            $rules = [
                'institution_id' => 'required|integer|in:'.$institution->id,
                'risk_level' => 'required|in:assessed,insufficient_data',
                'assessment_status' => 'required|in:assessed,insufficient_data',
                'processing_status' => 'required|in:completed',
                'missing_inputs' => 'present|array',
                'invalid_inputs' => 'present|array',
                'unavailable_reasons' => 'present|array',
                'inventory_inputs' => 'required|array',
            ];
            foreach (['gap_score', 'infrastructure_deficit_score', 'connectivity_divide_score', 'technological_obsolescence_score'] as $key) {
                $rules[$key] = 'present|nullable|numeric|between:0,100';
            }
            if (array_key_exists('internet_connections', $payload)) {
                $rules['connectivity_method'] = 'required|in:'.InternetConnectivityAssessment::METHOD;
            }
            foreach (['totals' => 'required|array', 'recorded_row_counts' => 'required|array', 'included_rows' => 'required|integer|min:0', 'coverage' => 'required|string', 'missing_expected_inventory' => 'present|array'] as $field => $rule) {
                $rules['inventory_inputs.'.$field] = $rule;
            }
            if (($data['success'] ?? true) === false || isset($data['error'])) {
                throw new \UnexpectedValueException('Python rejected the assessment; local fallback is prohibited.');
            }
            $validation = Validator::make($data, $rules);
            if ($validation->fails() || ($data['success'] ?? true) === false || isset($data['error'])) {
                Log::error('Python analytics returned an invalid successful response.', [
                    'institution_id' => $institution->id,
                    'status' => $response->status(),
                    'response_keys' => array_keys($data),
                    'invalid_fields' => $validation->errors()->keys(),
                ]);
                throw new \UnexpectedValueException('Python analytics returned an invalid successful response: '.implode(', ', $validation->errors()->keys()).'.');
            }

            if ($data['risk_level'] !== $data['assessment_status']) {
                throw new \UnexpectedValueException('Python analytics returned inconsistent assessment status values.');
            }

            return $data;
        } catch (Throwable $exception) {
            if ($exception instanceof \UnexpectedValueException) { throw $exception; }
            if ($exception instanceof \Illuminate\Http\Client\RequestException
                && $exception->response->status() >= 400 && $exception->response->status() < 500) {
                throw new \UnexpectedValueException('Python rejected calculation inputs; local fallback is prohibited.', 0, $exception);
            }
            Log::warning(
                'Python analytics service was unavailable.',
                [
                    'institution_id' => $institution->id,
                    'error' => $exception->getMessage(),
                ]
            );
            return null;
        }
    }

    protected function logPythonFailure(
        Response $response,
        Institution $institution
    ): void {
        Log::warning(
            'Python analytics request failed.',
            [
                'institution_id' => $institution->id,
                'status' => $response->status(),
                'response_type' => get_debug_type($response->json()),
                'response_keys' => is_array($response->json()) ? array_keys($response->json()) : [],
            ]
        );
    }

    /**
     * Build the analytics payload only from values obtained by the approved
     * TALASTAS institution profile, ICT inventory and connectivity source data.
     */
    protected function buildPythonPayload(
        Institution $institution
    ): array {
        $inventory = $institution->ictInventory;

        $applicable = $inventory->filter(
            fn (IctInventory $item): bool =>
                app(\App\Services\InventoryCalculationScope::class)->includes($item, $this->includeSoftware ?? true)
        );

        $institution->loadMissing('internetConnections');
        $connections = $institution->internetConnections->map(fn ($connection) =>
            app(InternetConnectivityAssessment::class)->prepareConnection($connection->getAttributes()))->values()->all();
        $reported = $institution->q10_number_of_connections;

        return [
            'inventory_invalid_inputs' => collect($applicable)->flatMap(function ($item) {
                $counts = collect(['total_count', ...\App\Services\InventoryCounts::AVAILABILITY_FIELDS])
                    ->mapWithKeys(fn ($field) => [$field => $item->{$field}])->all();
                return collect(\App\Services\InventoryCounts::errors($counts))
                    ->mapWithKeys(fn ($message, $field) => ['inventory.'.$item->getKey().'.'.$field => $message]);
            })->all(),
            'id' => $institution->id,
            'has_internet_connection' =>
                $institution->has_internet_connection,
            'reported_internet_connection_count' => $reported,
            'internet_connections' => $connections,
            'total_applicable_inventory_count' =>
                \App\Services\InventoryCounts::sum($applicable, 'total_count'),
            'active_applicable_inventory_count' =>
                \App\Services\InventoryCounts::sum($applicable, 'active_count'),
            'retired_applicable_inventory_count' =>
                \App\Services\InventoryCounts::sum($applicable, 'retired_count'),
            'defective_applicable_inventory_count' =>
                \App\Services\InventoryCounts::sum($applicable, 'defective_count'),
            'defective_active_applicable_inventory_count' => \App\Services\InventoryCounts::sum($applicable, 'defective_active_count'),
            // No catalog-wide applicability is established by the current schema.
            'inventory_coverage' => 'saved_applicable_rows',
            'missing_expected_inventory' => [],
            'functional_applicable_inventory_count' => \App\Services\InventoryCounts::sum($applicable, 'functional_count'),
            'applicable_inventory_record_count' => $applicable->count(),
            'availability_recorded_row_counts' => collect(\App\Services\InventoryCounts::AVAILABILITY_FIELDS)->mapWithKeys(
                fn ($field) => [$field => $applicable->whereNotNull($field)->count()]
            )->all(),
            'inventory' => $applicable
                ->map(function (IctInventory $item): array {
                    return [
                        'label' => $item->asset_name,
                        'total_count' => $item->total_count,
                        'active_count' => $item->active_count,
                        'retired_count' => $item->retired_count,
                        'functional_count' => $item->functional_count,
                        'defective_count' => $item->defective_count,
                        'defective_active_count' => $item->defective_active_count,
                        'functional_count_recorded' => $item->functional_count_recorded,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    protected function buildAiPlanningEvidence(
        Institution $institution,
        array $payload,
        array $scores
    ): array {
        $components = collect($payload['inventory'] ?? []);

        return [
            'scope' => [
                'calculation' => app(\App\Services\InventoryCalculationScope::class)->describe($this->includeSoftware ?? true),
                'institution' => $institution->name,
                'institution_type' => $institution->type,
                'barangay' => $institution->barangay?->name,
            ],
            'inventory_status' => ($payload['applicable_inventory_record_count'] ?? count($payload['inventory'] ?? [])) === 0
                ? 'unrecorded'
                : ($payload['total_applicable_inventory_count'] === 0 ? 'confirmed_zero' : 'recorded'),
            'availability_recorded_row_counts' => $payload['availability_recorded_row_counts'] ?? [],
            'availability_status' => $components->every(fn ($item) => collect(\App\Services\InventoryCounts::AVAILABILITY_FIELDS)->every(fn ($field) => isset($item[$field]))) && $components->isNotEmpty()
                ? 'Availability Recorded' : 'Availability Not Recorded or Partially Recorded',
            'inventory_totals' => [
                'total' =>
                    $payload['total_applicable_inventory_count'],
                'active' =>
                    $payload['active_applicable_inventory_count'],
                'retired' =>
                    $payload['retired_applicable_inventory_count'],
                'functional' => $components->isNotEmpty() && $components->every(fn (array $item): bool => isset($item['functional_count']))
                    ? (int) $components->sum('functional_count') : null,
                'defective_active' => $payload['defective_active_applicable_inventory_count'],
                'defective' =>
                    $payload['defective_applicable_inventory_count'],
            ],
            'connectivity' => [
                'has_internet_connection' =>
                    $payload['has_internet_connection'],
                ...app(InternetConnectivityAssessment::class)->assess($payload),
            ],
            'top_defective_components' => $components
                ->filter(
                    fn (array $item): bool =>
                        (int) ($item['defective_count'] ?? 0) > 0
                )
                ->sortByDesc('defective_count')
                ->take(5)
                ->values()
                ->all(),
            'top_retired_components' => $components
                ->filter(
                    fn (array $item): bool =>
                        (int) ($item['retired_count'] ?? 0) > 0
                )
                ->sortByDesc('retired_count')
                ->take(5)
                ->values()
                ->all(),
            'deterministic_scores' => [
                'overall_ict_gap' => $scores['gap_score'],
                'infrastructure_deficit' =>
                    $scores['infrastructure_deficit_score'],
                'connectivity_divide' =>
                    $scores['connectivity_divide_score'],
                'technological_obsolescence_proxy' =>
                    $scores['technological_obsolescence_score'],
            ],
        ];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Institution analytics job failed permanently.', [
            'institution_id' => $this->institutionId,
            'processing_token' => $this->processingToken,
            'stage' => $this->processingStage,
            'exception' => $exception,
        ]);
        $query = InstitutionAnalytics::query()
            ->where('institution_id', $this->institutionId);

        if ($this->processingToken !== null) {
            $query->where(
                'processing_token',
                $this->processingToken
            );
        }

        try {
            $query->update([
                'processing_status' => 'failed',
                'processing_stage' => 'failed',
                'processing_completed_at' => now(),
                'processing_error' => mb_substr(
                    $exception->getMessage(),
                    0,
                    2000
                ),
            ]);
        } catch (Throwable $statusException) {
            Log::error('Institution analytics failed-state persistence failed.', [
                'institution_id' => $this->institutionId,
                'processing_token' => $this->processingToken,
                'exception' => $statusException,
            ]);
            throw $statusException;
        }
    }
}
