@extends('layouts.app')

@section('dashboard-layout')
@endsection

@section('content')
    @php
        $inventoryItems = $institution->ictInventory;
        $score = $institution->data_completeness_score ?? 0;
        $populationLabel = $institution->type === 'School' ? 'Students' : 'Staff';
        $populationValue = $institution->type === 'School'
            ? $institution->student_population
            : $institution->staff_population;

        $categoryLabels = [
            'computer' => 'Computer',
            'laptop' => 'Laptop',
            'printer' => 'Printer',
            'router' => 'Router or Network Device',
            'smart_tv' => 'Smart TV',
            'projector' => 'Projector',
            'pa_system' => 'Public Address System',
            'other' => 'Other Equipment',
        ];

        $conditionLabels = [
            'new' => 'New',
            'excellent' => 'Excellent',
            'good' => 'Good',
            'fair' => 'Fair',
            'poor' => 'Poor',
            'needs_repair' => 'Needs Repair',
        ];

        $statusLabels = [
            'active' => 'Active',
            'under_repair' => 'Under Repair',
            'inactive' => 'Inactive',
            'retired' => 'Retired',
        ];
    @endphp

    <div class="flex h-full min-h-0 flex-col bg-gray-100">
        <div class="shrink-0 bg-blue-700 px-6 py-4">
            <div class="flex items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <a
                        href="{{ route('institutions.index') }}"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-blue-100 transition hover:bg-blue-500 hover:text-white"
                        title="Back to institutions"
                    >
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-blue-100">
                        <i class="fa-solid fa-building"></i>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="truncate text-xl font-semibold leading-tight tracking-tight text-white">
                                {{ $institution->name }}
                            </h1>

                            @if ($institution->is_pilot_site)
                                <span class="inline-flex items-center gap-1 rounded-full border border-blue-300/30 bg-blue-500/30 px-2 py-0.5 text-xs font-medium text-blue-100">
                                    <i class="fa-solid fa-flask text-[10px]"></i>
                                    Pilot
                                </span>
                            @endif
                        </div>

                        <p class="mt-0.5 text-xs text-blue-200">
                            {{ $institution->type }}
                            <span class="mx-1 text-blue-300">•</span>
                            {{ $institution->barangay?->name ?? 'Barangay not recorded' }}
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <a
                        href="{{ route('institutions.edit', $institution) }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-blue-100 bg-white px-4 py-2 text-sm font-medium text-blue-700 shadow-sm transition hover:bg-blue-50"
                    >
                        <i class="fa-solid fa-pen"></i>
                        Edit
                    </a>

                    <form method="POST" action="{{ route('institutions.destroy', $institution) }}">
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            onclick="return confirm('Delete {{ addslashes($institution->name) }} and all linked inventory items?')"
                            class="inline-flex items-center gap-2 rounded-lg border border-red-300/30 bg-red-500/20 px-4 py-2 text-sm font-medium text-red-100 transition hover:bg-red-600 hover:text-white"
                        >
                            <i class="fa-solid fa-trash"></i>
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="mx-5 mt-4 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                <i class="fa-solid fa-circle-check mt-0.5"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Record Completion</p>
                        <p class="text-xl font-bold text-gray-900">{{ $score }}%</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-600">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">{{ $populationLabel }}</p>
                        <p class="text-xl font-bold text-gray-900">
                            {{ $populationValue !== null ? number_format($populationValue) : '—' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                        <i class="fa-solid fa-gauge-high"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Internet Speed</p>
                        <p class="text-xl font-bold text-gray-900">
                            {{ $institution->internet_speed !== null ? $institution->internet_speed . ' Mbps' : '—' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Inventory Items</p>
                        <p class="text-xl font-bold text-gray-900">{{ $inventoryItems->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
                <div class="space-y-5">
                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-info text-blue-600"></i>
                                <h2 class="text-sm font-semibold text-gray-800">Institution Information</h2>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-100">
                            <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                                <span class="text-gray-500">Institution Type</span>
                                <span class="text-right font-medium text-gray-900">{{ $institution->type }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                                <span class="text-gray-500">Barangay</span>
                                <span class="text-right font-medium text-gray-900">{{ $institution->barangay?->name ?? '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                                <span class="text-gray-500">Internet Provider</span>
                                <span class="text-right font-medium text-gray-900">{{ $institution->isp_provider ?: '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                                <span class="text-gray-500">Recorded Computers</span>
                                <span class="text-right font-medium text-gray-900">
                                    {{ $institution->number_of_computers !== null ? number_format($institution->number_of_computers) : '—' }}
                                </span>
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-location-dot text-blue-600"></i>
                                <h2 class="text-sm font-semibold text-gray-800">Location</h2>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-100">
                            <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                                <span class="text-gray-500">Latitude</span>
                                <span class="font-mono text-xs font-medium text-gray-900">{{ $institution->latitude }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                                <span class="text-gray-500">Longitude</span>
                                <span class="font-mono text-xs font-medium text-gray-900">{{ $institution->longitude }}</span>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">
                            <a
                                href="https://www.google.com/maps?q={{ $institution->latitude }},{{ $institution->longitude }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 text-xs font-medium text-blue-600 transition hover:text-blue-800"
                            >
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                Open location in Google Maps
                            </a>
                        </div>
                    </section>
                </div>

                <div class="space-y-5 xl:col-span-2">
                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-computer text-blue-600"></i>
                                <h2 class="text-sm font-semibold text-gray-800">Available ICT Facilities</h2>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ([
                                ['Computer Laboratory', $institution->has_computer_lab, 'fa-desktop'],
                                ['Smart TV', $institution->has_smart_tv, 'fa-tv'],
                                ['Projected Whiteboard', $institution->has_projected_whiteboard, 'fa-chalkboard'],
                                ['Public Address System', $institution->has_public_address_system, 'fa-bullhorn'],
                            ] as [$label, $active, $icon])
                                <div class="rounded-xl border p-3 {{ $active ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50' }}">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $active ? 'bg-white text-green-600' : 'bg-white text-gray-400' }}">
                                            <i class="fa-solid {{ $icon }}"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800">{{ $label }}</p>
                                            <p class="mt-0.5 text-xs {{ $active ? 'text-green-700' : 'text-gray-500' }}">
                                                {{ $active ? 'Available' : 'Not available' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-clipboard-list text-blue-600"></i>
                                <h2 class="text-sm font-semibold text-gray-800">Institution Status</h2>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 p-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium {{ $institution->is_active ? 'border-green-200 bg-green-50 text-green-700' : 'border-gray-200 bg-gray-50 text-gray-500' }}">
                                <i class="fa-solid {{ $institution->is_active ? 'fa-circle-check' : 'fa-circle-minus' }}"></i>
                                {{ $institution->is_active ? 'Active Institution' : 'Inactive Institution' }}
                            </span>

                            <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium {{ $institution->needs_maintenance ? 'border-orange-200 bg-orange-50 text-orange-700' : 'border-green-200 bg-green-50 text-green-700' }}">
                                <i class="fa-solid {{ $institution->needs_maintenance ? 'fa-screwdriver-wrench' : 'fa-circle-check' }}"></i>
                                {{ $institution->needs_maintenance ? 'Maintenance Needed' : 'No Reported Maintenance Issue' }}
                            </span>

                            <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium {{ $institution->requires_audit ? 'border-yellow-200 bg-yellow-50 text-yellow-700' : 'border-green-200 bg-green-50 text-green-700' }}">
                                <i class="fa-solid {{ $institution->requires_audit ? 'fa-clipboard-question' : 'fa-clipboard-check' }}"></i>
                                {{ $institution->requires_audit ? 'Field Checking Needed' : 'Field Check Complete' }}
                            </span>
                        </div>
                    </section>

                    @if ($institution->notes)
                        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-note-sticky text-blue-600"></i>
                                    <h2 class="text-sm font-semibold text-gray-800">Notes</h2>
                                </div>
                            </div>
                            <p class="whitespace-pre-line px-4 py-3 text-sm leading-relaxed text-gray-600">{{ $institution->notes }}</p>
                        </section>
                    @endif
                </div>
            </div>

            <section class="mt-5 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-boxes-stacked text-blue-600"></i>
                        <div>
                            <h2 class="text-sm font-semibold text-gray-800">ICT Inventory</h2>
                            <p class="text-xs text-gray-500">
                                {{ $inventoryItems->count() }} recorded {{ $inventoryItems->count() === 1 ? 'item' : 'items' }}
                            </p>
                        </div>
                    </div>

                    <a
                        href="{{ route('ict-inventory.create', [
                            'institution_id' => $institution->id,
                            'return_to' => 'institution',
                        ]) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Add Inventory Item
                    </a>
                </div>

                @if ($inventoryItems->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[820px] text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-white text-left">
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Equipment</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Condition</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Year Acquired</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Current Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                @foreach ($inventoryItems as $item)
                                    @php
                                        $conditionClass = match ($item->condition) {
                                            'new', 'excellent', 'good' => 'border-green-200 bg-green-50 text-green-700',
                                            'fair' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
                                            'poor', 'needs_repair' => 'border-red-200 bg-red-50 text-red-700',
                                            default => 'border-gray-200 bg-gray-50 text-gray-600',
                                        };

                                        $statusClass = match ($item->status) {
                                            'active' => 'border-blue-200 bg-blue-50 text-blue-700',
                                            'under_repair' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
                                            'inactive' => 'border-gray-200 bg-gray-50 text-gray-600',
                                            'retired' => 'border-red-200 bg-red-50 text-red-700',
                                            default => 'border-gray-200 bg-gray-50 text-gray-600',
                                        };
                                    @endphp

                                    <tr class="transition hover:bg-blue-50/40">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                                    <i class="fa-solid fa-computer"></i>
                                                </div>
                                                <span class="font-medium text-gray-900">
                                                    {{ $categoryLabels[$item->category] ?? ucfirst(str_replace('_', ' ', $item->category)) }}
                                                </span>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $conditionClass }}">
                                                {{ $conditionLabels[$item->condition] ?? ucfirst(str_replace('_', ' ', $item->condition)) }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 text-gray-600">{{ $item->acquisition_year }}</td>

                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                                {{ $statusLabels[$item->status] ?? ucfirst(str_replace('_', ' ', $item->status)) }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-3">
                                                <a
                                                    href="{{ route('ict-inventory.edit', $item) }}"
                                                    class="inline-flex items-center gap-1 text-xs font-medium text-gray-600 transition hover:text-gray-900"
                                                >
                                                    <i class="fa-solid fa-pen"></i>
                                                    Edit
                                                </a>

                                                <form method="POST" action="{{ route('ict-inventory.destroy', $item) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="return_to" value="institution">

                                                    <button
                                                        type="submit"
                                                        onclick="return confirm('Delete this inventory item?')"
                                                        class="inline-flex items-center gap-1 text-xs font-medium text-gray-400 transition hover:text-red-600"
                                                    >
                                                        <i class="fa-solid fa-trash"></i>
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center px-5 py-12 text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-500">
                            <i class="fa-solid fa-box-open text-xl"></i>
                        </div>
                        <h3 class="mt-4 text-sm font-semibold text-gray-900">No inventory items yet</h3>
                        <p class="mt-1 max-w-md text-sm text-gray-500">
                            Add the computers, network devices, and other ICT equipment assigned to this institution.
                        </p>
                        <a
                            href="{{ route('ict-inventory.create', [
                                'institution_id' => $institution->id,
                                'return_to' => 'institution',
                            ]) }}"
                            class="mt-5 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                        >
                            <i class="fa-solid fa-plus"></i>
                            Add First Inventory Item
                        </a>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection