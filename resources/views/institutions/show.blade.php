@extends('layouts.app')

@section('content')
<div class="flex flex-col h-full min-h-0 bg-gray-100">

    {{-- ── Fixed Page Header ────────────────────────────────────────── --}}
    <div class="flex-shrink-0 bg-blue-700 px-6 py-4">
        <div class="flex items-center justify-between gap-4">
            {{-- Back + Title --}}
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('institutions.index') }}"
                   class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-600 hover:bg-blue-500 text-white transition shrink-0"
                   title="Back to list">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-lg font-semibold text-white leading-tight truncate">{{ $institution->name }}</h1>
                        @if($institution->is_pilot_site)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-400/20 text-green-200 border border-green-400/30 shrink-0">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Pilot Site
                        </span>
                        @endif
                        @if($institution->needs_maintenance)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-red-400/20 text-red-200 border border-red-400/30 shrink-0">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Needs Maintenance
                        </span>
                        @endif
                    </div>
                    <p class="text-blue-200 text-xs mt-0.5">{{ $institution->sector_type }} &bull; {{ $institution->barangay_name }}</p>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('institutions.edit', $institution) }}"
                   class="flex items-center gap-2 bg-white text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-50 font-medium text-sm shadow-sm transition border border-blue-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
                <form method="POST" action="{{ route('institutions.destroy', $institution) }}" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            onclick="return confirm('Delete {{ addslashes($institution->name) }}? This cannot be undone.')"
                            class="flex items-center gap-2 bg-red-600/20 text-red-200 border border-red-400/30 px-4 py-2 rounded-lg hover:bg-red-600 hover:text-white font-medium text-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Scrollable Body ───────────────────────────────────────────── --}}
    <div class="flex-1 min-h-0 overflow-y-auto px-6 py-5">

        {{-- Flash message --}}
        @if(session('success'))
        <div class="mb-4 flex items-center gap-2 bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3 rounded-lg">
            <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        {{-- ── Top row: 3 stat cards ──────────────────────────────────── --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            {{-- Data completeness --}}
            @php $score = $institution->data_completeness_score ?? 0; @endphp
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Data Completeness</p>
                    <p class="text-xl font-bold text-gray-900">{{ $score }}<span class="text-sm font-normal text-gray-400">%</span></p>
                </div>
            </div>

            {{-- Students --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Students</p>
                    <p class="text-xl font-bold text-gray-900">{{ $institution->student_population ? number_format($institution->student_population) : '—' }}</p>
                </div>
            </div>

            {{-- Staff --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Staff</p>
                    <p class="text-xl font-bold text-gray-900">{{ $institution->staff_population ? number_format($institution->staff_population) : '—' }}</p>
                </div>
            </div>

            {{-- Internet speed --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Internet Speed</p>
                    <p class="text-xl font-bold text-gray-900">{{ $institution->internet_speed ? $institution->internet_speed : '—' }}<span class="text-sm font-normal text-gray-400">{{ $institution->internet_speed ? ' Mbps' : '' }}</span></p>
                </div>
            </div>
        </div>

        {{-- ── Main 2-col layout ──────────────────────────────────────── --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-5">

            {{-- Left col: Basic info + Location --}}
            <div class="xl:col-span-1 space-y-5">

                {{-- Basic Information --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-6 h-6 rounded bg-blue-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-800">Basic Information</h2>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <x-detail-row label="Sector Type" :value="$institution->sector_type"/>
                        <x-detail-row label="Barangay" :value="$institution->barangay_name"/>
                        <x-detail-row label="ISP Provider" :value="$institution->isp_provider ?? '—'"/>
                        <x-detail-row label="Computers" :value="$institution->number_of_computers ? $institution->number_of_computers . ' units' : '—'"/>
                    </div>
                </div>

                {{-- Location --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-6 h-6 rounded bg-emerald-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-800">Location</h2>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <x-detail-row label="Latitude" :value="$institution->latitude"/>
                        <x-detail-row label="Longitude" :value="$institution->longitude"/>
                    </div>
                    {{-- Mini map link --}}
                    <div class="px-4 py-3 bg-gray-50">
                        <a href="https://www.google.com/maps?q={{ $institution->latitude }},{{ $institution->longitude }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 font-medium transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Open in Google Maps
                        </a>
                    </div>
                </div>
            </div>

            {{-- Right col: ICT Facilities + Status + Notes --}}
            <div class="xl:col-span-2 space-y-5">

                {{-- ICT Facilities --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-6 h-6 rounded bg-purple-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-800">ICT Facilities</h2>
                    </div>
                    <div class="p-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <x-facility-badge label="Computer Lab"        :active="$institution->has_computer_lab"/>
                        <x-facility-badge label="Smart TV"            :active="$institution->has_smart_tv"/>
                        <x-facility-badge label="Proj. Whiteboard"    :active="$institution->has_projected_whiteboard"/>
                        <x-facility-badge label="PA System"           :active="$institution->has_public_address_system"/>
                    </div>
                </div>

                {{-- Status flags --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-6 h-6 rounded bg-orange-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-800">Status</h2>
                    </div>
                    <div class="p-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <x-status-badge label="Pilot Site"       :active="$institution->is_pilot_site"      activeColor="green"/>
                        <x-status-badge label="Active"           :active="$institution->is_active"          activeColor="green"/>
                        <x-status-badge label="Needs Maintenance":active="$institution->needs_maintenance"  activeColor="red"/>
                        <x-status-badge label="Requires Audit"   :active="$institution->requires_audit"     activeColor="yellow"/>
                    </div>
                </div>

                {{-- Notes --}}
                @if($institution->notes)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-6 h-6 rounded bg-gray-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-800">Notes</h2>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ $institution->notes }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- ── ICT Inventory Table ────────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded bg-blue-100 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <h2 class="text-sm font-semibold text-gray-800">ICT Inventory</h2>
                    <span class="text-xs text-gray-400 font-normal">({{ $institution->ictInventory->count() }} items)</span>
                </div>
                <a href="{{ route('ict-inventory.create') }}?institution_id={{ $institution->id }}"
                   class="flex items-center gap-1.5 bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 font-medium text-xs transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Item
                </a>
            </div>

            @if($institution->ictInventory->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-left">
                            <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Category</th>
                            <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Condition</th>
                            <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Acquired</th>
                            <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($institution->ictInventory as $item)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $item->category }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    @if($item->condition == 'Excellent') bg-green-100 text-green-800
                                    @elseif($item->condition == 'Good') bg-blue-100 text-blue-800
                                    @elseif($item->condition == 'Fair') bg-yellow-100 text-yellow-800
                                    @else bg-red-100 text-red-800 @endif">
                                    {{ $item->condition }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $item->acquisition_year }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    @if($item->status == 'Active') bg-green-100 text-green-800
                                    @elseif($item->status == 'Inactive') bg-gray-100 text-gray-600
                                    @elseif($item->status == 'Under Repair') bg-yellow-100 text-yellow-800
                                    @else bg-red-100 text-red-800 @endif">
                                    {{ $item->status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <svg class="w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm font-medium text-gray-500">No inventory items yet</p>
                <p class="text-xs text-gray-400 mt-1">Add ICT equipment records for this institution.</p>
            </div>
            @endif
        </div>

    </div>{{-- end scrollable body --}}
</div>
@endsection
