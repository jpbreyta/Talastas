@extends('layouts.app')

@section('content')
    @php
        $analytics = $institution->institutionAnalytics;

        $recommendationDetails = $analytics?->recommendations_detail;

        if (is_string($recommendationDetails)) {
            $recommendationDetails = json_decode($recommendationDetails, true);
        }

        if (!is_array($recommendationDetails)) {
            $recommendationDetails = [];
        }

        $providerKey = strtolower((string) ($recommendationDetails['provider'] ?? 'unknown'));
        $providerLabel = match ($providerKey) {
            'gemini' => 'Gemini',
            'groq' => 'Groq',
            'local' => 'Local',
            default => 'Not available',
        };

        $providerClasses = match ($providerKey) {
            'gemini' => 'border-blue-200 bg-blue-50 text-blue-700',
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
        $riskLabel = match ($riskKey) {
            'critical' => 'Critical',
            'warning', 'high' => 'Warning',
            'adequate', 'low' => 'Adequate',
            'moderate' => 'Moderate',
            default => 'Insufficient Data',
        };

        $riskClasses = match ($riskKey) {
            'critical' => 'border-red-200 bg-red-50 text-red-700',
            'warning', 'high' => 'border-orange-200 bg-orange-50 text-orange-700',
            'moderate' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
            'adequate', 'low' => 'border-green-200 bg-green-50 text-green-700',
            default => 'border-gray-200 bg-gray-50 text-gray-600',
        };

        $gapScore = $analytics?->gap_score;
        $gapProgress = $gapScore !== null
            ? min(100, max(0, (float) $gapScore))
            : 0;

        $completenessScore = $analytics?->data_completeness_score
            ?? $institution->data_completeness_score;

        $populationLabel = $institution->type === 'School'
            ? 'Students'
            : 'Staff';

        $populationValue = $institution->type === 'School'
            ? $institution->student_population
            : $institution->staff_population;

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
    @endphp

    <div class="flex h-full min-h-0 flex-col bg-gray-100">
        <div class="shrink-0 bg-blue-700 px-5 py-4 sm:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <a href="{{ route('institutions.index') }}"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white transition hover:bg-blue-500"
                        title="Back to institutions">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="truncate text-lg font-semibold leading-tight text-white">
                                {{ $institution->name }}
                            </h1>

                            @if ($institution->is_pilot_site)
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-blue-300/30 bg-blue-500/30 px-2 py-0.5 text-xs font-medium text-blue-100">
                                    <i class="fa-solid fa-flask text-[10px]"></i>
                                    Pilot Site
                                </span>
                            @endif

                            @if ($institution->needs_maintenance)
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-red-300/30 bg-red-500/20 px-2 py-0.5 text-xs font-medium text-red-100">
                                    <i class="fa-solid fa-screwdriver-wrench text-[10px]"></i>
                                    Needs Maintenance
                                </span>
                            @endif
                        </div>

                        <p class="mt-1 text-xs text-blue-200">
                            {{ $institution->type }}
                            <span class="px-1">•</span>
                            {{ $institution->barangay?->name ?? 'Barangay not available' }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('institutions.edit', $institution) }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-blue-100 bg-white px-4 py-2 text-sm font-medium text-blue-700 shadow-sm transition hover:bg-blue-50">
                        <i class="fa-solid fa-pen-to-square"></i>
                        Edit Institution
                    </a>

                    <button type="button" data-delete-institution
                        data-delete-url="{{ route('institutions.destroy', $institution) }}"
                        data-institution-name="{{ $institution->name }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-300/30 bg-red-500/20 px-4 py-2 text-sm font-medium text-red-100 transition hover:bg-red-600 hover:text-white">
                        <i class="fa-solid fa-trash-can"></i>
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Overall Gap Score</p>
                        <p class="mt-0.5 text-xl font-bold text-gray-900">
                            {{ $gapScore !== null ? number_format((float) $gapScore, 1) : '—' }}
                            @if ($gapScore !== null)
                                <span class="text-sm font-normal text-gray-400">%</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <i class="fa-solid fa-chart-column"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Data Completeness</p>
                        <p class="mt-0.5 text-xl font-bold text-gray-900">
                            {{ $completenessScore !== null ? number_format((float) $completenessScore, 1) : '—' }}
                            @if ($completenessScore !== null)
                                <span class="text-sm font-normal text-gray-400">%</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <i class="fa-solid {{ $institution->type === 'School' ? 'fa-user-graduate' : 'fa-users' }}"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">{{ $populationLabel }}</p>
                        <p class="mt-0.5 text-xl font-bold text-gray-900">
                            {{ $populationValue !== null ? number_format($populationValue) : '—' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                        <i class="fa-solid fa-gauge-high"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Internet Speed</p>
                        <p class="mt-0.5 text-xl font-bold text-gray-900">
                            {{ $institution->internet_speed !== null ? number_format((float) $institution->internet_speed, 1) : '—' }}
                            @if ($institution->internet_speed !== null)
                                <span class="text-sm font-normal text-gray-400">Mbps</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
                <div class="space-y-5 xl:col-span-1">
                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                <i class="fa-solid fa-circle-info text-xs"></i>
                            </span>
                            <h2 class="text-sm font-semibold text-gray-800">Basic Information</h2>
                        </div>

                        <div class="divide-y divide-gray-100">
                            <x-detail-row label="Sector Type" :value="$institution->type" />
                            <x-detail-row label="Barangay" :value="$institution->barangay?->name ?? '—'" />
                            <x-detail-row label="ISP Provider" :value="$institution->isp_provider ?? '—'" />
                            <x-detail-row label="Computers" :value="$institution->number_of_computers !== null
                                ? $institution->number_of_computers . ' units'
                                : '—'" />
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <span
                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                                <i class="fa-solid fa-location-dot text-xs"></i>
                            </span>
                            <h2 class="text-sm font-semibold text-gray-800">Location</h2>
                        </div>

                        <div class="divide-y divide-gray-100">
                            <x-detail-row label="Latitude" :value="$institution->latitude ?? '—'" />
                            <x-detail-row label="Longitude" :value="$institution->longitude ?? '—'" />
                        </div>

                        @if ($institution->latitude !== null && $institution->longitude !== null)
                            <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">
                                <a href="https://www.google.com/maps?q={{ $institution->latitude }},{{ $institution->longitude }}"
                                    target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 transition hover:text-blue-800">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    Open in Google Maps
                                </a>
                            </div>
                        @endif
                    </section>
                </div>

                <div class="space-y-5 xl:col-span-2">
                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                                <i class="fa-solid fa-computer text-xs"></i>
                            </span>
                            <h2 class="text-sm font-semibold text-gray-800">ICT Facilities</h2>
                        </div>

                        <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-4">
                            <x-facility-badge label="Computer Lab" :active="$institution->has_computer_lab" />
                            <x-facility-badge label="Smart TV" :active="$institution->has_smart_tv" />
                            <x-facility-badge label="Projected Whiteboard" :active="$institution->has_projected_whiteboard" />
                            <x-facility-badge label="PA System" :active="$institution->has_public_address_system" />
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-100 text-orange-600">
                                <i class="fa-solid fa-clipboard-check text-xs"></i>
                            </span>
                            <h2 class="text-sm font-semibold text-gray-800">Status</h2>
                        </div>

                        <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-4">
                            <x-status-badge label="Pilot Site" :active="$institution->is_pilot_site" activeColor="green" />
                            <x-status-badge label="Active" :active="$institution->is_active" activeColor="green" />
                            <x-status-badge label="Needs Maintenance" :active="$institution->needs_maintenance"
                                activeColor="red" />
                            <x-status-badge label="Requires Audit" :active="$institution->requires_audit"
                                activeColor="yellow" />
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div
                            class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-200 text-gray-600">
                                    <i class="fa-solid fa-note-sticky text-xs"></i>
                                </span>
                                <div>
                                    <h2 class="text-sm font-semibold text-gray-800">Institution Notes</h2>
                                    <p class="text-xs text-gray-500">Record additional information for future reference.</p>
                                </div>
                            </div>

                            <button type="button" data-open-notes-modal
                                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                                <i class="fa-solid {{ $institution->notes ? 'fa-pen' : 'fa-plus' }}"></i>
                                <span id="notesActionLabel">{{ $institution->notes ? 'Edit Notes' : 'Add Notes' }}</span>
                            </button>
                        </div>

                        <div class="px-4 py-4">
                            <p id="institutionNotesText"
                                class="{{ $institution->notes ? '' : 'hidden' }} whitespace-pre-line text-sm leading-6 text-gray-700">
                                {{ $institution->notes }}
                            </p>

                            <div id="institutionNotesEmpty"
                                class="{{ $institution->notes ? 'hidden' : '' }} flex items-start gap-3 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-4">
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-gray-400 shadow-sm">
                                    <i class="fa-regular fa-note-sticky"></i>
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">No notes recorded yet</p>
                                    <p class="mt-1 text-xs leading-5 text-gray-500">
                                        Use the Add Notes button to save observations, reminders, or follow-up details.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <section class="mb-5 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div
                    class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                            <i class="fa-solid fa-chart-pie"></i>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900">ICT Gap Analytics Report</h2>
                            <p class="mt-0.5 text-xs text-gray-500">
                                Current scoring, analysis narrative, and policy recommendation.
                            </p>
                        </div>
                    </div>

                    <button type="button" id="updateAnalyticsButton"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-xs font-medium text-white transition hover:bg-blue-700">
                        <i class="fa-solid fa-rotate"></i>
                        Update Analytics
                    </button>
                </div>

                @if ($analytics)
                    <div class="p-5">
                        <div class="mb-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 lg:col-span-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Overall Gap Score
                                        </p>
                                        <p class="mt-2 text-4xl font-bold text-gray-900">
                                            {{ $gapScore !== null ? number_format((float) $gapScore, 1) : '—' }}
                                            @if ($gapScore !== null)
                                                <span class="text-lg font-medium text-gray-400">%</span>
                                            @endif
                                        </p>
                                    </div>

                                    <span
                                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $riskClasses }}">
                                        {{ $riskLabel }}
                                    </span>
                                </div>

                                <div class="mt-5 h-2 overflow-hidden rounded-full bg-gray-200">
                                    <div class="h-full rounded-full bg-blue-600 transition-all"
                                        style="width: {{ $gapProgress }}%"></div>
                                </div>

                                <p class="mt-3 text-xs leading-5 text-gray-500">
                                    Higher percentages indicate greater ICT infrastructure gaps and stronger intervention needs.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:col-span-2">
                                @php
                                    $analyticsMetrics = [
                                        [
                                            'label' => 'Infrastructure Deficit',
                                            'value' => $analytics->infrastructure_deficit_score,
                                            'icon' => 'fa-building-circle-exclamation',
                                            'classes' => 'bg-red-50 text-red-600',
                                        ],
                                        [
                                            'label' => 'Connectivity Divide',
                                            'value' => $analytics->connectivity_divide_score,
                                            'icon' => 'fa-wifi',
                                            'classes' => 'bg-orange-50 text-orange-600',
                                        ],
                                        [
                                            'label' => 'Technological Obsolescence',
                                            'value' => $analytics->technological_obsolescence_score,
                                            'icon' => 'fa-clock-rotate-left',
                                            'classes' => 'bg-purple-50 text-purple-600',
                                        ],
                                        [
                                            'label' => 'Asset-to-User Ratio',
                                            'value' => $analytics->asset_to_user_ratio,
                                            'icon' => 'fa-users-gear',
                                            'classes' => 'bg-emerald-50 text-emerald-600',
                                        ],
                                    ];
                                @endphp

                                @foreach ($analyticsMetrics as $metric)
                                    <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4">
                                        <span
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $metric['classes'] }}">
                                            <i class="fa-solid {{ $metric['icon'] }}"></i>
                                        </span>
                                        <div>
                                            <p class="text-xs font-medium text-gray-500">{{ $metric['label'] }}</p>
                                            <p class="mt-1 text-lg font-bold text-gray-900">
                                                {{ $metric['value'] !== null ? number_format((float) $metric['value'], 1) : '—' }}
                                                @if ($metric['value'] !== null)
                                                    <span class="text-xs font-normal text-gray-400">%</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                            <article class="rounded-xl border border-gray-200 bg-white p-5">
                                <div class="mb-3 flex items-center gap-2">
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                        <i class="fa-solid fa-file-waveform text-sm"></i>
                                    </span>
                                    <h3 class="text-sm font-semibold text-gray-900">Infrastructure Analysis</h3>
                                </div>

                                <p class="whitespace-pre-line text-sm leading-6 text-gray-600">
                                    {{ $analytics->infrastructure_narrative ?: 'No analysis narrative is available yet.' }}
                                </p>
                            </article>

                            <article class="rounded-xl border border-blue-200 bg-blue-50/40 p-5">
                                <div class="mb-3 flex items-center gap-2">
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                                        <i class="fa-solid fa-lightbulb text-sm"></i>
                                    </span>
                                    <h3 class="text-sm font-semibold text-gray-900">Policy Recommendation</h3>
                                </div>

                                <p class="whitespace-pre-line text-sm leading-6 text-gray-700">
                                    {{ $analytics->policy_recommendation ?: 'No policy recommendation is available yet.' }}
                                </p>
                            </article>
                        </div>

                        @if (count($priorities) > 0)
                            <div class="mt-4 rounded-xl border border-gray-200 bg-white p-5">
                                <div class="mb-4 flex items-center gap-2">
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                                        <i class="fa-solid fa-list-check text-sm"></i>
                                    </span>
                                    <h3 class="text-sm font-semibold text-gray-900">Recommended Priorities</h3>
                                </div>

                                <div class="space-y-3">
                                    @foreach ($priorities as $priority)
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
                                        @endphp

                                        <div class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                                            <span
                                                class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-blue-600 shadow-sm">
                                                <i class="fa-solid fa-check text-xs"></i>
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-sm font-semibold text-gray-800">
                                                        {{ $priorityName }}
                                                    </p>

                                                    @if ($priorityUrgency)
                                                        <span
                                                            class="rounded-full border border-gray-200 bg-white px-2 py-0.5 text-[11px] font-medium uppercase tracking-wide text-gray-500">
                                                            {{ str_replace('_', ' ', $priorityUrgency) }}
                                                        </span>
                                                    @endif
                                                </div>

                                                @if ($priorityRationale)
                                                    <p class="mt-1 text-xs leading-5 text-gray-500">
                                                        {{ $priorityRationale }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
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

                            <span class="inline-flex items-center gap-1.5">
                                <i class="fa-regular fa-calendar text-gray-400"></i>
                                {{ $analytics->analysis_date
                                    ? $analytics->analysis_date->format('M d, Y g:i A')
                                    : 'Analysis date not available' }}
                            </span>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center px-5 py-14 text-center">
                        <span
                            class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-500">
                            <i class="fa-solid fa-chart-pie text-xl"></i>
                        </span>
                        <h3 class="mt-4 text-sm font-semibold text-gray-900">No analytics report yet</h3>
                        <p class="mt-1 max-w-md text-sm leading-6 text-gray-500">
                            This institution does not have a saved analytics result. The Update Analytics button is ready
                            for its function to be connected later.
                        </p>
                    </div>
                @endif
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div
                    class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                            <i class="fa-solid fa-boxes-stacked text-xs"></i>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-gray-800">ICT Inventory</h2>
                            <p class="text-xs text-gray-500">
                                {{ $institution->ictInventory->count() }}
                                {{ $institution->ictInventory->count() === 1 ? 'recorded item' : 'recorded items' }}
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('ict-inventory.create') }}?institution_id={{ $institution->id }}"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-blue-700">
                        <i class="fa-solid fa-plus"></i>
                        Add Item
                    </a>
                </div>

                @if ($institution->ictInventory->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[680px] text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-white text-left">
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Category
                                    </th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Condition
                                    </th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Acquired
                                    </th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Status
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                @foreach ($institution->ictInventory as $item)
                                    @php
                                        $conditionKey = strtolower((string) $item->condition);
                                        $conditionClasses = match ($conditionKey) {
                                            'excellent' => 'border-green-200 bg-green-50 text-green-700',
                                            'good' => 'border-blue-200 bg-blue-50 text-blue-700',
                                            'fair' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
                                            default => 'border-red-200 bg-red-50 text-red-700',
                                        };

                                        $statusKey = strtolower((string) $item->status);
                                        $statusClasses = match ($statusKey) {
                                            'active' => 'border-green-200 bg-green-50 text-green-700',
                                            'inactive' => 'border-gray-200 bg-gray-50 text-gray-600',
                                            'under repair' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
                                            default => 'border-red-200 bg-red-50 text-red-700',
                                        };
                                    @endphp

                                    <tr class="transition hover:bg-blue-50/30">
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $item->category }}</td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $conditionClasses }}">
                                                {{ $item->condition }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">{{ $item->acquisition_year ?: '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $statusClasses }}">
                                                {{ $item->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center px-4 py-12 text-center">
                        <span
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-50 text-gray-300">
                            <i class="fa-solid fa-box-open text-xl"></i>
                        </span>
                        <p class="mt-3 text-sm font-medium text-gray-500">No inventory items yet</p>
                        <p class="mt-1 text-xs text-gray-400">
                            Add ICT equipment records for this institution.
                        </p>
                    </div>
                @endif
            </section>
        </div>

        @include('institutions.show-modals')
    </div>
@endsection