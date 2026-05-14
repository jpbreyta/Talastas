@extends('layouts.app')

@section('content')
<div class="flex flex-col h-full min-h-0 bg-gray-100">

    <form method="POST" action="{{ route('institutions.update', $institution) }}" id="editForm" class="flex flex-col h-full min-h-0">
        @csrf
        @method('PUT')

        {{-- ── Fixed Action Bar (top) ───────────────────────────────── --}}
        <div class="flex-shrink-0 bg-blue-700 px-6 py-4">
            <div class="flex items-center justify-between gap-4">
                {{-- Back + Title --}}
                <div class="flex items-center gap-3 min-w-0">
                    <a href="{{ route('institutions.show', $institution) }}"
                       class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-600 hover:bg-blue-500 text-white transition shrink-0"
                       title="Back to view">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div class="min-w-0">
                        <h1 class="text-lg font-semibold text-white leading-tight">Edit Institution</h1>
                        <p class="text-blue-200 text-xs mt-0.5 truncate">{{ $institution->name }}</p>
                    </div>
                </div>

                {{-- Save / Cancel --}}
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('institutions.show', $institution) }}"
                       class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg font-medium text-sm transition border border-blue-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancel
                    </a>
                    <button type="submit"
                            class="flex items-center gap-2 bg-white text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-50 font-medium text-sm shadow-sm transition border border-blue-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Scrollable Form Body ──────────────────────────────────── --}}
        <div class="flex-1 min-h-0 overflow-y-auto px-6 py-5 space-y-5">



            {{-- ── Section 1: Basic Information ──────────────────────── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <button type="button" onclick="toggleSection('sec-basic')"
                        class="w-full flex items-center justify-between px-5 py-3.5 text-left hover:bg-gray-50 transition">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-gray-800">Basic Information</span>
                            <span class="ml-2 text-xs text-gray-400">Name, sector, population</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 section-chevron transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="sec-basic" class="border-t border-gray-100 px-5 py-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">

                        {{-- Name --}}
                        <div class="xl:col-span-2">
                            <label for="name" class="block text-xs font-semibold text-gray-600 mb-1.5">
                                Institution Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name"
                                   value="{{ old('name', $institution->name) }}" required
                                   class="form-input w-full @error('name') border-red-400 @enderror"
                                   placeholder="e.g. Lipa City National High School">
                            @error('name')
                            <p class="mt-1 text-xs text-red-600 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                         {{-- Sector Type --}}
                         <div>
                             <label for="type" class="block text-xs font-semibold text-gray-600 mb-1.5">
                                 Sector Type <span class="text-red-500">*</span>
                             </label>
                             <select name="type" id="type" required
                                     class="form-input w-full @error('type') border-red-400 @enderror">
                                  @foreach(['School','Barangay Hall','Local Government Office'] as $type)
                                 <option value="{{ $type }}" {{ old('type', $institution->type) == $type ? 'selected' : '' }}>{{ $type }}</option>
                                 @endforeach
                             </select>
                             @error('type')
                             <p class="mt-1 text-xs text-red-600 flex items-center gap-1">
                                 <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                 {{ $message }}
                             </p>
                             @enderror
                         </div>

                         {{-- Student Population --}}
                         <div class="population-field" data-type="school">
                             <label for="student_population" class="block text-xs font-semibold text-gray-600 mb-1.5">Student Population <span class="text-red-500">*</span></label>
                             <input type="number" name="student_population" id="student_population" min="0"
                                    value="{{ old('student_population', $institution->student_population) }}"
                                    class="form-input w-full @error('student_population') border-red-400 @enderror"
                                    placeholder="e.g. 1200" required>
                             @error('student_population')
                             <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                             @enderror
                         </div>

                         {{-- Staff Population --}}
                         <div class="population-field" data-type="other">
                             <label for="staff_population" class="block text-xs font-semibold text-gray-600 mb-1.5">Staff Population <span class="text-red-500">*</span></label>
                             <input type="number" name="staff_population" id="staff_population" min="0"
                                    value="{{ old('staff_population', $institution->staff_population) }}"
                                    class="form-input w-full @error('staff_population') border-red-400 @enderror"
                                    placeholder="e.g. 45" required>
                             @error('staff_population')
                             <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                             @enderror
                         </div>

                        {{-- ISP Provider --}}
                        <div>
                            <label for="isp_provider" class="block text-xs font-semibold text-gray-600 mb-1.5">ISP Provider</label>
                            <input type="text" name="isp_provider" id="isp_provider"
                                   value="{{ old('isp_provider', $institution->isp_provider) }}"
                                   class="form-input w-full"
                                   placeholder="e.g. PLDT, Globe, Converge">
                        </div>

                        {{-- Internet Speed --}}
                        <div>
                            <label for="internet_speed" class="block text-xs font-semibold text-gray-600 mb-1.5">Internet Speed (Mbps)</label>
                            <input type="number" step="0.1" name="internet_speed" id="internet_speed" min="0"
                                   value="{{ old('internet_speed', $institution->internet_speed) }}"
                                   class="form-input w-full @error('internet_speed') border-red-400 @enderror"
                                   placeholder="e.g. 100">
                            @error('internet_speed')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- ── Section 2: Location ────────────────────────────────── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <button type="button" onclick="toggleSection('sec-location')"
                        class="w-full flex items-center justify-between px-5 py-3.5 text-left hover:bg-gray-50 transition">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-gray-800">Location</span>
                            <span class="ml-2 text-xs text-gray-400">Barangay and GPS coordinates</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 section-chevron transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="sec-location" class="border-t border-gray-100 px-5 py-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                        {{-- Barangay --}}
                        <div>
                            <label for="barangay_name" class="block text-xs font-semibold text-gray-600 mb-1.5">
                                Barangay <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="barangay_name" id="barangay_name"
                                   value="{{ old('barangay_name', $institution->barangay_name) }}" required
                                   class="form-input w-full @error('barangay_name') border-red-400 @enderror"
                                   placeholder="e.g. Barangay Mataas na Lupa">
                            @error('barangay_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Latitude --}}
                        <div>
                            <label for="latitude" class="block text-xs font-semibold text-gray-600 mb-1.5">
                                Latitude <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.000001" name="latitude" id="latitude"
                                   value="{{ old('latitude', $institution->latitude) }}" required
                                   class="form-input w-full @error('latitude') border-red-400 @enderror"
                                   placeholder="e.g. 13.9411">
                            @error('latitude')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Longitude --}}
                        <div>
                            <label for="longitude" class="block text-xs font-semibold text-gray-600 mb-1.5">
                                Longitude <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.000001" name="longitude" id="longitude"
                                   value="{{ old('longitude', $institution->longitude) }}" required
                                   class="form-input w-full @error('longitude') border-red-400 @enderror"
                                   placeholder="e.g. 121.1644">
                            @error('longitude')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                    <p class="mt-3 text-xs text-gray-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Lipa City is approximately at 13.94°N, 121.16°E. Use Google Maps to find precise coordinates.
                    </p>
                </div>
            </div>

            {{-- ── Section 3: ICT Facilities ──────────────────────────── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <button type="button" onclick="toggleSection('sec-ict')"
                        class="w-full flex items-center justify-between px-5 py-3.5 text-left hover:bg-gray-50 transition">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-purple-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-gray-800">ICT Facilities</span>
                            <span class="ml-2 text-xs text-gray-400">Equipment and hardware availability</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 section-chevron transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="sec-ict" class="border-t border-gray-100 px-5 py-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
                        <x-toggle-card name="has_computer_lab"         label="Computer Lab"        :checked="old('has_computer_lab', $institution->has_computer_lab)"/>
                        <x-toggle-card name="has_smart_tv"             label="Smart TV"            :checked="old('has_smart_tv', $institution->has_smart_tv)"/>
                        <x-toggle-card name="has_projected_whiteboard" label="Projected Whiteboard":checked="old('has_projected_whiteboard', $institution->has_projected_whiteboard)"/>
                        <x-toggle-card name="has_public_address_system"label="PA System"           :checked="old('has_public_address_system', $institution->has_public_address_system)"/>
                    </div>
                    <div class="max-w-xs">
                        <label for="number_of_computers" class="block text-xs font-semibold text-gray-600 mb-1.5">Number of Computers</label>
                        <input type="number" name="number_of_computers" id="number_of_computers" min="0"
                               value="{{ old('number_of_computers', $institution->number_of_computers) }}"
                               class="form-input w-full"
                               placeholder="e.g. 30">
                    </div>
                </div>
            </div>

            {{-- ── Section 4: Status & Flags ──────────────────────────── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <button type="button" onclick="toggleSection('sec-status')"
                        class="w-full flex items-center justify-between px-5 py-3.5 text-left hover:bg-gray-50 transition">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-orange-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-gray-800">Status & Flags</span>
                            <span class="ml-2 text-xs text-gray-400">Operational status and audit flags</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 section-chevron transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="sec-status" class="border-t border-gray-100 px-5 py-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        <x-toggle-card name="is_pilot_site"    label="Pilot Site"       :checked="old('is_pilot_site', $institution->is_pilot_site)"       color="green"/>
                        <x-toggle-card name="is_active"        label="Active"           :checked="old('is_active', $institution->is_active)"               color="green"/>
                        <x-toggle-card name="needs_maintenance"label="Needs Maintenance":checked="old('needs_maintenance', $institution->needs_maintenance)" color="red"/>
                        <x-toggle-card name="requires_audit"   label="Requires Audit"  :checked="old('requires_audit', $institution->requires_audit)"      color="yellow"/>
                    </div>
                </div>
            </div>

            {{-- ── Section 5: Notes ───────────────────────────────────── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <button type="button" onclick="toggleSection('sec-notes')"
                        class="w-full flex items-center justify-between px-5 py-3.5 text-left hover:bg-gray-50 transition">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-gray-800">Notes</span>
                            <span class="ml-2 text-xs text-gray-400">Additional remarks or observations</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 section-chevron transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="sec-notes" class="border-t border-gray-100 px-5 py-5">
                    <textarea name="notes" id="notes" rows="4"
                              class="form-input w-full resize-none"
                              placeholder="Add any additional notes, observations, or remarks about this institution…">{{ old('notes', $institution->notes) }}</textarea>
                    @error('notes')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Bottom save bar (convenience duplicate) --}}
            <div class="flex items-center justify-end gap-3 py-2">
                <a href="{{ route('institutions.show', $institution) }}"
                   class="flex items-center gap-2 bg-white text-gray-700 px-5 py-2.5 rounded-lg hover:bg-gray-50 font-medium text-sm transition border border-gray-200 shadow-sm">
                    Cancel
                </a>
                <button type="submit"
                        class="flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 font-medium text-sm transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Changes
                </button>
            </div>

        </div>{{-- end scrollable body --}}
    </form>
</div>

<style>
    .form-input {
        @apply block border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 bg-white
               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
               placeholder-gray-400 transition;
    }
    select.form-input { @apply pr-8; }
</style>

@endsection

@section('scripts')
<script>
// ── Toggle cards ─────────────────────────────────────────────────────
document.querySelectorAll('.toggle-card-label').forEach(label => {
    const input  = label.querySelector('.toggle-card-input');
    const body   = label.querySelector('.toggle-card-body');
    const track  = label.querySelector('.toggle-track');
    const thumb  = label.querySelector('.toggle-thumb');
    const text   = label.querySelector('.toggle-label-text');
    const active = body.dataset.activeClass.split(' ');
    const tc     = body.dataset.textClass;
    const trk    = body.dataset.trackClass;

    function sync() {
        if (input.checked) {
            body.classList.add(...active);
            body.classList.remove('border-gray-200', 'bg-white');
            track.classList.add(trk);
            track.classList.remove('bg-gray-200');
            thumb.style.transform = 'translateX(1rem)';
            text.classList.add(tc);
            text.classList.remove('text-gray-500');
        } else {
            body.classList.remove(...active);
            body.classList.add('border-gray-200', 'bg-white');
            track.classList.remove(trk);
            track.classList.add('bg-gray-200');
            thumb.style.transform = 'translateX(0)';
            text.classList.remove(tc);
            text.classList.add('text-gray-500');
        }
    }
    sync();
    input.addEventListener('change', sync);
});

// ── Accordion sections ───────────────────────────────────────────────
function toggleSection(id) {
    const body    = document.getElementById(id);
    const btn     = body.previousElementSibling;
    const chevron = btn.querySelector('.section-chevron');
    const isOpen  = !body.classList.contains('hidden');

    body.classList.toggle('hidden', isOpen);
    chevron.style.transform = isOpen ? 'rotate(-90deg)' : 'rotate(0deg)';
}

// ── Dynamic population fields ─────────────────────────────────────────
function updatePopulationFields() {
    const typeSelect = document.getElementById('type');
    const selectedType = typeSelect.value;

    const schoolField = document.querySelector('.population-field[data-type="school"]');
    const otherField = document.querySelector('.population-field[data-type="other"]');
    const studentInput = document.getElementById('student_population');
    const staffInput = document.getElementById('staff_population');

    if (selectedType === 'School') {
        schoolField.style.display = 'block';
        otherField.style.display = 'none';
        studentInput.required = true;
        staffInput.required = false;
        // Don't clear values on edit
    } else {
        schoolField.style.display = 'none';
        otherField.style.display = 'block';
        studentInput.required = false;
        staffInput.required = true;
        // Don't clear values on edit
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', updatePopulationFields);

// Update when type changes
document.getElementById('type').addEventListener('change', updatePopulationFields);

// Warn on unsaved changes
(function () {
    let dirty = false;
    document.getElementById('editForm').addEventListener('change', () => dirty = true);
    window.addEventListener('beforeunload', e => {
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });
    document.getElementById('editForm').addEventListener('submit', () => dirty = false);
    document.querySelectorAll('a[href]').forEach(a => {
        a.addEventListener('click', e => {
            if (dirty && !confirm('You have unsaved changes. Leave anyway?')) e.preventDefault();
        });
    });
})();
</script>
@endsection
