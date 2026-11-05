@php
    $q10Connections = $selectedInstitution?->internetConnections->keyBy('connection_number')->except(old('q10.removed_connection_numbers', [])) ?? collect();
    $q10Fields = ['isp' => 'ISP', 'plan' => 'Plan', 'allocation' => 'Allocation', 'validity' => 'Validity',
        'context' => 'Connection Context', 'advertised_download_mbps' => 'Advertised download (Mbps)', 'download_speed_mbps' => 'Measured download (Mbps)',
        'upload_speed_mbps' => 'Upload Speed (Mbps)', 'latency_ms' => 'Latency (ms)', 'throughput_mbps' => 'Throughput (Mbps)'];
    $previousConnections = old('q10.connections', []);
    $connectionNumbers = $q10Connections->keys()->merge(array_keys(is_array($previousConnections) ? $previousConnections : []))
        ->filter(fn ($number) => preg_match('/^[1-9][0-9]*$/D', (string) $number) && (int) $number <= \App\Http\Controllers\IctInventoryController::MAX_INTERNET_CONNECTIONS)
        ->map(fn ($number) => (int) $number)->unique()->values()->all();
    $reportedCount = min((int) old('q10.number_of_connections', $selectedInstitution?->q10_number_of_connections ?? 0), \App\Http\Controllers\IctInventoryController::MAX_INTERNET_CONNECTIONS);
    for ($number = 1; count($connectionNumbers) < $reportedCount; $number++) {
        if (!in_array($number, $connectionNumbers, true) && !in_array($number, array_map('intval', old('q10.removed_connection_numbers', [])), true)) $connectionNumbers[] = $number;
    }
    sort($connectionNumbers);
    $connectionAssessments = collect(data_get($selectedInstitution?->institutionAnalytics?->recommendations_detail, 'connectivity_assessment.connections', []))->keyBy('connection_number');
@endphp
<div class="inventory-connection-card mb-5">
    <div class="mb-5 grid gap-4 sm:grid-cols-2">
        <label class="text-xs font-semibold text-gray-600">Internet service
            @php($serviceStatus = old('q10.has_internet_connection', $selectedInstitution?->has_internet_connection))
            <select name="q10[has_internet_connection]" class="form-input mt-1.5 w-full">
                <option value="" @selected($serviceStatus === null || $serviceStatus === '')>Not recorded</option>
                <option value="1" @selected(in_array($serviceStatus, [true, 1, '1'], true))>Yes</option>
                <option value="0" @selected(in_array($serviceStatus, [false, 0, '0'], true))>No service</option>
            </select>
        </label>
        <label class="text-xs font-semibold text-gray-600">Reported number of Internet connections
            <input type="number" name="q10[number_of_connections]" min="0" max="255" step="1"
                value="{{ old('q10.number_of_connections', $selectedInstitution?->q10_number_of_connections) }}"
                placeholder="Not recorded" class="form-input mt-1.5 w-full">
        </label>
    </div>
    <p class="mb-4 text-xs text-gray-500">No service requires zero connections. Record a verified advertised download in Mbps; plan prices are not speeds. Blank measurements stay unknown. Remove saved connections explicitly; omitted records are retained.</p>
    <div id="removedInternetConnections">
        @foreach(old('q10.removed_connection_numbers', []) as $removedNumber)
            <input type="hidden" name="q10[removed_connection_numbers][]" value="{{ $removedNumber }}">
        @endforeach
    </div>
    <h3 class="mb-5 text-base font-semibold text-gray-900">Internet Connection Details</h3>
    <p id="internetConnectionsEmpty" class="text-sm text-gray-500" @if ($connectionNumbers) hidden @endif>Not recorded</p>
    <div id="internetConnectionList">
        @foreach ($connectionNumbers as $connectionNumber)
            @php($connectionValues = array_replace($q10Connections->get($connectionNumber)?->getAttributes() ?? [], (array) ($previousConnections[$connectionNumber] ?? [])))
            @include('ict-inventory._internet-connection', ['connectionNumber' => $connectionNumber, 'connectionValues' => $connectionValues, 'removable' => true, 'isNewConnection' => !$q10Connections->has($connectionNumber)])
        @endforeach
    </div>
    <div class="mt-5 flex justify-end">
        <button type="button" id="addInternetConnection" data-add-connection
            @disabled(($connectionNumbers ? max($connectionNumbers) : 0) >= \App\Http\Controllers\IctInventoryController::MAX_INTERNET_CONNECTIONS)
            class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-50">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>Add connection
        </button>
    </div>
    <template id="internetConnectionTemplate">
        @include('ict-inventory._internet-connection', ['connectionNumber' => '__CONNECTION__', 'connectionValues' => [], 'isNewConnection' => true, 'removable' => true])
    </template>
</div>
