@extends('layouts.app')

@section('dashboard-layout')@endsection

@section('content')
<div class="flex flex-col h-full min-h-0 bg-gray-100">

    {{-- ── Page Header ──────────────────────────────────────────────── --}}
    <div class="flex-shrink-0 bg-blue-700 px-6 py-3">
        <div class="flex items-center justify-between">

            {{-- Title --}}
            <div class="flex items-center gap-3">
                <svg class="w-7 h-7 text-blue-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                </svg>
                <div>
                    <h1 class="text-xl font-semibold text-white tracking-tight leading-tight">ICT Inventory</h1>
                    <p class="text-blue-200 text-xs mt-0.5">Manage ICT assets and equipment across institutions</p>
                </div>
            </div>

            {{-- Right controls --}}
            <div class="flex items-center gap-2 sm:gap-3 flex-wrap justify-end">

                {{-- Search --}}
                <div class="relative hidden sm:block">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-300 pointer-events-none"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                    </svg>
                    <input id="inventorySearch" type="text" placeholder="Search inventory…"
                           class="bg-blue-800 text-white placeholder-blue-300 text-sm pl-9 pr-4 py-2 rounded-lg border border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300 w-56 transition"/>
                </div>

                {{-- Status filter --}}
                <select id="statusFilter"
                        class="bg-blue-800 text-white text-sm px-3 py-2 rounded-lg border border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300 transition cursor-pointer">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="retired">Retired</option>
                </select>

                {{-- Add button --}}
                <a href="{{ route('ict-inventory.create') }}"
                   class="flex items-center gap-2 bg-white text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-50 font-medium text-sm shadow-sm transition border border-blue-100 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Item
                </a>
            </div>
        </div>
    </div>

    {{-- ── Content Area ─────────────────────────────────────────────── --}}
    <div class="flex-1 min-h-0 flex flex-col">

        {{-- Flash message --}}
        @if(session('success'))
        <div class="flex-shrink-0 mx-5 mt-3 flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

