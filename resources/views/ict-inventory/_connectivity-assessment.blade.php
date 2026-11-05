@if (is_array($assessment ?? null))
    @php($planCounts = $assessment['plan_counts'] ?? [])
    <div class="mt-3 text-xs text-slate-600" data-connectivity-assessment>
        @if (($assessment['status'] ?? '') === 'no_service')
            <p>No Internet service recorded.</p>
        @else
            <p>{{ $assessment['valid_connection_count'] ?? 0 }} / {{ $assessment['reported_connection_count'] ?? '—' }} connections scored</p>
        @endif
        @if (($assessment['recorded_connection_count'] ?? 0) > 0)
            <p class="mt-1">Plans: {{ $planCounts['identifiable'] ?? 0 }} identifiable · {{ $planCounts['price_only'] ?? 0 }} price only · {{ $planCounts['ambiguous'] ?? 0 }} ambiguous · {{ $planCounts['not_recorded'] ?? 0 }} not recorded</p>
        @endif
        @foreach ($assessment['reason_codes'] ?? [] as $reason)
            <p class="mt-1">{{ \App\Services\InternetConnectivityAssessment::reasonLabel($reason) }}</p>
        @endforeach
        @if (!($compact ?? false) && !empty($assessment['connections']))
            <div class="mt-3 overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left text-xs">
                    <thead class="bg-slate-50">
                        <tr>
                            @foreach (['Connection', 'Advertised Mbps', 'Download Mbps', 'Upload Mbps', 'Latency ms', 'Throughput Mbps', 'Shortfall', 'Status'] as $label)
                                <th class="px-3 py-2 font-semibold">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($assessment['connections'] as $connection)
                            <tr class="border-t border-slate-100">
                                <td class="px-3 py-2">{{ $connection['connection_number'] }}</td>
                                @foreach (['advertised_download_mbps', 'download_speed_mbps', 'upload_speed_mbps', 'latency_ms', 'throughput_mbps'] as $field)
                                    <td class="px-3 py-2">{{ isset($connection[$field]) ? number_format($connection[$field], 2) : '—' }}</td>
                                @endforeach
                                <td class="px-3 py-2">{{ isset($connection['shortfall_percent']) ? number_format($connection['shortfall_percent'], 2).'%' : 'Unavailable' }}</td>
                                <td class="px-3 py-2">
                                    @forelse ($connection['reason_codes'] ?? [] as $reason)
                                        <p>{{ \App\Services\InternetConnectivityAssessment::reasonLabel($reason) }}</p>
                                    @empty
                                        Calculated
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
