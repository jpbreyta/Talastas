@extends('layouts.app')

@section('dashboard-layout')
@endsection

@section('content')
    <div class="flex flex-col h-full min-h-0 bg-gray-100">

        {{-- ── Page Header ──────────────────────────────────────────────── --}}
        <div class="flex-shrink-0 bg-blue-700 px-6 py-4">
            <div class="flex items-center justify-between">
                {{-- Title --}}
                <div class="flex items-center gap-3">
                    <svg class="w-7 h-7 text-blue-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
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
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-300 pointer-events-none"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                        </svg>
                        <input id="institutionSearch" type="text" placeholder="Search institutions…"
                            class="bg-blue-800 text-white placeholder-blue-300 text-sm pl-9 pr-4 py-2 rounded-lg border border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300 w-56 transition" />
                    </div>

                    {{-- Add button --}}
                    <button type="button" id="openAddModal"
                        class="flex items-center gap-2 bg-white text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-50 font-medium text-sm shadow-sm transition border border-blue-100 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Institution
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Content Area ─────────────────────────────────────────────── --}}
        <div class="flex-1 min-h-0 flex flex-col">

            @if ($institutions->count() > 0)
                {{-- ── TABLE VIEW ─────────────────────────────────────────────── --}}
                <div class="flex-1 min-h-0 overflow-y-auto px-5 pt-3 pb-1">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr class="bg-gray-50 border-b border-gray-200 text-left">
                                    <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">
                                        Institution</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Sector
                                    </th>
                                    <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">
                                        Barangay</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">
                                        Population</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Status
                                    </th>
                                    <th
                                        class="px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide text-right">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($institutions as $institution)
                                    <tr class="inst-row hover:bg-blue-50/40 transition">
                                        <td class="px-4 py-3">
                                            <span class="font-medium text-gray-900">{{ $institution->name }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">{{ $institution->type }}</td>
                                        <td class="px-4 py-3 text-gray-500">{{ $institution->barangay->name }}</td>
                                        <td class="px-4 py-3 text-gray-500">
                                            {{ $institution->type === 'School'
                                                ? ($institution->student_population
                                                    ? number_format($institution->student_population)
                                                    : '—')
                                                : ($institution->staff_population
                                                    ? number_format($institution->staff_population)
                                                    : '—') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($institution->is_pilot_site)
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                            clip-rule="evenodd" />
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
                                                <form method="POST"
                                                    action="{{ route('institutions.destroy', $institution) }}"
                                                    class="inline">
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
                            <svg class="mx-auto h-8 w-8 text-gray-300 mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
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
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900">No institutions yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by adding your first institution.</p>
                    <div class="mt-5">
                        <button type="button" id="openAddModalEmpty"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Institution
                        </button>
                    </div>
                </div>
            @endif
        </div>{{-- /content area --}}

        {{-- Add Institution Modal --}}
        <div id="addInstitutionModal" class="hidden fixed inset-0 z-[3000] overflow-y-auto" aria-hidden="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-gray-900/50 transition-opacity" id="modalOverlay"></div>

                <div class="relative bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col">
                    <div class="flex-shrink-0 bg-blue-700 px-6 py-4 rounded-t-xl">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-white">Add New Institution</h2>
                            <button type="button" id="closeAddModal" class="text-blue-200 hover:text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('institutions.store') }}" id="addInstitutionForm"
                        class="flex-1 min-h-0 overflow-y-auto">
                        @csrf
                        <div class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label for="modalName"
                                        class="block text-xs font-semibold text-gray-600 mb-1.5">Institution Name <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div class="flex gap-2">
                                            <input type="text" name="name" id="modalName" required
                                                class="form-input flex-1"
                                                placeholder="e.g. Lipa City National High School">
                                            <button type="button" id="geocodeSearchBtn"
                                                class="px-3 py-2 text-sm font-medium rounded-lg text-blue-700 bg-blue-50 border border-blue-200 hover:bg-blue-100">
                                                Search
                                            </button>
                                        </div>
                                        <div id="searchResultsDropdown"
                                            class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label for="modalType" class="block text-xs font-semibold text-gray-600 mb-1.5">Sector
                                        Type <span class="text-red-500">*</span></label>
                                    <select name="type" id="modalType" required class="form-input w-full">
                                        @foreach (['School', 'Barangay Hall', 'Local Government Office'] as $type)
                                            <option value="{{ $type }}"
                                                {{ $type == 'School' ? 'selected' : '' }}>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="modalBarangay"
                                        class="block text-xs font-semibold text-gray-600 mb-1.5">Barangay <span
                                            class="text-red-500">*</span></label>
                                    <select name="barangay_id" id="modalBarangay" required class="form-input w-full">
                                        <option value="">Select Barangay</option>
                                        @foreach (\App\Models\Barangay::orderBy('name')->get() as $barangay)
                                            <option value="{{ $barangay->id }}">{{ $barangay->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="modal-population-field" data-type="school">
                                    <label for="modalStudentPop"
                                        class="block text-xs font-semibold text-gray-600 mb-1.5">Student Population <span
                                            class="text-red-500">*</span></label>
                                    <input type="number" name="student_population" id="modalStudentPop" min="0"
                                        class="form-input w-full" placeholder="e.g. 1200">
                                </div>
                                <div class="modal-population-field hidden" data-type="other">
                                    <label for="modalStaffPop"
                                        class="block text-xs font-semibold text-gray-600 mb-1.5">Staff Population <span
                                            class="text-red-500">*</span></label>
                                    <input type="number" name="staff_population" id="modalStaffPop" min="0"
                                        class="form-input w-full" placeholder="e.g. 45">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-2">Location Preview <span
                                        class="text-gray-400">(auto-geocoded on save)</span></label>

                                <div id="mapPickerContainer"
                                    class="relative h-64 rounded-lg border border-gray-300 overflow-hidden bg-gray-50">
                                    <div id="mapPicker" class="w-full h-full"></div>
                                </div>

                                <input type="hidden" name="latitude" id="modalLatitude">
                                <input type="hidden" name="longitude" id="modalLongitude">
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox"
                                        name="has_computer_lab" class="rounded"> Computer Lab</label>
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="has_smart_tv"
                                        class="rounded"> Smart TV</label>
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox"
                                        name="has_projected_whiteboard" class="rounded"> Whiteboard</label>
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox"
                                        name="has_public_address_system" class="rounded"> PA System</label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="modalComputers"
                                        class="block text-xs font-semibold text-gray-600 mb-1.5">Number of
                                        Computers</label>
                                    <input type="number" name="number_of_computers" id="modalComputers" min="0"
                                        class="form-input w-full" placeholder="e.g. 30">
                                </div>
                                <div>
                                    <label for="modalInternet"
                                        class="block text-xs font-semibold text-gray-600 mb-1.5">Internet Speed
                                        (Mbps)</label>
                                    <input type="number" step="0.1" name="internet_speed" id="modalInternet"
                                        min="0" class="form-input w-full" placeholder="e.g. 100">
                                </div>
                                <div>
                                    <label for="modalIsp" class="block text-xs font-semibold text-gray-600 mb-1.5">ISP
                                        Provider</label>
                                    <input type="text" name="isp_provider" id="modalIsp" class="form-input w-full"
                                        placeholder="e.g. PLDT, Globe">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox"
                                        name="is_pilot_site" checked class="rounded"> Pilot Site</label>
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active"
                                        checked class="rounded"> Active</label>
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox"
                                        name="needs_maintenance" class="rounded"> Needs Maintenance</label>
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox"
                                        name="requires_audit" checked class="rounded"> Requires Audit</label>
                            </div>

                            <div>
                                <label for="modalNotes" class="block text-xs font-semibold text-gray-600 mb-1.5">Notes
                                    (optional)</label>
                                <textarea name="notes" id="modalNotes" rows="2" class="form-input w-full resize-none"
                                    placeholder="Additional notes..."></textarea>
                            </div>
                        </div>
                    </form>

                    <div class="flex-shrink-0 px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                        <button type="button" id="cancelAddModal"
                            class="px-4 py-2 text-sm font-medium rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Cancel</button>
                        <button type="submit" form="addInstitutionForm"
                            class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Create Institution
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>{{-- /page wrapper --}}

    <style>
        .form-input {
            @apply block border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 transition;
        }

        select.form-input {
            @apply pr-8;
        }
    </style>

@endsection

@section('scripts')
    <script>
        (function() {
            const searchInput = document.getElementById('institutionSearch');
            const geocodeSearchBtn = document.getElementById('geocodeSearchBtn');
            const modalName = document.getElementById('modalName');
            const modalBarangay = document.getElementById('modalBarangay');
            const searchResultsDropdown = document.getElementById('searchResultsDropdown');

            // ── Table filter search ─────────────────────────────────────
            searchInput?.addEventListener('input', function() {
                const q = this.value.toLowerCase().trim();
                let visible = 0;

                document.querySelectorAll('.inst-row').forEach(row => {
                    const match = !q || row.textContent.toLowerCase().includes(q);
                    row.style.display = match ? '' : 'none';
                    if (match) visible++;
                });

                document.getElementById('noResults')?.classList.toggle('hidden', visible > 0);
            });

            // ── Modal open/close ─────────────────────────────────────────
            const modal = document.getElementById('addInstitutionModal');
            const openModalBtns = document.querySelectorAll('#openAddModal, #openAddModalEmpty');
            const closeModalBtn = document.getElementById('closeAddModal');
            const cancelModalBtn = document.getElementById('cancelAddModal');
            const modalOverlay = document.getElementById('modalOverlay');
            const modalType = document.getElementById('modalType');

            openModalBtns.forEach(btn => btn.addEventListener('click', openModal));
            closeModalBtn?.addEventListener('click', closeModal);
            cancelModalBtn?.addEventListener('click', closeModal);
            modalOverlay?.addEventListener('click', closeModal);
            modalType?.addEventListener('change', updatePopulationFields);
            geocodeSearchBtn?.addEventListener('click', searchLocations);

            // Clear the red error border as soon as the user fixes the field
            modalName?.addEventListener('input', () => modalName.classList.remove('border-red-500', 'ring-2', 'ring-red-200'));
            modalBarangay?.addEventListener('change', () => modalBarangay.classList.remove('border-red-500', 'ring-2', 'ring-red-200'));

            let mapPicker, pickerMarker;

            function openModal() {
                modal.classList.remove('hidden');
                modal.removeAttribute('aria-hidden');
                document.body.style.overflow = 'hidden';
                setTimeout(initMapPicker, 100);

                setTimeout(() => {
                    document.getElementById('modalName').focus();
                }, 150);
            }

            function closeModal() {
                document.activeElement.blur();
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                if (mapPicker) {
                    mapPicker.remove();
                    mapPicker = null;
                    pickerMarker = null;
                }
                clearFieldErrors();
                hideSearchResults();
            }

            function clearFieldErrors() {
                modalName?.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
                modalBarangay?.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
            }

            function hideSearchResults() {
                if (searchResultsDropdown) {
                    searchResultsDropdown.classList.add('hidden');
                    searchResultsDropdown.innerHTML = '';
                }
            }

            function updatePopulationFields() {
                const selectedType = modalType.value;
                document.querySelectorAll('.modal-population-field').forEach(field => {
                    const type = field.dataset.type;
                    field.classList.toggle('hidden',
                        (type === 'school' && selectedType !== 'School') ||
                        (type === 'other' && selectedType === 'School')
                    );
                });
            }

            function initMapPicker() {
                if (mapPicker) return;

                mapPicker = L.map('mapPicker', {
                    center: [13.9411, 121.1631],
                    zoom: 13
                });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(mapPicker);
            }

            // ── Geocode search (Institution Name + Barangay → Nominatim) ──
            async function searchLocations() {
                const name = modalName.value.trim();
                const barangayId = modalBarangay.value;

                clearFieldErrors();

                // Build a specific message instead of a generic one
                const missing = [];
                if (!name) {
                    missing.push('Institution Name');
                    modalName.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                }
                if (!barangayId) {
                    missing.push('Barangay');
                    modalBarangay.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                }

                if (missing.length > 0) {
                    showErrorInDropdown(`Please fill in: ${missing.join(' and ')}`);
                    return;
                }

                showLoadingInDropdown();

                try {
                    const response = await fetch('/institutions/geocode', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
                        },
                        body: JSON.stringify({
                            name,
                            barangay_id: barangayId
                        })
                    });

                    let data = null;
                    try {
                        data = await response.json();
                    } catch (parseErr) {
                        showErrorInDropdown('Unexpected server response. Please try again.');
                        return;
                    }

                    if (response.ok && Array.isArray(data) && data.length > 0) {
                        showSearchResults(data);
                    } else if (response.status === 422) {
                        // Laravel validation error shape: { message, errors: {field: [msgs]} }
                        const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                        showErrorInDropdown(firstError || data?.message || 'Invalid search input.');
                    } else if (response.status === 404) {
                        showErrorInDropdown(data?.message || 'No locations found for this search.');
                    } else {
                        showErrorInDropdown(data?.message || 'No locations found.');
                    }
                } catch (error) {
                    console.error('Geocode search failed:', error);
                    showErrorInDropdown('Search failed. Check your connection and try again.');
                }
            }

            function showSearchResults(results) {
                searchResultsDropdown.innerHTML = '';

                results.forEach((result, index) => {
                    const div = document.createElement('div');
                    div.className =
                        'p-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0 text-sm';
                    div.textContent = result.display_name || `Location ${index + 1}`;
                    div.addEventListener('click', () => selectLocation(result));
                    searchResultsDropdown.appendChild(div);
                });

                searchResultsDropdown.classList.remove('hidden');
                positionDropdown();
            }

            function showLoadingInDropdown() {
                searchResultsDropdown.innerHTML =
                    '<div class="p-3 text-center text-gray-500 text-sm"><svg class="animate-spin h-4 w-4 inline-block mr-2" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" class="opacity-75"></path></svg>Searching locations...</div>';
                searchResultsDropdown.classList.remove('hidden');
                positionDropdown();
            }

            function showErrorInDropdown(message) {
                searchResultsDropdown.innerHTML = `<div class="p-3 text-center text-red-600 text-sm">${escapeHtml(message)}</div>`;
                searchResultsDropdown.classList.remove('hidden');
                positionDropdown();
            }

            function escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            function positionDropdown() {
                const rect = modalName.getBoundingClientRect();
                searchResultsDropdown.style.width = rect.width + 'px';
            }

            function selectLocation(result) {
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lng);

                if (Number.isNaN(lat) || Number.isNaN(lng)) {
                    showErrorInDropdown('Invalid coordinates returned for this location.');
                    return;
                }

                const latlng = [lat, lng];
                if (mapPicker) {
                    mapPicker.setView(latlng, 16);
                }
                setMarkerAndInputs(latlng);
                hideSearchResults();
            }

            function setMarkerAndInputs(latlng) {
                document.getElementById('modalLatitude').value = latlng[0].toFixed(7);
                document.getElementById('modalLongitude').value = latlng[1].toFixed(7);

                if (pickerMarker) {
                    pickerMarker.setLatLng(latlng);
                } else {
                    pickerMarker = L.marker(latlng, {
                        draggable: true
                    }).addTo(mapPicker);
                    pickerMarker.on('dragend', function(e) {
                        const newLatLng = e.target.getLatLng();
                        document.getElementById('modalLatitude').value = newLatLng.lat.toFixed(7);
                        document.getElementById('modalLongitude').value = newLatLng.lng.toFixed(7);
                    });
                }

                // Rebind click handler each time to avoid stacking duplicate listeners
                mapPicker.off('click');
                mapPicker.on('click', function(e) {
                    pickerMarker.setLatLng(e.latlng);
                    document.getElementById('modalLatitude').value = e.latlng.lat.toFixed(7);
                    document.getElementById('modalLongitude').value = e.latlng.lng.toFixed(7);
                });
            }

            // Close dropdown if clicking outside it
            document.addEventListener('click', function(e) {
                if (!searchResultsDropdown) return;
                const clickedInsideDropdown = searchResultsDropdown.contains(e.target);
                const clickedSearchBtn = geocodeSearchBtn?.contains(e.target);
                const clickedNameInput = modalName?.contains(e.target);
                if (!clickedInsideDropdown && !clickedSearchBtn && !clickedNameInput) {
                    hideSearchResults();
                }
            });

            // Location is auto-geocoded server-side via Nominatim on save if
            // modalLatitude/modalLongitude are left blank — no extra handling needed here.
        })();
    </script>
@endsection
