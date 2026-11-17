@php($canManageInstitutions = auth()->user()->canManageInstitutions())
        {{-- Content Area --}}
        <div data-institution-results data-total="{{ $institutions->total() }}" data-page="{{ $institutions->currentPage() }}" class="flex min-h-0 flex-1 flex-col">
            @if ($institutions->count() > 0)
                <div class="flex min-h-0 flex-1 flex-col px-5 pb-1 pt-3">
                    <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                        @if ($canManageInstitutions)
                            data-institution-selection data-batch-delete-url="{{ route('institutions.batch-destroy') }}"
                        @endif>
                        @if ($canManageInstitutions)
                            <div data-institution-batch-actions hidden
                                class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-red-50/50 px-4 py-2">
                                <span data-institution-selected-count class="text-xs font-medium text-gray-700" role="status" aria-live="polite"></span>
                                <div class="flex items-center gap-2">
                                    <button type="button" data-institution-clear-selection
                                        class="rounded-lg px-3 py-2 text-xs font-medium text-gray-600 hover:bg-white focus-visible:outline-2 focus-visible:outline-red-700">Clear selection</button>
                                    <button type="button" data-institution-batch-delete
                                        class="inline-flex items-center gap-2 rounded-lg bg-red-700 px-3 py-2 text-xs font-semibold text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        <span data-batch-delete-label>Delete selected</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                        <div class="institution-table-scroll table-scroll min-h-0 flex-1" role="region" aria-label="Institutions table" tabindex="0">
                            <table class="w-full min-w-[800px] text-sm">
                                <thead class="sticky top-0 z-10">
                                    <tr class="border-b border-gray-200 bg-gray-50 text-left">
                                        @if ($canManageInstitutions)
                                            <th scope="col" class="w-12 px-6 py-4">
                                                <label data-institution-selection-control class="inline-flex h-8 w-8 cursor-pointer items-center justify-center">
                                                    <input type="checkbox" data-institution-select-all aria-label="Select all visible institutions on this page"
                                                        class="h-4 w-4 cursor-pointer accent-red-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                                                </label>
                                            </th>
                                        @endif
                                        <th scope="col" class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            Institution
                                        </th>
                                        <th scope="col" class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            Sector
                                        </th>
                                        <th scope="col" class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            Barangay
                                        </th>
                                        <th scope="col" class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            Status
                                        </th>
                                        <th scope="col" class="w-20 px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($institutions as $institution)
                                        <tr id="institution-row-{{ $institution->id }}"
                                            data-institution-show-url="{{ route('institutions.show', $institution) }}"
                                            class="inst-row cursor-pointer bg-white transition hover:bg-gray-50 focus-within:bg-gray-50">
                                            @if ($canManageInstitutions)
                                                <td class="w-12 px-6 py-4">
                                                    <label data-institution-selection-control class="inline-flex h-8 w-8 cursor-pointer items-center justify-center">
                                                        <input type="checkbox" data-institution-select name="institution_ids[]" value="{{ $institution->id }}"
                                                            data-institution-name="{{ $institution->name }}"
                                                            aria-label="Select {{ $institution->name }}"
                                                            class="h-4 w-4 cursor-pointer accent-red-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                                                    </label>
                                                </td>
                                            @endif
                                            <td class="px-6 py-4">
                                                <a href="{{ route('institutions.show', $institution) }}"
                                                    class="table-text-wrap block font-medium text-gray-900 hover:text-red-800 hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                                                    {{ $institution->name }}
                                                </a>
                                            </td>

                                            <td class="px-6 py-4 text-gray-500">
                                                {{ $institution->type }}
                                            </td>

                                            <td class="px-6 py-4 text-gray-500">
                                                {{ $institution->barangay?->name ?? '—' }}
                                            </td>

                                            <td class="px-6 py-4">
                                                @if ($institution->is_active)
                                                    <span class="inline-flex whitespace-nowrap items-center rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                                                        Operational
                                                    </span>
                                                @else
                                                    <span class="inline-flex whitespace-nowrap items-center gap-1 rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-500">
                                                        <i class="fa-solid fa-circle-minus text-[10px]"></i>
                                                        Inactive
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="w-20 px-6 py-4 text-right">
                                                <div class="flex justify-end">
                                                    @if ($canManageInstitutions)
                                                        <button type="button" data-institution-actions-toggle
                                                            aria-label="Open actions for {{ $institution->name }}" aria-expanded="false" aria-haspopup="menu"
                                                            aria-controls="institution-actions-{{ $institution->id }}"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                                                            <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
                                                        </button>
                                                        <div id="institution-actions-{{ $institution->id }}" data-institution-actions-menu hidden
                                                            role="menu" aria-label="Actions for {{ $institution->name }}"
                                                            class="fixed z-[2500] w-36 max-w-[calc(100vw-16px)] rounded-lg border border-gray-200 bg-white p-1 text-left shadow-md">
                                                        <a href="{{ route('institutions.show', $institution) }}"
                                                            role="menuitem" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-red-700">
                                                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                                            View Details
                                                        </a>
                                                        <a href="{{ route('institutions.edit', $institution) }}"
                                                            role="menuitem" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-red-700">
                                                            <i class="fa-solid fa-pen"></i>
                                                            Edit
                                                        </a>

                                                        <button type="button"
                                                            role="menuitem" class="delete-institution-button flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-red-700 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-red-700"
                                                            data-delete-url="{{ route('institutions.destroy', $institution) }}"
                                                            data-institution-id="{{ $institution->id }}"
                                                            data-institution-name="{{ $institution->name }}">
                                                            <i class="fa-solid fa-trash"></i>
                                                            Delete
                                                        </button>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-200 bg-white px-5 py-2">
                    {{ $institutions->links() }}
                </div>
            @else
                <div class="flex flex-1 flex-col items-center justify-center px-5 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-500">
                        <i class="fa-solid fa-building text-xl"></i>
                    </div>

                    <h3 class="mt-4 text-sm font-semibold text-gray-900">
                        {{ request()->filled('search') || request()->filled('barangay') || request()->filled('sector') ? 'No matching institutions' : 'No institutions yet' }}
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ request()->filled('search') || request()->filled('barangay') || request()->filled('sector')
                            ? 'Try another search or reset your filters.'
                            : ($canManageInstitutions ? 'Get started by adding your first institution.' : 'No institution records are currently available.') }}
                    </p>

                    @if ($canManageInstitutions)
                        <button type="button" id="openAddModalEmpty" data-open-institution-modal
                            class="mt-5 inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                            <i class="fa-solid fa-plus"></i>
                            Add Institution
                        </button>
                    @endif
                </div>
            @endif
        </div>

