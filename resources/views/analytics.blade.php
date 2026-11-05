@extends('layouts.app')

@section('page-title', 'ICT Gap Analysis')
@section('page-icon', 'fa-chart-line')

@section('page-status')
    <span class="analysis-status {{ is_numeric($averageGapScore) ? 'is-complete' : '' }}">
        <span aria-hidden="true"></span>
        {{ is_numeric($averageGapScore) ? 'Calculated analysis available for selection' : 'No complete analysis for selection' }}
    </span>
@endsection

@section('content')
    @php
        $overallGap = is_numeric($averageGapScore ?? null)
            ? (float) $averageGapScore
            : null;

        $gapDrivers = [
            [
                'label' => 'Infrastructure Deficit',
                'value' => $infrastructureDeficit,
                'icon' => 'fa-computer',
                'description' => 'Percentage of active applicable ICT units recorded as defective.',
                'formula' => '(Defective Active Applicable Units / Active Applicable Units) x 100',
            ],
            [
                'label' => 'Connectivity Divide',
                'value' => $connectivityDivide,
                'icon' => 'fa-wifi',
                'description' => 'Mean advertised-download shortfall, available only when every reported connection has usable data.',
                'formula' => 'Mean of [max(Advertised Download Mbps - Measured Download Mbps, 0) / Advertised Download Mbps] x 100 across all reported connections',
            ],
            [
                'label' => 'Technological Obsolescence Proxy',
                'value' => $technologicalObsolescence,
                'icon' => 'fa-clock-rotate-left',
                'description' => 'Retired applicable ICT units as a share of total applicable inventory.',
                'formula' => '(Retired Applicable Units / Total Applicable Units) x 100',
            ],
        ];

        $scoreBarClass = fn ($score): string =>
            is_numeric($score) ? 'bg-red-500' : 'bg-gray-300';

        $scoreTextClass = fn ($score): string =>
            is_numeric($score) ? 'text-gray-900' : 'text-gray-500';

        $topBarangays = collect($barangayScores ?? [])
            ->take(5);

        $hasFilters =
            request()->filled('q')
            || request()->filled('barangay')
            || request()->filled('type')
            || request()->filled('risk');
    @endphp

    <div class="analytics-dashboard min-h-full pb-6">
        <div class="dashboard-content">

            <form method="GET" action="{{ route('analytics') }}" class="reporting-selection dashboard-card">
                <div class="reporting-heading"><h2>Reporting Period and Selection</h2><div class="reporting-exports">@if(auth()->user()->isAdmin() || auth()->user()->isViewer())
<a href="{{ route('analytics.report', request()->except('page')) }}" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-file-pdf"></i> LGU PDF Report</a>
<a href="{{ route('analytics.csv', request()->except('page')) }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                        <i class="fa-solid fa-file-csv"></i>
                        Export CSV
                    </a>
