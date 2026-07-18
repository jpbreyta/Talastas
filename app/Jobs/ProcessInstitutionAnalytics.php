<?php

namespace App\Jobs;

use App\Models\Institution;
use App\Models\InstitutionAnalytics;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    public function __construct(
        public int $institutionId
    ) {
    }

    public function handle(
        GeminiService $geminiService
    ): void {
        $institution = Institution::query()
            ->with([
                'barangay',
                'ictInventory',
                'institutionAnalytics',
            ])
            ->find($this->institutionId);

        if (!$institution) {
            Log::warning(
                'Institution analytics job skipped because the institution was not found.',
                [
                    'institution_id' => $this->institutionId,
                ]
            );

            return;
        }

        $pythonPayload = $this->buildPythonPayload(
            $institution
        );

        $pythonResult = $this->requestPythonAnalysis(
            $pythonPayload,
            $institution
        );

        $scores = $pythonResult !== null
            ? $this->normalizeScores(
                $pythonResult,
                $institution
            )
            : $this->fallbackScores(
                $institution,
                $pythonPayload
            );

        $aiResult = $geminiService->analyzeInfrastructure(
            $institution,
            $scores
        );

        $recommendationDetails = array_merge(
            $aiResult['recommendations_detail'] ?? [],
            [
                'score_source' => $pythonResult !== null
                    ? 'python_service'
                    : 'local_deterministic_fallback',
                'python_service_used' => $pythonResult !== null,
            ]
        );

        DB::transaction(function () use (
            $institution,
            $scores,
            $aiResult,
            $recommendationDetails
        ): void {
            InstitutionAnalytics::query()->updateOrCreate(
                [
                    'institution_id' => $institution->id,
                ],
                [
                    'gap_score' => $scores['gap_score'],
                    'risk_level' => $scores['risk_level'],
                    'data_completeness_score' =>
                        $scores['data_completeness_score'],

                    'infrastructure_deficit_score' =>
                        $scores['infrastructure_deficit_score'],

                    'connectivity_divide_score' =>
                        $scores['connectivity_divide_score'],

                    'technological_obsolescence_score' =>
                        $scores['technological_obsolescence_score'],

                    'asset_to_user_ratio' =>
                        $scores['asset_to_user_ratio'],

                    'infrastructure_narrative' =>
                        $aiResult['infrastructure_narrative'],

                    'policy_recommendation' =>
                        $aiResult['policy_recommendation'],

                    'recommendations_detail' =>
                        $recommendationDetails,

                    'analysis_date' => now(),
                ]
            );

            $institution->forceFill([
                'data_completeness_score' =>
                    $scores['data_completeness_score'],
            ])->saveQuietly();
        });
    }

    protected function requestPythonAnalysis(
        array $payload,
        Institution $institution
    ): ?array {
        $baseUrl = rtrim(
            (string) config(
                'services.python_analytics.url',
                'http://127.0.0.1:8002'
            ),
            '/'
        );

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->connectTimeout(3)
                ->timeout((int) config(
                    'services.python_analytics.timeout',
                    15
                ))
                ->retry(
                    2,
                    250,
                    null,
                    false
                )
                ->post(
                    $baseUrl . '/analyze',
                    $payload
                );

            if (!$response->successful()) {
                $this->logPythonFailure(
                    $response,
                    $institution
                );

                return null;
            }

            $data = $response->json();

            if (!is_array($data)) {
                Log::warning(
                    'Python analytics returned an invalid response.',
                    [
                        'institution_id' => $institution->id,
                    ]
                );

                return null;
            }

            return $data;
        } catch (Throwable $exception) {
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
                'body' => mb_substr(
                    $response->body(),
                    0,
                    1000
                ),
            ]
        );
    }

    protected function buildPythonPayload(
        Institution $institution
    ): array {
        $inventory = $institution->ictInventory;
        $currentYear = (int) now()->format('Y');

        $activeInventory = $inventory->filter(
            fn ($item) => !$this->containsAny(
                $item->status,
                [
                    'retired',
                    'inactive',
                    'disposed',
                ]
            )
        );

        $years = $inventory
            ->pluck('acquisition_year')
            ->filter(fn ($year) => is_numeric($year))
            ->map(fn ($year) => (int) $year)
            ->values();

        $averageAge = $years->isEmpty()
            ? null
            : round(
                $years
                    ->map(
                        fn (int $year) =>
                            max(0, $currentYear - $year)
                    )
                    ->average(),
                2
            );

        return [
            'id' => $institution->id,
            'name' => $institution->name,
            'institution_type' => $institution->type,

            'internet_speed' => $institution->internet_speed,

            'student_population' =>
                $institution->student_population,

            'staff_population' =>
                $institution->staff_population,

            'needs_maintenance' =>
                (bool) $institution->needs_maintenance,

            'has_computer_lab' =>
                (bool) $institution->has_computer_lab,

            'has_smart_tv' =>
                (bool) $institution->has_smart_tv,

            'has_projected_whiteboard' =>
                (bool) $institution->has_projected_whiteboard,

            'has_public_address_system' =>
                (bool) $institution->has_public_address_system,

            'active_computers_count' =>
                $institution->number_of_computers,

            'smart_tvs_count' => $activeInventory
                ->filter(
                    fn ($item) => $this->containsAny(
                        $item->category,
                        [
                            'smart tv',
                            'smart television',
                        ]
                    )
                )
                ->count(),

            'projectors_count' => $activeInventory
                ->filter(
                    fn ($item) => $this->containsAny(
                        $item->category,
                        [
                            'projector',
                            'projected whiteboard',
                        ]
                    )
                )
                ->count(),

            'pa_systems_count' => $activeInventory
                ->filter(
                    fn ($item) => $this->containsAny(
                        $item->category,
                        [
                            'public address',
                            'pa system',
                        ]
                    )
                )
                ->count(),

            'total_inventory_count' =>
                $inventory->count(),

            'old_equipment_count' => $inventory
                ->filter(
                    fn ($item) =>
                        is_numeric($item->acquisition_year)
                        && (int) $item->acquisition_year
                            <= $currentYear - 5
                )
                ->count(),

            'poor_condition_count' => $inventory
                ->filter(
                    fn ($item) => $this->containsAny(
                        $item->condition,
                        [
                            'poor',
                            'damaged',
                            'critical',
                            'needs maintenance',
                        ]
                    )
                )
                ->count(),

            'needs_repair_count' => $inventory
                ->filter(
                    fn ($item) =>
                        $this->containsAny(
                            $item->status,
                            [
                                'repair',
                                'maintenance',
                            ]
                        )
                        || $this->containsAny(
                            $item->condition,
                            [
                                'needs maintenance',
                                'damaged',
                            ]
                        )
                )
                ->count(),

            'retired_equipment_count' => $inventory
                ->filter(
                    fn ($item) => $this->containsAny(
                        $item->status,
                        [
                            'retired',
                            'disposed',
                        ]
                    )
                )
                ->count(),

            'average_equipment_age' => $averageAge,
        ];
    }

    protected function normalizeScores(
        array $result,
        Institution $institution
    ): array {
        $gapScore = $this->nullableScore(
            $result['gap_score'] ?? null
        );

        $riskLevel = $this->normalizeRiskLevel(
            $result['risk_level'] ?? null,
            $gapScore
        );

        return [
            'gap_score' => $gapScore,

            'risk_level' => $riskLevel,

            'data_completeness_score' =>
                $this->score(
                    $result['data_completeness_score']
                        ?? $institution
                            ->institutionAnalytics
                            ?->data_completeness_score
                        ?? 0
                ),

            'infrastructure_deficit_score' =>
                $this->nullableScore(
                    $result[
                        'infrastructure_deficit_score'
                    ] ?? null
                ),

            'connectivity_divide_score' =>
                $this->nullableScore(
                    $result[
                        'connectivity_divide_score'
                    ] ?? null
                ),

            'technological_obsolescence_score' =>
                $this->nullableScore(
                    $result[
                        'technological_obsolescence_score'
                    ] ?? null
                ),

            'asset_to_user_ratio' =>
                $this->assetToUserRatio($institution),
        ];
    }

    protected function fallbackScores(
        Institution $institution,
        array $payload
    ): array {
        $completeness = $this->calculateCompleteness(
            $payload
        );

        $infrastructure = $this->infrastructureDeficit(
            $payload
        );

        $connectivity = $this->connectivityDeficit(
            $payload['internet_speed']
        );

        $obsolescence = $this->obsolescenceDeficit(
            $payload
        );

        $hasRequiredData =
            $completeness >= 60
            && $payload['internet_speed'] !== null
            && $payload['active_computers_count'] !== null
            && $payload['total_inventory_count'] !== null
            && (
                $payload['institution_type'] === 'School'
                    ? $payload['student_population'] !== null
                    : $payload['staff_population'] !== null
            );

        $gapScore = $hasRequiredData
            ? $this->weightedAverage([
                [$infrastructure, 0.40],
                [$connectivity, 0.35],
                [$obsolescence, 0.25],
            ])
            : null;

        return [
            'gap_score' => $gapScore,

            'risk_level' => $this->normalizeRiskLevel(
                null,
                $gapScore
            ),

            'data_completeness_score' =>
                $completeness,

            'infrastructure_deficit_score' =>
                $infrastructure,

            'connectivity_divide_score' =>
                $connectivity,

            'technological_obsolescence_score' =>
                $obsolescence,

            'asset_to_user_ratio' =>
                $this->assetToUserRatio($institution),
        ];
    }

    protected function calculateCompleteness(
        array $payload
    ): float {
        $requiredFields = [
            $payload['institution_type'],
            $payload['internet_speed'],
            $payload['active_computers_count'],
            $payload['total_inventory_count'],
            $payload['institution_type'] === 'School'
                ? $payload['student_population']
                : $payload['staff_population'],
        ];

        $supportingFields = [
            $payload['has_computer_lab'],
            $payload['has_smart_tv'],
            $payload['has_projected_whiteboard'],
            $payload['has_public_address_system'],
            $payload['smart_tvs_count'],
            $payload['projectors_count'],
            $payload['pa_systems_count'],
            $payload['old_equipment_count'],
            $payload['poor_condition_count'],
            $payload['needs_repair_count'],
            $payload['retired_equipment_count'],
            $payload['average_equipment_age'],
        ];

        $requiredComplete = collect($requiredFields)
            ->filter(fn ($value) => $value !== null)
            ->count();

        $supportingComplete = collect($supportingFields)
            ->filter(fn ($value) => $value !== null)
            ->count();

        $requiredScore =
            $requiredComplete / count($requiredFields);

        $supportingScore =
            $supportingComplete / count($supportingFields);

        return $this->score(
            ($requiredScore * 70)
            + ($supportingScore * 30)
        );
    }

    protected function infrastructureDeficit(
        array $payload
    ): ?float {
        $population = $payload['institution_type']
            === 'School'
                ? $payload['student_population']
                : $payload['staff_population'];

        $computers = $payload['active_computers_count'];

        $assetDeficit = null;

        if (
            $population !== null
            && (int) $population > 0
            && $computers !== null
        ) {
            if ((int) $computers === 0) {
                $assetDeficit = 100.0;
            } else {
                $usersPerComputer =
                    (float) $population
                    / (float) $computers;

                if ($payload['institution_type'] === 'School') {
                    $assetDeficit = match (true) {
                        $usersPerComputer > 40 => 100.0,
                        $usersPerComputer > 25 => 75.0,
                        $usersPerComputer > 15 => 50.0,
                        $usersPerComputer > 10 => 25.0,
                        default => 0.0,
                    };
                } else {
                    $assetDeficit = match (true) {
                        $usersPerComputer > 5 => 100.0,
                        $usersPerComputer > 3 => 75.0,
                        $usersPerComputer > 2 => 50.0,
                        $usersPerComputer > 1 => 25.0,
                        default => 0.0,
                    };
                }
            }
        }

        $displayDeficit = (
            (int) $payload['smart_tvs_count'] > 0
            || (int) $payload['projectors_count'] > 0
            || $payload['has_smart_tv']
            || $payload['has_projected_whiteboard']
        )
            ? 0.0
            : 100.0;

        $paDeficit = (
            (int) $payload['pa_systems_count'] > 0
            || $payload['has_public_address_system']
        )
            ? 0.0
            : 100.0;

        if ($payload['institution_type'] === 'School') {
            $labDeficit = $payload['has_computer_lab']
                ? 0.0
                : 100.0;

            return $this->weightedAverage([
                [$assetDeficit, 60],
                [$labDeficit, 15],
                [$displayDeficit, 15],
                [$paDeficit, 10],
            ]);
        }

        return $this->weightedAverage([
            [$assetDeficit, 70],
            [$displayDeficit, 15],
            [$paDeficit, 15],
        ]);
    }

    protected function connectivityDeficit(
        mixed $speed
    ): ?float {
        if (!is_numeric($speed)) {
            return null;
        }

        $speed = (float) $speed;

        return match (true) {
            $speed <= 0 => 100.0,
            $speed < 10 => 90.0,
            $speed < 25 => 70.0,
            $speed < 50 => 45.0,
            $speed < 100 => 20.0,
            default => 0.0,
        };
    }

    protected function obsolescenceDeficit(
        array $payload
    ): ?float {
        $total = $payload['total_inventory_count'];

        if ($total === null) {
            return null;
        }

        $total = (int) $total;

        $oldRatio = $this->equipmentRatio(
            $payload['old_equipment_count'],
            $total
        );

        $poorRatio = $this->equipmentRatio(
            $payload['poor_condition_count'],
            $total
        );

        $repairRatio = $this->equipmentRatio(
            $payload['needs_repair_count'],
            $total
        );

        $retiredRatio = $this->equipmentRatio(
            $payload['retired_equipment_count'],
            $total
        );

        $conditionDeficit = $poorRatio !== null
            || $repairRatio !== null
                ? $this->score(
                    ($poorRatio ?? 0)
                    + ($repairRatio ?? 0)
                )
                : null;

        $ageDeficit = null;

        if (is_numeric($payload['average_equipment_age'])) {
            $age = (float) $payload[
                'average_equipment_age'
            ];

            $ageDeficit = match (true) {
                $age >= 8 => 100.0,
                $age >= 6 => 75.0,
                $age >= 4 => 50.0,
                $age >= 2 => 20.0,
                default => 0.0,
            };
        }

        $maintenanceDeficit =
            $payload['needs_maintenance']
                ? 100.0
                : 0.0;

        return $this->weightedAverage([
            [$oldRatio, 30],
            [$conditionDeficit, 35],
            [$retiredRatio, 15],
            [$ageDeficit, 15],
            [$maintenanceDeficit, 5],
        ]);
    }

    protected function equipmentRatio(
        mixed $count,
        int $total
    ): ?float {
        if (!is_numeric($count)) {
            return null;
        }

        if ($total <= 0) {
            return 0.0;
        }

        return $this->score(
            ((float) $count / $total) * 100
        );
    }

    protected function weightedAverage(
        array $items
    ): ?float {
        $available = collect($items)
            ->filter(
                fn (array $item) =>
                    $item[0] !== null
                    && is_numeric($item[0])
                    && (float) $item[1] > 0
            );

        if ($available->isEmpty()) {
            return null;
        }

        $totalWeight = $available->sum(
            fn (array $item) => (float) $item[1]
        );

        if ($totalWeight <= 0) {
            return null;
        }

        $weightedValue = $available->sum(
            fn (array $item) =>
                (float) $item[0]
                * (float) $item[1]
        );

        return $this->score(
            $weightedValue / $totalWeight
        );
    }

    protected function assetToUserRatio(
        Institution $institution
    ): ?float {
        $population = $institution
            ->getAnalysisPopulation();

        $computers = $institution
            ->number_of_computers;

        if (
            $population <= 0
            || $computers === null
        ) {
            return null;
        }

        return round(
            (float) $computers / $population,
            4
        );
    }

    protected function normalizeRiskLevel(
        mixed $riskLevel,
        ?float $gapScore
    ): string {
        $riskLevel = strtolower(
            trim((string) $riskLevel)
        );

        if (
            in_array(
                $riskLevel,
                [
                    'adequate',
                    'warning',
                    'critical',
                    'insufficient_data',
                ],
                true
            )
        ) {
            return $riskLevel;
        }

        if ($gapScore === null) {
            return 'insufficient_data';
        }

        return match (true) {
            $gapScore >= 80 => 'critical',
            $gapScore >= 50 => 'warning',
            default => 'adequate',
        };
    }

    protected function nullableScore(
        mixed $value
    ): ?float {
        if (!is_numeric($value)) {
            return null;
        }

        return $this->score($value);
    }

    protected function score(
        mixed $value
    ): float {
        return round(
            max(
                0,
                min(
                    100,
                    (float) $value
                )
            ),
            2
        );
    }

    protected function containsAny(
        mixed $value,
        array $needles
    ): bool {
        $value = mb_strtolower(
            trim((string) $value)
        );

        foreach ($needles as $needle) {
            if (
                str_contains(
                    $value,
                    mb_strtolower($needle)
                )
            ) {
                return true;
            }
        }

        return false;
    }

    public function failed(
        Throwable $exception
    ): void {
        Log::error(
            'Institution analytics job failed permanently.',
            [
                'institution_id' => $this->institutionId,
                'error' => $exception->getMessage(),
            ]
        );
    }
}