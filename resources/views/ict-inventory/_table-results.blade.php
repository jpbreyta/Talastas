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
                                                $institution->active_assets_count,
                                                $institution->retired_assets_count,
                                                $institution->functional_assets_count,
                                                $institution->attention_assets_count,
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
                                            data-accordion-panel
                                            data-inventory-details-url="{{ route('ict-inventory.institution-details', $institution) }}">
                                            <td colspan="9" class="p-0">
                                                <div class="border-t border-gray-200 px-5 py-5" data-inventory-details-container aria-live="polite">
                                                    <div class="text-sm text-gray-600" role="status" data-inventory-details-status>
                                                        Expand to load inventory details.
                                                    </div>
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
