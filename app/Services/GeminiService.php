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
        array $scores = []
    ): array {
        return $this->analyze($institution, $scores);
    }

    public function analyze(
        Institution $institution,
        array $scores = []
    ): array {
        $institution->loadMissing('barangay');

        $context = $this->buildContext($institution, $scores);
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
        array $scores
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
Analyze the ICT infrastructure record below for decision support in Lipa City,
Batangas, Philippines.

The numerical scores were calculated by the application's deterministic
analytics process. Do not change, recalculate, or contradict those scores.

Write:
1. A concise infrastructure narrative describing the evidence in the record.
2. A practical policy recommendation for the LGU or institution.
3. Up to five prioritized actions.

Rules:
- Use formal, clear English.
- Base every statement only on the supplied record.
- Do not invent equipment, budgets, agencies, dates, or survey findings.
- Treat missing values as unavailable data, not as confirmed deficiencies.
- Mention the need for validation when data completeness is low or an audit is
  required.
- Keep each narrative under 900 characters.
- Return only JSON matching the required schema.

Institution record:
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
                    'maxItems' => 5,
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
            ->take(5)
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
        $record = $context['institution'];
        $scores = $context['calculated_scores'];

        $population = (int) ($record['population'] ?? 0);
        $computers = (int) ($record['number_of_computers'] ?? 0);
        $speed = $record['internet_speed_mbps'];
        $gapScore = $scores['gap_score'];
        $riskLevel = $scores['risk_level'] ?: 'unclassified';

        $facts = [];

        if ($speed !== null) {
            $facts[] = "recorded internet speed is {$speed} Mbps";
        } else {
            $facts[] = 'internet speed is not recorded';
        }

        $facts[] = "{$computers} computer(s) are recorded";

        if ($population > 0) {
            $facts[] = "the recorded service population is {$population}";
        }

        if ($institution->needs_maintenance) {
            $facts[] = 'maintenance is required';
        }

        $scoreText = $gapScore !== null
            ? " The calculated ICT gap score is {$gapScore}% with a "
                . "{$riskLevel} risk classification."
            : '';

        $narrative = ucfirst(implode(', ', $facts)) . '.' . $scoreText;

        $priorities = [];

        if ($institution->needs_maintenance) {
            $priorities[] = [
                'priority' => 'Inspect and repair existing ICT assets',
                'rationale' =>
                    'The institution record indicates that maintenance is required.',
                'urgency' => 'immediate',
            ];
        }

        if ($speed === null || (float) $speed <= 0) {
            $priorities[] = [
                'priority' => 'Validate internet connectivity information',
                'rationale' =>
                    'A usable internet speed is not available in the current record.',
                'urgency' => 'short_term',
            ];
        } elseif ((float) $speed < 25) {
            $priorities[] = [
                'priority' => 'Review internet capacity',
                'rationale' =>
                    'The recorded connection may be limited for shared institutional use.',
                'urgency' => 'short_term',
            ];
        }

        if (!$institution->has_computer_lab || $computers <= 0) {
            $priorities[] = [
                'priority' => 'Assess computer access requirements',
                'rationale' =>
                    'The record shows limited or unavailable dedicated computer facilities.',
                'urgency' => 'medium_term',
            ];
        }

        if (
            $institution->requires_audit
            || (float) ($scores['data_completeness_score'] ?? 100) < 80
        ) {
            $priorities[] = [
                'priority' => 'Complete field validation',
                'rationale' =>
                    'The record requires verification before major resource decisions.',
                'urgency' => 'short_term',
            ];
        }

        if ($priorities === []) {
            $priorities[] = [
                'priority' => 'Maintain and periodically validate ICT records',
                'rationale' =>
                    'No immediate deficiency is confirmed by the available record.',
                'urgency' => 'medium_term',
            ];
        }

        return [
            'infrastructure_narrative' => $narrative,
            'policy_recommendation' =>
                'Use the calculated scores and verified field data to prioritize '
                . 'maintenance, connectivity, and equipment investments. Confirm '
                . 'missing information before approving major interventions.',
            'recommendations_detail' => [
                'method' => 'local_fallback',
                'provider' => 'local',
                'model' => null,
                'priorities' => array_slice($priorities, 0, 5),
                'generated_at' => now()->toIso8601String(),
            ],
        ];
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