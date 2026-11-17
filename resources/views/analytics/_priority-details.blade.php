<div class="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-2">
    <section aria-label="Connectivity assessment">
        <h3 class="text-sm font-semibold text-gray-900">Connectivity assessment</h3>
        @php($connectionAssessment = $recommendationDetails['connectivity_assessment'] ?? null)
        @if (is_array($connectionAssessment))
            @if (($connectionAssessment['status'] ?? '') === 'no_service')
                <p class="mt-2 text-xs text-gray-600">No Internet service recorded.</p>
            @else
                <p class="mt-2 text-xs text-gray-600">Connections scored: {{ $connectionAssessment['valid_connection_count'] ?? 'Not recorded' }} of {{ $connectionAssessment['reported_connection_count'] ?? 'Not recorded' }} reported.</p>
            @endif
            <dl class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600">
                @foreach (['identifiable' => 'Identifiable plans', 'price_only' => 'Price-only plans', 'ambiguous' => 'Ambiguous plans', 'not_recorded' => 'Unrecorded plans'] as $field => $label)
                    <div><dt>{{ $label }}</dt><dd>{{ data_get($connectionAssessment, 'plan_counts.'.$field) ?? 'Not recorded' }}</dd></div>
                @endforeach
            </dl>
            @foreach ($connectionAssessment['reason_codes'] ?? [] as $reason)
                <p class="mt-1 text-xs text-amber-800">{{ \App\Services\InternetConnectivityAssessment::reasonLabel($reason) }}</p>
            @endforeach
        @else
            <p class="mt-2 text-xs text-gray-600">Not recorded for this report.</p>
        @endif
        @foreach (data_get($recommendationDetails, 'connectivity_assessment.connections', []) as $connection)
            <div class="mt-3">
                <h4 class="text-xs font-semibold text-gray-700">Connection {{ $connection['connection_number'] ?? 'Not recorded' }}</h4>
                <dl class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600">
                    @foreach (['advertised_download_mbps' => 'Acquired Mbps', 'download_speed_mbps' => 'Measured download Mbps', 'upload_speed_mbps' => 'Measured upload Mbps', 'shortfall_percent' => 'Shortfall (%)'] as $field => $label)
                        <div><dt>{{ $label }}</dt><dd class="font-medium tabular-nums">{{ isset($connection[$field]) && is_numeric($connection[$field]) ? number_format($connection[$field], 2) : ($field === 'shortfall_percent' ? 'Not calculated' : 'Not recorded') }}</dd></div>
                    @endforeach
                </dl>
            </div>
        @endforeach
        @if ($institution)
            <a href="{{ route('institutions.show', ['institution' => $institution->id, 'tab' => 'connectivity']) }}#institutionInventory" class="mt-3 inline-block text-xs font-semibold text-red-700 hover:underline">Internet Connectivity</a>
        @endif
    </section>
    <section aria-label="Assessment context and recommendations">
        <h3 class="text-sm font-semibold text-gray-900">Assessment context and recommendations</h3>
        <p class="mt-2 text-xs text-gray-600">Report scope: {{ data_get($recommendationDetails, 'calculation_scope.label') ?: 'Not recorded for this report' }}.</p>
        <p class="mt-1 text-xs text-gray-600">Assessment date: {{ $analytic->analysis_date?->format('M d, Y g:i A') ?? 'Not recorded' }}.</p>
        @foreach (data_get($recommendationDetails, 'assessment.unavailable_reasons', []) as $reason)
            <p class="mt-2 text-xs text-amber-800">{{ $reason }}</p>
        @endforeach
        @if ($analytic->infrastructure_narrative)
            <p class="mt-2 whitespace-pre-line text-xs leading-6 text-gray-600">{{ $analytic->infrastructure_narrative }}</p>
        @endif
        @if ($firstAction)
            <p class="mt-3 text-sm font-semibold text-gray-800">{{ $firstAction }}</p>
        @endif
        @if (is_array($firstPriority) && filled($firstPriority['rationale'] ?? null))
            <p class="mt-1 text-xs leading-6 text-gray-600">{{ $firstPriority['rationale'] }}</p>
        @endif
        @if ($analytic->policy_recommendation && $analytic->policy_recommendation !== $firstAction)
            <p class="mt-2 whitespace-pre-line text-xs leading-6 text-gray-600">{{ $analytic->policy_recommendation }}</p>
        @endif
    </section>
</div>
