@extends('layouts.app')

@section('dashboard-layout')
@endsection

@section('content')
    @php
        $categoryLabels = collect($inventoryConfig['categories'] ?? [])
            ->mapWithKeys(
                fn ($category, $key) => [
                    $key => $category['label'] ?? str($key)->headline(),
                ]
            )
            ->all();

        $conditionStyles = [
            'new' => ['label' => 'New', 'class' => 'border-violet-200 bg-violet-50 text-violet-700'],
            'excellent' => ['label' => 'Excellent', 'class' => 'border-blue-200 bg-blue-50 text-blue-700'],
            'good' => ['label' => 'Good', 'class' => 'border-green-200 bg-green-50 text-green-700'],
            'fair' => ['label' => 'Fair', 'class' => 'border-yellow-200 bg-yellow-50 text-yellow-700'],
            'poor' => ['label' => 'Poor', 'class' => 'border-red-200 bg-red-50 text-red-700'],
            'needs_repair' => ['label' => 'Needs Repair', 'class' => 'border-orange-200 bg-orange-50 text-orange-700'],
        ];

        $statusStyles = [
            'active' => ['label' => 'Active', 'class' => 'border-green-200 bg-green-50 text-green-700', 'icon' => 'fa-circle-check'],
            'under_repair' => ['label' => 'Under Repair', 'class' => 'border-orange-200 bg-orange-50 text-orange-700', 'icon' => 'fa-screwdriver-wrench'],
            'inactive' => ['label' => 'Inactive', 'class' => 'border-gray-200 bg-gray-100 text-gray-600', 'icon' => 'fa-circle-pause'],
            'retired' => ['label' => 'Retired', 'class' => 'border-slate-200 bg-slate-100 text-slate-600', 'icon' => 'fa-box-archive'],
            'disposed' => ['label' => 'Disposed', 'class' => 'border-slate-200 bg-slate-100 text-slate-500', 'icon' => 'fa-trash-can'],
        ];

        $formatSpecificationValue = function ($value): string {
            if (is_bool($value)) {
                return $value ? 'Yes' : 'No';
            }

            if (is_array($value)) {
                return collect($value)->filter()->implode(', ');
            }

            return str((string) $value)
                ->replace('_', ' ')
                ->headline()
                ->toString();
        };
    @endphp

    <div class="flex h-full min-h-0 flex-col bg-gray-100">
        <header class="shrink-0 bg-red-700 px-6 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-600 text-red-100">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </span>

                    <div class="min-w-0">
                        <h1 class="text-xl font-semibold tracking-tight text-white">ICT Inventory</h1>
                        <p class="mt-0.5 text-xs text-red-200">
                            Review grouped ICT assets, technical specifications, and equipment conditions by institution.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-red-300">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </span>

                        <input
                            id="inventoryInstitutionSearch"
                            type="search"
                            placeholder="Search institutions or assets..."
                            autocomplete="off"
                            class="w-full rounded-lg border border-red-600 bg-red-800 py-2 pl-9 pr-4 text-sm text-white placeholder-red-300 transition focus:outline-none focus:ring-2 focus:ring-red-300 sm:w-64"
                        >
                    </div>

                    <select
                        id="inventoryInstitutionFilter"
                        class="rounded-lg border border-red-600 bg-red-800 px-3 py-2 text-sm text-white transition focus:outline-none focus:ring-2 focus:ring-red-300"
                    >
                        <option value="all">All Institutions</option>
                        <option value="active">With Active Assets</option>
                        <option value="attention">Needs Attention</option>
                        <option value="empty">No Inventory</option>
                    </select>

                    <a
                        href="{{ route('ict-inventory.create') }}"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-red-100 bg-white px-4 py-2 text-sm font-medium text-red-700 shadow-sm transition hover:bg-red-50"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Add Record
                    </a>
                </div>
            </div>
        </header>

        <main class="flex min-h-0 flex-1 flex-col">
            @if (session('success'))
                <div class="mx-5 mt-4 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
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
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">Institution</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">Barangay</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Total Assets</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Active</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Attention</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Actions</th>
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
                                                    $categoryLabels[$item->category] ?? str($item->category)->headline(),
                                                    $item->asset_name,
                                                    $item->brand,
                                                    $item->model,
                                                    $item->location,
                                                    $item->condition,
                                                    $item->status,
                                                    $item->acquisition_year,
                                                    json_encode($item->specifications ?? []),
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
                                        data-attention="{{ $institution->attention_assets_count }}"
                                    >
                                        <tr
                                            class="cursor-pointer bg-white transition hover:bg-red-50/50"
                                            data-accordion-trigger
                                            tabindex="0"
                                            role="button"
                                            aria-expanded="false"
                                            aria-controls="institution-assets-{{ $institution->id }}"
                                        >
                                            <td class="px-4 py-4 text-center">
                                                <span
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500 transition"
                                                    data-chevron-container
                                                >
                                                    <i class="fa-solid fa-chevron-right text-xs transition-transform duration-200" data-chevron></i>
                                                </span>
                                            </td>

                                            <td class="px-4 py-4">
                                                <div class="flex items-center gap-3">
                                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                        <i class="fa-solid fa-building"></i>
                                                    </span>
                                                    <div class="min-w-0">
                                                        <p class="truncate font-semibold text-gray-900">{{ $institution->name }}</p>
                                                        <p class="mt-0.5 text-xs text-gray-500">{{ $institution->type }}</p>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="px-4 py-4 text-gray-600">
                                                {{ $institution->barangay?->name ?? 'Not assigned' }}
                                            </td>

                                            <td class="px-4 py-4 text-center">
                                                <span class="inline-flex min-w-9 items-center justify-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                    {{ number_format($institution->total_assets_count) }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-4 text-center">
                                                <span class="inline-flex min-w-9 items-center justify-center rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">
                                                    {{ number_format($institution->active_assets_count) }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-4 text-center">
                                                <span
                                                    class="inline-flex min-w-9 items-center justify-center rounded-full px-2.5 py-1 text-xs font-semibold
                                                        {{ $institution->attention_assets_count > 0
                                                            ? 'bg-orange-50 text-orange-700'
                                                            : 'bg-gray-100 text-gray-500' }}"
                                                >
                                                    {{ number_format($institution->attention_assets_count) }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a
                                                        href="{{ route('ict-inventory.create', ['institution_id' => $institution->id]) }}"
                                                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 transition hover:bg-red-100"
                                                        data-stop-accordion
                                                    >
                                                        <i class="fa-solid fa-plus"></i>
                                                        Add Record
                                                    </a>

                                                    <a
                                                        href="{{ route('institutions.show', $institution) }}"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-red-600"
                                                        title="View institution"
                                                        aria-label="View {{ $institution->name }}"
                                                        data-stop-accordion
                                                    >
                                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>

                                        <tr
                                            id="institution-assets-{{ $institution->id }}"
                                            class="hidden bg-slate-50"
                                            data-accordion-panel
                                        >
                                            <td colspan="7" class="p-0">
                                                <div class="border-t border-gray-200 px-5 py-5">
                                                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                        <div>
                                                            <h2 class="text-sm font-semibold text-gray-900">Equipment Records</h2>
                                                            <p class="mt-1 text-xs text-gray-500">
                                                                {{ $institution->ictInventory->count() }}
                                                                grouped
                                                                {{ Str::plural('record', $institution->ictInventory->count()) }}
                                                                representing
                                                                {{ number_format($institution->total_assets_count) }}
                                                                physical
                                                                {{ Str::plural('asset', $institution->total_assets_count) }}.
                                                            </p>
                                                        </div>
                                                    </div>

                                                    @if ($institution->ictInventory->isNotEmpty())
                                                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                                                            <div class="overflow-x-auto">
                                                                <table class="w-full min-w-[1180px] text-sm">
                                                                    <thead>
                                                                        <tr class="border-b border-gray-200 bg-gray-50 text-left">
                                                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Equipment</th>
                                                                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Qty</th>
                                                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Brand / Model</th>
                                                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Specifications</th>
                                                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Condition</th>
                                                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Year</th>
                                                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                                                                        </tr>
                                                                    </thead>

                                                                    <tbody class="divide-y divide-gray-100">
                                                                        @foreach ($institution->ictInventory as $inventory)
                                                                            @php
                                                                                $condition = $conditionStyles[$inventory->condition] ?? [
                                                                                    'label' => str($inventory->condition)->headline(),
                                                                                    'class' => 'border-gray-200 bg-gray-100 text-gray-600',
                                                                                ];

                                                                                $status = $statusStyles[$inventory->status] ?? [
                                                                                    'label' => str($inventory->status)->headline(),
                                                                                    'class' => 'border-gray-200 bg-gray-100 text-gray-600',
                                                                                    'icon' => 'fa-circle',
                                                                                ];

                                                                                $specDefinitions =
                                                                                    $inventoryConfig['categories'][$inventory->category]['specifications']
                                                                                    ?? [];

                                                                                $specificationSummary = collect($inventory->specifications ?? [])
                                                                                    ->take(3)
                                                                                    ->map(
                                                                                        fn ($value, $key) =>
                                                                                            ($specDefinitions[$key]['label']
                                                                                                ?? str($key)->headline())
                                                                                            . ': '
                                                                                            . $formatSpecificationValue($value)
                                                                                    )
                                                                                    ->implode(' • ');
                                                                            @endphp

                                                                            <tr class="transition hover:bg-gray-50">
                                                                                <td class="px-4 py-3">
                                                                                    <div>
                                                                                        <p class="font-medium text-gray-900">
                                                                                            {{ $inventory->asset_name
                                                                                                ?: ($categoryLabels[$inventory->category]
                                                                                                    ?? str($inventory->category)->headline()) }}
                                                                                        </p>
                                                                                        <p class="mt-0.5 text-xs text-gray-500">
                                                                                            {{ $categoryLabels[$inventory->category]
                                                                                                ?? str($inventory->category)->headline() }}
                                                                                            @if ($inventory->location)
                                                                                                <span class="mx-1 text-gray-300">•</span>
                                                                                                {{ $inventory->location }}
                                                                                            @endif
                                                                                        </p>
                                                                                    </div>
                                                                                </td>

                                                                                <td class="px-4 py-3 text-center">
                                                                                    <span class="inline-flex min-w-8 items-center justify-center rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                                                                        {{ number_format($inventory->quantity ?? 1) }}
                                                                                    </span>
                                                                                </td>

                                                                                <td class="px-4 py-3">
                                                                                    <p class="font-medium text-gray-700">{{ $inventory->brand ?: '—' }}</p>
                                                                                    <p class="mt-0.5 text-xs text-gray-500">
                                                                                        {{ $inventory->model ?: 'Model not recorded' }}
                                                                                    </p>
                                                                                </td>

                                                                                <td class="max-w-sm px-4 py-3 text-xs leading-5 text-gray-600">
                                                                                    {{ $specificationSummary ?: 'No specifications recorded' }}
                                                                                </td>

                                                                                <td class="px-4 py-3">
                                                                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium {{ $condition['class'] }}">
                                                                                        {{ $condition['label'] }}
                                                                                    </span>
                                                                                </td>

                                                                                <td class="px-4 py-3 tabular-nums text-gray-600">
                                                                                    {{ $inventory->acquisition_year ?? '—' }}
                                                                                </td>

                                                                                <td class="px-4 py-3">
                                                                                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium {{ $status['class'] }}">
                                                                                        <i class="fa-solid {{ $status['icon'] }} text-[10px]"></i>
                                                                                        {{ $status['label'] }}
                                                                                    </span>

                                                                                    @if ($inventory->needs_maintenance)
                                                                                        <p class="mt-1 text-[11px] font-medium text-orange-600">
                                                                                            Maintenance needed
                                                                                        </p>
                                                                                    @endif
                                                                                </td>

                                                                                <td class="px-4 py-3">
                                                                                    <div class="flex items-center justify-end gap-1.5">
                                                                                        <a
                                                                                            href="{{ route('ict-inventory.show', $inventory) }}"
                                                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-600 text-white transition hover:bg-red-700"
                                                                                            title="View record"
                                                                                            aria-label="View inventory record {{ $inventory->asset_id }}"
                                                                                        >
                                                                                            <i class="fa-solid fa-eye text-xs"></i>
                                                                                        </a>

                                                                                        <a
                                                                                            href="{{ route('ict-inventory.edit', $inventory) }}"
                                                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-red-600"
                                                                                            title="Edit record"
                                                                                            aria-label="Edit inventory record {{ $inventory->asset_id }}"
                                                                                        >
                                                                                            <i class="fa-solid fa-pen text-xs"></i>
                                                                                        </a>

                                                                                        <form method="POST" action="{{ route('ict-inventory.destroy', $inventory) }}">
                                                                                            @csrf
                                                                                            @method('DELETE')

                                                                                            <button
                                                                                                type="submit"
                                                                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-100"
                                                                                                title="Delete record"
                                                                                                aria-label="Delete inventory record {{ $inventory->asset_id }}"
                                                                                                onclick="return confirm('Delete this inventory record?')"
                                                                                            >
                                                                                                <i class="fa-solid fa-trash-can text-xs"></i>
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
                                                        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center">
                                                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                                                <i class="fa-solid fa-box-open"></i>
                                                            </span>

                                                            <h3 class="mt-3 text-sm font-semibold text-gray-800">No equipment records</h3>
                                                            <p class="mt-1 text-xs text-gray-500">
                                                                Add the first ICT inventory record for this institution.
                                                            </p>

                                                            <a
                                                                href="{{ route('ict-inventory.create', ['institution_id' => $institution->id]) }}"
                                                                class="mt-4 inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-xs font-medium text-white transition hover:bg-red-700"
                                                            >
                                                                <i class="fa-solid fa-plus"></i>
                                                                Add Record
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

                        <div id="inventoryNoResults" class="hidden border-t border-gray-200 px-6 py-14 text-center">
                            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <h3 class="mt-3 text-sm font-semibold text-gray-800">No matching institutions</h3>
                            <p class="mt-1 text-xs text-gray-500">Adjust the search text or inventory filter.</p>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-200 bg-white px-5 py-3">
                    {{ $institutions->links() }}
                </div>
            @else
                <div class="flex flex-1 flex-col items-center justify-center px-6 text-center">
                    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-red-50 text-red-500">
                        <i class="fa-solid fa-building-circle-xmark text-2xl"></i>
                    </span>

                    <h2 class="mt-4 text-base font-semibold text-gray-900">No institutions available</h2>
                    <p class="mt-1 max-w-md text-sm text-gray-500">
                        Create an institution before assigning ICT equipment.
                    </p>

                    <a
                        href="{{ route('institutions.create') }}"
                        class="mt-5 inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700"
                    >
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

            const searchInput = document.getElementById('inventoryInstitutionSearch');
            const filterSelect = document.getElementById('inventoryInstitutionFilter');
            const noResults = document.getElementById('inventoryNoResults');

            const groups = Array.from(
                document.querySelectorAll('[data-institution-group]')
            );

            function setExpanded(group, expanded) {
                const trigger = group.querySelector('[data-accordion-trigger]');
                const panel = group.querySelector('[data-accordion-panel]');
                const chevron = group.querySelector('[data-chevron]');
                const chevronContainer = group.querySelector('[data-chevron-container]');

                trigger?.setAttribute('aria-expanded', String(expanded));
                panel?.classList.toggle('hidden', !expanded);
                chevron?.classList.toggle('rotate-90', expanded);
                chevronContainer?.classList.toggle('border-red-200', expanded);
                chevronContainer?.classList.toggle('bg-red-50', expanded);
                chevronContainer?.classList.toggle('text-red-600', expanded);
            }

            function toggleGroup(group) {
                const trigger = group.querySelector('[data-accordion-trigger]');
                const expanded = trigger?.getAttribute('aria-expanded') === 'true';

                setExpanded(group, !expanded);
            }

            groups.forEach((group) => {
                const trigger = group.querySelector('[data-accordion-trigger]');

                trigger?.addEventListener('click', (event) => {
                    if (event.target.closest('[data-stop-accordion]')) {
                        return;
                    }

                    toggleGroup(group);
                });

                trigger?.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }

                    event.preventDefault();
                    toggleGroup(group);
                });
            });

            function matchesFilter(group, filter) {
                const total = Number(group.dataset.total || 0);
                const active = Number(group.dataset.active || 0);
                const attention = Number(group.dataset.attention || 0);

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
                const query = (searchInput?.value || '').toLowerCase().trim();
                const filter = filterSelect?.value || 'all';

                let visibleCount = 0;

                groups.forEach((group) => {
                    const matchesSearch =
                        query === ''
                        || group.dataset.search.includes(query);

                    const visible =
                        matchesSearch
                        && matchesFilter(group, filter);

                    group.classList.toggle('hidden', !visible);

                    if (!visible) {
                        setExpanded(group, false);
                    } else {
                        visibleCount++;
                    }
                });

                noResults?.classList.toggle('hidden', visibleCount > 0);
            }

            searchInput?.addEventListener('input', applyFilters);
            filterSelect?.addEventListener('change', applyFilters);
        })();
    </script>
@endsection
