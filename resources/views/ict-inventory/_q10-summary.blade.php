@php
    $connectionAssessments = collect(data_get($institution->institutionAnalytics?->recommendations_detail, 'connectivity_assessment.connections', []))->keyBy('connection_number');
    $detailFields = ['isp' => 'ISP', 'plan' => 'Plan', 'allocation' => 'Allocation', 'validity' => 'Validity', 'context' => 'Connection Context'];
    $performanceFields = ['advertised_download_mbps' => 'Advertised download (Mbps)', 'download_speed_mbps' => 'Download Speed (Mbps)', 'upload_speed_mbps' => 'Upload Speed (Mbps)', 'latency_ms' => 'Latency (ms)', 'throughput_mbps' => 'Throughput (Mbps)'];
@endphp
<section class="rounded-xl border border-gray-200 bg-white p-5" data-connectivity-connections>
    <h3 class="text-sm font-semibold text-gray-900">Internet Connection Details</h3>
    <p class="mt-2 text-xs text-gray-500">Reported connections: {{ $institution->q10_number_of_connections ?? 'Not recorded' }}</p>
    @forelse ($institution->internetConnections as $connection)
        @php($connectionNumber = $connection->connection_number)
        <article class="mt-4 border-t border-gray-100 pt-4" data-connection-summary="{{ $connectionNumber }}">
            <h4 class="text-sm font-semibold text-gray-900">Connection {{ $connectionNumber }}</h4>
            <dl class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($detailFields as $field => $label)
                    <div><dt class="text-xs text-gray-500">{{ $label }}</dt><dd class="mt-1 break-words">{{ $connection->{$field} ?? 'Not recorded' }}</dd></div>
                @endforeach
            </dl>
            <h5 class="mt-4 text-xs font-semibold text-gray-700">Network Performance</h5>
            <dl class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($performanceFields as $field => $label)
                    <div><dt class="text-xs text-gray-500">{{ $label }}</dt><dd class="mt-1 tabular-nums">{{ $connection->{$field} === null ? 'Not recorded' : number_format($connection->{$field}, 2) }}</dd></div>
                @endforeach
            </dl>
            @include('ict-inventory._connection-result')
        </article>
    @empty
        <p class="mt-3 text-sm text-gray-500">Not recorded</p>
    @endforelse
</section>
