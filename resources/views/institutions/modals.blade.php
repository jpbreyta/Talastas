{{-- Reusable institution modals --}}
{{-- Add Institution Modal --}}
        <div id="addInstitutionModal" class="fixed inset-0 z-[3000] hidden overflow-y-auto" aria-hidden="true" inert
            role="dialog" aria-modal="true" aria-labelledby="addInstitutionModalTitle">
            <div class="flex min-h-screen items-center justify-center px-4 py-8">
                <div id="modalOverlay" class="fixed inset-0 bg-gray-900/60 backdrop-blur-[1px]"></div>

                <div
                    class="relative flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                    {{-- Modal Header --}}
                    <div class="shrink-0 border-b border-red-600 bg-red-700 px-6 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-600 text-red-100">
                                    <i class="fa-solid fa-building-circle-check"></i>
                                </div>

                                <div class="min-w-0">
                                    <h2 id="addInstitutionModalTitle" class="text-lg font-semibold text-white">
                                        Add New Institution
                                    </h2>
                                    <p class="mt-0.5 text-xs text-red-200">
                                        Register the institution first, then complete its profile after creation
                                    </p>
                                </div>
                            </div>

                            <button type="button" id="closeAddModal"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-red-200 transition hover:bg-red-600 hover:text-white"
                                aria-label="Close modal">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Form --}}
                    <form method="POST" action="{{ route('institutions.store') }}" id="addInstitutionForm"
                        class="min-h-0 flex-1 overflow-y-auto">
                        @csrf

                        <div class="space-y-6 p-6">
                            {{-- Institution and Location --}}
                            <section class="overflow-visible rounded-xl border border-gray-200 bg-white shadow-sm">
                                <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-100 text-red-700">
                                            <i class="fa-solid fa-location-dot"></i>
                                        </div>

                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900">
                                                Institution and Location
                                            </h3>
                                            <p class="mt-0.5 text-xs text-gray-500">
                                                Search for the institution to detect its mapped barangay and coordinates
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-5 p-5">
                                    <div>
                                        <label for="modalName" class="mb-1.5 block text-xs font-semibold text-gray-600">
                                            Institution Name
                                            <span class="text-red-500">*</span>
                                        </label>

                                        <div class="relative">
                                            <div class="flex flex-col gap-2 sm:flex-row">
                                                <div class="relative flex-1">
                                                    <div
                                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                                        <i class="fa-solid fa-building-columns text-sm"></i>
                                                    </div>

                                                    <input type="text" id="modalName" value="{{ old('name') }}"
                                                        required autocomplete="off" class="form-input w-full pl-9"
                                                        placeholder="e.g. Lipa City National High School">

                                                    <input type="hidden" name="name" id="selectedInstitutionName"
                                                        value="{{ old('name') }}">
                                                </div>

                                                <button type="button" id="geocodeSearchBtn"
                                                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 transition hover:border-red-300 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-60">
                                                    <i class="fa-solid fa-magnifying-glass"></i>
                                                    <span id="geocodeSearchButtonText">Search Location</span>
                                                </button>
                                            </div>

                                            <div id="searchResultsDropdown"
                                                class="absolute left-0 right-0 z-[4000] mt-2 hidden max-h-72 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Map Preview --}}
                                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                                        <div
                                            class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-map-location-dot text-red-600"></i>
                                                <h4 class="text-sm font-semibold text-gray-800">Location Preview</h4>
                                            </div>
                                            <span class="text-xs text-gray-400">OpenStreetMap</span>
                                        </div>

                                        <div id="mapPickerContainer" class="relative h-64 bg-gray-100">
                                            <div id="mapPicker" class="h-full w-full"></div>

                                            <div id="mapPlaceholder"
                                                class="pointer-events-none absolute inset-0 z-[500] flex items-center justify-center bg-gray-50">
                                                <div class="px-4 text-center">
                                                    <div
                                                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-500">
                                                        <i class="fa-solid fa-map-pin"></i>
                                                    </div>
                                                    <p class="mt-3 text-sm font-medium text-gray-600">
                                                        Search and select a location
                                                    </p>
                                                    <p class="mt-1 text-xs text-gray-400">
                                                        The selected institution will appear on the map
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Detected Location Card --}}
                                    <div id="detectedLocationCard"
                                        class="overflow-hidden rounded-xl border border-gray-200 bg-white"
                                        aria-live="polite">
                                        <div
                                            class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-700">
                                                    <i class="fa-solid fa-location-crosshairs"></i>
                                                </div>
                                                <div>
                                                    <h4 class="text-sm font-semibold text-gray-900">
                                                        Automatically Detected Location
                                                    </h4>
                                                    <p class="mt-0.5 text-xs text-gray-500">
                                                        Based on the selected result and barangay boundary
                                                    </p>
                                                </div>
                                            </div>

                                            <span id="locationStatusBadge"
                                                class="inline-flex w-fit items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-500">
                                                <i class="fa-regular fa-clock"></i>
                                                <span id="locationStatusText">Waiting for selection</span>
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-1 gap-px bg-gray-200 sm:grid-cols-2 lg:grid-cols-4">
                                            <div class="bg-white p-4">
                                                <div class="flex items-start gap-3">
                                                    <div
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                        <i class="fa-solid fa-map-pin"></i>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p
                                                            class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                                            Barangay</p>
                                                        <p id="detectedBarangay"
                                                            class="mt-1 truncate text-sm font-semibold text-gray-800">Not
                                                            detected</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="bg-white p-4">
                                                <div class="flex items-start gap-3">
                                                    <div
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                        <i class="fa-solid fa-city"></i>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p
                                                            class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                                            City</p>
                                                        <p id="detectedCity"
                                                            class="mt-1 truncate text-sm font-semibold text-gray-800">Not
                                                            detected</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="bg-white p-4">
                                                <div class="flex items-start gap-3">
                                                    <div
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                        <i class="fa-solid fa-map"></i>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p
                                                            class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                                            Province</p>
                                                        <p id="detectedProvince"
                                                            class="mt-1 truncate text-sm font-semibold text-gray-800">Not
                                                            detected</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="bg-white p-4">
                                                <div class="flex items-start gap-3">
                                                    <div
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                        <i class="fa-solid fa-earth-asia"></i>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p
                                                            class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                                            Country</p>
                                                        <p id="detectedCountry"
                                                            class="mt-1 truncate text-sm font-semibold text-gray-800">Not
                                                            detected</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 border-t border-gray-200 bg-gray-50 sm:grid-cols-2">
                                            <div class="flex items-center gap-3 px-4 py-3 sm:border-r sm:border-gray-200">
                                                <div
                                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm">
                                                    <i class="fa-solid fa-arrows-up-down text-xs"></i>
                                                </div>
                                                <div>
                                                    <p
                                                        class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                                        Latitude</p>
                                                    <p id="detectedLatitude"
                                                        class="mt-0.5 font-mono text-sm font-medium text-gray-700">—</p>
                                                </div>
                                            </div>

                                            <div
                                                class="flex items-center gap-3 border-t border-gray-200 px-4 py-3 sm:border-t-0">
                                                <div
                                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm">
                                                    <i class="fa-solid fa-arrows-left-right text-xs"></i>
                                                </div>
                                                <div>
                                                    <p
                                                        class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                                        Longitude</p>
                                                    <p id="detectedLongitude"
                                                        class="mt-0.5 font-mono text-sm font-medium text-gray-700">—</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="locationValidationMessage"
                                            class="hidden border-t border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                            <div class="flex items-start gap-2">
                                                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                                                <span id="locationValidationText">Please select a valid location.</span>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" name="barangay_id" id="modalBarangayId"
                                        value="{{ old('barangay_id') }}">
                                    <input type="hidden" name="latitude" id="modalLatitude"
                                        value="{{ old('latitude') }}">
                                    <input type="hidden" name="longitude" id="modalLongitude"
                                        value="{{ old('longitude') }}">
                                </div>
                            </section>

                            {{-- Institution Classification --}}
                            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-100 text-red-700">
                                            <i class="fa-solid fa-layer-group"></i>
                                        </div>

                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900">
                                                Institution Classification
                                            </h3>
                                            <p class="mt-0.5 text-xs text-gray-500">
                                                Choose the institution sector. The remaining information can be completed after creation.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-5">
                                    <label for="modalType"
                                        class="mb-1.5 block text-xs font-semibold text-gray-600">
                                        Sector Type
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <select name="type" id="modalType" required class="form-input w-full">
                                        @foreach (['School', 'Barangay Hall', 'Local Government Office'] as $type)
                                            <option value="{{ $type }}" @selected(old('type', 'School') === $type)>
                                                {{ $type }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <div
                                        class="mt-4 flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
                                        <span
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-blue-600 shadow-sm">
                                            <i class="fa-solid fa-circle-info"></i>
                                        </span>

                                        <div>
                                            <p class="text-sm font-semibold text-blue-900">
                                                Complete the profile after creation
                                            </p>
                                            <p class="mt-1 text-xs leading-5 text-blue-700">
                                                Population, connectivity, computer laboratory, audit settings, notes, and ICT inventory are added later from the institution edit and inventory pages.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </form>

                    {{-- Modal Footer --}}
                    <div
                        class="flex shrink-0 flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-end">
                        <button type="button" id="cancelAddModal"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                            <i class="fa-solid fa-xmark"></i>
                            Cancel
                        </button>

                        <button type="submit" form="addInstitutionForm" id="createInstitutionBtn" disabled
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500">
                            <i class="fa-solid fa-plus"></i>
                            <span>Create Institution</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
{{-- Delete Institution Modal --}}
        <div id="deleteInstitutionModal"
            class="fixed inset-0 z-[3200] hidden overflow-y-auto"
            role="dialog"
            aria-modal="true"
            aria-hidden="true"
            aria-labelledby="deleteInstitutionModalTitle"
            inert>
            <div class="flex min-h-screen items-center justify-center px-4 py-8">
                <div id="deleteInstitutionModalOverlay"
                    class="fixed inset-0 bg-gray-900/60 backdrop-blur-[1px]"></div>

                <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <div class="border-b border-gray-200 px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <span
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600">
                                    <i class="fa-solid fa-trash-can"></i>
                                </span>

                                <div>
                                    <h2 id="deleteInstitutionModalTitle"
                                        class="text-lg font-semibold text-gray-900">
                                        Delete Institution
                                    </h2>
                                    <p class="mt-1 text-sm leading-5 text-gray-500">
                                        This action permanently removes the institution and its related records.
                                    </p>
                                </div>
                            </div>

                            <button type="button" id="closeDeleteInstitutionModal"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                                aria-label="Close delete confirmation">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-5">
                        <p class="text-sm text-gray-600">
                            Are you sure you want to delete
                            <span id="deleteInstitutionName" class="font-semibold text-gray-900"></span>?
                        </p>

                        <div class="mt-4 flex items-start gap-3 rounded-xl border border-red-100 bg-red-50 px-4 py-3">
                            <i class="fa-solid fa-circle-exclamation mt-0.5 text-red-600"></i>
                            <p class="text-sm leading-5 text-red-700">
                                Deleted information cannot be recovered from this page.
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end">
                        <button type="button" id="cancelDeleteInstitution"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                            <i class="fa-solid fa-xmark"></i>
                            Cancel
                        </button>

                        <button type="button" id="confirmDeleteInstitution"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">
                            <i class="fa-solid fa-trash"></i>
                            <span>Delete Institution</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Fallback Toast Container --}}
        <div id="institutionToastContainer"
            class="pointer-events-none fixed left-4 right-4 top-4 z-[5000] flex flex-col items-end gap-3 sm:left-auto sm:w-96"
            aria-live="polite"
            aria-atomic="true">
        </div>

