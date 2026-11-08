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
        $inputSummary = $this->inputSummary($context);
        $attempts = [];

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

        Log::info('AI prescriptive analytics prepared.', [
            'institution_id' => $institution->id,
            'providers' => $providers,
            'prompt_characters' => mb_strlen($prompt),
            'input_summary' => $inputSummary,
        ]);

        foreach ($providers as $provider) {
            $model = $this->providerModel($provider);
            $apiKeyConfigured =
                $this->providerApiKey($provider) !== '';

            if (!in_array($provider, ['gemini', 'groq'], true)) {
                $attempts[] = [
                    'provider' => $provider,
                    'model' => $model,
                    'status' => 'skipped',
                    'message' =>
                        'Unsupported AI provider configuration.',
                ];

                Log::warning('AI analytics provider skipped.', [
                    'provider' => $provider,
                    'institution_id' => $institution->id,
                    'reason' => 'unsupported_provider',
                ]);

                continue;
            }

            if (!$apiKeyConfigured) {
                $attempts[] = [
                    'provider' => $provider,
                    'model' => $model,
                    'status' => 'skipped',
                    'message' =>
                        strtoupper($provider)
                        . '_API_KEY is not configured.',
                ];

                Log::warning('AI analytics provider skipped.', [
                    'provider' => $provider,
                    'institution_id' => $institution->id,
                    'model' => $model,
                    'reason' => 'api_key_missing',
                ]);

                continue;
            }

            Log::info('AI analytics provider request starting.', [
                'provider' => $provider,
                'model' => $model,
                'institution_id' => $institution->id,
                'prompt_characters' => mb_strlen($prompt),
            ]);

            try {
                $result = match ($provider) {
                    'gemini' => $this->requestGemini($prompt),
                    'groq' => $this->requestGroq($prompt),
                };

                if ($result === null) {
                    throw new RuntimeException(
                        'AI provider returned no structured result.'
                    );
                }

                $attempts[] = [
                    'provider' => $provider,
                    'model' => $model,
                    'status' => 'success',
                    'message' =>
                        'Structured prescriptive analytics received.',
                ];

                Log::info('AI analytics provider request completed.', [
                    'provider' => $provider,
                    'model' => $model,
                    'institution_id' => $institution->id,
                ]);

                return $this->formatResult(
                    $result,
                    $provider,
                    $model,
                    $attempts,
                    $inputSummary
                );
            } catch (Throwable $exception) {
                $message = $this->safeAttemptMessage(
                    $exception->getMessage()
                );

                $attempts[] = [
                    'provider' => $provider,
                    'model' => $model,
                    'status' => 'failed',
                    'message' => $message,
                ];

                Log::warning('AI analytics provider failed.', [
                    'provider' => $provider,
                    'model' => $model,
                    'institution_id' => $institution->id,
                    'error' => $message,
                ]);
            }
        }

        Log::warning(
            'AI analytics providers unavailable; local prescriptive fallback used.',
            [
                'institution_id' => $institution->id,
                'attempts' => $attempts,
            ]
        );

        return $this->buildLocalFallback(
            $institution,
            $context,
            $attempts,
            $inputSummary
        );
    }

    protected function requestGemini(string $prompt): ?array
    {
        $apiKey = trim((string) config('services.gemini.api_key'));

        if ($apiKey === '') {
            return null;
        }

        $model = trim((string) config(
            'services.gemini.model',
            'gemini-2.5-flash-lite'
        ));

        $baseUrl = rtrim((string) config(
            'services.gemini.base_url',
            'https://generativelanguage.googleapis.com/v1beta/models'
        ), '/');

        $response = $this->httpClient()
            ->post(
                "{$baseUrl}/{$model}:generateContent?key={$apiKey}",
                [
                    'systemInstruction' => [
                        'parts' => [
                            [
                                'text' => $this->systemPrompt(),
                            ],
                        ],
                    ],
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
                        'responseSchema' => $this->geminiResponseSchema(),
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

        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt()
                        . "\nReturn exactly one JSON object matching this schema; no Markdown or commentary:\n"
                        . json_encode($this->responseSchema(), JSON_THROW_ON_ERROR),
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.2,
            // Reasoning models share this budget between reasoning and the JSON answer.
            'max_completion_tokens' => $this->groqCompletionTokens(),
            'response_format' => in_array($model, ['openai/gpt-oss-20b', 'openai/gpt-oss-120b'], true) ? [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'institution_ict_analysis',
                    'strict' => true,
                    'schema' => $this->responseSchema(),
                ],
            ] : ['type' => 'json_object'],
        ];

        if (in_array($model, ['openai/gpt-oss-20b', 'openai/gpt-oss-120b'], true)) {
            $payload['reasoning_effort'] = 'low';
        }

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $response = $this->httpClient()->withToken($apiKey)->post($url, $payload);
            $formatFailure = $response->status() === 400 && $response->json('error.code') === 'json_validate_failed';
            if (! $formatFailure) {
                // Authentication, quota, and other HTTP errors must not trigger a format retry.
                $this->ensureSuccessfulResponse($response, 'groq');
                try {
                    return $this->decodeGroqResponse($response);
                } catch (RuntimeException $exception) {
                    if ($attempt === 1) {
                        throw $exception;
                    }
                }
            } elseif ($attempt === 1) {
                $this->ensureSuccessfulResponse($response, 'groq');
            }

            Log::warning('Groq structured generation failed; retrying once in JSON object mode.', [
                'model' => $model, 'status' => $response->status(),
                'error_code' => $response->json('error.code'),
                'finish_reason' => $response->json('choices.0.finish_reason'),
                'max_completion_tokens' => $payload['max_completion_tokens'],
                'request_id' => $response->json('request_id') ?? $response->header('x-request-id'),
            ]);
            // Change the failed strategy instead of repeating the same constrained generation.
            // Local validation below still enforces every field, urgency, and priority limit.
            $payload['response_format'] = ['type' => 'json_object'];
            $payload['max_completion_tokens'] = min(8192, $payload['max_completion_tokens'] * 2);
            $payload['temperature'] = 0;
            $payload['messages'][0]['content'] .= "\nThe previous generation failed JSON validation."
                . ' Include all required fields, use strings for narratives, and an array of at most three priorities.'
                . ' Each priority must contain priority, rationale, and urgency. Use only the allowed urgency values.';
        }

        throw new RuntimeException('Groq could not generate valid analytics JSON.');
    }

    protected function groqCompletionTokens(): int
    {
        return max(1024, min(8192, (int) config('services.groq.max_completion_tokens', 2400)));
    }

    protected function decodeGroqResponse(Response $response): array
    {
        if ($response->json('choices.0.finish_reason') === 'length') {
            throw new RuntimeException('Groq reached its completion token limit before finishing the analytics JSON.');
        }

        $text = $response->json('choices.0.message.content');

        if (!is_string($text)) {
            throw new RuntimeException(
                'Groq returned an invalid response structure.'
            );
        }

        $object = json_decode($text);
        $decoded = json_decode($text, true);
        if (! $object instanceof \stdClass || ! is_array($decoded)) {
            throw new RuntimeException('Groq did not return a valid JSON object.');
        }
        if (! is_array($object->priorities ?? null)) {
            throw new RuntimeException('Groq returned JSON that does not match the analytics schema.');
        }
        $validator = \Illuminate\Support\Facades\Validator::make(
            $decoded,
            [
                'infrastructure_narrative' => 'required|string',
                'policy_recommendation' => 'required|string',
                'priorities' => 'present|array|max:3',
                'priorities.*' => 'array:priority,rationale,urgency',
                'priorities.*.priority' => 'required|string',
                'priorities.*.rationale' => 'required|string',
                'priorities.*.urgency' => 'required|in:immediate,short_term,medium_term',
            ]
        );
        if ($validator->fails()
            || array_diff(array_keys($decoded), $this->responseSchema()['required'])) {
            throw new RuntimeException('Groq returned JSON that does not match the analytics schema.');
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
            ],
            'calculated_scores' => [
                'overall_ict_gap' => $this->scoreValue(
                    $scores,
                    'gap_score',
                    $analytics?->gap_score
                ),
                'assessment_status' =>
                    $scores['risk_level']
                    ?? $analytics?->risk_level
                    ?? 'insufficient_data',
                'infrastructure_deficit' =>
                    $this->scoreValue(
                        $scores,
                        'infrastructure_deficit_score',
                        $analytics?->infrastructure_deficit_score
                    ),
                'connectivity_divide' =>
                    $this->scoreValue(
                        $scores,
                        'connectivity_divide_score',
                        $analytics?->connectivity_divide_score
                    ),
                'technological_obsolescence_proxy' =>
                    $this->scoreValue(
                        $scores,
                        'technological_obsolescence_score',
                        $analytics?->technological_obsolescence_score
                    ),
            ],
            'planning_evidence' => $planningEvidence,
        ];
    }

    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