@if($inventories->count() > 0)

        {{-- ── TABLE VIEW ─────────────────────────────────────────────── --}}
        <div class="flex-1 min-h-0 px-5 pt-3 pb-1 flex flex-col min-w-0">
            <div class="flex flex-col bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden min-h-0 flex-1">
                {{-- Fixed max height table with sticky header --}}
                <div class="overflow-auto max-h-[calc(100vh-200px)] min-h-0 max-w-full">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-gray-50 border-b border-gray-200 text-left">
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide w-10">#</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Institution</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Category</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide whitespace-nowrap">Condition</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide whitespace-nowrap">Year Acquired</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide whitespace-nowrap">Status</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide text-right whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($inventories as $inventory)
                        @php
                            $conditionMap = [
                                'good'      => ['bg-green-50 text-green-700 border-green-200',    'Good'],
                                'fair'      => ['bg-yellow-50 text-yellow-700 border-yellow-200', 'Fair'],
                                'poor'      => ['bg-red-50 text-red-700 border-red-200',           'Poor'],
                                'excellent' => ['bg-blue-50 text-blue-700 border-blue-200',        'Excellent'],
                            ];
                            $condKey   = strtolower($inventory->condition);
                            $condStyle = $conditionMap[$condKey] ?? ['bg-gray-100 text-gray-600 border-gray-200', ucfirst($inventory->condition)];
                        @endphp
                        <tr class="inv-row hover:bg-blue-50/40 transition"
                            data-search="{{ strtolower($inventory->institution->name ?? '') }} {{ strtolower($inventory->category) }} {{ strtolower($inventory->condition) }}"
                            data-status="{{ $inventory->status }}">

                            <td class="px-2 sm:px-4 py-2 sm:py-3 text-gray-400 text-xs font-mono">
                                {{ $inventories->firstItem() + $loop->index }}
                            </td>

                            <td class="px-2 sm:px-4 py-2 sm:py-3">
                                <div class="flex items-center gap-1.5 sm:gap-2">
                                    <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center shrink-0">
                                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <span class="font-medium text-gray-900 text-xs sm:text-sm truncate">{{ $inventory->institution->name ?? '—' }}</span>
                                </div>
                            </td>

                            <td class="px-2 sm:px-4 py-2 sm:py-3">
                                <span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200 truncate">
                                    {{ $inventory->category }}
                                </span>
                            </td>

                            <td class="px-2 sm:px-4 py-2 sm:py-3">
                                <span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded text-xs font-medium border {{ $condStyle[0] }}">
                                    {{ $condStyle[1] }}
                                </span>
                            </td>

                            <td class="px-2 sm:px-4 py-2 sm:py-3 text-gray-500 tabular-nums text-xs sm:text-sm">
                                {{ $inventory->acquisition_year }}
                            </td>

                            <td class="px-2 sm:px-4 py-2 sm:py-3">
                                @if($inventory->status === 'active')
                                <span class="inline-flex items-center gap-1 px-1.5 sm:px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                    <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 rounded-full bg-blue-500 inline-block"></span>
                                    <span class="hidden sm:inline">Active</span>
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 px-1.5 sm:px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200">
                                    <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 rounded-full bg-gray-400 inline-block"></span>
                                    <span class="hidden sm:inline">Retired</span>
                                </span>
                                @endif
                            </td>

                            <td class="px-2 sm:px-4 py-2 sm:py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('ict-inventory.show', $inventory) }}"
                                       class="bg-blue-600 text-white px-1.5 sm:px-2.5 py-1 rounded-lg hover:bg-blue-700 font-medium text-xs transition flex items-center gap-0.5 sm:gap-1">
                                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span class="hidden sm:inline">View</span>
                                    </a>
                                    <a href="{{ route('ict-inventory.edit', $inventory) }}"
                                       class="bg-white text-gray-700 px-1.5 sm:px-2.5 py-1 rounded-lg hover:bg-gray-50 font-medium text-xs transition flex items-center gap-0.5 sm:gap-1 border border-gray-200">
                                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        <span class="hidden sm:inline">Edit</span>
                                    </a>
                                    <form method="POST" action="{{ route('ict-inventory.destroy', $inventory) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                onclick="return confirm('Delete this inventory item?')"
                                                class="bg-gray-50 text-gray-400 px-1.5 sm:px-2.5 py-1 rounded-lg hover:bg-red-50 hover:text-red-600 font-medium text-xs transition flex items-center justify-center border border-gray-200">
                                            <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                </div>{{-- /scrollable table wrapper --}}

                {{-- Empty filter state --}}
                <div id="noResults" class="hidden py-12 text-center">
                    <svg class="mx-auto h-8 w-8 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                    </svg>
                    <p class="text-sm text-gray-500">No items match your search.</p>
                </div>
            </div>{{-- /card --}}
        </div>{{-- /table view wrapper --}}

        {{-- Pagination --}}
        <div class="flex-shrink-0 px-4 py-2 border-t border-gray-200 bg-white overflow-x-auto">
            <div class="min-w-0">
                {{ $inventories->links() }}
            </div>
        </div>

        @else
        {{-- ── Empty state (no data at all) ───────────────────────────── --}}
        <div class="flex-1 flex flex-col items-center justify-center text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-blue-50 mb-4">
                <svg class="h-7 w-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-gray-900">No inventory items yet</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by adding your first ICT asset.</p>
            <div class="mt-5">
                <a href="{{ route('ict-inventory.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Item
                </a>
            </div>
        </div>
        @endif

    </div>{{-- /content area --}}
</div>{{-- /page wrapper --}}

@endsection

@section('scripts')
<script>
(function () {
    const searchInput = document.getElementById('inventorySearch');
    const statusSel   = document.getElementById('statusFilter');

    function applyFilters() {
        const q      = (searchInput?.value ?? '').toLowerCase().trim();
        const status = (statusSel?.value   ?? '').toLowerCase();
        let visible  = 0;

        document.querySelectorAll('.inv-row').forEach(row => {
            const ok = (!q      || row.dataset.search.includes(q))
                    && (!status || row.dataset.status === status);
            row.style.display = ok ? '' : 'none';
            if (ok) visible++;
        });

        document.getElementById('noResults')?.classList.toggle('hidden', visible > 0);
    }

    searchInput?.addEventListener('input',  applyFilters);
    statusSel?.addEventListener('change',   applyFilters);
})();
</script>
@endsection
