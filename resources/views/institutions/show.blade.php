@extends('layouts.app')

@section('page-title', 'Institution Details')
@section('page-icon', 'fa-building')

@section('content')
    @php
        $analytics = $institution->institutionAnalytics;

        $analyticsProcessing = in_array(
            $analytics?->processing_status,
            ['queued', 'processing'],
            true
        );

        $analyticsProcessingToken =
            $analyticsProcessing
                ? $analytics?->processing_token
                : null;

        $analyticsFailed =
            $analytics?->processing_status === 'failed';

        $recommendationDetails = $analytics?->recommendations_detail;

        if (is_string($recommendationDetails)) {
            $recommendationDetails = json_decode($recommendationDetails, true);
        }

        if (!is_array($recommendationDetails)) {
            $recommendationDetails = [];
        }

        $providerKey = strtolower((string) ($recommendationDetails['provider'] ?? 'unknown'));

        $aiStatus = strtolower((string) (
            $recommendationDetails['ai_status']
            ?? ($providerKey === 'local' ? 'fallback' : 'unknown')
        ));

        $providerLabel = match ($providerKey) {
            'gemini' => 'Gemini',
            'groq' => 'Groq',
            'local' => 'Local',
            default => 'Not available',
        };

        $providerClasses = match ($providerKey) {
            'gemini' => 'border-red-200 bg-red-50 text-red-700',
            'groq' => 'border-orange-200 bg-orange-50 text-orange-700',
            'local' => 'border-gray-200 bg-gray-50 text-gray-600',
            default => 'border-gray-200 bg-white text-gray-500',
        };

        $providerIcon = match ($providerKey) {
            'gemini' => 'fa-wand-magic-sparkles',
            'groq' => 'fa-bolt',
            'local' => 'fa-microchip',
            default => 'fa-circle-question',
        };

        $riskKey = strtolower((string) ($analytics?->risk_level ?? 'insufficient_data'));
        $riskLabel = $riskKey === 'assessed'
            ? 'Assessed'
            : 'Insufficient Data';

        $riskClasses = $riskKey === 'assessed'
            ? 'border-blue-200 bg-blue-50 text-blue-700'
            : 'border-gray-200 bg-gray-50 text-gray-600';

        $gapScore = $analytics?->gap_score;
        $gapProgress = $gapScore !== null
            ? min(100, max(0, (float) $gapScore))
            : 0;

        $scoreSource = $recommendationDetails['score_source'] ?? null;
        $scoreSourceLabel = match ($scoreSource) {
            'python_service' => 'Python analytics',
            'local_deterministic_fallback' => 'Local score fallback',
            default => 'Not available',
        };

        $priorities = $recommendationDetails['priorities'] ?? [];

        if (!is_array($priorities)) {
            $priorities = [];
        }

        $analyticsModel = $recommendationDetails['model'] ?? null;

        $deficitDimensions = collect([
            [
                'label' => 'Infrastructure Deficit',
                'score' => $analytics?->infrastructure_deficit_score,
                'icon' => 'fa-building-circle-exclamation',
            ],
            [
                'label' => 'Connectivity Divide',
                'score' => $analytics?->connectivity_divide_score,
                'icon' => 'fa-wifi',
            ],
            [
                'label' => 'Technological Obsolescence Proxy',
                'score' => $analytics?->technological_obsolescence_score,
                'icon' => 'fa-clock-rotate-left',
            ],
        ])
            ->filter(fn ($item) => is_numeric($item['score']))
            ->sortByDesc(fn ($item) => (float) $item['score'])
            ->values();

        $primaryConcern = $deficitDimensions->first();
        $decisionSupportVersion = $recommendationDetails['decision_support_version'] ?? null;

        $inventoryItems = $institution->ictInventory
            ->whereIn('component_type', ['hardware', 'software', 'network'])->values();

        $inventoryTotal = (int) $inventoryItems->sum('total_count');

        $inventoryActive = \App\Services\InventoryCounts::sum($inventoryItems, 'active_count');

        $inventoryRetired = \App\Services\InventoryCounts::sum($inventoryItems, 'retired_count');

        $inventoryFunctional = $inventoryItems->isNotEmpty() && $inventoryItems->every(fn ($item) => $item->functional_count !== null)
            ? (int) $inventoryItems->sum('functional_count') : null;

        $inventoryAttention = \App\Services\InventoryCounts::sum($inventoryItems, 'defective_count');

        $inventoryConfig = config('ict_inventory', []);

        $inventoryCategoryIcons = [
            'computer' => 'fa-computer',
            'desktop_computer' => 'fa-desktop',
            'laptop' => 'fa-laptop',
            'tablet' => 'fa-tablet-screen-button',
            'printer' => 'fa-print',
            'router' => 'fa-wifi',
            'network_switch' => 'fa-network-wired',
            'access_point' => 'fa-signal',
            'smart_tv' => 'fa-tv',
            'projector' => 'fa-video',
            'projected_whiteboard' => 'fa-chalkboard',
            'server' => 'fa-server',
            'ups' => 'fa-battery-full',
            'other' => 'fa-box',
        ];

        $inventoryCategoryDetails = collect(
            $inventoryConfig['categories'] ?? []
        )
            ->mapWithKeys(
                fn ($category, $key) => [
                    $key => [
                        'label' =>
                            $category['label']
                            ?? str($key)->headline(),
                        'icon' =>
                            $inventoryCategoryIcons[$key]
                            ?? 'fa-box',
                    ],
                ]
            )
            ->all();

        $inventoryConditionDetails = [
            'new' => [
                'label' => 'New',
                'class' =>
                    'border-violet-200 bg-violet-50 text-violet-700',
            ],
            'excellent' => [
                'label' => 'Excellent',
                'class' =>
                    'border-blue-200 bg-blue-50 text-blue-700',
            ],
            'good' => [
                'label' => 'Good',
                'class' =>
                    'border-green-200 bg-green-50 text-green-700',
            ],
            'fair' => [
                'label' => 'Fair',
                'class' =>
                    'border-yellow-200 bg-yellow-50 text-yellow-700',
            ],
            'poor' => [
                'label' => 'Poor',
                'class' =>
                    'border-red-200 bg-red-50 text-red-700',
            ],
            'needs_repair' => [
                'label' => 'Needs Repair',
                'class' =>
                    'border-orange-200 bg-orange-50 text-orange-700',
            ],
        ];

        $inventoryStatusDetails = [
            'active' => [
                'label' => 'Active',
                'class' =>
                    'border-green-200 bg-green-50 text-green-700',
                'icon' => 'fa-circle-check',
            ],
            'under_repair' => [
                'label' => 'Under Repair',
                'class' =>
                    'border-orange-200 bg-orange-50 text-orange-700',
                'icon' => 'fa-screwdriver-wrench',
            ],
            'inactive' => [
                'label' => 'Inactive',
                'class' =>
                    'border-gray-200 bg-gray-100 text-gray-600',
                'icon' => 'fa-circle-pause',
            ],
            'retired' => [
                'label' => 'Retired',
                'class' =>
                    'border-slate-200 bg-slate-100 text-slate-600',
                'icon' => 'fa-box-archive',
            ],
            'disposed' => [
                'label' => 'Disposed',
                'class' =>
                    'border-slate-200 bg-slate-100 text-slate-500',
                'icon' => 'fa-trash-can',
            ],
        ];

        $formatSpecificationValue = function ($value): string {
            if (is_bool($value)) {
                return $value ? 'Yes' : 'No';
            }

            if (is_array($value)) {
                return collect($value)
                    ->filter()
                    ->implode(', ');
            }

            return str((string) $value)
                ->replace('_', ' ')
                ->headline()
                ->toString();
        };
    @endphp

    <div class="flex h-full min-h-0 flex-col bg-slate-50">
        <x-page-header :title="$institution->name" :subtitle="$institution->type . ' - ' . ($institution->barangay?->name ?? 'Barangay not available')" :back-url="route('institutions.index')" back-label="Back to institutions" :padded="true" class="border-b border-gray-200 bg-white">

            <x-slot:actions>

                    <a href="{{ route('institutions.edit', $institution) }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-red-700">
                        <i class="fa-solid fa-pen-to-square"></i>
                        Edit Institution
                    </a>

                    <button type="button" data-delete-institution
                        data-delete-url="{{ route('institutions.destroy', $institution) }}"
                        data-institution-name="{{ $institution->name }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50 hover:text-red-800">
                        <i class="fa-regular fa-trash-can"></i>
                        Delete
                    </button>

            </x-slot:actions>
        </x-page-header>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
            <div id="institutionSummaryMetrics"
                class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @php
                    $savedBoundaryType =
                        $institution->location_geojson['type']
                        ?? null;

                    $hasSavedBoundary = in_array(
                        $savedBoundaryType,
                        ['Polygon', 'MultiPolygon'],
                        true
                    );

                    $summaryMetrics = [
                        [
                            'key' => 'gap_score',
                            'label' => 'Overall ICT Gap',
                            'value' => $analytics?->gap_score,
                            'icon' => 'fa-chart-line',
                            'icon_class' => 'bg-red-50 text-red-600',
                            'formula' => '(Infrastructure Deficit + Connectivity Divide + Technological Obsolescence Proxy) / 3',
                            'description' => 'Equal arithmetic mean of the three available core gap dimensions. The composite is unavailable when any core component is unavailable.',
                        ],
                        [
                            'key' => 'infrastructure_deficit_score',
                            'label' => 'Infrastructure Deficit',
                            'value' => $analytics?->infrastructure_deficit_score,
                            'icon' => 'fa-building-circle-exclamation',
                            'icon_class' => 'bg-blue-50 text-blue-600',
                            'formula' => '(Defective Active Applicable Units / Active Applicable Units) x 100',
                            'description' => 'Percentage of active applicable ICT units recorded as defective.',
                        ],
                        [
                            'key' => 'connectivity_divide_score',
                            'label' => 'Connectivity Divide',
                            'value' => $analytics?->connectivity_divide_score,
                            'icon' => 'fa-wifi',
                            'icon_class' => 'bg-emerald-50 text-emerald-600',
                            'formula' => 'Mean across connections of the average download and upload shortfall: [max(Acquired Mbps - Measured Mbps, 0) / Acquired Mbps] x 100',
                            'description' => 'Available only when every reported connection has an identifiable acquired Mbps and recorded download and upload measurements.',
                        ],
                        [
                            'key' => 'technological_obsolescence_score',
                            'label' => 'Technological Obsolescence Proxy',
                            'value' => $analytics?->technological_obsolescence_score,
                            'icon' => 'fa-clock-rotate-left',
                            'icon_class' => 'bg-amber-50 text-amber-600',
                            'formula' => '(Retired Applicable Units / Total Applicable Units) x 100',
                            'description' => 'Percentage of applicable ICT inventory recorded as retired. This is a retirement-based proxy, not an age calculation.',
                        ],
                    ];
                @endphp

                @foreach ($summaryMetrics as $metric)
                    <div data-analytics-metric="{{ $metric['key'] }}"
                        title="{{ data_get($analytics?->recommendations_detail, 'assessment.unavailable_reasons.'.$metric['key'], $metric['description']) }}"
                        data-metric-available="{{ $metric['value'] !== null ? '1' : '0' }}"
                        class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $metric['icon_class'] }}">
                            <i class="fa-solid {{ $metric['icon'] }}"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <p class="text-xs font-medium text-slate-500">{{ $metric['label'] }}</p>
                                <x-metric-info
                                    :title="$metric['label']"
                                    :formula="$metric['formula']"
                                    :description="$metric['description']" />
                            </div>

                            <div class="mt-1 flex min-h-7 items-baseline gap-1">
                                <span data-metric-value
                                    class="text-xl font-bold text-slate-900">{{ $metric['value'] !== null ? number_format((float) $metric['value'], 1) : '' }}</span>
                                <span data-metric-unit
                                    class="text-sm font-normal text-slate-400 {{ $metric['value'] !== null ? '' : 'hidden' }}">%</span>
                                <span data-metric-empty
                                    class="text-sm font-semibold text-slate-400 {{ $metric['value'] !== null ? 'hidden' : '' }}">
                                    Not calculated
                                </span>
                            </div>
                            @if ($metric['value'] === null && ($reason = data_get($analytics?->recommendations_detail, 'assessment.unavailable_reasons.'.$metric['key'])))
                                <p class="mt-1 text-xs text-amber-700">{{ $reason }}</p>
                            @endif
                            @if ($metric['key'] === 'gap_score')
                                <span class="mt-1 inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $riskClasses }}">{{ $riskLabel }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <section class="mb-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div
                    class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50/80 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <i class="fa-solid fa-map-location-dot text-sm"></i>
                        </span>

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-sm font-semibold text-slate-900">
                                    Institution Location
                                </h2>

                                <span id="institutionBoundaryStatus"
                                    class="inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[10px] font-medium {{ $hasSavedBoundary ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-500' }}">
                                    <i class="fa-solid {{ $hasSavedBoundary ? 'fa-draw-polygon' : 'fa-location-dot' }}"></i>
                                    <span>
                                        {{ $hasSavedBoundary ? 'Saved boundary' : 'Point only' }}
                                    </span>
                                </span>
                            </div>

                            <p class="mt-0.5 text-xs text-slate-500">
                                Locked map preview centered on the saved institution coordinates.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if (auth()->user()->canManageInstitutions())
                            <button type="button"
                                id="fetchInstitutionOsmBoundary"
                                data-url="{{ route('institutions.osm-boundary.fetch', $institution) }}"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-60">
                                <i class="fa-solid fa-cloud-arrow-down"></i>
                                <span>Fetch map data Boundary</span>
                            </button>
                        @endif

                        @if ($institution->latitude !== null && $institution->longitude !== null)
                            <a href="https://www.google.com/maps?q={{ $institution->latitude }},{{ $institution->longitude }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                Open in Google Maps
                            </a>
                        @endif
                    </div>
                </div>

                <div class="relative h-72 bg-slate-100 sm:h-80">
                    @if ($institution->latitude !== null && $institution->longitude !== null)
                        <div id="institutionLocationMiniMap"
                            class="h-full w-full"
                            data-latitude="{{ $institution->latitude }}"
                            data-longitude="{{ $institution->longitude }}"
                            data-boundary='@json($hasSavedBoundary ? $institution->location_geojson : null)'>
                        </div>

                        <div
                            class="pointer-events-none absolute bottom-3 left-3 z-[500] rounded-lg border border-white/70 bg-white/90 px-3 py-2 text-[11px] text-slate-600 shadow-sm backdrop-blur">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-lock text-slate-400"></i>
                                <span>Map interaction is locked</span>
                            </div>
                        </div>
                    @else
                        <div class="flex h-full flex-col items-center justify-center px-6 text-center">
                            <span
                                class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                                <i class="fa-solid fa-location-crosshairs text-lg"></i>
                            </span>
                            <p class="mt-3 text-sm font-semibold text-slate-700">
                                No saved coordinates
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                Edit this institution to define its mapped location.
                            </p>
                        </div>
                    @endif
                </div>

                <div id="institutionBoundaryMessage"
                    class="hidden border-t border-slate-200 px-4 py-3 text-xs">
                </div>
            </section>

            <section id="institutionAnalyticsReport"
                aria-busy="{{ $analyticsProcessing ? 'true' : 'false' }}"
                data-analytics-active="{{ $analyticsProcessing ? '1' : '0' }}"
                data-analytics-stream-url="{{ $analyticsProcessingToken
                    ? route('institutions.analytics.stream', [
                        'institution' => $institution->id,
                        'token' => $analyticsProcessingToken,
                    ])
                    : '' }}"
                data-analytics-state-url="{{ $analyticsProcessingToken
                    ? route('institutions.analytics.state', [
                        'institution' => $institution->id,
                        'token' => $analyticsProcessingToken,
                    ])
                    : '' }}"
                data-analytics-stage="{{ $analytics?->processing_stage ?? 'idle' }}"
                class="relative mb-5 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div
                    class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-700">
                            <i class="fa-solid fa-chart-pie"></i>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900">ICT Gap Analytics Report</h2>
                            <p class="mt-0.5 text-xs text-gray-500">
                                Current scoring, analysis narrative, and policy recommendation.</p>
                            <p class="mt-1 text-xs text-gray-500">Last report scope: {{ data_get($recommendationDetails, 'calculation_scope.label', 'Not recorded for this report') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col items-stretch gap-1.5 sm:items-end">
                        <form method="POST"
                            action="{{ route('institutions.analytics.run', $institution) }}"
                            data-infrastructure-analytics-form>
                            @csrf
                            <button type="submit"
                                id="runInfrastructureAnalyticsButton"
                                data-analytics-inventory-blocked="0"
                                @disabled($analyticsProcessing)
                                title="Calculate recorded indicators; missing inputs remain unavailable."
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-xs font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500 sm:w-auto">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                <span>Run Infrastructure Analytics</span>
                            </button>
                        </form>

                        @if ($institution->ictInventory->isEmpty())
                            <p class="text-[11px] text-orange-600">
                                Inventory indicators need recorded inventory. Available connectivity results can still be calculated.
                            </p>
                        @endif
                    </div>
                </div>

                <div data-analytics-processing
                    class="{{ $analyticsProcessing ? '' : 'hidden' }} border-b border-red-100 bg-red-50/50 px-5 py-4"
                    role="status" aria-live="polite" aria-atomic="true">
                    <div class="flex items-start gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900">Analyzing institution data</p>
                            <p data-analytics-processing-label class="mt-0.5 text-xs leading-5 text-gray-600">
                                Please wait while TALASTAS processes the latest data.
                            </p>
                            <div class="mt-3 h-1 overflow-hidden rounded-full bg-red-100" aria-hidden="true">
                                <div data-analytics-progress-bar class="h-full rounded-full bg-red-600 transition-[width] duration-300 motion-reduce:transition-none"
                                    style="width: 8%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($analyticsFailed)
                    <div class="border-b border-red-100 bg-red-50 px-5 py-3 text-sm text-red-700">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span>
                                The most recent analytics run did not complete. The last successful report is shown below.
                            </span>
                        </div>
                    </div>
                @endif

                @if ($analytics && $analytics->processing_stage === 'outdated')
                    @php
                        $lastCompletedAssessment = data_get($analytics->recommendations_detail, 'last_completed_assessment');
                        $lastCompletedAt = data_get($lastCompletedAssessment, 'completed_at');
                        $sourceDataChangedAt = data_get($analytics->recommendations_detail, 'source_data_changed_at');
                    @endphp
                    <div class="border-b border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-900" role="status">
                        <p class="font-semibold">Inventory or connectivity data changed; the current analytics need recalculation.</p>
                        <p class="mt-1 text-xs leading-5">
                            @if ($lastCompletedAt)
                                Last completed assessment: {{ \Illuminate\Support\Carbon::parse($lastCompletedAt)->format('M d, Y g:i A') }}.
                            @else
                                No completed assessment is available for the current data.
                            @endif
                            Latest source change recorded: {{ $sourceDataChangedAt ? \Illuminate\Support\Carbon::parse($sourceDataChangedAt)->format('M d, Y g:i A') : 'time unavailable' }}. The previous assessment is historical and is not presented as current.
                        </p>
                    </div>
                @endif

                @if ($analytics && $aiStatus === 'fallback')
                    <div class="border-b border-orange-100 bg-orange-50 px-5 py-3 text-sm text-orange-800">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span>
                                Generative AI was not available for this run. TALASTAS used its local evidence-based prescriptive fallback instead.
                            </span>
                        </div>
                    </div>
                @endif

                @if ($analytics)
                    <div class="p-5">
                        {{-- LGU Decision Support --}}
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            {{-- Column 1: Planning Assessment --}}
                            <article class="institution-planning-insight flex h-full flex-col rounded-xl border border-gray-200 bg-white p-5">
                                <div class="mb-3 flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                            <i class="fa-solid fa-file-waveform text-sm"></i>
                                        </span>
                                        <div>
                                            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                                                Decision Support
                                            </p>
                                            <h3 class="text-sm font-semibold text-gray-900">
                                                Planning Assessment
                                            </h3>
                                        </div>
                                    </div>

                                    @if ($primaryConcern)
                                        <span
                                            class="inline-flex shrink-0 items-center rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700">
                                            {{ number_format((float) $primaryConcern['score'], 1) }}%
                                        </span>
                                    @endif
                                </div>

                                @if ($primaryConcern)
                                    <div class="institution-primary-concern mb-3 rounded-lg border px-3 py-2">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                                            Primary Concern
                                        </p>
                                        <p class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-gray-800">
                                            <i class="fa-solid {{ $primaryConcern['icon'] }} text-red-500"></i>
                                            {{ $primaryConcern['label'] }}
                                        </p>
                                    </div>
                                @endif

                                <p class="whitespace-pre-line text-sm leading-6 text-gray-600">
                                    {{ $analytics->infrastructure_narrative ?: 'No planning assessment is available yet.' }}
                                </p>
                            </article>

                            {{-- Column 2: Recommended First Action --}}
                            <article class="institution-planning-insight flex h-full flex-col rounded-xl border border-slate-200 bg-slate-50 p-5">
                                <div class="mb-3 flex items-center gap-2">
                                    <span
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-red-600 shadow-sm ring-1 ring-slate-200">
                                        <i class="fa-solid fa-bullseye text-sm"></i>
                                    </span>
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-red-500">
                                            LGU Action
                                        </p>
                                        <h3 class="text-sm font-semibold text-gray-900">
                                            Recommended First Action
                                        </h3>
                                    </div>
                                </div>

                                <p class="whitespace-pre-line text-sm leading-6 text-gray-700">
                                    {{ $analytics->policy_recommendation ?: 'No policy recommendation is available yet.' }}
                                </p>
                            </article>

                            
                        </div>

                        @if (count($priorities) > 0)
                            <div class="mt-4">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                                            <i class="fa-solid fa-list-check text-sm"></i>
                                        </span>
                                        <div>
                                            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                                                Ranked Actions
                                            </p>
                                            <h3 class="text-sm font-semibold text-gray-900">
                                                Recommended Priorities
                                            </h3>
                                        </div>
                                    </div>

                                    <span class="text-[11px] text-gray-400">
                                        Highest priority first
                                    </span>
                                </div>

                                {{-- Three compact priority cards instead of full-width rows --}}
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                    @foreach (array_slice($priorities, 0, 3) as $index => $priority)
                                        @php
                                            $priorityName = is_array($priority)
                                                ? ($priority['priority'] ?? 'Recommended action')
                                                : (string) $priority;

                                            $priorityRationale = is_array($priority)
                                                ? ($priority['rationale'] ?? null)
                                                : null;

                                            $priorityUrgency = is_array($priority)
                                                ? ($priority['urgency'] ?? null)
                                                : null;

                                            $priorityUrgencyLabel = $priorityUrgency
                                                ? str_replace('_', ' ', $priorityUrgency)
                                                : null;

                                            $priorityUrgencyClasses = match ($priorityUrgency) {
                                                'immediate' => 'border-red-200 bg-red-50 text-red-700',
                                                'short_term' => 'border-orange-200 bg-orange-50 text-orange-700',
                                                'medium_term' => 'border-blue-200 bg-blue-50 text-blue-700',
                                                default => 'border-gray-200 bg-gray-50 text-gray-600',
                                            };
                                        @endphp

                                        <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                            <div class="mb-3 flex items-start justify-between gap-3">
                                                <span
                                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-600 text-xs font-bold text-white">
                                                    {{ $index + 1 }}
                                                </span>

                                                @if ($priorityUrgencyLabel)
                                                    <span
                                                        class="rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $priorityUrgencyClasses }}">
                                                        {{ $priorityUrgencyLabel }}
                                                    </span>
                                                @endif
                                            </div>

                                            <h4 class="text-sm font-semibold leading-5 text-gray-900">
                                                {{ $priorityName }}
                                            </h4>

                                            @if ($priorityRationale)
                                                <p class="mt-2 text-xs leading-5 text-gray-500">
                                                    {{ $priorityRationale }}
                                                </p>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div
                            class="mt-5 flex flex-col gap-3 border-t border-gray-200 pt-4 text-xs text-gray-500 sm:flex-row sm:flex-wrap sm:items-center">
                            <span
                                class="inline-flex w-fit items-center gap-1.5 rounded-full border px-2.5 py-1 font-medium {{ $providerClasses }}"
                                title="{{ $analyticsModel ?: 'Model not available' }}">
                                <i class="fa-solid {{ $providerIcon }}"></i>
                                {{ $providerLabel }}
                            </span>

                            <span class="inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-calculator text-gray-400"></i>
                                {{ $scoreSourceLabel }}
                            </span>

                            @if ($analyticsModel)
                                <span class="inline-flex items-center gap-1.5">
                                    <i class="fa-solid fa-cube text-gray-400"></i>
                                    {{ $analyticsModel }}
                                </span>
                            @endif

                            @if ($decisionSupportVersion)
                                <span class="inline-flex items-center gap-1.5">
                                    <i class="fa-solid fa-layer-group text-gray-400"></i>
                                    Decision Support v{{ $decisionSupportVersion }}
                                </span>
                            @endif

                            <span class="inline-flex items-center gap-1.5">
                                <i class="fa-regular fa-calendar text-gray-400"></i>
                                {{ $analytics->analysis_date
                                    ? $analytics->analysis_date->format('M d, Y g:i A')
                                    : 'Recalculation required' }}
                            </span>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center px-5 py-14 text-center">
                        <span
                            class="flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-500">
                            <i class="fa-solid fa-chart-pie text-xl"></i>
                        </span>
                        <h3 class="mt-4 text-sm font-semibold text-gray-900">No analytics report yet</h3>
                        <p class="mt-1 max-w-md text-sm leading-6 text-gray-500">
                            Run infrastructure analytics to calculate the current scores and generate an AI-assisted
                            analysis and policy recommendation from the synchronized inventory data.
                        </p>
                    </div>
                @endif
            </section>

            <section id="institutionInventory" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div
                    class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50/80 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-600 shadow-sm ring-1 ring-slate-200">
                            <i class="fa-solid fa-boxes-stacked text-sm"></i>
                        </span>

                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">
                                ICT Inventory
                            </h2>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Hardware, Software and Network inventory.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('ict-inventory.index') }}"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-100">
                            <i class="fa-solid fa-list-ul"></i>
                            Open Inventory
                        </a>

                        <a href="{{ route('ict-inventory.create', ['institution_id' => $institution->id]) }}"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-600 px-3 py-2 text-xs font-medium text-white shadow-sm transition hover:bg-red-700">
                            <i class="fa-solid fa-pen-to-square"></i>
                            Manage Inventory
                        </a>
                    </div>
                </div>

                @php
                    $showConnectivity = request('tab') === 'connectivity';
                    $selectedPanel = $showConnectivity ? 'connectivity' : 'inventory';
                @endphp
                <nav class="flex gap-1 overflow-x-auto border-b border-slate-200 px-4 pt-3" role="tablist" aria-label="Institution inventory">
                    @foreach (['inventory' => 'ICT Inventory', 'connectivity' => 'Internet Connectivity'] as $tabKey => $tabLabel)
                        <a id="institution-tab-{{ $tabKey }}" data-institution-inventory-tab="{{ $tabKey }}"
                           href="{{ route('institutions.show', ['institution' => $institution->id, 'tab' => $tabKey]) }}#institutionInventory"
                           role="tab" aria-controls="institution-panel-{{ $tabKey }}" aria-selected="{{ $selectedPanel === $tabKey ? 'true' : 'false' }}"
                           tabindex="{{ $selectedPanel === $tabKey ? '0' : '-1' }}"
                           class="shrink-0 rounded-t-lg border-b-2 px-4 py-3 text-sm font-semibold {{ $selectedPanel === $tabKey ? 'border-red-700 text-red-700' : 'border-transparent text-slate-500' }}">{{ $tabLabel }}</a>
                    @endforeach
                </nav>
                <div id="institution-panel-inventory" role="tabpanel" aria-labelledby="institution-tab-inventory" data-institution-inventory-panel="inventory" @if ($showConnectivity) hidden @endif>
                <div class="grid grid-cols-2 gap-3 border-b border-slate-200 p-4 xl:grid-cols-5">
                    @foreach ([
                        [
                            'label' => 'Recorded Units',
                            'value' => $inventoryTotal,
                            'caption' => $inventoryItems->count() . ' grouped ' . Str::plural('record', $inventoryItems->count()),
                            'icon' => 'fa-boxes-stacked',
                            'icon_class' => 'bg-slate-100 text-slate-600',
                            'value_class' => 'text-slate-900',
                        ],
                        [
                            'label' => 'Active Units',
                            'value' => $inventoryActive,
                            'caption' => 'Unavailable if any Active count is unrecorded',
                            'icon' => 'fa-circle-check',
                            'icon_class' => 'bg-emerald-50 text-emerald-600',
                            'value_class' => 'text-emerald-700',
                        ],
                        [
                            'label' => 'Retired Units',
                            'value' => $inventoryRetired,
                            'caption' => 'Unavailable if any Retired count is unrecorded',
                            'icon' => 'fa-box-archive',
                            'icon_class' => 'bg-amber-50 text-amber-600',
                            'value_class' => 'text-amber-700',
                        ],
                        [
                            'label' => 'Functional Units',
                            'value' => $inventoryFunctional,
                            'caption' => 'Survey count; unavailable if any row is unrecorded',
                            'icon' => 'fa-circle-check',
                            'icon_class' => 'bg-emerald-50 text-emerald-600',
                            'value_class' => 'text-emerald-700',
                        ],
                        [
                            'label' => 'Defective Units',
                            'value' => $inventoryAttention,
                            'caption' => 'Unavailable if any Defective count is unrecorded',
                            'icon' => 'fa-triangle-exclamation',
                            'icon_class' => 'bg-orange-50 text-orange-600',
                            'value_class' => 'text-orange-700',
                        ],
                    ] as $inventoryMetric)
                        <div class="rounded-xl border border-slate-200 bg-white p-3.5">
                            <div class="flex items-start justify-between gap-3">
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $inventoryMetric['icon_class'] }}">
                                    <i class="fa-solid {{ $inventoryMetric['icon'] }} text-sm"></i>
                                </span>

                                <span class="text-2xl font-bold tabular-nums {{ $inventoryMetric['value_class'] }}">
                                    {{ $inventoryMetric['value'] === null ? '—' : number_format($inventoryMetric['value']) }}
                                </span>
                            </div>

                            <p class="mt-3 text-xs font-semibold text-slate-700">
                                {{ $inventoryMetric['label'] }}
                            </p>
                            <p class="mt-0.5 text-[11px] leading-4 text-slate-400">
                                {{ $inventoryMetric['caption'] }}
                            </p>
                        </div>
                    @endforeach
                </div>

                @if ($inventoryItems->isNotEmpty())
                    <div class="w-full overflow-x-auto">
                        <table class="w-full min-w-[900px] table-auto text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 bg-white text-left">
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Component
                                    </th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Category
                                    </th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Total
                                    </th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Active
                                    </th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Retired
                                    </th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Functional</th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Defective
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                @foreach ($inventoryItems as $item)
                                    @php
                                        $categoryLabel = match ($item->component_type) {
                                            'software' => 'Software & Systems',
                                            'network' => 'Network Infrastructure',
                                            'connectivity' => 'Internet Connectivity',
                                            default => 'Hardware & Equipment',
                                        };

                                        $componentIcon = match ($item->component_type) {
                                            'software' => 'fa-code',
                                            'connectivity' => 'fa-globe',
                                            'network' => 'fa-network-wired',
                                            default => 'fa-computer',
                                        };
                                    @endphp

                                    <tr class="transition hover:bg-slate-50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2.5">
                                                <span
                                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                                    <i class="fa-solid {{ $componentIcon }} text-xs"></i>
                                                </span>

                                                <p class="font-medium text-slate-900">
                                                    {{ $item->asset_name }}
                                                </p>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 text-xs text-slate-600">
                                            {{ $categoryLabel }}
                                            <p @if ($item->availability_status === 'Availability Recorded') hidden @endif class="mt-1 text-xs text-amber-700">{{ $item->availability_status === 'Availability Recorded' ? '' : $item->availability_status }}</p>
                                        </td>

                                        @foreach ([
                                            $item->total_count,
                                            $item->active_count,
                                            $item->retired_count,
                                            $item->functional_count,
                                            $item->defective_count,
                                        ] as $inventoryCount)
                                            <td class="px-3 py-3 text-center tabular-nums">
                                                <span
                                                    class="inline-flex min-w-8 items-center justify-center rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                                    {{ $inventoryCount === null ? '—' : number_format((int) $inventoryCount) }}
                                                </span>
                                            </td>
                                        @endforeach

                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <a href="{{ route('ict-inventory.show', $item) }}"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                                                    title="View inventory record"
                                                    aria-label="View inventory record">
                                                    <i class="fa-solid fa-eye text-xs"></i>
                                                </a>

                                                <a href="{{ route('ict-inventory.edit', $item) }}?return_to=institution"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                                                    title="Edit inventory record"
                                                    aria-label="Edit inventory record">
                                                    <i class="fa-solid fa-pen text-xs"></i>
                                                </a>

                                                <form method="POST"
                                                    action="{{ route('ict-inventory.destroy', $item) }}">
                                                    @csrf
                                                    @method('DELETE')

                                                    <input type="hidden"
                                                        name="return_to"
                                                        value="institution">

                                                    <button type="submit"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 transition hover:bg-red-50 hover:text-red-700"
                                                        title="Delete inventory record"
                                                        aria-label="Delete inventory record"
                                                        data-confirm-inventory-delete
                                                        data-inventory-name="{{ $item->asset_name }}">
                                                        <i class="fa-regular fa-trash-can text-xs"></i>
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
                    <div class="flex flex-col items-center justify-center px-4 py-12 text-center">
                        <span
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <i class="fa-solid fa-box-open text-xl"></i>
                        </span>

                        <p class="mt-3 text-sm font-semibold text-slate-700">
                            No inventory records yet
                        </p>

                        <p class="mt-1 max-w-md text-xs leading-5 text-slate-400">
                            Add inventory using the Hardware, Software, Network and Internet Connectivity templates.
                        </p>

                        <a href="{{ route('ict-inventory.create', ['institution_id' => $institution->id]) }}"
                            class="mt-4 inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-xs font-medium text-white transition hover:bg-red-700">
                            <i class="fa-solid fa-plus"></i>
                            Add Inventory
                        </a>
                    </div>
                @endif
                </div>
                <div id="institution-panel-connectivity" role="tabpanel" aria-labelledby="institution-tab-connectivity" data-institution-inventory-panel="connectivity" @if (!$showConnectivity) hidden @endif>
                    @include('ict-inventory._connectivity-panel')
                </div>
            @if ($institution->auditRecords->isEmpty())
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <a href="{{ route('audits.create', $institution) }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <i class="fa-solid fa-clipboard-check"></i>
                        Submit Quarterly Audit
                    </a>
                    <p class="text-xs text-slate-400">No audit records submitted yet.</p>
                </div>
            @else
                <div class="mt-5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">Audit History</h3>
                        <a href="{{ route('audits.create', $institution) }}"
                           class="text-xs font-medium text-red-600 hover:text-red-700">
                            + Submit Audit
                        </a>
                    </div>
                    <div class="mt-3 space-y-2">
                        @foreach ($institution->auditRecords->sortByDesc('audit_date') as $audit)
                            <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-slate-700">
                                        {{ $audit->audit_date->format('M d, Y') }}
                                        <span class="text-slate-400">by</span>
                                        {{ $audit->auditor?->name ?? 'Unknown' }}
                                    </p>
                                    @if ($audit->notes)
                                        <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ $audit->notes }}</p>
                                    @endif
                                </div>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold
                                    {{ $audit->validation_status === 'approved' ? 'bg-emerald-100 text-emerald-700' :
                                       ($audit->validation_status === 'validated' ? 'bg-blue-100 text-blue-700' :
                                       ($audit->validation_status === 'flagged' ? 'bg-amber-100 text-amber-700' :
                                       ($audit->validation_status === 'revision_required' ? 'bg-red-100 text-red-700' :
                                       'bg-slate-200 text-slate-600'))) }}">
                                    {{ ucfirst(str_replace('_', ' ', $audit->validation_status)) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            </section>
        </div>

        @include('institutions.show-modals')
    </div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = Array.from(document.querySelectorAll('[data-institution-inventory-tab]'));
    const panels = Array.from(document.querySelectorAll('[data-institution-inventory-panel]'));
    function selectInventoryPanel(tab) {
        tabs.forEach(button => {
            const selected = button === tab;
            button.setAttribute('aria-selected', String(selected));
            button.tabIndex = selected ? 0 : -1;
            button.classList.toggle('border-red-700', selected);
            button.classList.toggle('text-red-700', selected);
            button.classList.toggle('border-transparent', !selected);
            button.classList.toggle('text-slate-500', !selected);
        });
        panels.forEach(panel => panel.hidden = panel.dataset.institutionInventoryPanel !== tab.dataset.institutionInventoryTab);
        window.history.replaceState(null, '', tab.href);
    }
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', event => {
            if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            selectInventoryPanel(tab);
        });
        tab.addEventListener('keydown', event => {
            const target = {ArrowRight: (index + 1) % tabs.length, ArrowLeft: (index + tabs.length - 1) % tabs.length, Home: 0, End: tabs.length - 1}[event.key];
            if (target === undefined) return;
            event.preventDefault();
            selectInventoryPanel(tabs[target]);
            tabs[target].focus();
        });
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const mapElement =
        document.getElementById('institutionLocationMiniMap');

    if (!mapElement || typeof L === 'undefined') {
        return;
    }

    const latitude =
        Number.parseFloat(mapElement.dataset.latitude);
    const longitude =
        Number.parseFloat(mapElement.dataset.longitude);

    if (
        !Number.isFinite(latitude)
        || !Number.isFinite(longitude)
    ) {
        return;
    }

    const map = L.map(mapElement, {
        center: [latitude, longitude],
        zoom: 17,
        zoomControl: false,
        attributionControl: true,
        dragging: false,
        touchZoom: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        boxZoom: false,
        keyboard: false,
        tap: false
    });

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: 'Â© OpenStreetMap contributors'
        }
    ).addTo(map);

    const marker = L.circleMarker(
        [latitude, longitude],
        {
            radius: 7,
            color: '#ffffff',
            weight: 3,
            fillColor: '#dc2626',
            fillOpacity: 1
        }
    ).addTo(map);

    let boundaryLayer = null;

    const renderBoundary = (geometry) => {
        if (boundaryLayer) {
            map.removeLayer(boundaryLayer);
            boundaryLayer = null;
        }

        if (
            !geometry
            || !['Polygon', 'MultiPolygon'].includes(
                geometry.type
            )
        ) {
            map.setView([latitude, longitude], 17);
            return;
        }

        boundaryLayer = L.geoJSON(
            geometry,
            {
                style: {
                    color: '#dc2626',
                    weight: 3,
                    opacity: 0.9,
                    fillColor: '#ef4444',
                    fillOpacity: 0.1
                },
                interactive: false
            }
        ).addTo(map);

        const bounds = boundaryLayer.getBounds();

        if (bounds.isValid()) {
            map.fitBounds(bounds, {
                padding: [24, 24],
                maxZoom: 18,
                animate: false
            });
        }

        marker.bringToFront();
    };

    const storedBoundary =
        mapElement.dataset.boundary
            ? JSON.parse(mapElement.dataset.boundary)
            : null;

    renderBoundary(storedBoundary);

    const fetchButton =
        document.getElementById(
            'fetchInstitutionOsmBoundary'
        );

    const status =
        document.getElementById(
            'institutionBoundaryStatus'
        );

    const message =
        document.getElementById(
            'institutionBoundaryMessage'
        );

    const showMessage = (type, text) => {
        if (!message) {
            return;
        }

        message.className =
            'border-t px-4 py-3 text-xs '
            + (
                type === 'error'
                    ? 'border-red-200 bg-red-50 text-red-700'
                    : type === 'warning'
                        ? 'border-amber-200 bg-amber-50 text-amber-700'
                        : 'border-emerald-200 bg-emerald-50 text-emerald-700'
            );

        message.textContent = text;
    };

    const setBoundaryStatus = (
        label,
        saved
    ) => {
        if (!status) {
            return;
        }

        status.className =
            'inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[10px] font-medium '
            + (
                saved
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                    : 'border-blue-200 bg-blue-50 text-blue-700'
            );

        status.innerHTML = `
            <i class="fa-solid fa-draw-polygon"></i>
            <span>${label}</span>
        `;
    };

    fetchButton?.addEventListener('click', async () => {
        if (fetchButton.disabled) {
            return;
        }

        const originalHtml = fetchButton.innerHTML;

        fetchButton.disabled = true;
        fetchButton.innerHTML = `
            <span class="global-loading-icon"><i class="fa-solid"></i></span>
            <span>Fetching Boundary</span>
        `;

        try {
            const response = await fetch(
                fetchButton.dataset.url,
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN':
                            document
                                .querySelector(
                                    'meta[name="csrf-token"]'
                                )
                                ?.getAttribute('content')
                            || ''
                    },
                    credentials: 'same-origin'
                }
            );

            const data = await response.json();

            if (!response.ok) {
                throw new Error(
                    (response.status >= 500 ? 'Unable to retrieve location information. Please try again.' : data?.message)
                    || 'OpenStreetMap boundary could not be fetched.'
                );
            }

            renderBoundary(data.geojson);

            if (data.saved) {
                setBoundaryStatus(
                    'map data boundary saved',
                    true
                );

                showMessage(
                    'success',
                    data.message
                );

                return;
            }

            setBoundaryStatus(
                'map data preview',
                false
            );

            showMessage(
                'warning',
                data.message
            );
        } catch (error) {
            showMessage(
                'error',
                error instanceof Error
                    ? error.message
                    : 'OpenStreetMap boundary could not be fetched.'
            );
        } finally {
            fetchButton.disabled = false;
            fetchButton.innerHTML = originalHtml;
        }
    });

    setTimeout(() => map.invalidateSize(), 50);
});
</script>
@endsection