You are the prescriptive analytics layer of TALASTAS, an ICT infrastructure
decision-support system for public institutions in Lipa City.

The numerical values supplied under calculated_scores are AUTHORITATIVE,
DETERMINISTIC, and IMMUTABLE. They were computed before this AI request.

NON-NEGOTIABLE RULES
1. Never calculate, recalculate, average, normalize, estimate, replace, round,
   correct, reinterpret, or override any numerical score.
2. Never create a score when a supplied score is null or unavailable.
3. Never infer a missing value as zero, absent, compliant, defective, or adequate.
   A confirmed_zero inventory status is recorded evidence, not missing baseline
   data. Zero-denominator inventory ratios are unavailable, not proof of a gap.
   Do not assume every institution requires every component or operating system.
4. Never invent thresholds, severity bands, minimum device ratios, target Mbps,
   procurement quantities, budgets, brands, vendors, causes, laws, standards,
   outage histories, or compliance claims.
5. Treat Technological Obsolescence strictly as a RETIREMENT-BASED PROXY.
   Never describe it as hardware age, depreciation, end-of-life, or remaining
   useful life.
6. Use only the supplied deterministic scores and field evidence.
7. Recommendations must follow from observable evidence. Defective units require
   verification/repair assessment before replacement is proposed. Bandwidth
   shortfall requires measurement/service validation before capacity action.