<style>
        .form-input {
            display: block;
            border: 1px solid rgb(209 213 219);
            border-radius: 0.5rem;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            padding-right: 0.75rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: rgb(17 24 39);
            background-color: white;
            transition: border-color 150ms ease, box-shadow 150ms ease;
        }

        .form-input::placeholder {
            color: rgb(156 163 175);
        }

        .form-input:focus {
            outline: none;
            border-color: rgb(59 130 246);
            box-shadow: 0 0 0 3px rgb(191 219 254 / 0.8);
        }

        select.form-input {
            padding-left: 0.75rem;
        }

        .feature-card {
            display: flex;
            cursor: pointer;
            align-items: center;
            gap: 0.75rem;
            border: 1px solid rgb(229 231 235);
            border-radius: 0.75rem;
            padding: 0.75rem;
            background-color: white;
            transition: border-color 150ms ease, background-color 150ms ease;
        }

        .feature-card:hover {
            border-color: rgb(147 197 253);
            background-color: rgb(239 246 255);
        }

        .feature-checkbox {
            width: 1rem;
            height: 1rem;
            border-radius: 0.25rem;
            accent-color: rgb(37 99 235);
        }

        .feature-icon {
            display: flex;
            width: 2.25rem;
            height: 2.25rem;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            color: rgb(37 99 235);
            background-color: rgb(239 246 255);
        }

        .feature-icon-green {
            color: rgb(22 163 74);
            background-color: rgb(240 253 244);
        }

        .feature-icon-orange {
            color: rgb(234 88 12);
            background-color: rgb(255 247 237);
        }

        .feature-icon-yellow {
            color: rgb(202 138 4);
            background-color: rgb(254 252 232);
        }

        #toast-container.toast-top-right {
            top: 1rem;
            right: 1rem;
        }

        #toast-container > div {
            width: 22rem;
            max-width: calc(100vw - 2rem);
            border-radius: 0.75rem;
            opacity: 1;
            box-shadow: 0 10px 30px rgb(15 23 42 / 0.18);
        }

        #toast-container .toast-message {
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
    </style>

