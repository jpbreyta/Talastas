@extends('layouts.app')

@section('dashboard-layout')@endsection

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

    {{-- ── Content Area ─────────────────────────────────────────────── --}}
    <div class="flex-1 min-h-0 flex flex-col">

        @if($institutions->count() > 0)

        {{-- ── TABLE VIEW ─────────────────────────────────────────────── --}}
        <div class="flex-1 min-h-0 overflow-y-auto px-5 pt-3 pb-1">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-gray-50 border-b border-gray-200 text-left">
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Institution</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Sector</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Barangay</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Population</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($institutions as $institution)
                        <tr class="inst-row hover:bg-blue-50/40 transition">
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-900">{{ $institution->name }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $institution->type }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $institution->barangay->name }}</td>
                            <td class="px-4 py-3 text-gray-500">
                                {{
                                    $institution->type === 'School'
                                        ? ($institution->student_population
                                            ? number_format($institution->student_population)
                                            : '—')
                                        : ($institution->staff_population
                                            ? number_format($institution->staff_population)
                                            : '—')
                                }}
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

                {{-- Empty filter state --}}
                <div id="noResults" class="hidden py-12 text-center">
                    <svg class="mx-auto h-8 w-8 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                    </svg>
                    <p class="text-sm text-gray-500">No institutions match your search.</p>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="flex-shrink-0 px-5 py-2 border-t border-gray-200 bg-white">
            {{ $institutions->links() }}
        </div>

        @else
        {{-- Empty state --}}
        <div class="flex-1 flex flex-col items-center justify-center text-center">
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
    </div>{{-- /content area --}}
</div>{{-- /page wrapper --}}

@endsection

@section('scripts')
<script>
(function () {
    const searchInput = document.getElementById('institutionSearch');

    searchInput?.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        let visible = 0;

        document.querySelectorAll('.inst-row').forEach(row => {
            const match = !q || row.textContent.toLowerCase().includes(q);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        document.getElementById('noResults')?.classList.toggle('hidden', visible > 0);
    });
})();
</script>
@endsection