8. Urgency is operational planning guidance, not a research severity class.
   Use "immediate" only when the evidence explicitly shows a service-stopping
   condition such as no active applicable infrastructure or no Internet service.
9. Return valid JSON only and follow the response schema exactly.

BASELINE DEFINITIONS
- Infrastructure Deficit:
  Explicitly recorded Defective within Active / Active Applicable Units x 100. Active means deployed/in-service and may include defective units. General Defective and Functional are separate responses. Never infer missing Defective within Active.
- Connectivity Divide:
  For each connection, calculate download and upload shortfall as
  max(identified acquired Mbps - measured Mbps, 0) / identified acquired Mbps x 100,
  then average the two. Average connection scores only if every reported connection
  has a numeric acquired speed in the Plan field (treated as Mbps) and both
  measured speeds. Never infer acquired speed from prices, latency, throughput or
  an external target.
- Technological Obsolescence Proxy:
  Retired Applicable Units / Total Applicable Units x 100.
- Overall ICT Gap:
  arithmetic mean of the three core component percentages, available only when
  all three components are available.

Your role is PRESCRIPTIVE only: explain the deterministic result, identify
evidence-based priorities, and formulate practical policy recommendations.
PROMPT;
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
TASK

Produce a concise, evidence-based prescriptive analysis for the supplied
institution.

infrastructure_narrative
- Maximum 3 short sentences.
- State the strongest observed issue first.
- Use exact supplied counts, percentages, Mbps values, or recorded yes/no facts.
- Distinguish defective active infrastructure, Internet performance, and
  retired inventory.
- If Overall ICT Gap is unavailable, state that the required core evidence is
  incomplete without assigning a replacement score.

policy_recommendation
- Maximum 3 short sentences.
- Give a practical action sequence based on the evidence.
- Prefer: validate evidence -> inspect/repair -> verify Internet performance ->
  reassess -> plan phased action.
- Do not prescribe quantities or budgets not supplied by the data.

priorities
- Return at most 3 actions ordered by practical need.
- Every rationale must cite at least one supplied fact.
- urgency must be one of: immediate, short_term, medium_term.

