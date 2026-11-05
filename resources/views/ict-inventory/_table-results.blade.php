        <main data-inventory-results class="flex min-h-0 flex-1 flex-col">

            @if ($institutions->count() > 0)
                <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-2 pt-4">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[900px] text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50 text-left">
                                        <th class="w-14 px-4 py-3"></th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">Institution</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">Barangay</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Total</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Active</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Retired</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Functional</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Defective</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Actions</th>
                                    </tr>
                                </thead>

                                @foreach ($institutions as $institution)
                                    <tbody class="institution-group border-b border-gray-100 last:border-b-0"
                                        data-institution-group>

                                        <tr class="cursor-pointer bg-white transition hover:bg-red-50/50"
                                            data-accordion-trigger
                                            tabindex="0"
                                            role="button"
                                            aria-expanded="false"
                                            aria-controls="institution-assets-{{ $institution->id }}">

                                            <td class="px-4 py-4 text-center">
                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                                    <i class="fa-solid fa-chevron-right text-xs transition-transform duration-200"
                                                        data-chevron></i>
                                                </span>
                                            </td>

                                            <td class="px-4 py-4">
                                                <p class="font-semibold text-gray-900">
                                                    {{ $institution->name }}
                                                </p>
                                                <p class="mt-0.5 text-xs text-gray-500">
                                                    {{ $institution->type }}
                                                    @if(request('sort') === 'most_complete')
                                                        · {{ number_format((float) $institution->inventory_input_coverage) }}% inventory inputs recorded and valid
                                                    @elseif(request('sort') === 'latest')
                                                        · Updated {{ $institution->latest_inventory_update ? \Illuminate\Support\Carbon::parse($institution->latest_inventory_update)->format('M j, Y g:i A') : '—' }}
                                                    @endif
                                                </p>
                                            </td>

                                            <td class="px-4 py-4 text-gray-600">
                                                {{ $institution->barangay?->name ?? 'Not assigned' }}
                                            </td>

                                            @foreach ([
                                                $institution->total_assets_count,
                                                \App\Services\InventoryCounts::sum($institution->ictInventory, 'active_count'),
                                                \App\Services\InventoryCounts::sum($institution->ictInventory, 'retired_count'),
                                                $institution->ictInventory->isNotEmpty() && $institution->ictInventory->every(fn ($item) => $item->functional_count !== null) ? $institution->ictInventory->sum('functional_count') : null,
                                                \App\Services\InventoryCounts::sum($institution->ictInventory, 'defective_count'),
                                            ] as $value)
                                                <td class="px-4 py-4 text-center">
                                                    <span class="inline-flex min-w-9 items-center justify-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                                        {{ $value === null ? '—' : number_format((int) $value) }}
                                                    </span>
                                                </td>
                                            @endforeach

                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('ict-inventory.create', ['institution_id' => $institution->id]) }}"
                                                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 transition hover:bg-red-100"
                                                        data-stop-accordion>
                                                        <i class="fa-solid fa-plus"></i>
                                                        Manage Inventory
                                                    </a>

                                                </div>
                                            </td>
                                        </tr>

                                        <tr id="institution-assets-{{ $institution->id }}"
                                            class="hidden bg-slate-50"
                                            data-accordion-panel>
                                            <td colspan="9" class="p-0">
                                                <div class="border-t border-gray-200 px-5 py-5">
                                                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                                        <p class="text-xs text-gray-500">
                                                            Inventory records are grouped below by category. A dash means availability is unrecorded or incomplete; 0 is confirmed zero.
                                                        </p>

                                                    </div>

                                                    @if ($institution->ictInventory->isNotEmpty())
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
                                                                        @foreach ($institution->ictInventory as $inventory)
                                                                            @php
                                                                                $categoryLabel = match ($inventory->component_type) {
                                                                                    'software' => 'Software & Systems',
                                                                                    'network' => 'Network Infrastructure',
                                            'connectivity' => 'Internet Connectivity',
                                                                                    default => 'Hardware & Equipment',
                                                                                };
                                                                            @endphp

                                                                            <tr data-inventory-show-url="{{ route('ict-inventory.show', $inventory) }}"
                                                                                class="cursor-pointer transition hover:bg-red-50/40 focus-within:bg-red-50/40">
                                                                                <td class="px-4 py-3">
                                                                                    <a href="{{ route('ict-inventory.show', $inventory) }}"
                                                                                        class="rounded-sm font-medium text-gray-900 transition hover:text-red-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2">
                                                                                        {{ $inventory->asset_name }}
                                                                                    </a>
                                                                                    <p class="mt-1 text-xs text-gray-500">Defective within Active: {{ $inventory->defective_active_count === null ? 'Not recorded' : number_format($inventory->defective_active_count) }}</p>
                                                                                    <p @if ($inventory->availability_status === 'Availability Recorded') hidden @endif class="mt-1 text-xs text-amber-700">{{ $inventory->availability_status === 'Availability Recorded' ? '' : $inventory->availability_status }}</p>

                                                                                </td>

                                                                                <td class="px-4 py-3 text-xs text-gray-600">
                                                                                    {{ $categoryLabel }}
                                                                                </td>

                                                                                @foreach ([
                                                                                    $inventory->total_count,
                                                                                    $inventory->active_count,
                                                                                    $inventory->retired_count,
                                                                                    $inventory->functional_count,
                                                                                    $inventory->defective_count,
                                                                                ] as $count)
                                                                                    <td class="px-3 py-3 text-center font-semibold text-gray-700">
                                                                                        {{ $count === null ? '—' : number_format((int) $count) }}
                                                                                    </td>
                                                                                @endforeach

                                                                                <td class="px-4 py-3">
                                                                                    <div class="flex items-center justify-end gap-1.5">
                                                                                        <a href="{{ route('ict-inventory.edit', $inventory) }}"
                                                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600">
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
                                                            <p class="text-sm font-medium text-gray-700">
                                                                No ICT inventory records yet.
                                                            </p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                @endforeach
                            </table>
                        </div>

                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-200 bg-white px-5 py-3">
                    {{ $institutions->links() }}
                </div>
            @else
                <div class="flex flex-1 flex-col items-center justify-center px-6 text-center">
                    <h2 class="text-base font-semibold text-gray-900">
                        {{ filled(request('search')) || request('status', 'all') !== 'all' ? 'No matching institutions' : 'No public schools or barangay halls available' }}
                    </h2>
                    <p class="mt-1 max-w-md text-sm text-gray-500">
                        {{ filled(request('search')) || request('status', 'all') !== 'all' ? 'Adjust the search or inventory filter.' : 'Register an in-scope institution before encoding ICT inventory data.' }}
                    </p>
                </div>
            @endif
        </main>
