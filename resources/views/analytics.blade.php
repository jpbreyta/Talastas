@extends('layouts.app')

@section('content')
    <div class="min-h-full bg-gray-50 py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Page Header --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        ICT Gap Analytics
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Infrastructure assessment and decision support for Lipa City
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-2 text-sm text-gray-500">
                        <span class="h-2 w-2 rounded-full bg-green-500"></span>
                        Updated {{ now()->format('M d, Y') }}
                    </span>

                    <a href="{{ url('/api/v1/analytics/export') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                        <i class="fa-solid fa-file-export"></i>
                        Export CSV
                    </a>
                </div>
            </div>

            {{-- Executive Summary --}}
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

                {{-- Overall Gap Score --}}
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Overall Gap Score
                        </span>

                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600">
                            <i class="fa-solid fa-chart-line"></i>
                        </span>
                    </div>

                    <div class="flex items-end gap-1">
                        <span class="text-4xl font-bold text-gray-900">
                            {{ number_format($averageGapScore ?? 0, 1) }}
                        </span>

                        <span class="pb-1 text-xl font-semibold text-gray-400">
                            %
                        </span>
                    </div>

                    <p class="mt-2 text-xs text-gray-500">
                        Average ICT gap among analyzed institutions
                    </p>
                </div>

                {{-- Institutions --}}
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Institutions
                        </span>

                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600">
                            <i class="fa-solid fa-building"></i>
                        </span>
                    </div>

                    <p class="text-4xl font-bold text-gray-900">
                        {{ $institutionCount ?? 0 }}
                    </p>

                    <p class="mt-2 text-xs text-gray-500">
                        {{ $analyzedInstitutionCount ?? 0 }} institutions analyzed
                    </p>
                </div>

                {{-- Critical --}}
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Critical Priority
                        </span>

                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </span>
                    </div>

                    <p class="text-4xl font-bold text-red-600">
                        {{ $criticalAreas ?? 0 }}
                    </p>

                    <p class="mt-2 text-xs text-gray-500">
                        Institutions requiring immediate intervention
                    </p>
                </div>

                {{-- Warning --}}
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Needs Improvement
                        </span>

                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                            <i class="fa-solid fa-circle-exclamation"></i>
                        </span>
                    </div>

                    <p class="text-4xl font-bold text-amber-600">
                        {{ $needsAttention ?? 0 }}
                    </p>

                    <p class="mt-2 text-xs text-gray-500">
                        Institutions with moderate ICT gaps
                    </p>
                </div>
            </div>

            {{-- Gap Category Breakdown --}}
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-5">
                    <h2 class="text-base font-semibold text-gray-900">
                        ICT Gap Category Breakdown
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Main factors contributing to the overall ICT gap
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                    {{-- Infrastructure Deficit --}}
                    @php
                        $infrastructureValue = min(100, max(0, (float) ($infrastructureDeficit ?? 0)));

                        $infrastructureColor =
                            $infrastructureValue >= 80
                                ? 'bg-red-500'
                                : ($infrastructureValue >= 50
                                    ? 'bg-amber-400'
                                    : 'bg-green-500');

                        $infrastructureText =
                            $infrastructureValue >= 80
                                ? 'text-red-600'
                                : ($infrastructureValue >= 50
                                    ? 'text-amber-600'
                                    : 'text-green-600');
                    @endphp

                    <div class="rounded-xl border border-gray-200 p-5">
                        <div class="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Infrastructure Deficit
                                </h3>

                                <p class="mt-1 text-xs leading-relaxed text-gray-500">
                                    Availability of computers, laboratories, display equipment, and ICT facilities
                                </p>
                            </div>

                            <span
                                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                <i class="fa-solid fa-computer"></i>
                            </span>
                        </div>

                        <p class="text-3xl font-bold {{ $infrastructureText }}">
                            {{ number_format($infrastructureValue, 1) }}%
                        </p>

                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full {{ $infrastructureColor }}"
                                style="width: {{ $infrastructureValue }}%"></div>
                        </div>
                    </div>

                    {{-- Connectivity Divide --}}
                    @php
                        $connectivityValue = min(100, max(0, (float) ($connectivityDivide ?? 0)));

                        $connectivityColor =
                            $connectivityValue >= 80
                                ? 'bg-red-500'
                                : ($connectivityValue >= 50
                                    ? 'bg-amber-400'
                                    : 'bg-green-500');

                        $connectivityText =
                            $connectivityValue >= 80
                                ? 'text-red-600'
                                : ($connectivityValue >= 50
                                    ? 'text-amber-600'
                                    : 'text-green-600');
                    @endphp

                    <div class="rounded-xl border border-gray-200 p-5">
                        <div class="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Connectivity Divide
                                </h3>

                                <p class="mt-1 text-xs leading-relaxed text-gray-500">
                                    Internet availability, provider access, and connection capacity
                                </p>
                            </div>

                            <span
                                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                                <i class="fa-solid fa-wifi"></i>
                            </span>
                        </div>

                        <p class="text-3xl font-bold {{ $connectivityText }}">
                            {{ number_format($connectivityValue, 1) }}%
                        </p>

                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full {{ $connectivityColor }}"
                                style="width: {{ $connectivityValue }}%"></div>
                        </div>
                    </div>

                    {{-- Technological Obsolescence --}}
                    @php
                        $obsolescenceValue = min(100, max(0, (float) ($technologicalObsolescence ?? 0)));

                        $obsolescenceColor =
                            $obsolescenceValue >= 80
                                ? 'bg-red-500'
                                : ($obsolescenceValue >= 50
                                    ? 'bg-amber-400'
                                    : 'bg-green-500');

                        $obsolescenceText =
                            $obsolescenceValue >= 80
                                ? 'text-red-600'
                                : ($obsolescenceValue >= 50
                                    ? 'text-amber-600'
                                    : 'text-green-600');
                    @endphp

                    <div class="rounded-xl border border-gray-200 p-5">
                        <div class="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Technology Obsolescence
                                </h3>

                                <p class="mt-1 text-xs leading-relaxed text-gray-500">
                                    Aging, damaged, retired, and maintenance-dependent ICT equipment
                                </p>
                            </div>

                            <span
                                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </span>
                        </div>

                        <p class="text-3xl font-bold {{ $obsolescenceText }}">
                            {{ number_format($obsolescenceValue, 1) }}%
                        </p>

                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full {{ $obsolescenceColor }}"
                                style="width: {{ $obsolescenceValue }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Infrastructure Status and Priority Needs --}}
            <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

                {{-- Infrastructure Availability --}}
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-5">
                        <h2 class="text-base font-semibold text-gray-900">
                            ICT Infrastructure Availability
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Percentage of active institutions with available ICT resources
                        </p>
                    </div>

                    @php
                        $statusItems = [
                            [
                                'label' => 'Computer Laboratories',
                                'value' => $infrastructureStatus['computer_laboratories'] ?? 0,
                                'icon' => 'fa-solid fa-desktop',
                            ],
                            [
                                'label' => 'Internet Connectivity',
                                'value' => $infrastructureStatus['internet_connectivity'] ?? 0,
                                'icon' => 'fa-solid fa-wifi',
                            ],
                            [
                                'label' => 'Smart Televisions',
                                'value' => $infrastructureStatus['smart_tvs'] ?? 0,
                                'icon' => 'fa-solid fa-tv',
                            ],
                            [
                                'label' => 'Projected Whiteboards',
                                'value' => $infrastructureStatus['projected_whiteboards'] ?? 0,
                                'icon' => 'fa-solid fa-display',
                            ],
                            [
                                'label' => 'Public Address Systems',
                                'value' => $infrastructureStatus['public_address_systems'] ?? 0,
                                'icon' => 'fa-solid fa-volume-high',
                            ],
                        ];
                    @endphp

                    <div class="space-y-5">
                        @foreach ($statusItems as $item)
                            @php
                                $availabilityValue = min(100, max(0, (float) $item['value']));

                                $availabilityBar =
                                    $availabilityValue >= 80
                                        ? 'bg-green-500'
                                        : ($availabilityValue >= 50
                                            ? 'bg-amber-400'
                                            : 'bg-red-500');
                            @endphp

                            <div>
                                <div class="mb-2 flex items-center justify-between gap-4">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                            <i class="{{ $item['icon'] }}"></i>
                                        </span>

                                        <span class="truncate text-sm font-medium text-gray-700">
                                            {{ $item['label'] }}
                                        </span>
                                    </div>

                                    <span class="text-sm font-bold text-gray-900">
                                        {{ number_format($availabilityValue, 1) }}%
                                    </span>
                                </div>

                                <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-full rounded-full {{ $availabilityBar }}"
                                        style="width: {{ $availabilityValue }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Priority ICT Needs --}}
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-5">
                        <h2 class="text-base font-semibold text-gray-900">
                            Priority ICT Needs
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Most common areas requiring intervention
                        </p>
                    </div>

                    @if (collect($priorityNeeds ?? [])->isEmpty())
                        <div class="flex min-h-64 flex-col items-center justify-center text-center">
                            <span
                                class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-green-50 text-green-600">
                                <i class="fa-solid fa-circle-check text-xl"></i>
                            </span>

                            <p class="text-sm font-semibold text-gray-700">
                                No priority needs detected
                            </p>

                            <p class="mt-1 text-xs text-gray-500">
                                Available records do not currently show major deficiencies.
                            </p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($priorityNeeds as $index => $need)
                                <div class="flex items-center gap-4 rounded-lg border border-gray-200 p-4">
                                    <span
                                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-red-50 text-sm font-bold text-red-600">
                                        {{ $index + 1 }}
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-gray-800">
                                            {{ $need['label'] }}
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ $need['count'] }}
                                            {{ Str::plural('institution', $need['count']) }}
                                            affected
                                        </p>
                                    </div>

                                    <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-600">
                                        {{ $need['count'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Barangay Priority and Risk Distribution --}}
            <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- Priority Barangays --}}
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">
                                Priority Barangays
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Barangays ranked by average institutional ICT gap
                            </p>
                        </div>

                        <span
                            class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-500">
                            Top 10
                        </span>
                    </div>

                    @php
                        $topBarangays = collect($barangayScores ?? [])->take(10);
                    @endphp

                    @if ($topBarangays->isEmpty())
                        <div class="py-12 text-center">
                            <i class="fa-solid fa-map-location-dot text-3xl text-gray-300"></i>

                            <p class="mt-3 text-sm text-gray-500">
                                No barangay analytics available.
                            </p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach ($topBarangays as $barangay)
                                @php
                                    $barangayGap = min(100, max(0, (float) ($barangay['gap_score'] ?? 0)));

                                    $barangayRisk = $barangay['risk_level'] ?? 'adequate';

                                    $barangayBar = match ($barangayRisk) {
                                        'critical' => 'bg-red-500',
                                        'warning' => 'bg-amber-400',
                                        default => 'bg-green-500',
                                    };

                                    $barangayBadge = match ($barangayRisk) {
                                        'critical' => 'bg-red-50 text-red-700',
                                        'warning' => 'bg-amber-50 text-amber-700',
                                        default => 'bg-green-50 text-green-700',
                                    };
                                @endphp

                                <div>
                                    <div class="mb-2 flex items-center justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-gray-800">
                                                {{ $barangay['name'] ?? 'Unknown Barangay' }}
                                            </p>

                                            <p class="text-xs text-gray-500">
                                                {{ $barangay['institution_count'] ?? 0 }}
                                                {{ Str::plural('institution', $barangay['institution_count'] ?? 0) }}
                                            </p>
                                        </div>

                                        <div class="flex flex-shrink-0 items-center gap-3">
                                            <span
                                                class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $barangayBadge }}">
                                                {{ ucfirst($barangayRisk) }}
                                            </span>

                                            <span class="w-14 text-right text-sm font-bold text-gray-900">
                                                {{ number_format($barangayGap, 1) }}%
                                            </span>
                                        </div>
                                    </div>

                                    <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                                        <div class="h-full rounded-full {{ $barangayBar }}"
                                            style="width: {{ $barangayGap }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Risk Distribution --}}
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-5">
                        <h2 class="text-base font-semibold text-gray-900">
                            Risk Distribution
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Institutions grouped by gap severity
                        </p>
                    </div>

                    @php
                        $riskTotal =
                            ($criticalAreas ?? 0) +
                            ($needsAttention ?? 0) +
                            ($adequateAreas ?? 0) +
                            ($insufficientData ?? 0);

                        $riskItems = [
                            [
                                'label' => 'Critical',
                                'count' => $criticalAreas ?? 0,
                                'dot' => 'bg-red-500',
                                'badge' => 'bg-red-50 text-red-700',
                            ],
                            [
                                'label' => 'Warning',
                                'count' => $needsAttention ?? 0,
                                'dot' => 'bg-amber-400',
                                'badge' => 'bg-amber-50 text-amber-700',
                            ],
                            [
                                'label' => 'Adequate',
                                'count' => $adequateAreas ?? 0,
                                'dot' => 'bg-green-500',
                                'badge' => 'bg-green-50 text-green-700',
                            ],
                            [
                                'label' => 'Insufficient Data',
                                'count' => $insufficientData ?? 0,
                                'dot' => 'bg-gray-400',
                                'badge' => 'bg-gray-100 text-gray-700',
                            ],
                        ];
                    @endphp

                    <div class="mb-6 flex justify-center">
                        <div
                            class="flex h-32 w-32 flex-col items-center justify-center rounded-full border-[14px] border-red-100">
                            <span class="text-3xl font-bold text-gray-900">
                                {{ $riskTotal }}
                            </span>

                            <span class="text-xs text-gray-500">
                                analyzed
                            </span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach ($riskItems as $risk)
                            @php
                                $riskPercentage = $riskTotal > 0 ? round(($risk['count'] / $riskTotal) * 100, 1) : 0;
                            @endphp

                            <div class="flex items-center gap-3">
                                <span class="h-3 w-3 flex-shrink-0 rounded-full {{ $risk['dot'] }}"></span>

                                <span class="min-w-0 flex-1 text-sm text-gray-600">
                                    {{ $risk['label'] }}
                                </span>

                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $risk['badge'] }}">
                                    {{ $risk['count'] }}
                                </span>

                                <span class="w-12 text-right text-xs font-medium text-gray-500">
                                    {{ number_format($riskPercentage, 1) }}%
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Data Quality --}}
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span
                            class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
                            <i class="fa-solid fa-database"></i>
                        </span>

                        <div>
                            <p class="text-sm font-semibold text-red-900">
                                Analytics Data Completeness
                            </p>

                            <p class="mt-1 text-xs leading-relaxed text-red-700">
                                Institutions with incomplete records may be classified as insufficient data instead of
                                receiving an inaccurate gap score.
                            </p>
                        </div>
                    </div>

                    <div class="flex-shrink-0 text-left sm:text-right">
                        <p class="text-2xl font-bold text-red-900">
                            {{ number_format($averageCompleteness ?? 0, 1) }}%
                        </p>

                        <p class="text-xs text-red-700">
                            average completeness
                        </p>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="flex items-center gap-2 lg:mr-2">
                        <i class="fa-solid fa-filter text-gray-400"></i>

                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Filter Institutions
                        </span>
                    </div>

                    <select id="barangayFilter"
                        class="min-w-44 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                        <option value="">All Barangays</option>

                        @foreach ($barangays ?? [] as $barangay)
                            <option value="{{ strtolower($barangay) }}">
                                {{ $barangay }}
                            </option>
                        @endforeach
                    </select>

                    <select id="typeFilter"
                        class="min-w-44 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                        <option value="">All Institution Types</option>
                        <option value="school">School</option>
                        <option value="barangay hall">Barangay Hall</option>
                        <option value="local government office">Local Government Office</option>
                    </select>

                    <select id="riskFilter"
                        class="min-w-44 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                        <option value="">All Risk Levels</option>
                        <option value="critical">Critical</option>
                        <option value="warning">Warning</option>
                        <option value="adequate">Adequate</option>
                        <option value="insufficient_data">Insufficient Data</option>
                    </select>

                    <button type="button" id="clearFilters"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 lg:ml-auto">
                        <i class="fa-solid fa-rotate-left"></i>
                        Clear Filters
                    </button>
                </div>
            </div>

            {{-- Institution Priority Table --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div
                    class="flex flex-col gap-3 border-b border-gray-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">
                            Institution Priority List
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Institutions ordered from highest to lowest ICT gap
                        </p>
                    </div>

                    <span
                        class="inline-flex w-fit items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                        {{ $analytics->total() ?? 0 }}
                        {{ Str::plural('record', $analytics->total() ?? 0) }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Institution
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Barangay
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Type
                                </th>

                                <th
                                    class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Risk
                                </th>

                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Gap Score
                                </th>

                                <th
                                    class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Source
                                </th>

                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Analysis and Recommendation
                                </th>
                            </tr>
                        </thead>

                        <tbody id="analyticsTableBody" class="divide-y divide-gray-100 bg-white">
                            @forelse($analytics ?? [] as $analytic)
                                @php
                                    $institution = $analytic->institution;

                                    $gapScore = $analytic->gap_score !== null ? (float) $analytic->gap_score : null;

                                    $riskLevel =
                                        $analytic->risk_level ??
                                        ($gapScore === null
                                            ? 'insufficient_data'
                                            : ($gapScore >= 80
                                                ? 'critical'
                                                : ($gapScore >= 50
                                                    ? 'warning'
                                                    : 'adequate')));

                                    $riskLabel = match ($riskLevel) {
                                        'critical' => 'Critical',
                                        'warning' => 'Warning',
                                        'adequate' => 'Adequate',
                                        'insufficient_data' => 'Insufficient Data',
                                        default => ucfirst(str_replace('_', ' ', $riskLevel)),
                                    };

                                    $riskBadge = match ($riskLevel) {
                                        'critical' => 'bg-red-100 text-red-700',
                                        'warning' => 'bg-amber-100 text-amber-700',
                                        'adequate' => 'bg-green-100 text-green-700',
                                        'insufficient_data' => 'bg-gray-100 text-gray-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };

                                    $gapText = match ($riskLevel) {
                                        'critical' => 'text-red-600',
                                        'warning' => 'text-amber-600',
                                        'adequate' => 'text-green-600',
                                        default => 'text-gray-500',
                                    };

                                    $gapBar = match ($riskLevel) {
                                        'critical' => 'bg-red-500',
                                        'warning' => 'bg-amber-400',
                                        'adequate' => 'bg-green-500',
                                        default => 'bg-gray-400',
                                    };

                                    $recommendationDetails = is_array($analytic->recommendations_detail)
                                        ? $analytic->recommendations_detail
                                        : [];

                                    $analysisMethod = strtolower(
                                        (string) ($recommendationDetails['method'] ?? '')
                                    );

                                    $analysisProvider = strtolower(
                                        (string) ($recommendationDetails['provider'] ?? '')
                                    );

                                    $analysisModel = $recommendationDetails['model'] ?? null;

                                    $scoreSource = strtolower(
                                        (string) ($recommendationDetails['score_source'] ?? '')
                                    );

                                    $providerLabel = match ($analysisProvider) {
                                        'gemini' => 'Gemini',
                                        'groq' => 'Groq',
                                        'local' => 'Local',
                                        default => $analysisMethod === 'generative_ai'
                                            ? 'AI'
                                            : 'Local',
                                    };

                                    $providerBadge = match ($analysisProvider) {
                                        'gemini' => 'bg-red-100 text-red-700',
                                        'groq' => 'bg-orange-100 text-orange-700',
                                        'local' => 'bg-gray-100 text-gray-700',
                                        default => $analysisMethod === 'generative_ai'
                                            ? 'bg-purple-100 text-purple-700'
                                            : 'bg-gray-100 text-gray-700',
                                    };

                                    $providerIcon = match ($analysisProvider) {
                                        'gemini' => 'fa-solid fa-wand-magic-sparkles',
                                        'groq' => 'fa-solid fa-bolt',
                                        default => 'fa-solid fa-microchip',
                                    };

                                    $scoreSourceLabel = match ($scoreSource) {
                                        'python_service' => 'Python scores',
                                        'local_deterministic_fallback' => 'Local scores',
                                        default => 'Score source unavailable',
                                    };
                                @endphp

                                <tr class="analytics-row transition hover:bg-gray-50"
                                    data-barangay="{{ strtolower($institution?->barangay?->name ?? '') }}"
                                    data-type="{{ strtolower($institution?->type ?? '') }}"
                                    data-risk="{{ strtolower($riskLevel) }}">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                <i class="fa-solid fa-building"></i>
                                            </span>

                                            <div class="min-w-0">
                                                <p class="max-w-52 truncate text-sm font-semibold text-gray-900">
                                                    {{ $institution?->name ?? 'Unknown Institution' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                        {{ $institution?->barangay?->name ?? 'Not assigned' }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4">
                                        <span
                                            class="rounded-md bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                                            {{ $institution?->type ?? 'Not specified' }}
                                        </span>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 text-center">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $riskBadge }}">
                                            {{ $riskLabel }}
                                        </span>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 text-right">
                                        @if ($gapScore !== null)
                                            <div class="flex items-center justify-end gap-3">
                                                <div class="h-2 w-16 overflow-hidden rounded-full bg-gray-100">
                                                    <div class="h-full rounded-full {{ $gapBar }}"
                                                        style="width: {{ min(100, max(0, $gapScore)) }}%"></div>
                                                </div>

                                                <span class="w-14 text-right text-sm font-bold {{ $gapText }}">
                                                    {{ number_format($gapScore, 1) }}%
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-xs font-medium text-gray-400">
                                                Not available
                                            </span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 text-center">
                                        <div class="flex flex-col items-center gap-1.5">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $providerBadge }}"
                                                @if ($analysisModel)
                                                    title="{{ $providerLabel }} model: {{ $analysisModel }}"
                                                @endif>
                                                <i class="{{ $providerIcon }}"></i>
                                                {{ $providerLabel }}
                                            </span>

                                            <span class="text-[11px] font-medium text-gray-400">
                                                {{ $scoreSourceLabel }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="min-w-80 px-5 py-4">
                                        @if ($analytic->infrastructure_narrative)
                                            <div class="mb-3">
                                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                    Analysis
                                                </p>

                                                <p class="mt-1 text-sm leading-relaxed text-gray-700"
                                                    title="{{ $analytic->infrastructure_narrative }}">
                                                    {{ Str::limit($analytic->infrastructure_narrative, 130) }}
                                                </p>
                                            </div>
                                        @endif

                                        @if ($analytic->policy_recommendation)
                                            <div class="rounded-lg bg-red-50 p-3">
                                                <div class="flex items-start gap-2">
                                                    <i class="fa-solid fa-lightbulb mt-0.5 text-red-600"></i>

                                                    <div>
                                                        <p
                                                            class="text-xs font-semibold uppercase tracking-wider text-red-700">
                                                            Recommendation
                                                        </p>

                                                        <p class="mt-1 text-sm font-medium leading-relaxed text-red-800"
                                                            title="{{ $analytic->policy_recommendation }}">
                                                            {{ Str::limit($analytic->policy_recommendation, 150) }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif(!$analytic->infrastructure_narrative)
                                            <span class="text-sm italic text-gray-400">
                                                No analysis available.
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-16 text-center">
                                        <i class="fa-solid fa-chart-column text-4xl text-gray-300"></i>

                                        <p class="mt-4 text-sm font-semibold text-gray-600">
                                            No analytics records available
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500">
                                            Run institution analysis to generate decision-support results.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="noFilteredResults" class="hidden border-t border-gray-200 px-5 py-12 text-center">
                    <i class="fa-solid fa-filter-circle-xmark text-3xl text-gray-300"></i>

                    <p class="mt-3 text-sm font-semibold text-gray-600">
                        No records match the selected filters.
                    </p>
                </div>

                @if (method_exists($analytics, 'links'))
                    <div class="border-t border-gray-200 px-5 py-4">
                        {{ $analytics->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const barangayFilter = document.getElementById('barangayFilter');
            const typeFilter = document.getElementById('typeFilter');
            const riskFilter = document.getElementById('riskFilter');
            const clearFiltersButton = document.getElementById('clearFilters');
            const rows = Array.from(
                document.querySelectorAll('.analytics-row')
            );
            const noFilteredResults = document.getElementById(
                'noFilteredResults'
            );

            function applyFilters() {
                const selectedBarangay =
                    barangayFilter?.value.trim().toLowerCase() ?? '';

                const selectedType =
                    typeFilter?.value.trim().toLowerCase() ?? '';

                const selectedRisk =
                    riskFilter?.value.trim().toLowerCase() ?? '';

                let visibleRows = 0;

                rows.forEach((row) => {
                    const rowBarangay =
                        row.dataset.barangay?.trim().toLowerCase() ?? '';

                    const rowType =
                        row.dataset.type?.trim().toLowerCase() ?? '';

                    const rowRisk =
                        row.dataset.risk?.trim().toLowerCase() ?? '';

                    const matchesBarangay = !selectedBarangay ||
                        rowBarangay === selectedBarangay;

                    const matchesType = !selectedType ||
                        rowType === selectedType;

                    const matchesRisk = !selectedRisk ||
                        rowRisk === selectedRisk;

                    const shouldDisplay =
                        matchesBarangay &&
                        matchesType &&
                        matchesRisk;

                    row.classList.toggle('hidden', !shouldDisplay);

                    if (shouldDisplay) {
                        visibleRows++;
                    }
                });

                if (noFilteredResults) {
                    noFilteredResults.classList.toggle(
                        'hidden',
                        visibleRows > 0 || rows.length === 0
                    );
                }
            }

            barangayFilter?.addEventListener(
                'change',
                applyFilters
            );

            typeFilter?.addEventListener(
                'change',
                applyFilters
            );

            riskFilter?.addEventListener(
                'change',
                applyFilters
            );

            clearFiltersButton?.addEventListener(
                'click',
                () => {
                    if (barangayFilter) {
                        barangayFilter.value = '';
                    }

                    if (typeFilter) {
                        typeFilter.value = '';
                    }

                    if (riskFilter) {
                        riskFilter.value = '';
                    }

                    applyFilters();
                }
            );
        });
    </script>
@endsection