DATA
{$contextJson}
PROMPT;
    }

    protected function geminiResponseSchema(): array
    {
        $schema = $this->responseSchema();
        // Gemini's responseSchema rejects this JSON Schema keyword; Groq's
        // strict JSON schema still needs it, so retain the shared definition.
        unset($schema['additionalProperties'], $schema['properties']['priorities']['items']['additionalProperties']);
        return $schema;
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
        string $model,
        array $attempts = [],
        array $inputSummary = []
    ): array {
        return [
            'infrastructure_narrative' =>
                $result['infrastructure_narrative'],
            'policy_recommendation' =>
                $result['policy_recommendation'],
            'recommendations_detail' => [
                'method' => 'generative_ai_prescriptive',
                'ai_status' => 'generated',
                'provider' => $provider,
                'model' => $model,
                'provider_attempts' => $attempts,
                'input_summary' => $inputSummary,
                'priorities' => $result['priorities'],
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    protected function buildLocalFallback(
        Institution $institution,
        array $context,
        array $attempts = [],
        array $inputSummary = []
    ): array {
        $scores = $context['calculated_scores'] ?? [];
        $evidence = $context['planning_evidence'] ?? [];
        $inventory = $evidence['inventory_totals'] ?? [];
        $connectivity = $evidence['connectivity'] ?? [];

        $parts = [];

        if (is_numeric($scores['infrastructure_deficit'] ?? null)) {
            $parts[] =
                number_format((int) ($inventory['defective_active'] ?? 0))
                . ' defective active applicable ICT unit(s) are recorded out of '
                . number_format((int) ($inventory['active'] ?? 0))
                . ' active unit(s).';
        }

        if (($connectivity['has_internet_connection'] ?? null) === false) {
            $parts[] = 'The encoded ICT data explicitly records no Internet connection.';
        } elseif (is_numeric($scores['connectivity_divide'] ?? null)) {
            $parts[] =
                'Mean acquired-Mbps download and upload shortfall is '
                . $this->numberText($scores['connectivity_divide'])
                . '% across '.(int) ($connectivity['valid_connection_count'] ?? 0).' recorded connection(s).';
        } elseif (isset($connectivity['reason_codes'])) {
            $parts[] = 'Connectivity could not be assessed. '.implode(' ', array_map(
                [InternetConnectivityAssessment::class, 'reasonLabel'], $connectivity['reason_codes']
            ));
        }

        if (is_numeric($scores['technological_obsolescence_proxy'] ?? null)) {
            $parts[] =
                number_format((int) ($inventory['retired'] ?? 0))
                . ' of '
                . number_format((int) ($inventory['total'] ?? 0))
                . ' applicable ICT unit(s) are recorded as retired.';
        }

        if ($parts === []) {
            $parts[] =
                'The current ICT data record is incomplete for a full ICT gap interpretation.';
        }

        if (($evidence['inventory_status'] ?? null) === 'confirmed_zero') {
            $parts[] = 'Applicable inventory counts are confirmed as zero. Inventory ratios have no denominator and are unavailable; these counts are not missing baseline data.';
        }

        $priorities = [];

        if (($scores['assessment_status'] ?? 'insufficient_data') === 'insufficient_data'
            && (($evidence['inventory_status'] ?? null) !== 'confirmed_zero'
                || !is_numeric($scores['connectivity_divide'] ?? null))) {
            $priorities[] = [
                'priority' => 'Complete missing required ICT data',
                'rationale' =>
                    'A complete three-dimension composite gap score cannot yet be produced from the available record.',
                'urgency' => 'short_term',
            ];
        }

        if ((int) ($inventory['defective'] ?? 0) > 0) {
            $priorities[] = [
                'priority' => 'Validate and assess recorded defective ICT components',
                'rationale' =>
                    number_format((int) $inventory['defective'])
                    . ' applicable unit(s) are recorded as generally defective; confirm their service status.',
                'urgency' => 'short_term',
            ];
        }

        if (($connectivity['has_internet_connection'] ?? null) === false) {
            $priorities[] = [
                'priority' => 'Validate the absence of institutional Internet service',
                'rationale' =>
                    'The encoded ICT data explicitly records no Internet connection.',
                'urgency' => 'immediate',
            ];
        } elseif (
            is_numeric($scores['connectivity_divide'] ?? null)
            && (float) $scores['connectivity_divide'] > 0
        ) {
            $priorities[] = [
                'priority' => 'Verify Internet performance against the recorded bandwidth allocation',
                'rationale' =>
                    'The field-data-derived connectivity gap is '
                    . $this->numberText($scores['connectivity_divide'])
                    . '%.',
                'urgency' => 'short_term',
            ];
        }

        if ((int) ($inventory['retired'] ?? 0) > 0) {
            $priorities[] = [
                'priority' => 'Review retired ICT stock for replacement planning and disposition',
                'rationale' =>
                    number_format((int) $inventory['retired'])
                    . ' applicable unit(s) are recorded as retired.',
                'urgency' => 'medium_term',
            ];
        }

        if ($priorities === []) {
            $priorities[] = [
                'priority' => 'Continue periodic field-data-based validation',
                'rationale' =>
                    'No defective active units, recorded bandwidth shortfall, or retired applicable units are confirmed by the available record.',
                'urgency' => 'medium_term',
            ];
        }

        $priorities = array_slice($priorities, 0, 3);

        return [
            'infrastructure_narrative' =>
                Str::limit(implode(' ', array_slice($parts, 0, 3)), 1200, ''),
            'policy_recommendation' =>
                Str::limit(
                    collect($priorities)->pluck('priority')->implode('. ') . '.',
                    900,
                    ''
                ),
            'recommendations_detail' => [
                'method' => 'local_field_data_prescriptive_fallback',
                'ai_status' => 'fallback',
                'provider' => 'local',
                'model' => null,
                'provider_attempts' => $attempts,
                'input_summary' => $inputSummary,
                'priorities' => $priorities,
                'generated_at' => now()->toIso8601String(),
            ],
        ];
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

    protected function providerApiKey(string $provider): string
    {
        return match ($provider) {
            'gemini' => trim((string) config(
                'services.gemini.api_key'
            )),
            'groq' => trim((string) config(
                'services.groq.api_key'
            )),
            default => '',
        };
    }

    protected function inputSummary(array $context): array
    {
        $evidence =
            $context['planning_evidence'] ?? [];

        return [
            'institution' =>
                $context['institution'] ?? [],
            'calculated_scores' =>
                $context['calculated_scores'] ?? [],
            'inventory_totals' =>
                $evidence['inventory_totals'] ?? [],
            'availability_status' => $evidence['availability_status'] ?? null,
            'availability_recorded_row_counts' => $evidence['availability_recorded_row_counts'] ?? [],
            'connectivity' =>
                $evidence['connectivity'] ?? [],
            'top_defective_components' =>
                $this->compactComponentEvidence(
                    $evidence['top_defective_components']
                    ?? []
                ),
            'top_retired_components' =>
                $this->compactComponentEvidence(
                    $evidence['top_retired_components']
                    ?? []
                ),
        ];
    }

    protected function compactComponentEvidence(
        mixed $items
    ): array {
        if (!is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->take(5)
            ->map(fn (array $item): array => [
                'label' =>
                    (string) ($item['label'] ?? ''),
                'total_count' =>
                    (int) ($item['total_count'] ?? 0),
                'active_count' =>
                    isset($item['active_count']) ? (int) $item['active_count'] : null,
                'retired_count' =>
                    isset($item['retired_count']) ? (int) $item['retired_count'] : null,
                'functional_count' => isset($item['functional_count']) ? (int) $item['functional_count'] : null,
                'defective_count' =>
                    isset($item['defective_count']) ? (int) $item['defective_count'] : null,
            ])
            ->values()
            ->all();
    }

    protected function safeAttemptMessage(string $message): string
    {
        $message = preg_replace(
            '/key=[^&\s]+/i',
            'key=[redacted]',
            $message
        ) ?? $message;

        return Str::limit(
            strip_tags($message),
            500,
            ''
        );
    }

    protected function providerModel(string $provider): string
    {
        return match ($provider) {
            'gemini' => (string) config(
                'services.gemini.model',
                'gemini-2.5-flash-lite'
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
