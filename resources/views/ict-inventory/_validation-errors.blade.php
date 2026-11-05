@php
    $remainingInventoryErrors = collect($errors->messages())->reject(function ($messages, $path) {
        return preg_match('/^(?:sections\.(hardware|software|network|connectivity)\.items\.\d+\.)?(total_count|active_count|retired_count|functional_count|defective_count|defective_active_count)$/', $path);
    });
@endphp
@if ($remainingInventoryErrors->isNotEmpty())
    <details class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <summary class="cursor-pointer font-semibold">{{ $remainingInventoryErrors->count() }} field(s) require correction</summary>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($remainingInventoryErrors as $path => $messages)
                @php
                    $parts = explode('.', $path);
                    $label = ucwords(str_replace('_', ' ', end($parts)));
                    if (($parts[0] ?? '') === 'sections' && isset($parts[3])) {
                        $name = old("sections.{$parts[1]}.items.{$parts[3]}.asset_name", 'Item '.((int) $parts[3] + 1));
                        $label = ucfirst($parts[1]).' / '.$name.' / '.$label;
                    }
                @endphp
                @foreach ($messages as $message)
                    <li><strong>{{ $label }}:</strong> {{ $message }}</li>
                @endforeach
            @endforeach
        </ul>
    </details>
@endif
