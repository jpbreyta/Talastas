<?php

namespace App\Services;

use App\Models\Institution;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GeminiService
{
    public function analyzeInfrastructure(
        Institution $institution,
        array $scores = [],
        array $planningEvidence = []
    ): array {
        return $this->analyze(
            $institution,
            $scores,
            $planningEvidence
        );
    }

    public function analyze(
        Institution $institution,
        array $scores = [],
        array $planningEvidence = []
    ): array {
        $institution->loadMissing('barangay');

        $context = $this->buildContext(
            $institution,
            $scores,
            $planningEvidence
        );
        $prompt = $this->buildPrompt($context);

        $providers = array_values(array_unique(array_filter([
            strtolower((string) config(
                'services.ai.primary_provider',
                'gemini'
            )),
            strtolower((string) config(
                'services.ai.fallback_provider',
                'groq'
            )),
        ])));

        foreach ($providers as $provider) {
            try {
                $result = match ($provider) {
                    'gemini' => $this->requestGemini($prompt),
                    'groq' => $this->requestGroq($prompt),
                    default => null,
                };

                if ($result !== null) {
                    return $this->formatResult(
                        $result,
                        $provider,
                        $this->providerModel($provider)
                    );
                }
            } catch (Throwable $exception) {
                Log::warning('AI analytics provider failed.', [
                    'provider' => $provider,
                    'institution_id' => $institution->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $this->buildLocalFallback($institution, $context);
    }

    protected function requestGemini(string $prompt): ?array
    {
        $apiKey = trim((string) config('services.gemini.api_key'));

        if ($apiKey === '') {
            return null;
        }

        $model = trim((string) config(
            'services.gemini.model',
            'gemini-3.1-flash-lite'
        ));

        $baseUrl = rtrim((string) config(
            'services.gemini.base_url',
            'https://generativelanguage.googleapis.com/v1beta/models'
        ), '/');

        $response = $this->httpClient()
            ->post(
                "{$baseUrl}/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => $this->maxOutputTokens(),
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $this->responseSchema(),
                    ],
                ]
            );

        $this->ensureSuccessfulResponse($response, 'gemini');

        $parts = $response->json('candidates.0.content.parts', []);

        if (!is_array($parts)) {
            throw new RuntimeException(
                'Gemini returned an invalid response structure.'
            );
        }

        $text = collect($parts)
            ->pluck('text')
            ->filter(fn ($value) => is_string($value))
            ->implode('');

        return $this->decodeStructuredResponse($text);
    }

    protected function requestGroq(string $prompt): ?array
    {
        $apiKey = trim((string) config('services.groq.api_key'));

        if ($apiKey === '') {
            return null;
        }

        $model = trim((string) config(
            'services.groq.model',
            'openai/gpt-oss-20b'
        ));

        $url = (string) config(
            'services.groq.base_url',
            'https://api.groq.com/openai/v1/chat/completions'
        );

        $response = $this->httpClient()
            ->withToken($apiKey)
            ->post($url, [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' =>
                            'You are an ICT infrastructure policy analyst. '
                            . 'Return only valid JSON that follows the supplied schema.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.2,
                'max_completion_tokens' => $this->maxOutputTokens(),
                'reasoning_effort' => 'low',
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'institution_ict_analysis',
                        'strict' => true,
                        'schema' => $this->responseSchema(),
                    ],
                ],
            ]);

        $this->ensureSuccessfulResponse($response, 'groq');

        $text = $response->json('choices.0.message.content');

        if (!is_string($text)) {
            throw new RuntimeException(
                'Groq returned an invalid response structure.'
            );
        }

        return $this->decodeStructuredResponse($text);
    }

    protected function httpClient()
    {
        return Http::acceptJson()
            ->asJson()
            ->connectTimeout((int) config(
                'services.ai.connect_timeout',
                5
            ))
            ->timeout((int) config(
                'services.ai.request_timeout',
                20
            ))
            ->retry(
                max(1, (int) config('services.ai.retry_times', 1)),
                max(0, (int) config('services.ai.retry_delay_ms', 300)),
                null,
                false
            );
    }

    protected function ensureSuccessfulResponse(
        Response $response,
        string $provider
    ): void {
        if ($response->successful()) {
            return;
        }

        $message = Str::limit(
            (string) (
                $response->json('error.message')
                ?? $response->body()
                ?? 'Unknown provider error.'
            ),
            500
        );

        throw new RuntimeException(
            ucfirst($provider)
            . " request failed with status {$response->status()}: "
            . $message
        );
    }

    protected function buildContext(
        Institution $institution,
        array $scores,
        array $planningEvidence = []
    ): array {
        $analytics = $institution->institutionAnalytics;

        return [
            'institution' => [
                'name' => $institution->name,
                'type' => $institution->type,
                'barangay' => $institution->barangay?->name,
                'population' => $institution->getAnalysisPopulation(),
                'student_population' => $institution->student_population,
                'staff_population' => $institution->staff_population,
                'isp_provider' => $institution->isp_provider,
                'internet_speed_mbps' => $institution->internet_speed,
                'has_computer_lab' => $institution->has_computer_lab,
                'number_of_computers' => $institution->number_of_computers,
                'has_projected_whiteboard' =>
                    $institution->has_projected_whiteboard,
                'has_smart_tv' => $institution->has_smart_tv,
                'has_public_address_system' =>
                    $institution->has_public_address_system,
                'needs_maintenance' => $institution->needs_maintenance,
                'requires_audit' => $institution->requires_audit,
                'notes' => $institution->notes,
            ],

            /*
             * These scores are authoritative outputs from the deterministic /
             * Python analytics layer. The generative AI only interprets them.
             */
            'calculated_scores' => [
                'gap_score' => $this->scoreValue(
                    $scores,
                    'gap_score',
                    $analytics?->gap_score
                ),

                'risk_level' => Arr::get(
                    $scores,
                    'risk_level',
                    $analytics?->risk_level
                ),

                'data_completeness_score' => $this->scoreValue(
                    $scores,
                    'data_completeness_score',
                    $analytics?->data_completeness_score
                        ?? $institution->data_completeness_score
                ),

                'infrastructure_deficit_score' => $this->scoreValue(
                    $scores,
                    'infrastructure_deficit_score',
                    $analytics?->infrastructure_deficit_score
                ),

                'connectivity_divide_score' => $this->scoreValue(
                    $scores,
                    'connectivity_divide_score',
                    $analytics?->connectivity_divide_score
                ),

                'technological_obsolescence_score' => $this->scoreValue(
                    $scores,
                    'technological_obsolescence_score',
                    $analytics?->technological_obsolescence_score
                ),

                'asset_to_user_ratio' => $this->scoreValue(
                    $scores,
                    'asset_to_user_ratio',
                    $analytics?->asset_to_user_ratio
                ),
            ],

            /*
             * Fresh, quantity-aware evidence built from the same inventory
             * snapshot used for scoring.
             */
            'planning_evidence' => $planningEvidence,
        ];
    }

    protected function buildPrompt(array $context): string
    {
        $contextJson = json_encode(
            $context,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_THROW_ON_ERROR
        );

        return <<<PROMPT
You are an ICT decision-support analyst assisting LGU ICT personnel and city
planners in Lipa City, Batangas, Philippines.

The numerical scores below are authoritative outputs of the application's
deterministic/Python analytics process. Do not recalculate, change, or
contradict them.

Score meaning:
- Deficit scores range from 0 to 100.
- A higher deficit score means a more serious verified gap.
- gap_score is the overall weighted ICT gap score.
- If gap_score is null or risk_level is "insufficient_data", avoid definitive
  investment recommendations and prioritize validation first.

Produce concise planning guidance, not a generic essay.

1. infrastructure_narrative
- Maximum 3 short sentences.
- Start with the most important verified planning concern.
- Use the strongest supplied numerical or recorded evidence.
- Mention the highest relevant deficit score when available.
- Explain what the evidence means for institutional ICT capacity.

2. policy_recommendation
- Maximum 3 short sentences.
- State what the LGU or institution should prioritize FIRST.
- Prefer a practical sequence when supported by the record:
  validate -> repair serviceable assets -> reassess remaining gap -> plan
  phased expansion or replacement.
- Do not invent exact budgets, procurement quantities, brands, suppliers,
  agencies, standards, or implementation dates.

3. priorities
- Return no more than 3 actions, ordered from most important to least important.
- "priority" must be a short action phrase.
- "rationale" must explain WHY using at least one supplied number or explicit
  recorded fact whenever available.
- "urgency" must be one of: immediate, short_term, medium_term.

Decision rules:
- Base every statement only on the supplied record and planning evidence.
- Prefer planning_evidence when it differs from older institution summary data.
- Do not invent usage statistics, outages, survey results, standards, or facts.
- Distinguish active assets from inactive, retired, and disposed assets.
- If repairable assets exist, recommend inspection/repair before suggesting
  broad replacement.
- For aging assets, recommend phased condition/replacement assessment rather
  than assuming every old asset must be replaced.
- A recorded internet speed alone does not prove poor actual performance.
  When connectivity is a concern, recommend reviewing capacity against actual
  simultaneous institutional use before specifying an upgrade.
- If data completeness is below 80 or requires_audit is true, include a
  validation action. Do not let a routine validation statement hide a clear,
  high-priority verified deficit.
- Treat missing values as unavailable data, not confirmed deficiencies.
- Avoid vague phrases such as "improve ICT infrastructure", "modernize
  equipment", or "upgrade connectivity" unless the same statement identifies
  the evidence and a practical next action.
- Keep the language formal, plain, and suitable for LGU planning discussions.
- Return only JSON matching the required schema.

Institution record and planning evidence:
{$contextJson}
PROMPT;
    }

    protected function responseSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'infrastructure_narrative' => [
                    'type' => 'string',
                    'description' =>
                        'Evidence-based summary of the institution ICT condition.',
                ],
                'policy_recommendation' => [
                    'type' => 'string',
                    'description' =>
                        'Practical recommendation for the institution or LGU.',
                ],
                'priorities' => [
                    'type' => 'array',
                    'maxItems' => 3,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'priority' => [
                                'type' => 'string',
                            ],
                            'rationale' => [
                                'type' => 'string',
                            ],
                            'urgency' => [
                                'type' => 'string',
                                'enum' => [
                                    'immediate',
                                    'short_term',
                                    'medium_term',
                                ],
                            ],
                        ],
                        'required' => [
                            'priority',
                            'rationale',
                            'urgency',
                        ],
                    ],
                ],
            ],
            'required' => [
                'infrastructure_narrative',
                'policy_recommendation',
                'priorities',
            ],
        ];
    }

    protected function decodeStructuredResponse(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            throw new RuntimeException('The AI provider returned empty text.');
        }

        $decoded = json_decode($text, true);

        if (!is_array($decoded)) {
            $decoded = json_decode(
                $this->extractJsonObject($text),
                true
            );
        }

        if (!is_array($decoded)) {
            throw new RuntimeException(
                'The AI provider did not return valid JSON.'
            );
        }

        $narrative = trim((string) (
            $decoded['infrastructure_narrative'] ?? ''
        ));

        $recommendation = trim((string) (
            $decoded['policy_recommendation'] ?? ''
        ));

        if ($narrative === '' || $recommendation === '') {
            throw new RuntimeException(
                'The AI response is missing required narrative fields.'
            );
        }

        return [
            'infrastructure_narrative' => Str::limit(
                $narrative,
                1500,
                ''
            ),
            'policy_recommendation' => Str::limit(
                $recommendation,
                1500,
                ''
            ),
            'priorities' => $this->normalizePriorities(
                $decoded['priorities'] ?? []
            ),
        ];
    }

    protected function extractJsonObject(string $text): string
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            return '';
        }

        return substr($text, $start, $end - $start + 1);
    }

    protected function normalizePriorities(mixed $priorities): array
    {
        if (!is_array($priorities)) {
            return [];
        }

        $allowedUrgencies = [
            'immediate',
            'short_term',
            'medium_term',
        ];

        return collect($priorities)
            ->filter(fn ($priority) => is_array($priority))
            ->map(function (array $priority) use ($allowedUrgencies) {
                $urgency = strtolower(trim((string) (
                    $priority['urgency'] ?? 'medium_term'
                )));

                if (!in_array($urgency, $allowedUrgencies, true)) {
                    $urgency = 'medium_term';
                }

                return [
                    'priority' => Str::limit(
                        trim((string) ($priority['priority'] ?? '')),
                        250,
                        ''
                    ),
                    'rationale' => Str::limit(
                        trim((string) ($priority['rationale'] ?? '')),
                        500,
                        ''
                    ),
                    'urgency' => $urgency,
                ];
            })
            ->filter(
                fn (array $priority) =>
                    $priority['priority'] !== ''
                    && $priority['rationale'] !== ''
            )
            ->take(3)
            ->values()
            ->all();
    }

    protected function formatResult(
        array $result,
        string $provider,
        string $model
    ): array {
        return [
            'infrastructure_narrative' =>
                $result['infrastructure_narrative'],
            'policy_recommendation' =>
                $result['policy_recommendation'],
            'recommendations_detail' => [
                'method' => 'generative_ai',
                'provider' => $provider,
                'model' => $model,
                'priorities' => $result['priorities'],
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    protected function buildLocalFallback(
        Institution $institution,
        array $context
    ): array {
        $scores = $context['calculated_scores'] ?? [];
        $evidence = $context['planning_evidence'] ?? [];

        $gapScore = $scores['gap_score'] ?? null;
        $riskLevel = strtolower((string) (
            $scores['risk_level'] ?? 'insufficient_data'
        ));
        $completeness =
            $scores['data_completeness_score'] ?? null;

        $rankedDeficits =
            $this->rankedDeficits($scores);

        $primary =
            $rankedDeficits[0] ?? null;

        $narrativeParts = [];

        if ($primary !== null) {
            $narrativeParts[] =
                $primary['label']
                . ' is the highest available planning concern at '
                . $this->numberText($primary['score'])
                . '%.';

            $primaryEvidence =
                $this->evidenceForArea(
                    $primary['key'],
                    $evidence
                );

            if ($primaryEvidence !== '') {
                $narrativeParts[] =
                    $primaryEvidence;
            }
        } else {
            $narrativeParts[] =
                'The current record does not contain enough scored evidence '
                . 'to identify a dominant ICT gap.';
        }

        if ($gapScore !== null) {
            $narrativeParts[] =
                'The overall ICT gap score is '
                . $this->numberText($gapScore)
                . '%.';
        }

        $narrative =
            implode(
                ' ',
                array_slice($narrativeParts, 0, 3)
            );

        $needsValidation =
            $riskLevel === 'insufficient_data'
            || $gapScore === null
            || (
                is_numeric($completeness)
                && (float) $completeness < 80
            )
            || $institution->requires_audit;

        $priorities = [];

        /*
         * If scoring is not reliable enough for a definitive result,
         * validation is the first action.
         */
        if (
            $riskLevel === 'insufficient_data'
            || $gapScore === null
        ) {
            $priorities[] =
                $this->validationPriority(
                    $completeness,
                    (bool) $institution->requires_audit
                );
        }

        foreach ($rankedDeficits as $dimension) {
            if (count($priorities) >= 3) {
                break;
            }

            if (
                !is_numeric($dimension['score'])
                || (float) $dimension['score'] <= 0
            ) {
                continue;
            }

            $priorities[] = [
                'priority' =>
                    $this->priorityActionForArea(
                        $dimension['key'],
                        $evidence
                    ),

                'rationale' =>
                    $this->evidenceForArea(
                        $dimension['key'],
                        $evidence
                    )
                    ?: (
                        $dimension['label']
                        . ' has a calculated deficit score of '
                        . $this->numberText(
                            $dimension['score']
                        )
                        . '%.'
                    ),

                'urgency' =>
                    $this->urgencyFromScore(
                        (float) $dimension['score']
                    ),
            ];
        }

        if (
            $needsValidation
            && count($priorities) < 3
            && !collect($priorities)->contains(
                fn (array $item): bool =>
                    $item['priority']
                    === 'Validate the institution ICT record'
            )
        ) {
            $priorities[] =
                $this->validationPriority(
                    $completeness,
                    (bool) $institution->requires_audit
                );
        }

        if ($priorities === []) {
            $priorities[] = [
                'priority' =>
                    'Maintain and periodically validate ICT capacity',

                'rationale' =>
                    'No material scored deficiency is confirmed by the available record.',

                'urgency' => 'medium_term',
            ];
        }

        $recommendation =
            $primary !== null
                ? $this->recommendationForArea(
                    $primary['key'],
                    $evidence,
                    $needsValidation
                )
                : (
                    'Complete the missing ICT profile and field validation '
                    . 'before making major resource-allocation decisions.'
                );

        return [
            'infrastructure_narrative' =>
                Str::limit(
                    $narrative,
                    900,
                    ''
                ),

            'policy_recommendation' =>
                Str::limit(
                    $recommendation,
                    900,
                    ''
                ),

            'recommendations_detail' => [
                'method' => 'local_fallback',
                'provider' => 'local',
                'model' => null,
                'priorities' =>
                    array_slice(
                        $priorities,
                        0,
                        3
                    ),
                'generated_at' =>
                    now()->toIso8601String(),
            ],
        ];
    }

    protected function rankedDeficits(
        array $scores
    ): array {
        $dimensions = [
            [
                'key' => 'infrastructure',
                'label' => 'Infrastructure deficit',
                'score' =>
                    $scores[
                        'infrastructure_deficit_score'
                    ] ?? null,
            ],
            [
                'key' => 'connectivity',
                'label' => 'Connectivity divide',
                'score' =>
                    $scores[
                        'connectivity_divide_score'
                    ] ?? null,
            ],
            [
                'key' => 'obsolescence',
                'label' => 'Technological obsolescence',
                'score' =>
                    $scores[
                        'technological_obsolescence_score'
                    ] ?? null,
            ],
        ];

        $dimensions =
            array_values(
                array_filter(
                    $dimensions,
                    fn (array $item): bool =>
                        is_numeric($item['score'])
                )
            );

        usort(
            $dimensions,
            fn (array $a, array $b): int =>
                (float) $b['score']
                <=> (float) $a['score']
        );

        return $dimensions;
    }

    protected function evidenceForArea(
        string $area,
        array $evidence
    ): string {
        return match ($area) {
            'infrastructure' =>
                $this->infrastructureEvidence(
                    $evidence
                ),

            'connectivity' =>
                $this->connectivityEvidence(
                    $evidence
                ),

            'obsolescence' =>
                $this->obsolescenceEvidence(
                    $evidence
                ),

            default => '',
        };
    }

    protected function infrastructureEvidence(
        array $evidence
    ): string {
        $population =
            $evidence['population'] ?? null;

        $populationLabel =
            $evidence['population_label']
            ?? 'users';

        $computers =
            $evidence['active_computers']
            ?? null;

        $usersPerComputer =
            $evidence['users_per_computer']
            ?? null;

        if (
            is_numeric($population)
            && (int) $population > 0
            && is_numeric($computers)
        ) {
            if ((int) $computers <= 0) {
                return
                    'No active computers are recorded for '
                    . number_format(
                        (int) $population
                    )
                    . " {$populationLabel}.";
            }

            $text =
                number_format(
                    (int) $computers
                )
                . ' active computer(s) serve '
                . number_format(
                    (int) $population
                )
                . " {$populationLabel}";

            if (is_numeric($usersPerComputer)) {
                $text .=
                    ', or about '
                    . $this->numberText(
                        $usersPerComputer
                    )
                    . ' users per computer';
            }

            return $text . '.';
        }

        if (is_numeric($computers)) {
            return
                number_format(
                    (int) $computers
                )
                . ' active computer(s) are recorded.';
        }

        return '';
    }

    protected function connectivityEvidence(
        array $evidence
    ): string {
        $speed =
            $evidence['internet_speed_mbps']
            ?? null;

        $provider =
            trim(
                (string) (
                    $evidence['isp_provider']
                    ?? ''
                )
            );

        if (!is_numeric($speed)) {
            return
                'Internet speed is not recorded and should be validated.';
        }

        $text =
            'Recorded internet speed is '
            . $this->numberText($speed)
            . ' Mbps';

        if ($provider !== '') {
            $text .=
                " through {$provider}";
        }

        return $text . '.';
    }

    protected function obsolescenceEvidence(
        array $evidence
    ): string {
        $total =
            $evidence['total_ict_assets']
            ?? null;

        if (!is_numeric($total)) {
            return '';
        }

        $parts = [];

        $old =
            $evidence['old_equipment_count']
            ?? null;

        $repair =
            $evidence['needs_repair_count']
            ?? null;

        $poor =
            $evidence['poor_condition_count']
            ?? null;

        $retired =
            $evidence['retired_or_disposed_count']
            ?? null;

        $averageAge =
            $evidence['average_equipment_age_years']
            ?? null;

        if (is_numeric($old)) {
            $parts[] =
                number_format((int) $old)
                . ' of '
                . number_format((int) $total)
                . ' assets are at least five years old';
        }

        if (
            is_numeric($repair)
            && (int) $repair > 0
        ) {
            $parts[] =
                number_format((int) $repair)
                . ' need repair or maintenance';
        }

        if (
            is_numeric($poor)
            && (int) $poor > 0
        ) {
            $parts[] =
                number_format((int) $poor)
                . ' are in poor or needs-repair condition';
        }

        if (
            is_numeric($retired)
            && (int) $retired > 0
        ) {
            $parts[] =
                number_format((int) $retired)
                . ' are retired or disposed';
        }

        if (is_numeric($averageAge)) {
            $parts[] =
                'average recorded equipment age is '
                . $this->numberText($averageAge)
                . ' years';
        }

        return $parts === []
            ? (
                number_format((int) $total)
                . ' ICT assets are recorded.'
            )
            : ucfirst(
                implode('; ', $parts)
            ) . '.';
    }

    protected function priorityActionForArea(
        string $area,
        array $evidence
    ): string {
        return match ($area) {
            'infrastructure' =>
                (int) (
                    $evidence[
                        'needs_repair_count'
                    ] ?? 0
                ) > 0
                    ? 'Restore usable computer capacity and assess remaining needs'
                    : 'Assess and address computer access capacity',

            'connectivity' =>
                'Validate and address institutional connectivity capacity',

            'obsolescence' =>
                (int) (
                    $evidence[
                        'needs_repair_count'
                    ] ?? 0
                ) > 0
                    ? 'Inspect repairable assets and plan phased replacement'
                    : 'Plan phased assessment of aging ICT assets',

            default =>
                'Review the identified ICT gap',
        };
    }

    protected function recommendationForArea(
        string $area,
        array $evidence,
        bool $needsValidation
    ): string {
        $validationSuffix =
            $needsValidation
                ? (
                    ' Confirm incomplete or audit-pending information '
                    . 'before approving major resource commitments.'
                )
                : '';

        return match ($area) {
            'infrastructure' =>
                (
                    (int) (
                        $evidence[
                            'needs_repair_count'
                        ] ?? 0
                    ) > 0
                        ? (
                            'Prioritize restoring usable ICT capacity first by '
                            . 'inspecting and repairing serviceable assets, then '
                            . 'reassess the remaining computer-access gap before '
                            . 'planning additional capacity.'
                        )
                        : (
                            'Prioritize the verified computer-access gap and '
                            . 'assess the remaining capacity needed to support '
                            . 'the recorded service population.'
                        )
                ) . $validationSuffix,

            'connectivity' =>
                (
                    'Prioritize validation of actual simultaneous-use '
                    . 'connectivity demand against the recorded bandwidth, '
                    . 'then plan capacity improvement only if that review '
                    . 'confirms the calculated connectivity gap.'
                ) . $validationSuffix,

            'obsolescence' =>
                (
                    'Prioritize condition assessment of the oldest and '
                    . 'poor-condition asset groups, repair serviceable units '
                    . 'first, and use the results to plan phased replacement '
                    . 'rather than replacing all older equipment at once.'
                ) . $validationSuffix,

            default =>
                (
                    'Use the verified highest-scoring deficit as the first '
                    . 'planning priority.'
                ) . $validationSuffix,
        };
    }

    protected function validationPriority(
        mixed $completeness,
        bool $requiresAudit
    ): array {
        $facts = [];

        if (is_numeric($completeness)) {
            $facts[] =
                'data completeness is '
                . $this->numberText(
                    $completeness
                )
                . '%';
        }

        if ($requiresAudit) {
            $facts[] =
                'the institution is marked as requiring audit';
        }

        return [
            'priority' =>
                'Validate the institution ICT record',

            'rationale' =>
                $facts !== []
                    ? ucfirst(
                        implode(
                            ' and ',
                            $facts
                        )
                    ) . '.'
                    : (
                        'The current record requires validation '
                        . 'before major resource decisions.'
                    ),

            'urgency' =>
                'short_term',
        ];
    }

    protected function urgencyFromScore(
        float $score
    ): string {
        return match (true) {
            $score >= 80 =>
                'immediate',

            $score >= 50 =>
                'short_term',

            default =>
                'medium_term',
        };
    }

    protected function numberText(
        mixed $value
    ): string {
        if (!is_numeric($value)) {
            return '0';
        }

        $number =
            round(
                (float) $value,
                2
            );

        return rtrim(
            rtrim(
                number_format(
                    $number,
                    2,
                    '.',
                    ''
                ),
                '0'
            ),
            '.'
        );
    }

    protected function scoreValue(
        array $scores,
        string $key,
        mixed $fallback
    ): ?float {
        $value = Arr::get($scores, $key, $fallback);

        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    protected function providerModel(string $provider): string
    {
        return match ($provider) {
            'gemini' => (string) config(
                'services.gemini.model',
                'gemini-3.1-flash-lite'
            ),
            'groq' => (string) config(
                'services.groq.model',
                'openai/gpt-oss-20b'
            ),
            default => '',
        };
    }

    protected function maxOutputTokens(): int
    {
        return max(
            256,
            min(
                2000,
                (int) config('services.ai.max_output_tokens', 700)
            )
        );
    }
}