@endif</div></div>
                <div class="reporting-fields">
                    <label class="text-xs text-gray-600">From<input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 block rounded-lg border border-gray-300 p-2 text-sm"></label>
                    <label class="text-xs text-gray-600">Through<input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 block rounded-lg border border-gray-300 p-2 text-sm"></label>
                        <label class="text-xs text-gray-600">Barangay<select aria-label="Filter by barangay" name="barangay" class="mt-1 block rounded-lg border border-gray-300 p-2 text-sm"><option value="">All barangays</option>@foreach($barangays as $barangay)<option value="{{ $barangay->id }}" @selected((string)request('barangay') === (string)$barangay->id)>{{ $barangay->name }}</option>@endforeach</select></label>
                        <label class="text-xs text-gray-600">Institution Type<select aria-label="Filter by institution type" name="type" class="mt-1 block rounded-lg border border-gray-300 p-2 text-sm"><option value="">All types</option>@foreach(['School', 'Barangay Hall'] as $type)<option @selected(request('type') === $type)>{{ $type }}</option>@endforeach</select></label>
                        @foreach(['q', 'risk'] as $filter)<input type="hidden" name="{{ $filter }}" value="{{ request($filter) }}">@endforeach
                    <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Apply Selection</button>
                    <a href="{{ route('analytics') }}" class="px-2 py-2 text-sm text-gray-600">Reset</a>
                </div>
                @error('date_from')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                @error('date_to')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </form>

            {{-- Finalized city-wide calculation metrics --}}
            <div class="dashboard-summary-grid mb-4">
                @php
                    $cityMetrics = [
                        [
                            'label' => 'Overall ICT Gap',
                            'value' => $overallGap,
                            'icon' => 'fa-chart-line',
                            'formula' => '(Infrastructure Deficit + Connectivity Divide + Technological Obsolescence Proxy) / 3',
                            'description' => 'Mean of the three finalized core gap dimensions across institutions with complete core assessments.',
                        ],
                        [
                            'label' => 'Infrastructure Deficit',
                            'value' => $infrastructureDeficit,
                            'icon' => 'fa-building-circle-exclamation',
                            'formula' => '(Defective Active Applicable Units / Active Applicable Units) x 100',
                            'description' => 'Average infrastructure-condition gap from defective active applicable ICT units.',
                        ],
                        [
                            'label' => 'Connectivity Divide',
                            'value' => $connectivityDivide,
                            'icon' => 'fa-wifi',
                            'formula' => 'Mean of [max(Advertised Download Mbps - Measured Download Mbps, 0) / Advertised Download Mbps] x 100 across all reported connections',
                            'description' => 'Average measured Internet-performance shortfall relative to recorded allocation.',
                        ],
                        [
                            'label' => 'Technological Obsolescence Proxy',
                            'value' => $technologicalObsolescence,
                            'icon' => 'fa-clock-rotate-left',
                            'formula' => '(Retired Applicable Units / Total Applicable Units) x 100',
                            'description' => 'Average retirement-based obsolescence proxy for applicable ICT inventory.',
                        ],
                    ];
                @endphp

                @foreach ($cityMetrics as $metric)
                    <x-dashboard-metric :label="$metric['label']" :icon="$metric['icon']" :value="$metric['value']" :formula="$metric['formula']" :description="$metric['description']" />
                @endforeach
            </div>

            {{-- 2 + 3. CAUSE AND LOCATION --}}
            <section class="planning-banner" aria-label="Overall assessment and priority actions">
                <div class="planning-banner-icon" aria-hidden="true"><i class="fa-solid fa-lightbulb"></i></div>
                <div class="planning-banner-body">
                    <h2>AI Prescription &amp; Planning Priorities</h2>
                    <p class="mt-2 text-sm">{{ $prescription['summary'] }}</p>
                    <p class="mt-2 text-sm">{{ $prescription['next_step'] }}</p>
                    @if(count($prescription['actions']))
                        <h3 class="mt-3 text-sm font-semibold text-white">Priority actions for the selected results</h3>
                        <ol class="planning-preview text-sm" style="list-style-type: decimal;">
                            @foreach(collect($prescription['actions'])->take(3) as $action)
                                <li>{{ $action['action'] }}<small>{{ $action['institution'] }} &middot; {{ $action['source'] }} &middot; {{ str_replace('_', ' ', $action['urgency']) }}</small></li>
                            @endforeach
                        </ol>
                    @else
                        <p class="mt-2 text-xs">No stored priority actions are available for this selection. Complete and validate the records, then run institution analytics.</p>
                    @endif
                    <p class="mt-2 text-xs">Overall assessment uses the selected calculated results. Priority actions come from stored institution recommendations, ordered by overall ICT gap; source labels identify AI recommendations and fallback guidance.</p>
                </div>
            </section>
            <div class="analytics-chart-grid">
                {{-- Gap Drivers --}}
                <section class="dashboard-card p-4">
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <div>

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
                                            <div class="flex items-center gap-1.5">
                                                <p class="text-sm font-semibold text-gray-800 transition group-hover:text-red-700">
                                                    {{ $driver['label'] }}
                                                </p>
                                                <x-metric-info
                                                    :title="$driver['label']"
                                                    :formula="$driver['formula']"
                                                    :description="$driver['description']" />
                                            </div>

                                            <p class="text-xs text-gray-500" title="{{ $driver['description'] }}">
                                                {{ $driver['description'] }}
                                            </p>
                                        </div>
                                    </div>

                                    <span class="shrink-0 text-sm font-bold {{ $scoreTextClass($driverValue) }}">
                                        {{ $driverValue !== null ? number_format($driverValue, 1) . '%' : 'No data' }}
                                    </span>
                                </div>

                                <div class="h-2 overflow-hidden rounded-full bg-gray-100 transition group-hover:shadow-inner">
                                    <div class="h-full rounded-full transition-all duration-500 driver-color-{{ $loop->index }}"
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
                <section class="dashboard-card p-4">
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <div>

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
                                No barangay gap summary available
                            </p>

                            <p class="mt-1 max-w-sm text-xs leading-5 text-gray-500">
                                Barangay summaries will appear after institutions have complete ICT gap assessments.
                            </p>
                        </div>
                    @else
                        <div class="barangay-chart" role="img" aria-label="Highest calculated barangay mean ICT gap scores">
                            @foreach ($topBarangays as $index => $barangay)
                                <a class="barangay-chart-column" href="{{ route('analytics', array_merge(request()->except('page'), ['barangay' => $barangay['id']])) }}"
                                    title="{{ $barangay['name'] }}: {{ number_format($barangay['gap_score'], 1) }}% mean ICT gap; {{ $barangay['institution_count'] }} analyzed institutions">
                                    <div class="barangay-chart-track"><strong style="bottom: calc({{ min(100, max(0, $barangay['gap_score'])) }}% + 0.375rem)">{{ number_format($barangay['gap_score'], 1) }}%</strong><span class="chart-color-{{ $index }}" style="height: {{ min(100, max(0, $barangay['gap_score'])) }}%"></span></div>
                                    <span>{{ $barangay['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Mean of available institution scores in this selection; colors distinguish barangays and do not indicate severity.</p>

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
            @unless(auth()->user()->isViewer())
            <section id="institution-list" class="dashboard-card institution-priority-table overflow-hidden">
                <div class="border-b border-gray-200 bg-gray-50 p-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div>

                            <h2 class="mt-1 text-base font-semibold text-gray-900">
                                Institution Priority List
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Highest gap scores first, with the main concern and recommended first action.
                            </p>
                        </div>

                        <form method="GET" action="{{ url()->current() }}"
                            class="w-full xl:w-auto">
                            <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                            <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-5">
                                <div class="relative lg:col-span-1">
                                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"></i>

                                    <input type="search" aria-label="Search institution" name="q" value="{{ request('q') }}"
                                        placeholder="Search institution"
                                        class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-700 transition focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                </div>

                                <select aria-label="Filter by barangay" name="barangay"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 transition focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                    <option value="">All Barangays</option>

                                    @foreach ($barangays ?? [] as $barangay)
                                        <option value="{{ $barangay->id }}"
                                            @selected((string) request('barangay') === (string) $barangay->id)>
                                            {{ $barangay->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <select aria-label="Filter by institution type" name="type"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 transition focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                    <option value="">All Types</option>
                                    <option value="School" @selected(request('type') === 'School')>School</option>
                                    <option value="Barangay Hall" @selected(request('type') === 'Barangay Hall')>Barangay Hall</option>
                                </select>

                                <select aria-label="Filter by assessment status" name="risk"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 transition focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                    <option value="">All Assessment Statuses</option>
                                    <option value="assessed" @selected(request('risk') === 'assessed')>Assessed</option>
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
                                            Status: {{ ucfirst(str_replace('_', ' ', request('risk'))) }}
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

                <div class="analytics-table-scroll overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 py-3 text-left text-xs text-gray-500">#</th>
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
                                        Overall ICT Gap
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
                                        (string) ($analytic->risk_level ?? 'insufficient_data')
                                    );

                                    $riskLabel = $analytic->processing_status === 'failed' ? 'Calculation Failed' : ($riskLevel === 'assessed'
                                        ? 'Assessed'
                                        : 'Insufficient Data');

                                    $riskBadge = $riskLevel === 'assessed'
                                        ? 'bg-blue-100 text-blue-700'
                                        : 'bg-gray-100 text-gray-700';

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
                                            'label' => 'Infrastructure Deficit',
                                            'score' => $analytic->infrastructure_deficit_score,
                                            'formula' => '(Defective Active Applicable Units / Active Applicable Units) x 100',
                                            'description' => 'Percentage of active applicable ICT units recorded as defective.',
                                        ],
                                        [
                                            'label' => 'Connectivity Divide',
                                            'score' => $analytic->connectivity_divide_score,
                                            'formula' => 'Mean of [max(Advertised Download Mbps - Measured Download Mbps, 0) / Advertised Download Mbps] x 100 across all reported connections',
                                            'description' => 'Relative bandwidth shortfall between recorded allocation and measured download speed.',
                                        ],
                                        [
                                            'label' => 'Technological Obsolescence Proxy',
                                            'score' => $analytic->technological_obsolescence_score,
                                            'formula' => '(Retired Applicable Units / Total Applicable Units) x 100',
                                            'description' => 'Retirement-based proxy for applicable ICT inventory.',
                                        ],
                                    ])
                                        ->filter(fn ($item) => is_numeric($item['score']))
                                        ->sortByDesc(fn ($item) => (float) $item['score'])
                                        ->values();

                                    $mainConcern = $rowDimensions->first();
                                @endphp

                                <tr class="align-top transition hover:bg-gray-50">
                                    <td class="px-3 py-4 text-xs text-gray-500">{{ ($analytics->firstItem() ?? 1) + $loop->index }}</td>
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
                                            @include('ict-inventory._connectivity-assessment', ['assessment' => $recommendationDetails['connectivity_assessment'] ?? null, 'compact' => true])
                                            @if ($institution)
                                                <a href="{{ route('institutions.show', ['institution' => $institution->id, 'tab' => 'connectivity']) }}#institutionInventory" class="text-xs font-semibold text-red-700 hover:underline">Internet Connectivity</a>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-4 py-4">
                                        @if ($mainConcern)
                                            <div class="flex items-center gap-1.5">
                                                <p class="text-sm font-semibold text-gray-800">
                                                    {{ $mainConcern['label'] }}
                                                </p>
                                                <x-metric-info
                                                    :title="$mainConcern['label']"
                                                    :formula="$mainConcern['formula']"
                                                    :description="$mainConcern['description']" />
                                            </div>

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
                                    <td colspan="6" class="px-5 py-14 text-center">
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
            @endunless

        </div>
    </div>
@endsection