<script>
        document.addEventListener('DOMContentLoaded', () => {
            'use strict';

            const defaultCenter = [13.9411, 121.1631];
            const defaultZoom = 13;

            const institutionSearch = document.getElementById('institutionSearch');
            const modal = document.getElementById('addInstitutionModal');
            const modalOverlay = document.getElementById('modalOverlay');
            const openModalButtons = document.querySelectorAll('[data-open-institution-modal]');
            const closeModalButton = document.getElementById('closeAddModal');
            const cancelModalButton = document.getElementById('cancelAddModal');
            const form = document.getElementById('addInstitutionForm');
            const createButton = document.getElementById('createInstitutionBtn');
            const modalName = document.getElementById('modalName');
            const selectedInstitutionName = document.getElementById('selectedInstitutionName');
            const modalType = document.getElementById('modalType');
            const barangayId = document.getElementById('modalBarangayId');
            const latitudeInput = document.getElementById('modalLatitude');
            const longitudeInput = document.getElementById('modalLongitude');
            const geocodeButton = document.getElementById('geocodeSearchBtn');
            const geocodeButtonText = document.getElementById('geocodeSearchButtonText');
            const searchResults = document.getElementById('searchResultsDropdown');
            const locationStatusBadge = document.getElementById('locationStatusBadge');
            const locationStatusText = document.getElementById('locationStatusText');
            const detectedBarangay = document.getElementById('detectedBarangay');
            const detectedCity = document.getElementById('detectedCity');
            const detectedProvince = document.getElementById('detectedProvince');
            const detectedCountry = document.getElementById('detectedCountry');
            const detectedLatitude = document.getElementById('detectedLatitude');
            const detectedLongitude = document.getElementById('detectedLongitude');
            const locationValidationMessage = document.getElementById('locationValidationMessage');
            const locationValidationText = document.getElementById('locationValidationText');
            const mapPlaceholder = document.getElementById('mapPlaceholder');

            const deleteModal = document.getElementById('deleteInstitutionModal');
            const deleteModalOverlay = document.getElementById('deleteInstitutionModalOverlay');
            const closeDeleteModalButton = document.getElementById('closeDeleteInstitutionModal');
            const cancelDeleteModalButton = document.getElementById('cancelDeleteInstitution');
            const confirmDeleteButton = document.getElementById('confirmDeleteInstitution');
            const deleteInstitutionName = document.getElementById('deleteInstitutionName');
            const deleteButtons = document.querySelectorAll('.delete-institution-button');

            let mapPicker = null;
            let pickerMarker = null;
            let lastFocusedElement = null;
            let lastDeleteFocusedElement = null;
            let pendingDelete = null;

            configureToastr();

            const storedToast = sessionStorage.getItem('institutionToast');

            if (storedToast) {
                sessionStorage.removeItem('institutionToast');

                try {
                    const toast = JSON.parse(storedToast);
                    notify(toast.type || 'success', toast.message || 'Operation completed.');
                } catch (error) {
                    console.error('Unable to read stored institution notification.', error);
                }
            }

            institutionSearch?.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                let visibleRows = 0;

                document.querySelectorAll('.inst-row').forEach(row => {
                    const matches = !query || row.textContent.toLowerCase().includes(query);
                    row.style.display = matches ? '' : 'none';

                    if (matches) {
                        visibleRows++;
                    }
                });

                document.getElementById('noResults')?.classList.toggle('hidden', visibleRows > 0);
            });

            openModalButtons.forEach(button => {
                button.addEventListener('click', openModal);
            });

            deleteButtons.forEach(button => {
                button.addEventListener('click', () => openDeleteModal(button));
            });

            closeModalButton?.addEventListener('click', closeModal);
            cancelModalButton?.addEventListener('click', closeModal);
            modalOverlay?.addEventListener('click', closeModal);

            closeDeleteModalButton?.addEventListener('click', closeDeleteModal);
            cancelDeleteModalButton?.addEventListener('click', closeDeleteModal);
            deleteModalOverlay?.addEventListener('click', closeDeleteModal);
            confirmDeleteButton?.addEventListener('click', deleteInstitution);
            geocodeButton?.addEventListener('click', searchLocations);

            document.addEventListener('keydown', event => {
                if (event.key !== 'Escape') {
                    return;
                }

                if (!deleteModal?.classList.contains('hidden')) {
                    closeDeleteModal();
                    return;
                }

                if (!modal?.classList.contains('hidden')) {
                    closeModal();
                }
            });

            document.addEventListener('click', event => {
                const insideDropdown = searchResults?.contains(event.target);
                const insideSearchButton = geocodeButton?.contains(event.target);
                const insideNameInput = modalName?.contains(event.target);

                if (!insideDropdown && !insideSearchButton && !insideNameInput) {
                    hideSearchResults();
                }
            });

            modalName?.addEventListener('input', function() {
                clearNameError();
                hideLocationValidation();

                selectedInstitutionName.value = '';

                if (barangayId.value) {
                    resetDetectedLocation();
                }
            });

            modalName?.addEventListener('keydown', event => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    searchLocations();
                }
            });
            form?.addEventListener('submit', event => {
                if (!selectedInstitutionName.value.trim()) {
                    event.preventDefault();

                    showLocationValidation(
                        'Search and select an institution from the results before creating it.'
                    );

                    modalName.focus();
                    return;
                }

                if (!barangayId.value) {
                    event.preventDefault();

                    showLocationValidation(
                        'Search and select a valid institution location before creating the institution.'
                    );

                    document
                        .getElementById('detectedLocationCard')
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                    return;
                }

                createButton.disabled = true;
                createButton.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i>' +
                    '<span>Creating Institution</span>';
            });

            function openModal(event) {
                lastFocusedElement = event?.currentTarget || document.activeElement;
                modal.classList.remove('hidden');
                modal.removeAttribute('aria-hidden');
                modal.removeAttribute('inert');
                document.body.style.overflow = 'hidden';

                setTimeout(() => {
                    initializeMap();
                    mapPicker?.invalidateSize();
                    modalName?.focus();
                }, 100);
            }

            function closeModal() {
                if (document.activeElement && modal.contains(document.activeElement)) {
                    document.activeElement.blur();
                }

                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                modal.setAttribute('inert', '');
                document.body.style.overflow = '';

                hideSearchResults();
                clearNameError();
                form?.reset();
                resetDetectedLocation(false);

                if (mapPicker) {
                    mapPicker.remove();
                    mapPicker = null;
                    pickerMarker = null;
                }

                resetCreateButton();
                lastFocusedElement?.focus();
            }

            function openDeleteModal(button) {
                pendingDelete = {
                    url: button.dataset.deleteUrl,
                    id: button.dataset.institutionId,
                    name: button.dataset.institutionName || 'this institution',
                    row: document.getElementById(`institution-row-${button.dataset.institutionId}`)
                };

                lastDeleteFocusedElement = button;
                deleteInstitutionName.textContent = pendingDelete.name;

                deleteModal.classList.remove('hidden');
                deleteModal.removeAttribute('aria-hidden');
                deleteModal.removeAttribute('inert');
                document.body.style.overflow = 'hidden';

                setTimeout(() => confirmDeleteButton?.focus(), 50);
            }

            function closeDeleteModal() {
                if (confirmDeleteButton?.disabled) {
                    return;
                }

                if (document.activeElement && deleteModal.contains(document.activeElement)) {
                    document.activeElement.blur();
                }

                deleteModal.classList.add('hidden');
                deleteModal.setAttribute('aria-hidden', 'true');
                deleteModal.setAttribute('inert', '');
                document.body.style.overflow = '';

                pendingDelete = null;
                resetDeleteButton();
                lastDeleteFocusedElement?.focus();
            }

            async function deleteInstitution() {
                if (!pendingDelete?.url || confirmDeleteButton?.disabled) {
                    return;
                }

                setDeleteLoading(true);

                try {
                    const response = await fetch(pendingDelete.url, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content') || ''
                        }
                    });

                    const contentType = response.headers.get('content-type') || '';
                    let data = null;

                    if (contentType.includes('application/json')) {
                        data = await response.json();
                    }

                    if (!response.ok) {
                        throw new Error(
                            data?.message ||
                            (response.status === 419
                                ? 'Your session expired. Refresh the page and try again.'
                                : 'The institution could not be deleted.')
                        );
                    }

                    const message = data?.message || 'Institution deleted successfully.';
                    const row = pendingDelete.row;

                    deleteModal.classList.add('hidden');
                    deleteModal.setAttribute('aria-hidden', 'true');
                    deleteModal.setAttribute('inert', '');
                    document.body.style.overflow = '';

                    if (row) {
                        row.classList.add('opacity-0');

                        setTimeout(() => {
                            row.remove();

                            const remainingRows =
                                document.querySelectorAll('.inst-row').length;

                            if (remainingRows === 0) {
                                sessionStorage.setItem(
                                    'institutionToast',
                                    JSON.stringify({
                                        type: 'success',
                                        message
                                    })
                                );

                                window.location.reload();
                                return;
                            }

                            notify('success', message);
                        }, 180);
                    } else {
                        notify('success', message);
                    }

                    pendingDelete = null;
                    resetDeleteButton();
                } catch (error) {
                    console.error('Institution deletion failed:', error);
                    notify(
                        'error',
                        error instanceof Error
                            ? error.message
                            : 'The institution could not be deleted.'
                    );
                    setDeleteLoading(false);
                }
            }

            function setDeleteLoading(isLoading) {
                confirmDeleteButton.disabled = isLoading;

                confirmDeleteButton.innerHTML = isLoading
                    ? '<i class="fa-solid fa-spinner fa-spin"></i><span>Deleting</span>'
                    : '<i class="fa-solid fa-trash"></i><span>Delete Institution</span>';
            }

            function resetDeleteButton() {
                if (!confirmDeleteButton) {
                    return;
                }

                confirmDeleteButton.disabled = false;
                confirmDeleteButton.innerHTML =
                    '<i class="fa-solid fa-trash"></i><span>Delete Institution</span>';
            }

            function initializeMap() {
                if (mapPicker) {
                    return;
                }

                if (typeof L === 'undefined') {
                    mapPlaceholder.innerHTML = `
                        <div class="px-4 text-center text-red-600">
                            <i class="fa-solid fa-circle-exclamation text-xl"></i>
                            <p class="mt-2 text-sm font-medium">Leaflet could not be loaded.</p>
                        </div>
                    `;
                    return;
                }

                mapPicker = L.map('mapPicker', {
                    center: defaultCenter,
                    zoom: defaultZoom,
                    zoomControl: true,
                    attributionControl: true
                });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(mapPicker);
            }

            function setMapLocation(latitude, longitude) {
                initializeMap();

                if (!mapPicker) {
                    return;
                }

                const latLng = [latitude, longitude];
                mapPlaceholder?.classList.add('hidden');
                mapPicker.setView(latLng, 16);

                if (pickerMarker) {
                    pickerMarker.setLatLng(latLng);
                } else {
                    pickerMarker = L.marker(latLng).addTo(mapPicker);
                }

                setTimeout(() => mapPicker.invalidateSize(), 50);
            }

            function resetMap() {
                if (!mapPicker) {
                    mapPlaceholder?.classList.remove('hidden');
                    return;
                }

                if (pickerMarker) {
                    mapPicker.removeLayer(pickerMarker);
                    pickerMarker = null;
                }

                mapPicker.setView(defaultCenter, defaultZoom);
                mapPlaceholder?.classList.remove('hidden');
            }

            async function searchLocations() {
                const name = modalName.value.trim();
                clearNameError();
                hideLocationValidation();

                if (!name) {
                    showNameError();
                    showDropdownError('Enter the institution name before searching.');
                    modalName.focus();
                    return;
                }

                setSearchLoading(true);
                showDropdownLoading();

                try {
                    const response = await fetch('{{ url('/institutions/geocode') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.getAttribute(
                                    'content') || ''
                        },
                        body: JSON.stringify({
                            name
                        })
                    });

                    let data;

                    try {
                        data = await response.json();
                    } catch (error) {
                        showDropdownError('The server returned an unexpected response.');
                        return;
                    }

                    if (response.ok && Array.isArray(data) && data.length > 0) {
                        showSearchResults(data);
                        return;
                    }

                    if (response.status === 422) {
                        const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                        showDropdownError(firstError || data?.message ||
                            'The search information is invalid.');
                        return;
                    }

                    showDropdownError(data?.message || 'No matching locations were found.');
                } catch (error) {
                    console.error('Location search failed:', error);
                    showDropdownError('The location search failed. Check your connection and try again.');
                } finally {
                    setSearchLoading(false);
                }
            }

            function setSearchLoading(isLoading) {
                geocodeButton.disabled = isLoading;
                const icon = geocodeButton.querySelector('i');

                if (isLoading) {
                    geocodeButtonText.textContent = 'Searching';
                    icon.className = 'fa-solid fa-spinner fa-spin';
                } else {
                    geocodeButtonText.textContent = 'Search Location';
                    icon.className = 'fa-solid fa-magnifying-glass';
                }
            }

            function showSearchResults(results) {
                searchResults.innerHTML = '';

                results.forEach((result, index) => {
                    const hasBarangay = Boolean(result.barangay_id);
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.disabled = !hasBarangay;
                    button.className = [
                        'flex w-full items-start gap-3 border-b border-gray-100 px-4 py-3 text-left transition last:border-b-0',
                        hasBarangay ?
                        'cursor-pointer hover:bg-red-50' :
                        'cursor-not-allowed bg-gray-50 opacity-60'
                    ].join(' ');

                    const displayName = escapeHtml(result.display_name || `Location ${index + 1}`);
                    const barangay = escapeHtml(result.barangay || 'Barangay not detected');
                    const city = escapeHtml(result.city || 'City not detected');

                    button.innerHTML = `
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${
                            hasBarangay ? 'bg-red-100 text-red-700' : 'bg-gray-200 text-gray-500'
                        }">
                            <i class="fa-solid fa-location-dot"></i>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-medium leading-5 text-gray-800">${displayName}</span>
                            <span class="mt-1 flex items-center gap-1.5 text-xs text-gray-500">
                                <i class="fa-solid fa-map-pin"></i>
                                ${barangay}, ${city}
                            </span>
                            ${!hasBarangay ? `
                                                                    <span class="mt-1.5 block text-xs font-medium text-red-500">
                                                                        This result is outside the mapped barangay boundaries.
                                                                    </span>
                                                                ` : ''}
                        </span>

                        ${hasBarangay ? '<i class="fa-solid fa-chevron-right mt-3 text-xs text-gray-300"></i>' : ''}
                    `;

                    if (hasBarangay) {
                        button.addEventListener('click', () => selectLocation(result));
                    }

                    searchResults.appendChild(button);
                });

                searchResults.classList.remove('hidden');
            }

            function selectLocation(result) {
                const latitude = Number.parseFloat(result.lat);
                const longitude = Number.parseFloat(result.lng);

                if (Number.isNaN(latitude) || Number.isNaN(longitude)) {
                    showDropdownError('The selected location returned invalid coordinates.');
                    return;
                }

                if (!result.barangay_id) {
                    showDropdownError('The barangay could not be detected for this location.');
                    return;
                }
                const selectedName = String(
                    result.name ||
                    result.display_name?.split(',')[0] ||
                    modalName.value
                ).trim();

                if (!selectedName) {
                    showDropdownError(
                        'The selected location does not contain an institution name.'
                    );
                    return;
                }

                modalName.value = selectedName;
                selectedInstitutionName.value = selectedName;
                barangayId.value = result.barangay_id;
                latitudeInput.value = latitude.toFixed(7);
                longitudeInput.value = longitude.toFixed(7);

                detectedBarangay.textContent = result.barangay || 'Not detected';
                detectedCity.textContent = result.city || 'Not detected';
                detectedProvince.textContent = result.province || 'Not detected';
                detectedCountry.textContent = result.country || 'Not detected';
                detectedLatitude.textContent = latitude.toFixed(7);
                detectedLongitude.textContent = longitude.toFixed(7);

                setLocationStatus('success');
                hideLocationValidation();
                createButton.disabled = false;
                setMapLocation(latitude, longitude);
                hideSearchResults();
            }

            function resetDetectedLocation(resetMapLocation = true) {
                barangayId.value = '';
                latitudeInput.value = '';
                longitudeInput.value = '';
                detectedBarangay.textContent = 'Not detected';
                detectedCity.textContent = 'Not detected';
                detectedProvince.textContent = 'Not detected';
                detectedCountry.textContent = 'Not detected';
                detectedLatitude.textContent = '—';
                detectedLongitude.textContent = '—';
                createButton.disabled = true;
                setLocationStatus('waiting');
                hideLocationValidation();

                if (resetMapLocation) {
                    resetMap();
                }
            }

            function setLocationStatus(status) {
                const icon = locationStatusBadge.querySelector('i');

                if (status === 'success') {
                    locationStatusBadge.className =
                        'inline-flex w-fit items-center gap-1.5 rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-medium text-green-700';
                    icon.className = 'fa-solid fa-circle-check';
                    locationStatusText.textContent = 'Location detected';
                    return;
                }

                if (status === 'error') {
                    locationStatusBadge.className =
                        'inline-flex w-fit items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-medium text-red-700';
                    icon.className = 'fa-solid fa-circle-exclamation';
                    locationStatusText.textContent = 'Location required';
                    return;
                }

                locationStatusBadge.className =
                    'inline-flex w-fit items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-500';
                icon.className = 'fa-regular fa-clock';
                locationStatusText.textContent = 'Waiting for selection';
            }

            function showLocationValidation(message) {
                locationValidationText.textContent = message;
                locationValidationMessage.classList.remove('hidden');
                setLocationStatus('error');
            }

            function hideLocationValidation() {
                locationValidationMessage.classList.add('hidden');
            }

            function showDropdownLoading() {
                searchResults.innerHTML = `
                    <div class="flex items-center justify-center gap-2 px-4 py-5 text-sm text-gray-500">
                        <i class="fa-solid fa-spinner fa-spin text-red-600"></i>
                        Searching for matching locations
                    </div>
                `;
                searchResults.classList.remove('hidden');
            }

            function showDropdownError(message) {
                searchResults.innerHTML = `
                    <div class="flex items-start gap-3 bg-red-50 px-4 py-4 text-sm text-red-700">
                        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                        <span>${escapeHtml(message)}</span>
                    </div>
                `;
                searchResults.classList.remove('hidden');
            }

            function hideSearchResults() {
                searchResults.classList.add('hidden');
                searchResults.innerHTML = '';
            }

            function showNameError() {
                modalName.classList.add('border-red-500');
                modalName.style.boxShadow = '0 0 0 3px rgb(254 202 202 / 0.8)';
            }

            function clearNameError() {
                modalName.classList.remove('border-red-500');
                modalName.style.boxShadow = '';
            }

            function resetCreateButton() {
                createButton.innerHTML = '<i class="fa-solid fa-plus"></i><span>Create Institution</span>';
                createButton.disabled = true;
            }

            function configureToastr() {
                if (!window.toastr) {
                    return;
                }

                window.toastr.options = {
                    ...window.toastr.options,
                    closeButton: true,
                    progressBar: true,
                    newestOnTop: true,
                    preventDuplicates: true,
                    positionClass: 'toast-top-right',
                    timeOut: 3500,
                    extendedTimeOut: 1000,
                    showDuration: 200,
                    hideDuration: 200,
                    showMethod: 'fadeIn',
                    hideMethod: 'fadeOut'
                };
            }

            function notify(type, message) {
                if (
                    window.toastr &&
                    typeof window.toastr[type] === 'function'
                ) {
                    window.toastr[type](message);
                    return;
                }

                showFallbackToast(type, message);
            }

            function showFallbackToast(type, message) {
                const container = document.getElementById(
                    'institutionToastContainer'
                );

                if (!container) {
                    return;
                }

                const isSuccess = type === 'success';
                const toast = document.createElement('div');

                toast.className = [
                    'pointer-events-auto flex w-full max-w-sm translate-x-4 items-start gap-3',
                    'rounded-xl border bg-white px-4 py-3 opacity-0 shadow-lg',
                    'transition duration-200',
                    isSuccess ? 'border-green-200' : 'border-red-200'
                ].join(' ');

                toast.innerHTML = `
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${
                        isSuccess
                            ? 'bg-green-50 text-green-600'
                            : 'bg-red-50 text-red-600'
                    }">
                        <i class="fa-solid ${
                            isSuccess
                                ? 'fa-circle-check'
                                : 'fa-circle-exclamation'
                        }"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900">
                            ${isSuccess ? 'Success' : 'Error'}
                        </p>
                        <p class="mt-0.5 text-sm leading-5 text-gray-600">
                            ${escapeHtml(message)}
                        </p>
                    </div>
                    <button type="button"
                        class="text-gray-400 transition hover:text-gray-600"
                        aria-label="Close notification">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                `;

                const removeToast = () => {
                    toast.classList.add('translate-x-4', 'opacity-0');

                    setTimeout(() => toast.remove(), 200);
                };

                toast.querySelector('button')?.addEventListener(
                    'click',
                    removeToast
                );

                container.appendChild(toast);

                requestAnimationFrame(() => {
                    toast.classList.remove('translate-x-4', 'opacity-0');
                });

                setTimeout(removeToast, 4000);
            }   

            function escapeHtml(value) {
                const element = document.createElement('div');
                element.textContent = String(value ?? '');
                return element.innerHTML;
            }

            window.InstitutionModal = {
                open: openModal,
                close: closeModal, 
                notify
            };
            resetDetectedLocation(false);

            @if ($errors->any())
                openModal();
            @endif
        });
    </script>