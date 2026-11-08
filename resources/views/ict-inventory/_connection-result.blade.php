@php($connectionAssessment = ($connectionAssessments ?? collect())->get($connectionNumber))
<div class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600" data-connection-result>
    @if ($connectionAssessment)
        <div class="flex flex-wrap gap-x-5 gap-y-1">
            <span>Advertised download: <strong>{{ isset($connectionAssessment['advertised_download_mbps']) ? number_format($connectionAssessment['advertised_download_mbps'], 2).' Mbps' : 'Unidentified' }}</strong></span>
            <span>Download/upload shortfall: <strong>{{ isset($connectionAssessment['shortfall_percent']) ? number_format($connectionAssessment['shortfall_percent'], 2).'%' : 'Unavailable' }}</strong></span>
        </div>
        @foreach ($connectionAssessment['reason_codes'] ?? [] as $reason)
            <p class="mt-1">{{ \App\Services\InternetConnectivityAssessment::reasonLabel($reason) }}</p>
        @endforeach
    @else
        Download shortfall: Not calculated
    @endif
</div>
