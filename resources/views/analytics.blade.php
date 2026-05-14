@extends('layouts.app')

@section('content')
<div class="py-6 bg-gray-50 min-h-full">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Page Header --}}
        <div class="mb-6 flex flex-wrap justify-between items-start gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">ICT Gap Analytics</h1>
                <p class="text-sm text-gray-500 mt-0.5">Lipa City Infrastructure Assessment &amp; Decision Support</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm text-gray-500">
                    <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                    Updated {{ now()->format('M d, Y') }}
                </div>
                <button id="exportBtn"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export Report
                </button>
            </div>
        </div>

        {{-- Row 1: Summary stat cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

            {{-- Citywide Gap Score --}}
            <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Citywide Gap Score</span>
                    <span class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </span>
                </div>
                <p class="text-4xl font-bold text-gray-900 leading-none">
                    {{ number_format($averageGapScore ?? 0, 1) }}<span class="text-2xl font-semibold text-gray-500">%</span>
                </p>
                <p class="text-xs text-gray-400 mt-2">Overall infrastructure deficit</p>
            </div>

            {{-- Institutions --}}
            <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Institutions</span>
                    <span class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </span>
                </div>
                <p class="text-4xl font-bold text-gray-900 leading-none">{{ $institutionCount ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-2">Total mapped locations</p>
            </div>

            {{-- Critical Areas --}}
            <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Critical Areas</span>
                    <span class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </span>
                </div>
                <p class="text-4xl font-bold text-gray-900 leading-none">{{ $criticalAreas ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-2">Gap score &ge; 80%</p>
            </div>

            {{-- Needs Attention --}}
            <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Needs Attention</span>
                    <span class="w-8 h-8 rounded-lg bg-yellow-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </span>
                </div>
                <p class="text-4xl font-bold text-gray-900 leading-none">{{ $needsAttention ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-2">Gap score 50&ndash;79%</p>
            </div>
        </div>

        {{-- Row 2: Secondary stat cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

            {{-- Data Completeness --}}
            <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Data Completeness</span>
                    <span class="w-8 h-8 rounded-lg bg-cyan-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                </div>
                <p class="text-4xl font-bold text-gray-900 leading-none">
                    {{ number_format($averageCompleteness ?? 0, 0) }}<span class="text-2xl font-semibold text-gray-500">%</span>
                </p>
                <p class="text-xs text-gray-400 mt-2">Avg record quality</p>
            </div>

            {{-- Audits Pending --}}
            <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Audits Pending</span>
                    <span class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </span>
                </div>
                <p class="text-4xl font-bold text-gray-900 leading-none">{{ $needsAudit ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-2">Field inspection required</p>
            </div>

            {{-- Pilot Sites --}}
            <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Pilot Sites</span>
                    <span class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </span>
                </div>
                <p class="text-4xl font-bold text-gray-900 leading-none">{{ $pilotSites ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-2">Representative locations</p>
            </div>

            {{-- Missing Critical --}}
            <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Missing Critical</span>
                    <span class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                </div>
                <p class="text-4xl font-bold text-gray-900 leading-none">{{ $missingCritical ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-2">Needs verification</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4 mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 mr-1">Filters</span>

                <select id="barangayFilter"
                    class="px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition min-w-[160px]">
                    <option value="">All Barangays</option>
                    @foreach($barangays ?? [] as $barangay)
                        <option value="{{ $barangay }}">{{ $barangay }}</option>
                    @endforeach
                </select>

                <select id="sectorFilter"
                    class="px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition min-w-[140px]">
                    <option value="">All Sectors</option>
                    <option value="School">School</option>
                    <option value="Hospital">Hospital</option>
                    <option value="Government">Government</option>
                    <option value="Library">Library</option>
                    <option value="Community Center">Community Center</option>
                    <option value="Other">Other</option>
                </select>

                <select id="riskFilter"
                    class="px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition min-w-[150px]">
                    <option value="">All Risk Levels</option>
                    <option value="critical">Critical (&ge;80%)</option>
                    <option value="warning">Warning (50–79%)</option>
                    <option value="adequate">Adequate (&lt;50%)</option>
                </select>

                <button id="clearFilters"
                    class="ml-auto text-sm text-gray-400 hover:text-gray-600 transition-colors">
                    Clear filters
                </button>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

            {{-- Gap Score by Barangay --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-1">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Gap Score by Barangay</h3>
                        <p class="text-xs text-gray-400">Average ICT deficit per barangay</p>
                    </div>
                    <span class="text-xs font-medium text-gray-400 bg-gray-50 border border-gray-100 rounded px-2 py-1">Top 10</span>
                </div>
                <div class="mt-4" id="barangayBarChart">
                    @php
                        $topBarangays = collect($barangayScores ?? [])->sortByDesc(fn($v) => $v)->take(10);
                    @endphp
                    @if($topBarangays->isEmpty())
                        <p class="text-sm text-gray-400 text-center py-8">No barangay data available.</p>
                    @else
                        <div class="space-y-2.5">
                            @foreach($topBarangays as $bName => $bScore)
                                @php
                                    $barColor = $bScore >= 80 ? 'bg-red-500' : ($bScore >= 50 ? 'bg-yellow-400' : 'bg-green-500');
                                    $textColor = $bScore >= 80 ? 'text-red-600' : ($bScore >= 50 ? 'text-yellow-600' : 'text-green-600');
                                @endphp
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-gray-500 w-32 truncate flex-shrink-0" title="{{ $bName }}">{{ $bName }}</span>
                                    <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                                        <div class="h-2 rounded-full {{ $barColor }} transition-all" style="width: {{ min(100, $bScore) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold {{ $textColor }} w-10 text-right flex-shrink-0">{{ number_format($bScore, 0) }}%</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Risk Distribution Donut --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="mb-1">
                    <h3 class="text-sm font-semibold text-gray-800">Risk Distribution</h3>
                    <p class="text-xs text-gray-400">Institutions by gap severity</p>
                </div>
                <div class="flex items-center justify-center gap-8 mt-4">
                    {{-- SVG Donut --}}
                    @php
                        $total = ($criticalAreas ?? 0) + ($needsAttention ?? 0) + max(0, ($institutionCount ?? 0) - ($criticalAreas ?? 0) - ($needsAttention ?? 0));
                        $adequate = max(0, ($institutionCount ?? 0) - ($criticalAreas ?? 0) - ($needsAttention ?? 0));
                        $r = 54; $cx = 64; $cy = 64; $circ = 2 * M_PI * $r;
                        $critPct  = $total > 0 ? ($criticalAreas  / $total) : 0;
                        $warnPct  = $total > 0 ? ($needsAttention / $total) : 0;
                        $adqPct   = $total > 0 ? ($adequate        / $total) : 1;
                        $critDash = $critPct  * $circ;
                        $warnDash = $warnPct  * $circ;
                        $adqDash  = $adqPct   * $circ;
                        $critOff  = 0;
                        $warnOff  = -$critDash;
                        $adqOff   = -($critDash + $warnDash);
                    @endphp
                    <svg width="128" height="128" viewBox="0 0 128 128" class="flex-shrink-0">
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none" stroke="#f3f4f6" stroke-width="16"/>
                        @if($total > 0)
                            {{-- Adequate (green) --}}
                            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none"
                                stroke="#22c55e" stroke-width="16"
                                stroke-dasharray="{{ $adqDash }} {{ $circ - $adqDash }}"
                                stroke-dashoffset="{{ $adqOff }}"
                                transform="rotate(-90 {{ $cx }} {{ $cy }})"/>
                            {{-- Warning (amber) --}}
                            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none"
                                stroke="#f59e0b" stroke-width="16"
                                stroke-dasharray="{{ $warnDash }} {{ $circ - $warnDash }}"
                                stroke-dashoffset="{{ $warnOff }}"
                                transform="rotate(-90 {{ $cx }} {{ $cy }})"/>
                            {{-- Critical (red) --}}
                            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none"
                                stroke="#ef4444" stroke-width="16"
                                stroke-dasharray="{{ $critDash }} {{ $circ - $critDash }}"
                                stroke-dashoffset="{{ $critOff }}"
                                transform="rotate(-90 {{ $cx }} {{ $cy }})"/>
                        @endif
                        <text x="{{ $cx }}" y="{{ $cy - 6 }}" text-anchor="middle" class="text-2xl font-bold" font-size="22" font-weight="700" fill="#111827">{{ $total }}</text>
                        <text x="{{ $cx }}" y="{{ $cy + 14 }}" text-anchor="middle" font-size="10" fill="#9ca3af">total</text>
                    </svg>
                    <div class="space-y-3">
                        <div class="flex items-center gap-2.5">
                            <span class="w-3 h-3 rounded-full bg-red-500 flex-shrink-0"></span>
                            <span class="text-sm text-gray-600">Critical</span>
                            <span class="ml-auto text-sm font-semibold text-gray-800">{{ $criticalAreas ?? 0 }}</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="w-3 h-3 rounded-full bg-yellow-400 flex-shrink-0"></span>
                            <span class="text-sm text-gray-600">Warning</span>
                            <span class="ml-auto text-sm font-semibold text-gray-800">{{ $needsAttention ?? 0 }}</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="w-3 h-3 rounded-full bg-green-500 flex-shrink-0"></span>
                            <span class="text-sm text-gray-600">Adequate</span>
                            <span class="ml-auto text-sm font-semibold text-gray-800">{{ $adequate }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Barangay Gap Heatmap --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Barangay Gap Heatmap</h3>
                    <p class="text-xs text-gray-400">Average gap score per barangay — red = critical, amber = warning, green = adequate</p>
                </div>
                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>&ge;80%</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-yellow-400 inline-block"></span>50-79%</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>&lt;60%</span>
                </div>
            </div>
            @if(empty($barangayScores) || count($barangayScores) === 0)
                <p class="text-sm text-gray-400 text-center py-6">No heatmap data available.</p>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                    @foreach($barangayScores as $bName => $bScore)
                        @php
                            $bgClass   = $bScore >= 80 ? 'bg-red-50 border-red-200'    : ($bScore >= 50 ? 'bg-yellow-50 border-yellow-200'    : 'bg-green-50 border-green-200');
                            $textClass = $bScore >= 80 ? 'text-red-700'                : ($bScore >= 50 ? 'text-yellow-700'                : 'text-green-700');
                            $barClass  = $bScore >= 80 ? 'bg-red-400'                  : ($bScore >= 50 ? 'bg-yellow-400'                  : 'bg-green-400');
                        @endphp
                        <div class="rounded-lg border {{ $bgClass }} p-3">
                            <p class="text-[10px] text-gray-500 uppercase truncate" title="{{ $bName }}">{{ $bName }}</p>
                            <p class="text-lg font-bold {{ $textClass }} leading-tight mt-0.5">{{ number_format($bScore, 0) }}%</p>
                            <div class="mt-1.5 h-1 rounded-full bg-gray-200 overflow-hidden">
                                <div class="h-full {{ $barClass }}" style="width: {{ min(100, $bScore) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Institution Analytics Table --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Institution Analytics</h3>
                    <p class="text-xs text-gray-400">Sorted by gap score — highest first</p>
                </div>
                <span class="text-xs text-gray-400 bg-gray-50 border border-gray-100 rounded px-2 py-1">
                    {{ $analytics->total() ?? 0 }} records
                </span>
            </div>

            <div class="overflow-x-auto">
                <table id="analyticsTable" class="min-w-full divide-y divide-gray-100">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Institution</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Barangay</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Sector</th>
                            <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">Pilot</th>
                            <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">Quality</th>
                            <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">Gap Score</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Recommendation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50" id="analyticsTableBody">
                        @forelse($analytics ?? [] as $analytic)
                        @php
                            $gap = $analytic->gap_score ?? 0;
                            $gapColor = $gap >= 80 ? 'text-red-600' : ($gap >= 50 ? 'text-yellow-600' : 'text-green-600');
                            $gapBar   = $gap >= 80 ? 'bg-red-500'   : ($gap >= 50 ? 'bg-yellow-400'   : 'bg-green-500');
                            $completeness = $analytic->institution->data_completeness_score ?? 0;
                            $qClass = $completeness >= 80 ? 'bg-green-100 text-green-700' : ($completeness >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
                            $sector = $analytic->institution->sector_type ?? $analytic->institution->type ?? 'N/A';
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors analytics-row"
                            data-barangay="{{ $analytic->institution->barangay_name ?? '' }}"
                            data-sector="{{ $sector }}"
                            data-risk="{{ $gap >= 80 ? 'critical' : ($gap >= 50 ? 'warning' : 'adequate') }}">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">
                                {{ $analytic->institution->name ?? 'N/A' }}
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-500 whitespace-nowrap">
                                {{ Str::limit($analytic->institution->barangay_name ?? 'N/A', 16) }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 text-xs rounded-md bg-blue-50 text-blue-700 font-medium">
                                    {{ Str::limit($sector, 12) }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                @if($analytic->institution->is_pilot_site ?? false)
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700 font-semibold">PILOT</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $qClass }}">
                                    {{ $completeness }}%
                                </span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-14 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full {{ $gapBar }}" style="width: {{ min(100, $gap) }}%"></div>
                                    </div>
                                    <span class="text-sm font-bold {{ $gapColor }} w-12 text-right">
                                        {{ number_format($gap, 1) }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 max-w-xs">
                                <span class="truncate block" title="{{ $analytic->policy_recommendation ?? '' }}">
                                    {{ Str::limit($analytic->policy_recommendation ?? 'No recommendations', 65) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">
                                No analytics data available.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($analytics, 'links'))
                <div class="mt-4 border-t border-gray-100 pt-4">
                    {{ $analytics->links() }}
                </div>
            @endif
        </div>

        {{-- Scope & Limitations --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs text-gray-500 leading-relaxed">
                    <strong class="text-gray-700">Scope &amp; Limitations</strong> &mdash;
                    Data covers public sector institutions (schools, barangay halls, city offices) only.
                    System is in <strong>pilot testing phase</strong> with representative sites.
                    No real-time IoT monitoring — regular field audits are required for data integrity.
                    <a href="{{ route('home') }}" class="text-blue-500 hover:underline ml-1">View full scope &rarr;</a>
                </p>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const barangayFilter = document.getElementById('barangayFilter');
    const sectorFilter   = document.getElementById('sectorFilter');
    const riskFilter     = document.getElementById('riskFilter');
    const clearBtn       = document.getElementById('clearFilters');
    const rows           = document.querySelectorAll('.analytics-row');

    function applyFilters() {
        const bVal = barangayFilter.value.toLowerCase();
        const sVal = sectorFilter.value.toLowerCase();
        const rVal = riskFilter.value.toLowerCase();

        rows.forEach(row => {
            const rowBarangay = (row.dataset.barangay || '').toLowerCase();
            const rowSector   = (row.dataset.sector   || '').toLowerCase();
            const rowRisk     = (row.dataset.risk      || '').toLowerCase();

            const bMatch = !bVal || rowBarangay === bVal;
            const sMatch = !sVal || rowSector.includes(sVal);
            const rMatch = !rVal || rowRisk === rVal;

            row.style.display = (bMatch && sMatch && rMatch) ? '' : 'none';
        });
    }

    barangayFilter.addEventListener('change', applyFilters);
    sectorFilter.addEventListener('change', applyFilters);
    riskFilter.addEventListener('change', applyFilters);

    clearBtn.addEventListener('click', () => {
        barangayFilter.value = '';
        sectorFilter.value   = '';
        riskFilter.value     = '';
        applyFilters();
    });

    document.getElementById('exportBtn').addEventListener('click', () => {
        window.open('/api/v1/analytics/export', '_blank');
    });
});
</script>
@endsection
