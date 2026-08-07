@extends('layouts.app')

@section('dashboard-layout')
@endsection

@section('content')
    @php
        $categoryLabels = [
            'computer' => 'Computer',
            'laptop' => 'Laptop',
            'printer' => 'Printer',
            'router' => 'Router',
            'smart_tv' => 'Smart TV',
            'projector' => 'Projector',
            'pa_system' => 'PA System',
            'other' => 'Other',
        ];

        $conditionStyles = [
            'new' => [
                'label' => 'New',
                'class' => 'border-violet-200 bg-violet-50 text-violet-700',
            ],
            'excellent' => [
                'label' => 'Excellent',
                'class' => 'border-blue-200 bg-blue-50 text-blue-700',
            ],
            'good' => [
                'label' => 'Good',
                'class' => 'border-green-200 bg-green-50 text-green-700',
            ],
            'fair' => [
                'label' => 'Fair',
                'class' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
            ],
            'poor' => [
                'label' => 'Poor',
                'class' => 'border-red-200 bg-red-50 text-red-700',
            ],
            'needs_repair' => [
                'label' => 'Needs Repair',
                'class' => 'border-orange-200 bg-orange-50 text-orange-700',
            ],
        ];

        $statusStyles = [
            'active' => [
                'label' => 'Active',
                'class' => 'border-green-200 bg-green-50 text-green-700',
                'icon' => 'fa-circle-check',
            ],
            'under_repair' => [
                'label' => 'Under Repair',
                'class' => 'border-orange-200 bg-orange-50 text-orange-700',
                'icon' => 'fa-screwdriver-wrench',
            ],
            'inactive' => [
                'label' => 'Inactive',
                'class' => 'border-gray-200 bg-gray-100 text-gray-600',
                'icon' => 'fa-circle-pause',
            ],
            'retired' => [
                'label' => 'Retired',
                'class' => 'border-slate-200 bg-slate-100 text-slate-600',
                'icon' => 'fa-box-archive',
            ],
        ];
    @endphp

    <div class="flex h-full min-h-0 flex-col bg-gray-100">
        <header class="shrink-0 bg-blue-700 px-6 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-blue-100">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </span>

                    <div class="min-w-0">
                        <h1 class="text-xl font-semibold tracking-tight text-white">
                            ICT Inventory
                        </h1>
                        <p class="mt-0.5 text-xs text-blue-200">
                            Review equipment grouped by institution
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-blue-300">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </span>

                        <input
                            id="inventoryInstitutionSearch"
                            type="search"
                            placeholder="Search institutions or assets..."
                            autocomplete="off"
                            class="w-full rounded-lg border border-blue-600 bg-blue-800 py-2 pl-9 pr-4 text-sm text-white placeholder-blue-300 transition focus:outline-none focus:ring-2 focus:ring-blue-300 sm:w-64">
                    </div>

                    <select
                        id="inventoryInstitutionFilter"
                        class="rounded-lg border border-blue-600 bg-blue-800 px-3 py-2 text-sm text-white transition focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <option value="all">All Institutions</option>
                        <option value="active">With Active Assets</option>
                        <option value="attention">Needs Attention</option>
                        <option value="empty">No Inventory</option>
                    </select>

                    <a
                        href="{{ route('ict-inventory.create') }}"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-blue-100 bg-white px-4 py-2 text-sm font-medium text-blue-700 shadow-sm transition hover:bg-blue-50">
                        <i class="fa-solid fa-plus"></i>
                        Add Item
                    </a>
                </div>
            </div>
        </header>

        <main class="flex min-h-0 flex-1 flex-col">
            @if (session('success'))
                <div
                    class="mx-5 mt-4 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    <i class="fa-solid fa-circle-check mt-0.5"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($institutions->count() > 0)
                <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-2 pt-4">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[900px] text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50 text-left">
                                        <th class="w-14 px-4 py-3"></th>
                                        <th
                                            class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Institution
                                        </th>
                                        <th
                                            class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Barangay
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Total Assets
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Active
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Attention
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>

                                @foreach ($institutions as $institution)
                                    @php
                                        $searchText = collect([
                                            $institution->name,
                                            $institution->type,
                                            $institution->barangay?->name,
                                            ...$institution->ictInventory->flatMap(
                                                fn ($item) => [
                                                    $categoryLabels[$item->category]
                                                        ?? str($item->category)->headline(),
                                                    $item->condition,
                                                    $item->status,
                                                    $item->acquisition_year,
                                                ],
                                            ),
                                        ])
                                            ->filter()
                                            ->implode(' ');
                                    @endphp

                                    <tbody
                                        class="institution-group border-b border-gray-100 last:border-b-0"
                                        data-institution-group
                                        data-search="{{ str($searchText)->lower() }}"
                                        data-total="{{ $institution->total_assets_count }}"
                                        data-active="{{ $institution->active_assets_count }}"
                                        data-attention="{{ $institution->attention_assets_count }}">
                                        <tr
                                            class="cursor-pointer bg-white transition hover:bg-blue-50/50"
                                            data-accordion-trigger
                                            tabindex="0"
                                            role="button"
                                            aria-expanded="false"
                                            aria-controls="institution-assets-{{ $institution->id }}">
                                            <td class="px-4 py-4 text-center">
                                                <span
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500 transition"
                                                    data-chevron-container>
                                                    <i
                                                        class="fa-solid fa-chevron-right text-xs transition-transform duration-200"
                                                        data-chevron></i>
                                                </span>
                                            </td>

                                            <td class="px-4 py-4">
                                                <div class="flex items-center gap-3">
                                                    <span
                                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                                        <i class="fa-solid fa-building"></i>
                                                    </span>

                                                    <div class="min-w-0">
                                                        <p class="truncate font-semibold text-gray-900">
                                                            {{ $institution->name }}
                                                        </p>
                                                        <p class="mt-0.5 text-xs text-gray-500">
                                                            {{ $institution->type }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="px-4 py-4 text-gray-600">
                                                {{ $institution->barangay?->name ?? 'Not assigned' }}
                                            </td>

                                            <td class="px-4 py-4 text-center">
                                                <span
                                                    class="inline-flex min-w-9 items-center justify-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                    {{ $institution->total_assets_count }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-4 text-center">
                                                <span
                                                    class="inline-flex min-w-9 items-center justify-center rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">
                                                    {{ $institution->active_assets_count }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-4 text-center">
                                                <span
                                                    class="inline-flex min-w-9 items-center justify-center rounded-full px-2.5 py-1 text-xs font-semibold
                                                        {{ $institution->attention_assets_count > 0
                                                            ? 'bg-orange-50 text-orange-700'
                                                            : 'bg-gray-100 text-gray-500' }}">
                                                    {{ $institution->attention_assets_count }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a
                                                        href="{{ route('ict-inventory.create', ['institution_id' => $institution->id]) }}"
                                                        class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 transition hover:bg-blue-100"
                                                        data-stop-accordion>
                                                        <i class="fa-solid fa-plus"></i>
                                                        Add Asset
                                                    </a>

                                                    <a
                                                        href="{{ route('institutions.show', $institution) }}"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-blue-600"
                                                        title="View institution"
                                                        aria-label="View {{ $institution->name }}"
                                                        data-stop-accordion>
                                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>

                                        <tr
                                            id="institution-assets-{{ $institution->id }}"
                                            class="hidden bg-slate-50"
                                            data-accordion-panel>
                                            <td colspan="7" class="p-0">
                                                <div class="border-t border-gray-200 px-5 py-5">
                                                    <div
                                                        class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                        <div>
                                                            <h2 class="text-sm font-semibold text-gray-900">
                                                                Equipment Records
                                                            </h2>
                                                            <p class="mt-1 text-xs text-gray-500">
                                                                Individual assets assigned to
                                                                {{ $institution->name }}
                                                            </p>
                                                        </div>

                                                        <span class="text-xs font-medium text-gray-500">
                                                            {{ $institution->total_assets_count }}
                                                            {{ Str::plural('record', $institution->total_assets_count) }}
                                                        </span>
                                                    </div>

                                                    @if ($institution->ictInventory->isNotEmpty())
                                                        <div
                                                            class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                                                            <div class="overflow-x-auto">
                                                                <table class="w-full min-w-[760px] text-sm">
                                                                    <thead>
                                                                        <tr
                                                                            class="border-b border-gray-200 bg-gray-50 text-left">
                                                                            <th
                                                                                class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                                                Asset ID
                                                                            </th>
                                                                            <th
                                                                                class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                                                Category
                                                                            </th>
                                                                            <th
                                                                                class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                                                Condition
                                                                            </th>
                                                                            <th
                                                                                class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                                                Acquisition Year
                                                                            </th>
                                                                            <th
                                                                                class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                                                Status
                                                                            </th>
                                                                            <th
                                                                                class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                                                Actions
                                                                            </th>
                                                                        </tr>
                                                                    </thead>

                                                                    <tbody class="divide-y divide-gray-100">
                                                                        @foreach ($institution->ictInventory as $inventory)
                                                                            @php
                                                                                $condition = $conditionStyles[
                                                                                    $inventory->condition
                                                                                ] ?? [
                                                                                    'label' => str(
                                                                                        $inventory->condition,
                                                                                    )->headline(),
                                                                                    'class' =>
                                                                                        'border-gray-200 bg-gray-100 text-gray-600',
                                                                                ];

                                                                                $status = $statusStyles[
                                                                                    $inventory->status
                                                                                ] ?? [
                                                                                    'label' => str(
                                                                                        $inventory->status,
                                                                                    )->headline(),
                                                                                    'class' =>
                                                                                        'border-gray-200 bg-gray-100 text-gray-600',
                                                                                    'icon' => 'fa-circle',
                                                                                ];
                                                                            @endphp

                                                                            <tr class="transition hover:bg-gray-50">
                                                                                <td
                                                                                    class="px-4 py-3 font-mono text-xs text-gray-500">
                                                                                    #{{ $inventory->asset_id }}
                                                                                </td>

                                                                                <td class="px-4 py-3">
                                                                                    <span
                                                                                        class="font-medium text-gray-800">
                                                                                        {{ $categoryLabels[$inventory->category]
                                                                                            ?? str($inventory->category)->headline() }}
                                                                                    </span>
                                                                                </td>

                                                                                <td class="px-4 py-3">
                                                                                    <span
                                                                                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium {{ $condition['class'] }}">
                                                                                        {{ $condition['label'] }}
                                                                                    </span>
                                                                                </td>

                                                                                <td
                                                                                    class="px-4 py-3 tabular-nums text-gray-600">
                                                                                    {{ $inventory->acquisition_year }}
                                                                                </td>

                                                                                <td class="px-4 py-3">
                                                                                    <span
                                                                                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium {{ $status['class'] }}">
                                                                                        <i
                                                                                            class="fa-solid {{ $status['icon'] }} text-[10px]"></i>
                                                                                        {{ $status['label'] }}
                                                                                    </span>
                                                                                </td>

                                                                                <td class="px-4 py-3">
                                                                                    <div
                                                                                        class="flex items-center justify-end gap-1.5">
                                                                                        <a
                                                                                            href="{{ route('ict-inventory.show', $inventory) }}"
                                                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white transition hover:bg-blue-700"
                                                                                            title="View asset"
                                                                                            aria-label="View asset {{ $inventory->asset_id }}">
                                                                                            <i
                                                                                                class="fa-solid fa-eye text-xs"></i>
                                                                                        </a>

                                                                                        <a
                                                                                            href="{{ route('ict-inventory.edit', $inventory) }}"
                                                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-blue-600"
                                                                                            title="Edit asset"
                                                                                            aria-label="Edit asset {{ $inventory->asset_id }}">
                                                                                            <i
                                                                                                class="fa-solid fa-pen text-xs"></i>
                                                                                        </a>

                                                                                        <form
                                                                                            method="POST"
                                                                                            action="{{ route('ict-inventory.destroy', $inventory) }}">
                                                                                            @csrf
                                                                                            @method('DELETE')

                                                                                            <button
                                                                                                type="submit"
                                                                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-100"
                                                                                                title="Delete asset"
                                                                                                aria-label="Delete asset {{ $inventory->asset_id }}"
                                                                                                onclick="return confirm('Delete this inventory item?')">
                                                                                                <i
                                                                                                    class="fa-solid fa-trash-can text-xs"></i>
                                                                                            </button>
                                                                                        </form>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div
                                                            class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center">
                                                            <span
                                                                class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                                                <i class="fa-solid fa-box-open"></i>
                                                            </span>

                                                            <h3 class="mt-3 text-sm font-semibold text-gray-800">
                                                                No equipment records
                                                            </h3>

                                                            <p class="mt-1 text-xs text-gray-500">
                                                                Add the first inventory item for this institution.
                                                            </p>

                                                            <a
                                                                href="{{ route('ict-inventory.create', ['institution_id' => $institution->id]) }}"
                                                                class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-xs font-medium text-white transition hover:bg-blue-700">
                                                                <i class="fa-solid fa-plus"></i>
                                                                Add Asset
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                @endforeach
                            </table>
                        </div>

                        <div
                            id="inventoryNoResults"
                            class="hidden border-t border-gray-200 px-6 py-14 text-center">
                            <span
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <h3 class="mt-3 text-sm font-semibold text-gray-800">
                                No matching institutions
                            </h3>
                            <p class="mt-1 text-xs text-gray-500">
                                Adjust the search text or inventory filter.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-200 bg-white px-5 py-3">
                    {{ $institutions->links() }}
                </div>
            @else
                <div class="flex flex-1 flex-col items-center justify-center px-6 text-center">
                    <span
                        class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 text-blue-500">
                        <i class="fa-solid fa-building-circle-xmark text-2xl"></i>
                    </span>

                    <h2 class="mt-4 text-base font-semibold text-gray-900">
                        No institutions available
                    </h2>

                    <p class="mt-1 max-w-md text-sm text-gray-500">
                        Create an institution before assigning ICT equipment.
                    </p>

                    <a
                        href="{{ route('institutions.create') }}"
                        class="mt-5 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                        <i class="fa-solid fa-plus"></i>
                        Add Institution
                    </a>
                </div>
            @endif
        </main>
    </div>
@endsection

@section('scripts')
    <script>
        (() => {
            'use strict';

            const searchInput = document.getElementById(
                'inventoryInstitutionSearch'
            );
            const filterSelect = document.getElementById(
                'inventoryInstitutionFilter'
            );
            const noResults = document.getElementById(
                'inventoryNoResults'
            );

            const groups = Array.from(
                document.querySelectorAll(
                    '[data-institution-group]'
                )
            );

            function setExpanded(group, expanded) {
                const trigger = group.querySelector(
                    '[data-accordion-trigger]'
                );
                const panel = group.querySelector(
                    '[data-accordion-panel]'
                );
                const chevron = group.querySelector(
                    '[data-chevron]'
                );
                const chevronContainer = group.querySelector(
                    '[data-chevron-container]'
                );

                trigger?.setAttribute(
                    'aria-expanded',
                    String(expanded)
                );
                panel?.classList.toggle('hidden', !expanded);
                chevron?.classList.toggle(
                    'rotate-90',
                    expanded
                );
                chevronContainer?.classList.toggle(
                    'border-blue-200',
                    expanded
                );
                chevronContainer?.classList.toggle(
                    'bg-blue-50',
                    expanded
                );
                chevronContainer?.classList.toggle(
                    'text-blue-600',
                    expanded
                );
            }

            function toggleGroup(group) {
                const trigger = group.querySelector(
                    '[data-accordion-trigger]'
                );
                const expanded =
                    trigger?.getAttribute('aria-expanded') ===
                    'true';

                setExpanded(group, !expanded);
            }

            groups.forEach((group) => {
                const trigger = group.querySelector(
                    '[data-accordion-trigger]'
                );

                trigger?.addEventListener('click', (event) => {
                    if (
                        event.target.closest(
                            '[data-stop-accordion]'
                        )
                    ) {
                        return;
                    }

                    toggleGroup(group);
                });

                trigger?.addEventListener(
                    'keydown',
                    (event) => {
                        if (
                            event.key !== 'Enter'
                            && event.key !== ' '
                        ) {
                            return;
                        }

                        event.preventDefault();
                        toggleGroup(group);
                    }
                );
            });

            function matchesFilter(group, filter) {
                const total = Number(group.dataset.total || 0);
                const active = Number(
                    group.dataset.active || 0
                );
                const attention = Number(
                    group.dataset.attention || 0
                );

                if (filter === 'active') {
                    return active > 0;
                }

                if (filter === 'attention') {
                    return attention > 0;
                }

                if (filter === 'empty') {
                    return total === 0;
                }

                return true;
            }

            function applyFilters() {
                const query = (
                    searchInput?.value || ''
                )
                    .toLowerCase()
                    .trim();
                const filter =
                    filterSelect?.value || 'all';

                let visibleCount = 0;

                groups.forEach((group) => {
                    const matchesSearch =
                        query === ''
                        || group.dataset.search.includes(query);

                    const visible =
                        matchesSearch
                        && matchesFilter(group, filter);

                    group.classList.toggle(
                        'hidden',
                        !visible
                    );

                    if (!visible) {
                        setExpanded(group, false);
                    } else {
                        visibleCount++;
                    }
                });

                noResults?.classList.toggle(
                    'hidden',
                    visibleCount > 0
                );
            }

            searchInput?.addEventListener(
                'input',
                applyFilters
            );
            filterSelect?.addEventListener(
                'change',
                applyFilters
            );
        })();
    </script>
@endsection
