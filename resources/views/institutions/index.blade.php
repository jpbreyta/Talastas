@extends('layouts.app')

@section('content')
<div class="flex flex-col h-full min-h-0 bg-gray-100">

    {{-- ── Page Header ──────────────────────────────────────────────── --}}
    <div class="flex-shrink-0 bg-blue-700 px-6 py-4">
        <div class="flex items-center justify-between">
            {{-- Title --}}
            <div class="flex items-center gap-3">
                <svg class="w-7 h-7 text-blue-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <div>
                    <h1 class="text-xl font-semibold text-white tracking-tight leading-tight">Institutions</h1>
                    <p class="text-blue-200 text-xs mt-0.5">Manage registered institutions and their ICT profiles</p>
                </div>
            </div>

            {{-- Right controls --}}
            <div class="flex items-center gap-3">

                {{-- Search --}}
                <div class="relative hidden sm:block">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-300 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                    </svg>
                    <input id="institutionSearch" type="text" placeholder="Search institutions…"
                           class="bg-blue-800 text-white placeholder-blue-300 text-sm pl-9 pr-4 py-2 rounded-lg border border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300 w-56 transition"/>
                </div>

                {{-- View toggle --}}
                <div class="flex items-center bg-blue-800 rounded-lg border border-blue-600 p-0.5 gap-0.5">
                    <button id="btnGrid" onclick="setView('grid')"
                            title="Grid view"
                            class="view-btn active-view p-1.5 rounded-md transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                    </button>
                    <button id="btnList" onclick="setView('list')"
                            title="List view"
                            class="view-btn p-1.5 rounded-md transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                    </button>
                </div>

                {{-- Column density (grid only) --}}
                <div id="colControls" class="flex items-center bg-blue-800 rounded-lg border border-blue-600 p-0.5 gap-0.5">
                    <button onclick="setCols(4)" data-cols="4" title="4 columns"
                            class="col-btn px-2 py-1.5 rounded-md text-xs font-medium text-blue-200 hover:bg-blue-700 hover:text-white transition">4</button>
                    <button onclick="setCols(5)" data-cols="5" title="5 columns"
                            class="col-btn active-cols px-2 py-1.5 rounded-md text-xs font-medium transition">5</button>
                    <button onclick="setCols(6)" data-cols="6" title="6 columns"
                            class="col-btn px-2 py-1.5 rounded-md text-xs font-medium text-blue-200 hover:bg-blue-700 hover:text-white transition">6</button>
                </div>

                {{-- Add button --}}
                <a href="{{ route('institutions.create') }}"
                   class="flex items-center gap-2 bg-white text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-50 font-medium text-sm shadow-sm transition border border-blue-100 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Institution
                </a>
            </div>
        </div>
    </div>

    {{-- ── Scrollable Content Area ───────────────────────────────────── --}}
    <div class="flex-1 min-h-0 overflow-y-auto px-5 py-4">

        @if($institutions->count() > 0)

        {{-- ── GRID VIEW ──────────────────────────────────────────────── --}}
        <div id="gridView"
             class="grid gap-4"
             style="grid-template-columns: repeat(var(--inst-cols, 5), minmax(0, 1fr));">

            @foreach($institutions as $institution)
            <div class="inst-card bg-white border border-gray-200 border-l-4 border-l-blue-600 rounded-xl shadow-sm hover:shadow-md transition duration-200 overflow-hidden flex flex-col">
                <div class="p-4 flex-1 flex flex-col">
                    {{-- Name + Pilot badge --}}
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900 leading-snug line-clamp-2">{{ $institution->name }}</h3>
                            <div class="flex items-center gap-1 mt-1 text-xs text-gray-500">
                                <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                {{ $institution->sector_type }}
                            </div>
                        </div>
                        @if($institution->is_pilot_site)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100 shrink-0">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Pilot
                        </span>
                        @endif
                    </div>

                    {{-- Meta --}}
                    <div class="space-y-1.5 mb-4 flex-1">
                        <div class="flex items-center gap-1.5 text-xs text-gray-500">
                            <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="truncate">{{ $institution->barangay->name }}</span>
                        </div>
                        @if($institution->student_population)
                        <div class="flex items-center gap-1.5 text-xs text-gray-500">
                            <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                            {{ number_format($institution->student_population) }} students
                        </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-1.5">
                        <a href="{{ route('institutions.show', $institution) }}"
                           class="flex-1 bg-blue-600 text-white px-2 py-1.5 rounded-lg hover:bg-blue-700 font-medium text-xs text-center transition flex items-center justify-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            View
                        </a>
                        <a href="{{ route('institutions.edit', $institution) }}"
                           class="flex-1 bg-white text-gray-700 px-2 py-1.5 rounded-lg hover:bg-gray-50 font-medium text-xs text-center transition flex items-center justify-center gap-1 border border-gray-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </a>
                        <form method="POST" action="{{ route('institutions.destroy', $institution) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    onclick="return confirm('Delete {{ addslashes($institution->name) }}?')"
                                    class="bg-gray-50 text-gray-400 px-2 py-1.5 rounded-lg hover:bg-red-50 hover:text-red-600 font-medium text-xs transition flex items-center justify-center border border-gray-200">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ── LIST VIEW ──────────────────────────────────────────────── --}}
        <div id="listView" class="hidden">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-left">
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Institution</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Sector</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Barangay</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Students</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($institutions as $institution)
                        <tr class="inst-card hover:bg-blue-50/40 transition group">
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-900">{{ $institution->name }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $institution->sector_type }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $institution->barangay->name }}</td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $institution->student_population ? number_format($institution->student_population) : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($institution->is_pilot_site)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Pilot
                                </span>
                                @else
                                <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('institutions.show', $institution) }}"
                                       class="text-blue-600 hover:text-blue-800 font-medium text-xs transition">View</a>
                                    <a href="{{ route('institutions.edit', $institution) }}"
                                       class="text-gray-600 hover:text-gray-900 font-medium text-xs transition">Edit</a>
                                    <form method="POST" action="{{ route('institutions.destroy', $institution) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                onclick="return confirm('Delete {{ addslashes($institution->name) }}?')"
                                                class="text-gray-400 hover:text-red-600 font-medium text-xs transition">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-5">
            {{ $institutions->links() }}
        </div>

        @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-blue-50 mb-4">
                <svg class="h-7 w-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-gray-900">No institutions yet</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by adding your first institution.</p>
            <div class="mt-5">
                <a href="{{ route('institutions.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Institution
                </a>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
    /* View toggle button states */
    .view-btn { color: #93c5fd; }
    .view-btn:hover { background-color: #1d4ed8; color: #fff; }
    .active-view { background-color: #1e40af; color: #fff; }

    /* Column density button states */
    .active-cols { background-color: #1e40af; color: #fff; }
    .col-btn:hover:not(.active-cols) { background-color: #1d4ed8; color: #fff; }

    /* CSS custom property drives the grid column count */
    #gridView { --inst-cols: 5; }
</style>

@endsection

@section('scripts')
<script>
(function () {
    // ── Persist preferences ──────────────────────────────────────────
    const PREF_VIEW = 'inst_view';
    const PREF_COLS = 'inst_cols';

    const gridView   = document.getElementById('gridView');
    const listView   = document.getElementById('listView');
    const colControls = document.getElementById('colControls');
    const searchInput = document.getElementById('institutionSearch');

    // ── View toggle ──────────────────────────────────────────────────
    window.setView = function (mode) {
        const isGrid = mode === 'grid';
        gridView.classList.toggle('hidden', !isGrid);
        listView.classList.toggle('hidden', isGrid);
        colControls.classList.toggle('hidden', !isGrid);

        document.getElementById('btnGrid').classList.toggle('active-view', isGrid);
        document.getElementById('btnList').classList.toggle('active-view', !isGrid);

        localStorage.setItem(PREF_VIEW, mode);
    };

    // ── Column density ───────────────────────────────────────────────
    window.setCols = function (n) {
        gridView.style.setProperty('--inst-cols', n);
        document.querySelectorAll('.col-btn').forEach(btn => {
            const active = parseInt(btn.dataset.cols) === n;
            btn.classList.toggle('active-cols', active);
            btn.classList.toggle('text-blue-200', !active);
        });
        localStorage.setItem(PREF_COLS, n);
    };

    // ── Client-side search ───────────────────────────────────────────
    searchInput.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.inst-card').forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = (!q || text.includes(q)) ? '' : 'none';
        });
    });

    // ── Restore preferences on load ──────────────────────────────────
    const savedView = localStorage.getItem(PREF_VIEW) || 'grid';
    const savedCols = parseInt(localStorage.getItem(PREF_COLS) || '5');

    setView(savedView);
    setCols(savedCols);
})();
</script>
@endsection
