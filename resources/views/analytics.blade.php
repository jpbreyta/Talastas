@extends('layouts.app')

@section('content')
    @php
        $overallGap = is_numeric($averageGapScore ?? null)
            ? (float) $averageGapScore
            : null;

        $overallRiskKey = strtolower((string) ($overallRiskLevel ?? 'insufficient_data'));

        $overallRiskLabel = match ($overallRiskKey) {
            'critical' => 'Critical',
            'warning' => 'Needs Attention',
            'adequate' => 'Adequate',
            default => 'Insufficient Data',
        };

        $overallRiskClasses = match ($overallRiskKey) {
            'critical' => 'border-red-200 bg-red-50 text-red-700',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
            'adequate' => 'border-green-200 bg-green-50 text-green-700',
            default => 'border-gray-200 bg-gray-50 text-gray-600',
        };

        $gapDrivers = [
            [
                'label' => 'Infrastructure Deficit',
                'value' => $infrastructureDeficit,
                'icon' => 'fa-computer',
                'description' => 'Computers, laboratories, display equipment, and ICT facilities',
            ],
            [
                'label' => 'Connectivity Divide',
                'value' => $connectivityDivide,
                'icon' => 'fa-wifi',
                'description' => 'Recorded internet access and institutional connection capacity',
            ],
            [
                'label' => 'Technological Obsolescence',
                'value' => $technologicalObsolescence,
                'icon' => 'fa-clock-rotate-left',
                'description' => 'Aging, poor-condition, retired, and maintenance-dependent equipment',
            ],
        ];

        $scoreBarClass = function ($score): string {
            if (!is_numeric($score)) {
                return 'bg-gray-300';
            }

            $score = (float) $score;

            return match (true) {
                $score >= 80 => 'bg-red-500',
                $score >= 50 => 'bg-amber-400',
                default => 'bg-green-500',
            };
        };

        $scoreTextClass = function ($score): string {
            if (!is_numeric($score)) {
                return 'text-gray-500';
            }

            $score = (float) $score;

            return match (true) {
                $score >= 80 => 'text-red-600',
                $score >= 50 => 'text-amber-600',
                default => 'text-green-600',
            };
        };

        $topBarangays = collect($barangayScores ?? [])
            ->take(5);

        $hasFilters =
            request()->filled('q')
            || request()->filled('barangay')
            || request()->filled('type')
            || request()->filled('risk');
    @endphp

    <div class="min-h-full bg-gray-50 py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        ICT Planning & Gap Analysis
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        City-wide priorities derived from institutional ICT gap assessments
                    </p>

                    <div class="mt-2 flex items-center gap-2 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-green-500"></span>
                            @if ($latestAnalysisDate)
                                Latest analysis:
                                {{ \Carbon\Carbon::parse($latestAnalysisDate)->format('M d, Y g:i A') }}
                            @else
                                No completed analysis yet
                            @endif
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ url('/api/v1/analytics/export') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                        <i class="fa-solid fa-file-csv"></i>
                        Export CSV
                    </a>
                </div>
            </div>

            {{-- 1. CITY STATUS --}}
            <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
                {{-- Overall Gap --}}
                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Overall ICT Gap
                            </p>

                            <div class="mt-3 flex items-end gap-2">
                                <span class="text-4xl font-bold text-gray-900">
                                    {{ $overallGap !== null ? number_format($overallGap, 1) : '—' }}
                                </span>

                                @if ($overallGap !== null)
                                    <span class="pb-1 text-lg font-semibold text-gray-400">%</span>
                                @endif
                            </div>
                        </div>

                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                            <i class="fa-solid fa-chart-line"></i>
                        </span>
                    </div>

                    <div class="mt-4">
                        <span
                            class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $overallRiskClasses }}">
                            {{ $overallRiskLabel }}
                        </span>
                    </div>

                    <p class="mt-3 text-xs leading-5 text-gray-500">
                        Average gap score among active institutions with completed analytics.
                    </p>

                    @if ($overallGap !== null && $overallGap >= 50)
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <a href="#institution-list" 
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-600 hover:text-red-700 transition">
                                <span>View Critical Institutions</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    @endif
                </section>

                {{-- Critical Institutions --}}
                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Critical Institutions
                            </p>

                            <p class="mt-3 text-4xl font-bold text-red-600">
                                {{ number_format((int) ($criticalAreas ?? 0)) }}
                            </p>
                        </div>

                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                        <span>
                            <strong class="font-semibold text-amber-600">
                                {{ number_format((int) ($needsAttention ?? 0)) }}
                            </strong>
                            warning
                        </span>

                        <span>
                            <strong class="font-semibold text-green-600">
                                {{ number_format((int) ($adequateAreas ?? 0)) }}
                            </strong>
                            adequate
                        </span>
                    </div>

                    <p class="mt-3 text-xs leading-5 text-gray-500">
                        Critical sites should be reviewed first in city-wide ICT planning.
                    </p>

                    @if (($criticalAreas ?? 0) > 0)
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <a href="{{ url()->current() }}?risk=critical" 
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-600 hover:text-red-700 transition">
                                <span>Filter Critical Sites</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    @endif
                </section>

                {{-- Coverage --}}
                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Analysis Coverage
                            </p>

                            <div class="mt-3 flex items-end gap-2">
                                <span class="text-4xl font-bold text-gray-900">
                                    {{ number_format((float) ($analysisCoveragePercentage ?? 0), 1) }}
                                </span>
                                <span class="pb-1 text-lg font-semibold text-gray-400">%</span>
                            </div>
                        </div>

                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <i class="fa-solid fa-database"></i>
                        </span>
                    </div>

                    <div class="mt-4">
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-blue-500 transition-all duration-500"
                                style="width: {{ min(100, (float) ($analysisCoveragePercentage ?? 0)) }}%"></div>
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-gray-600">
                        <strong class="font-semibold text-gray-900">
                            {{ number_format((int) ($analyzedInstitutionCount ?? 0)) }}
                        </strong>
                        of
                        <strong class="font-semibold text-gray-900">
                            {{ number_format((int) ($institutionCount ?? 0)) }}
                        </strong>
                        active institutions analyzed
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        Average data completeness:
                        <strong class="font-semibold text-gray-700">
                            {{ is_numeric($averageCompleteness ?? null)
                                ? number_format((float) $averageCompleteness, 1) . '%'
                                : '—' }}
                        </strong>

                        @if (($insufficientData ?? 0) > 0)
                            · {{ number_format((int) $insufficientData) }} insufficient-data
                        @endif
                    </p>
                </section>
            </div>

            {{-- 2 + 3. CAUSE AND LOCATION --}}
            <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                {{-- Gap Drivers --}}
                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-red-600">
                                What is driving the gap?
                            </p>

                            <h2 class="mt-1 text-base font-semibold text-gray-900">
                                City-wide Gap Drivers
                            </h2>
                        </div>

                        @if ($primaryCityConcern)
                            <div class="max-w-52 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-right">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-red-500">
                                    Primary Concern
                                </p>

                                <p class="mt-0.5 text-xs font-semibold text-red-800">
                                    {{ $primaryCityConcern['label'] }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-4">
                        @foreach ($gapDrivers as $driver)
                            @php
                                $driverValue = is_numeric($driver['value'])
                                    ? min(100, max(0, (float) $driver['value']))
                                    : null;
                            @endphp

                            <div class="group cursor-help">
                                <div class="mb-2 flex items-center justify-between gap-4">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 transition group-hover:bg-red-50 group-hover:text-red-600">
                                            <i class="fa-solid {{ $driver['icon'] }}"></i>
                                        </span>

                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-800 transition group-hover:text-red-700">
                                                {{ $driver['label'] }}
                                            </p>

                                            <p class="truncate text-xs text-gray-500" title="{{ $driver['description'] }}">
                                                {{ $driver['description'] }}
                                            </p>
                                        </div>
                                    </div>

                                    <span class="shrink-0 text-sm font-bold {{ $scoreTextClass($driverValue) }}">
                                        {{ $driverValue !== null ? number_format($driverValue, 1) . '%' : '—' }}
                                    </span>
                                </div>

                                <div class="h-2 overflow-hidden rounded-full bg-gray-100 transition group-hover:shadow-inner">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $scoreBarClass($driverValue) }}"
                                        style="width: {{ $driverValue ?? 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($primaryCityConcern)
                        <div class="mt-5 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                            <p class="text-xs leading-5 text-gray-600">
                                <strong class="font-semibold text-gray-900">
                                    Planning focus:
                                </strong>
                                {{ $primaryCityConcern['label'] }} currently has the highest average deficit at
                                <strong class="font-semibold text-gray-900">
                                    {{ number_format((float) $primaryCityConcern['score'], 1) }}%
                                </strong>
                                among the three research dimensions.
                            </p>
                        </div>
                    @endif
                </section>

                {{-- Priority Barangays --}}
                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-red-600">
                                Where should the city look first?
                            </p>

                            <h2 class="mt-1 text-base font-semibold text-gray-900">
                                Priority Barangays
                            </h2>
                        </div>

                        <span class="rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                            Top 5
                        </span>
                    </div>

                    @if ($topBarangays->isEmpty())
                        <div class="flex min-h-56 flex-col items-center justify-center text-center">
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                <i class="fa-solid fa-map-location-dot text-lg"></i>
                            </span>

                            <p class="mt-3 text-sm font-semibold text-gray-700">
                                No barangay ranking available
                            </p>

                            <p class="mt-1 max-w-sm text-xs leading-5 text-gray-500">
                                Barangay priorities will appear after institutions have completed gap assessments.
                            </p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($topBarangays as $index => $barangay)
                                @php
                                    $barangayGap = min(
                                        100,
                                        max(
                                            0,
                                            (float) ($barangay['gap_score'] ?? 0)
                                        )
                                    );

                                    $barangayRisk = strtolower(
                                        (string) ($barangay['risk_level'] ?? 'adequate')
                                    );

                                    $barangayBadge = match ($barangayRisk) {
                                        'critical' => 'bg-red-50 text-red-700',
                                        'warning' => 'bg-amber-50 text-amber-700',
                                        default => 'bg-green-50 text-green-700',
                                    };

                                    $barangayId = $barangay['id'] ?? null;
                                @endphp

                                <a href="{{ $barangayId ? url()->current() . '?barangay=' . $barangayId : '#' }}" 
                                    class="group flex items-center gap-3 rounded-lg border border-gray-200 px-3 py-3 transition hover:border-red-300 hover:bg-red-50/30 hover:shadow-sm">
                                    <span
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600 transition group-hover:bg-red-100 group-hover:text-red-700">
                                        {{ $index + 1 }}
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-gray-800 transition group-hover:text-red-700">
                                                    {{ $barangay['name'] ?? 'Unknown Barangay' }}
                                                </p>

                                                <p class="mt-0.5 text-xs text-gray-500">
                                                    {{ number_format((int) ($barangay['institution_count'] ?? 0)) }}
                                                    {{ Str::plural('institution', (int) ($barangay['institution_count'] ?? 0)) }}
                                                    @if(isset($barangay['analyzed_count']))
                                                        · {{ number_format((int) $barangay['analyzed_count']) }} analyzed
                                                    @endif
                                                </p>
                                            </div>

                                            <div class="flex shrink-0 items-center gap-2">
                                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $barangayBadge }}">
                                                    {{ ucfirst($barangayRisk) }}
                                                </span>

                                                <span class="w-12 text-right text-sm font-bold text-gray-900">
                                                    {{ number_format($barangayGap, 1) }}%
                                                </span>
                                            </div>
                                        </div>

                                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-100">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $scoreBarClass($barangayGap) }}"
                                                style="width: {{ $barangayGap }}%"></div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        @if ($topBarangays->count() >= 5)
                            <div class="mt-4 text-center">
                                <a href="{{ route('map') }}" 
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-600 hover:text-red-700 transition">
                                    <span>View All Barangays on Map</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                            </div>
                        @endif
                    @endif
                </section>
            </div>

            {{-- 4. INSTITUTION ACTION LIST --}}
            <section id="institution-list" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 p-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-red-600">
                                Which institutions need action?
                            </p>

                            <h2 class="mt-1 text-base font-semibold text-gray-900">
                                Institution Priority List
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Highest gap scores first, with the main concern and recommended first action.
                            </p>
                        </div>

                        <form method="GET" action="{{ url()->current() }}"
                            class="w-full xl:w-auto">
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-5">
                                <div class="relative lg:col-span-1">
                                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"></i>

                                    <input type="search" name="q" value="{{ request('q') }}"
                                        placeholder="Search institution"
                                        class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-700 transition focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                </div>

                                <select name="barangay"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 transition focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                    <option value="">All Barangays</option>

                                    @foreach ($barangays ?? [] as $barangay)
                                        <option value="{{ $barangay->id }}"
                                            @selected((string) request('barangay') === (string) $barangay->id)>
                                            {{ $barangay->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <select name="type"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 transition focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                    <option value="">All Types</option>
                                    <option value="School" @selected(request('type') === 'School')>School</option>
                                    <option value="Barangay Hall" @selected(request('type') === 'Barangay Hall')>Barangay Hall</option>
                                    <option value="Local Government Office" @selected(request('type') === 'Local Government Office')>
                                        Local Government Office
                                    </option>
                                </select>

                                <select name="risk"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 transition focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                    <option value="">All Risk Levels</option>
                                    <option value="critical" @selected(request('risk') === 'critical')>Critical</option>
                                    <option value="warning" @selected(request('risk') === 'warning')>Warning</option>
                                    <option value="adequate" @selected(request('risk') === 'adequate')>Adequate</option>
                                    <option value="insufficient_data" @selected(request('risk') === 'insufficient_data')>
                                        Insufficient Data
                                    </option>
                                </select>

                                <div class="flex gap-2">
                                    <button type="submit"
                                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                                        <i class="fa-solid fa-filter text-xs"></i>
                                        Apply
                                    </button>

                                    @if ($hasFilters)
                                        <a href="{{ url()->current() }}"
                                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
                                            title="Clear all filters">
                                            <i class="fa-solid fa-xmark"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>

                            @if ($hasFilters)
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-medium text-gray-500">Active filters:</span>
                                    
                                    @if(request('q'))
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            Search: "{{ request('q') }}"
                                            <a href="{{ url()->current() }}?{{ http_build_query(array_filter(request()->except('q'))) }}" 
                                                class="text-gray-400 hover:text-gray-600">
                                                <i class="fa-solid fa-xmark text-[10px]"></i>
                                            </a>
                                        </span>
                                    @endif

                                    @if(request('barangay'))
                                        @php
                                            $selectedBarangay = collect($barangays ?? [])->firstWhere('id', request('barangay'));
                                        @endphp
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            Barangay: {{ $selectedBarangay->name ?? 'Unknown' }}
                                            <a href="{{ url()->current() }}?{{ http_build_query(array_filter(request()->except('barangay'))) }}" 
                                                class="text-gray-400 hover:text-gray-600">
                                                <i class="fa-solid fa-xmark text-[10px]"></i>
                                            </a>
                                        </span>
                                    @endif

                                    @if(request('type'))
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            Type: {{ request('type') }}
                                            <a href="{{ url()->current() }}?{{ http_build_query(array_filter(request()->except('type'))) }}" 
                                                class="text-gray-400 hover:text-gray-600">
                                                <i class="fa-solid fa-xmark text-[10px]"></i>
                                            </a>
                                        </span>
                                    @endif

                                    @if(request('risk'))
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            Risk: {{ ucfirst(str_replace('_', ' ', request('risk'))) }}
                                            <a href="{{ url()->current() }}?{{ http_build_query(array_filter(request()->except('risk'))) }}" 
                                                class="text-gray-400 hover:text-gray-600">
                                                <i class="fa-solid fa-xmark text-[10px]"></i>
                                            </a>
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </form>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="group px-5 py-3 text-left">
                                    <a href="{{ url()->current() }}?{{ http_build_query(array_merge(request()->except('sort', 'order'), ['sort' => 'name', 'order' => request('sort') === 'name' && request('order') === 'asc' ? 'desc' : 'asc'])) }}"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500 transition hover:text-gray-700">
                                        Institution
                                        @if(request('sort') === 'name')
                                            <i class="fa-solid fa-sort-{{ request('order') === 'desc' ? 'down' : 'up' }} text-[10px]"></i>
                                        @else
                                            <i class="fa-solid fa-sort text-[10px] opacity-0 group-hover:opacity-50"></i>
                                        @endif
                                    </a>
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Barangay
                                </th>

                                <th class="group px-4 py-3 text-left">
                                    <a href="{{ url()->current() }}?{{ http_build_query(array_merge(request()->except('sort', 'order'), ['sort' => 'gap_score', 'order' => request('sort') === 'gap_score' && request('order') === 'desc' ? 'asc' : 'desc'])) }}"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500 transition hover:text-gray-700">
                                        Gap & Risk
                                        @if(request('sort') === 'gap_score' || !request('sort'))
                                            <i class="fa-solid fa-sort-{{ request('order') === 'asc' ? 'up' : 'down' }} text-[10px]"></i>
                                        @else
                                            <i class="fa-solid fa-sort text-[10px] opacity-0 group-hover:opacity-50"></i>
                                        @endif
                                    </a>
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Main Concern
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Recommended First Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($analytics ?? [] as $analytic)
                                @php
                                    $institution = $analytic->institution;

                                    $gapScore = is_numeric($analytic->gap_score)
                                        ? (float) $analytic->gap_score
                                        : null;

                                    $riskLevel = strtolower(
                                        (string) (
                                            $analytic->risk_level
                                            ?? (
                                                $gapScore === null
                                                    ? 'insufficient_data'
                                                    : ($gapScore >= 80
                                                        ? 'critical'
                                                        : ($gapScore >= 50
                                                            ? 'warning'
                                                            : 'adequate'))
                                            )
                                        )
                                    );

                                    $riskLabel = match ($riskLevel) {
                                        'critical' => 'Critical',
                                        'warning' => 'Warning',
                                        'adequate' => 'Adequate',
                                        default => 'Insufficient Data',
                                    };

                                    $riskBadge = match ($riskLevel) {
                                        'critical' => 'bg-red-100 text-red-700',
                                        'warning' => 'bg-amber-100 text-amber-700',
                                        'adequate' => 'bg-green-100 text-green-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };

                                    $recommendationDetails = $analytic->recommendations_detail;

                                    if (is_string($recommendationDetails)) {
                                        $recommendationDetails = json_decode(
                                            $recommendationDetails,
                                            true
                                        );
                                    }

                                    if (!is_array($recommendationDetails)) {
                                        $recommendationDetails = [];
                                    }

                                    $rowPriorities = $recommendationDetails['priorities'] ?? [];

                                    if (!is_array($rowPriorities)) {
                                        $rowPriorities = [];
                                    }

                                    $firstPriority = $rowPriorities[0] ?? null;

                                    $firstAction = is_array($firstPriority)
                                        ? ($firstPriority['priority'] ?? null)
                                        : (is_string($firstPriority) ? $firstPriority : null);

                                    $firstAction = $firstAction
                                        ?: $analytic->policy_recommendation;

                                    $urgency = is_array($firstPriority)
                                        ? ($firstPriority['urgency'] ?? null)
                                        : null;

                                    if (!$urgency && $gapScore !== null) {
                                        $urgency = match (true) {
                                            $gapScore >= 80 => 'immediate',
                                            $gapScore >= 50 => 'short_term',
                                            default => 'medium_term',
                                        };
                                    }

                                    $urgencyLabel = $urgency
                                        ? str_replace('_', ' ', $urgency)
                                        : null;

                                    $urgencyClasses = match ($urgency) {
                                        'immediate' => 'border-red-200 bg-red-50 text-red-700',
                                        'short_term' => 'border-orange-200 bg-orange-50 text-orange-700',
                                        'medium_term' => 'border-blue-200 bg-blue-50 text-blue-700',
                                        default => 'border-gray-200 bg-gray-50 text-gray-600',
                                    };

                                    $rowDimensions = collect([
                                        [
                                            'label' => 'Infrastructure',
                                            'score' => $analytic->infrastructure_deficit_score,
                                        ],
                                        [
                                            'label' => 'Connectivity',
                                            'score' => $analytic->connectivity_divide_score,
                                        ],
                                        [
                                            'label' => 'Obsolescence',
                                            'score' => $analytic->technological_obsolescence_score,
                                        ],
                                    ])
                                        ->filter(fn ($item) => is_numeric($item['score']))
                                        ->sortByDesc(fn ($item) => (float) $item['score'])
                                        ->values();

                                    $mainConcern = $rowDimensions->first();
                                @endphp

                                <tr class="align-top transition hover:bg-gray-50">
                                    <td class="px-5 py-4">
                                        @if ($institution)
                                            <a href="{{ route('institutions.show', $institution) }}"
                                                class="group block">
                                                <p
                                                    class="max-w-64 text-sm font-semibold text-gray-900 transition group-hover:text-red-700">
                                                    {{ $institution->name }}
                                                </p>

                                                <p class="mt-1 text-xs text-gray-500">
                                                    {{ $institution->type ?? 'Type not specified' }}
                                                </p>
                                            </a>
                                        @else
                                            <span class="text-sm text-gray-400">
                                                Institution unavailable
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4">
                                        @if($institution?->barangay)
                                            <a href="{{ url()->current() }}?barangay={{ $institution->barangay->id }}"
                                                class="text-sm font-medium text-gray-600 transition hover:text-red-600 hover:underline"
                                                title="Filter by {{ $institution->barangay->name }}">
                                                {{ $institution->barangay->name }}
                                            </a>
                                        @else
                                            <span class="text-sm text-gray-400">Not assigned</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4">
                                        <div class="flex flex-col items-start gap-1.5">
                                            <span class="text-sm font-bold {{ $scoreTextClass($gapScore) }}">
                                                {{ $gapScore !== null ? number_format($gapScore, 1) . '%' : '—' }}
                                            </span>

                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $riskBadge }}">
                                                {{ $riskLabel }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4">
                                        @if ($mainConcern)
                                            <p class="text-sm font-semibold text-gray-800">
                                                {{ $mainConcern['label'] }}
                                            </p>

                                            <p class="mt-1 text-xs text-gray-500">
                                                {{ number_format((float) $mainConcern['score'], 1) }}% deficit
                                            </p>
                                        @else
                                            <span class="text-sm text-gray-400">
                                                Not available
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4">
                                        @if ($firstAction)
                                            <div class="max-w-xl">
                                                <p class="text-sm font-semibold leading-relaxed {{ match($urgency) {
                                                    'immediate' => 'text-red-700',
                                                    'short_term' => 'text-orange-700',
                                                    'medium_term' => 'text-blue-700',
                                                    default => 'text-gray-800',
                                                } }}" title="{{ $firstAction }}">
                                                    {{ Str::limit($firstAction, 150) }}
                                                </p>
                                            </div>
                                        @else
                                            <span class="text-sm italic text-gray-400">
                                                No recommendation available.
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-14 text-center">
                                        <span
                                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                            <i class="fa-solid fa-chart-column"></i>
                                        </span>

                                        <p class="mt-3 text-sm font-semibold text-gray-700">
                                            {{ $hasFilters ? 'No institutions match the selected filters.' : 'No analytics records available.' }}
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ $hasFilters
                                                ? 'Clear or change the filters to review other institutions.'
                                                : 'Run institution analytics to generate decision-support results.' }}
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($analytics, 'links'))
                    <div class="border-t border-gray-200">
                        <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm text-gray-600">
                                Showing 
                                <span class="font-semibold text-gray-900">{{ $analytics->firstItem() ?? 0 }}</span>
                                to 
                                <span class="font-semibold text-gray-900">{{ $analytics->lastItem() ?? 0 }}</span>
                                of 
                                <span class="font-semibold text-gray-900">{{ $analytics->total() }}</span>
                                institutions
                            </div>
                            
                            {{ $analytics->links() }}
                        </div>
                    </div>
                @endif
            </section>

            @if (($pendingAssessments ?? 0) > 0)
                <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-circle-info mt-0.5 text-blue-600"></i>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-blue-900">
                                Pending Assessments
                            </p>
                            <p class="mt-1 text-sm text-blue-700">
                                {{ number_format((int) $pendingAssessments) }}
                                active
                                {{ Str::plural('institution', (int) $pendingAssessments) }}
                                still
                                {{ (int) $pendingAssessments === 1 ? 'requires' : 'require' }}
                                a completed gap assessment to appear in this list.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
