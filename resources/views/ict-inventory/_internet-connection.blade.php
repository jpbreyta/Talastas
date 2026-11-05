<fieldset @if($isNewConnection ?? false) data-new-connection @endif data-q10-connection="{{ $connectionNumber }}" class="mt-5 border-t border-gray-100 pt-4">
    <legend class="text-sm font-semibold text-gray-800" data-connection-label>Connection {{ $connectionNumber }}</legend>
    @if ($removable ?? false)
        <div class="mt-2 flex justify-end">
            <button type="button" data-remove-connection aria-label="Remove connection {{ $connectionNumber }}"
                class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold">
                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>Remove
            </button>
        </div>
    @endif
    @foreach (['Connection Details' => ['isp', 'plan', 'advertised_download_mbps', 'allocation', 'validity', 'context'], 'Network Performance' => ['advertised_download_mbps', 'download_speed_mbps', 'upload_speed_mbps', 'latency_ms', 'throughput_mbps']] as $groupLabel => $groupFields)
        <h4 class="mt-4 text-xs font-semibold text-gray-700">{{ $groupLabel }}</h4>
        <div class="inventory-connection-grid mt-3">
        @foreach ($groupFields as $field)
            @php($label = $q10Fields[$field])
            @php($measurement = in_array($field, ['download_speed_mbps', 'upload_speed_mbps', 'latency_ms', 'throughput_mbps'], true))
            <label class="block text-xs font-semibold text-gray-600">{{ $label }}
                <input name="q10[connections][{{ $connectionNumber }}][{{ $field }}]"
                    data-connection-field="{{ $field }}"
                    type="{{ $measurement ? 'number' : 'text' }}"
                    @if ($measurement) min="{{ $field === 'advertised_download_mbps' ? '0.000001' : '0' }}" max="1000000" step="any" data-connection-measurement @else maxlength="500" @endif
                    value="{{ $connectionValues[$field] ?? '' }}" placeholder="Not recorded"
                    class="form-input mt-1.5 w-full">
            </label>
        @endforeach
        </div>
    @endforeach
    @include('ict-inventory._connection-result')
</fieldset>
