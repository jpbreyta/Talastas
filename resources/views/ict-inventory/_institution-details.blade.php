<div data-inventory-institution-details>
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <p class="text-xs text-gray-500">
        Inventory records are grouped below by category. A dash means availability is unrecorded or incomplete; 0 is confirmed zero.
    </p>
</div>

@if ($inventory->isNotEmpty())
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Component</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Category</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Total</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Active</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Retired</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Functional</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Defective</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($inventory as $item)
                        @php
                            $categoryLabel = match ($item->component_type) {
                                'software' => 'Software & Systems',
                                'network' => 'Network Infrastructure',
                                'connectivity' => 'Internet Connectivity',
                                default => 'Hardware & Equipment',
                            };
                            $availabilityStatus = $item->availability_status;
                        @endphp
                        <tr data-inventory-show-url="{{ route('ict-inventory.show', $item) }}"
                            class="cursor-pointer transition hover:bg-red-50/40 focus-within:bg-red-50/40">
                            <td class="px-4 py-3">
                                <a href="{{ route('ict-inventory.show', $item) }}"
                                    class="rounded-sm font-medium text-gray-900 transition hover:text-red-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2">
                                    {{ $item->asset_name }}
                                </a>
                                <p class="mt-1 text-xs text-gray-500">Defective within Active: {{ $item->defective_active_count === null ? 'Not recorded' : number_format($item->defective_active_count) }}</p>
                                <p @if ($availabilityStatus === 'Availability Recorded') hidden @endif class="mt-1 text-xs text-amber-700">{{ $availabilityStatus === 'Availability Recorded' ? '' : $availabilityStatus }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ $categoryLabel }}</td>
                            @foreach ([$item->total_count, $item->active_count, $item->retired_count, $item->functional_count, $item->defective_count] as $count)
                                <td class="px-3 py-3 text-center font-semibold text-gray-700">{{ $count === null ? '—' : number_format((int) $count) }}</td>
                            @endforeach
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('ict-inventory.edit', $item) }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600"
                                        aria-label="Edit {{ $item->asset_name }}">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center">
        <p class="text-sm font-medium text-gray-700">No ICT inventory records yet.</p>
    </div>
@endif
</div>
