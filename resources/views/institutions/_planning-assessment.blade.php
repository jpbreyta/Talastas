@php
    $storedAssessment = $recommendationDetails['assessment'] ?? null;
    $assessmentKeys = ['gap_score', 'infrastructure_deficit_score', 'connectivity_divide_score', 'technological_obsolescence_score'];
    // Never present a stale snapshot as current or infer sections from narrative wording.
    $hasStructuredAssessment = is_array($storedAssessment)
        && is_array(data_get($storedAssessment, 'inventory_inputs.rows'))
        && filled($analytics->infrastructure_narrative)
        && $analytics->processing_stage !== 'outdated'
        && collect($assessmentKeys)->every(function ($key) use ($storedAssessment, $analytics) {
            $stored = $storedAssessment[$key] ?? null;
            $current = $analytics->{$key};
            return ($stored === null && $current === null)
                || (is_numeric($stored) && is_numeric($current) && (float) $stored === (float) $current);
        });
@endphp

@if ($hasStructuredAssessment)
    @php
        $assessmentRows = collect(data_get($storedAssessment, 'inventory_inputs.rows', []))->filter(fn ($row) => is_array($row));
        $recordedConnectivity = $recommendationDetails['connectivity_assessment'] ?? [];
        $unavailableReasons = $storedAssessment['unavailable_reasons'] ?? [];
        $assessmentIssues = collect($storedAssessment['invalid_inputs'] ?? [])
            ->merge(is_array($recordedConnectivity) ? ($recordedConnectivity['messages'] ?? []) : [])
            ->flatten()->filter(fn ($message) => is_string($message) && filled($message))
            ->reject(fn ($message) => in_array($message, $unavailableReasons, true))->unique();
    @endphp
    <div class="space-y-4 text-sm leading-6">
        <section aria-label="Connectivity Findings">
            <h4 class="text-xs font-semibold">Connectivity Findings</h4>
            <p class="mt-1">Connectivity Divide:
                {{ $analytics->connectivity_divide_score === null ? 'Not calculated' : number_format($analytics->connectivity_divide_score, 1).'%' }}.
            </p>
            @if (is_numeric(data_get($recordedConnectivity, 'valid_connection_count')) && is_numeric(data_get($recordedConnectivity, 'reported_connection_count')))
                <p class="mt-1 text-xs leading-5">Connections with valid assessment inputs: {{ number_format($recordedConnectivity['valid_connection_count']) }} of {{ number_format($recordedConnectivity['reported_connection_count']) }} reported.</p>
            @endif
        </section>

        <section aria-label="Infrastructure Findings">
            <h4 class="text-xs font-semibold">Infrastructure Findings</h4>
            <p class="mt-1">Infrastructure Deficit:
                {{ $analytics->infrastructure_deficit_score === null ? 'Not calculated' : number_format($analytics->infrastructure_deficit_score, 1).'%' }}.
                Retirement proxy:
                {{ $analytics->technological_obsolescence_score === null ? 'Not calculated' : number_format($analytics->technological_obsolescence_score, 1).'%' }}.
            </p>
        </section>

        <section aria-label="Data Completeness">
            <h4 class="text-xs font-semibold">Data Completeness</h4>
            @foreach (['infrastructure_deficit_score' => 'Infrastructure Deficit', 'connectivity_divide_score' => 'Connectivity Divide', 'technological_obsolescence_score' => 'Retirement proxy'] as $key => $label)
                @if ($analytics->{$key} === null)
                    <p class="mt-1 text-xs leading-6">{{ $label }}: Not calculated.</p>
                @endif
            @endforeach
            @if ($analytics->gap_score === null)
                <p class="mt-1 text-xs leading-6">Overall ICT Gap: Not calculated.</p>
            @else
                <p class="mt-1 text-xs leading-6">All three core indicators have calculated values in this report.</p>
            @endif
        </section>

        <details class="institution-original-assessment">
            <summary class="cursor-pointer text-xs font-semibold">
                <span class="institution-details-collapsed-label">View detailed findings</span>
                <span class="institution-details-expanded-label">Hide detailed findings</span>
            </summary>
            <div class="mt-3 space-y-4">
                <section aria-label="Inventory component breakdown">
                    <h4 class="text-xs font-semibold">Inventory component breakdown</h4>
                    @if ($assessmentRows->isNotEmpty())
                        <ul class="mt-2 space-y-2">
                            @foreach ($assessmentRows as $row)
                                <li>
                                    <p class="text-xs font-semibold">{{ filled($row['label'] ?? null) ? $row['label'] : 'Applicable inventory' }}</p>
                                    <p class="text-xs leading-6">
                                        @foreach (['active_count' => 'Active', 'defective_active_count' => 'Defective within Active', 'defective_count' => 'General Defective', 'retired_count' => 'Retired'] as $field => $label)
                                            {{ $label }}: {{ \App\Services\InventoryCounts::quantity($row[$field] ?? null) === null ? 'Not recorded' : number_format((int) $row[$field]) }}{{ $loop->last ? '.' : ';' }}
                                        @endforeach
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
                <section aria-label="Recorded limitations">
                    <h4 class="text-xs font-semibold">Recorded limitations</h4>
                    @foreach (['infrastructure_deficit_score' => 'Infrastructure Deficit', 'connectivity_divide_score' => 'Connectivity Divide', 'technological_obsolescence_score' => 'Retirement proxy'] as $key => $label)
                        @if ($analytics->{$key} === null)
                            <p class="mt-1 text-xs leading-6">{{ $label }}: Not calculated. {{ $unavailableReasons[$key] ?? 'No reason was recorded in this report.' }}</p>
                        @endif
                    @endforeach
                    @if ($assessmentIssues->isNotEmpty())
                        <ul class="mt-2 list-disc space-y-1 pl-4 text-xs leading-6">
                            @foreach ($assessmentIssues as $issue)
                                <li>{{ $issue }}</li>
                            @endforeach
                        </ul>
                    @endif
                </section>
                <h4 class="text-xs font-semibold">Full assessment text</h4>
                <p class="mt-2 max-w-prose whitespace-pre-line text-sm leading-7">{{ $analytics->infrastructure_narrative }}</p>
            </div>
        </details>
    </div>
@else
    <p class="max-w-prose whitespace-pre-line text-sm leading-7 text-gray-600">{{ $analytics->infrastructure_narrative ?: 'No planning assessment is available yet.' }}</p>
@endif
