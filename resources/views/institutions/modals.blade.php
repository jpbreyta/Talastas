{{-- Reusable institution modals --}}
@include('institutions._form-styles')
{{-- Add Institution Modal --}}
        <div id="addInstitutionModal" class="institution-form fixed inset-0 z-[10000] hidden overflow-hidden" aria-hidden="true" inert
            role="dialog" aria-modal="true" aria-labelledby="addInstitutionModalTitle">
            <div class="flex h-full items-center justify-center p-2 sm:p-4">
                <div id="modalOverlay" class="fixed inset-0 bg-gray-900/60 backdrop-blur-[1px]"></div>

                <div
                    class="institution-create-dialog relative flex w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                    {{-- Modal Header --}}
                    <header class="flex shrink-0 items-center justify-between gap-4 border-b border-gray-200 bg-white px-4 py-3 sm:px-6">
                        <div class="min-w-0">
                            <h2 id="addInstitutionModalTitle" class="text-lg font-semibold text-gray-900">Create Institution</h2>
                            <p class="mt-0.5 text-xs text-gray-500">Add an institution and map its location.</p>
                        </div>
                            <button type="button" id="closeAddModal"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100"
                                aria-label="Close modal">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>

                    </header>

                    {{-- Modal Form --}}
                    <form method="POST" action="{{ route('institutions.store') }}" id="addInstitutionForm"
                        class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
                        @csrf
                        <div id="institutionFormErrors" role="alert" tabindex="-1"
                            class="hidden m-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"></div>

                        <div class="space-y-6 p-4 sm:p-6">
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
                                    @include('institutions._sector-field', ['fieldId' => 'modalType', 'sectorValue' => ''])

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
                                                Core Internet measurements and ICT inventory counts are maintained from the inventory workflow after the institution is created.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </section>

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
                                            class="flex flex-col gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-map-location-dot text-red-600"></i>
                                                <div>
                                                    <h4 class="text-sm font-semibold text-gray-800">Location Preview</h4>
                                                    <p class="text-[11px] text-gray-500">
                                                        Exact OpenStreetMap boundaries are shown when available.
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-2">
                                                <span id="boundaryStatusBadge"
                                                    class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-medium text-gray-500">
                                                    <i class="fa-solid fa-draw-polygon"></i>
                                                    <span id="boundaryStatusText">No boundary</span>
                                                </span>

                                                <button type="button" id="drawBoundaryBtn"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-100">
                                                    <i class="fa-solid fa-pen-ruler"></i>
                                                    Draw Boundary
                                                </button>

                                                <button type="button" id="editBoundaryBtn" disabled
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                    Edit
                                                </button>

                                                <button type="button" id="resetOsmBoundaryBtn" disabled
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-40">
                                                    <i class="fa-solid fa-rotate-left"></i>
                                                    Reset to map data
                                                </button>

                                                <button type="button" id="clearBoundaryBtn" disabled
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40">
                                                    <i class="fa-solid fa-eraser"></i>
                                                    Clear
                                                </button>
                                            </div>
                                        </div>

                                        <div id="mapPickerContainer" class="relative bg-gray-100">
                                            <div id="mapPicker" class="h-full w-full"></div>

                                            <div id="mapPlaceholder"
                                                class="pointer-events-none absolute inset-0 z-[500] flex items-center justify-center bg-gray-50">
                                                <div class="px-4 text-center">
                                                    <div
                                                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-500">
                                                        <i class="fa-solid fa-map-pin"></i>
                                                    </div>
                                                    <p class="mt-3 text-sm font-medium text-gray-600">
                                                        Search a location or enter coordinates
                                                    </p>
                                                    <p class="mt-1 text-xs text-gray-400">
                                                        The institution point and available boundary will appear here.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">
                                            <details data-location-options class="location-options" @if (old('latitude') !== null || old('longitude') !== null || $errors->any()) open @endif>
                                                <summary class="location-options-toggle"><i class="fa-solid fa-sliders" aria-hidden="true"></i>Show more options<i class="fa-solid fa-chevron-down location-options-chevron" aria-hidden="true"></i></summary>
                                                <div class="location-method-grid">
                                                    @include('institutions._gps')
                                                    <div class="location-method-card">
                                                        <h4><i class="fa-solid fa-map-pin" aria-hidden="true"></i>Enter Exact Coordinates</h4>
                                                        <p class="location-method-help">Use a known latitude and longitude, or select a point on the map.</p>
                                                        <button type="button" id="toggleManualCoordinates" class="location-method-button" aria-expanded="false" aria-controls="manualCoordinatePanel"><i class="fa-solid fa-map-pin" aria-hidden="true"></i>Enter Exact Coordinates</button>
                                            <div id="manualCoordinatePanel"
                                                class="mt-3 hidden rounded-xl border border-gray-200 bg-white p-4">
                                                <div class="grid grid-cols-1 gap-3 xl:grid-cols-2 xl:items-end">
                                                    <div>
                                                        <label for="manualLatitude"
                                                            class="mb-1.5 block text-xs font-semibold text-gray-600">
                                                            Latitude
                                                        </label>
                                                        <input type="text" inputmode="decimal" pattern="-?[0-9]+([.][0-9]+)?" min="-90" max="90"
                                                            id="manualLatitude" class="form-input w-full"
                                                            placeholder="13.9411000">
                                                    </div>

                                                    <div>
                                                        <label for="manualLongitude"
                                                            class="mb-1.5 block text-xs font-semibold text-gray-600">
                                                            Longitude
                                                        </label>
                                                        <input type="text" inputmode="decimal" pattern="-?[0-9]+([.][0-9]+)?" min="-180" max="180"
                                                            id="manualLongitude" class="form-input w-full"
                                                            placeholder="121.1631000">
                                                    </div>

                                                    <button type="button" id="applyManualCoordinates"
                                                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">
                                                        <i class="fa-solid fa-check"></i>
                                                        Use Coordinates
                                                    </button>
                                                </div>

                                                <p class="mt-2 text-[11px] leading-5 text-gray-500">
                                                    You can also click the map while this panel is open to fill the latitude and longitude. TALASTAS will detect the containing Lipa City barangay before accepting the point.
                                                </p>
                                            </div>
                                                    </div>
                                                </div>
                                            </details>
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
                                                        Selected Location
                                                    </h4>
                                                    <p class="mt-0.5 text-xs text-gray-500">
                                                        Search result or validated manual coordinates
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
                                            role="alert" class="hidden border-t border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
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
                                    <input type="hidden" name="location_geojson" id="modalLocationGeojson"
                                        value="{{ old('location_geojson') }}">
                                    <input type="hidden" name="location_bounding_box" id="modalLocationBoundingBox"
                                        value="{{ old('location_bounding_box') }}">
                                    <input type="hidden" name="nominatim_place_id" id="modalNominatimPlaceId"
                                        value="{{ old('nominatim_place_id') }}">
                                    <input type="hidden" name="osm_type" id="modalOsmType"
                                        value="{{ old('osm_type') }}">
                                    <input type="hidden" name="osm_id" id="modalOsmId"
                                        value="{{ old('osm_id') }}">
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



<style>
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

    </style>

<script>
        document.addEventListener('DOMContentLoaded', () => {
            'use strict';

            const defaultCenter = [13.9411, 121.1631];
            const defaultZoom = 13;

            const modal = document.getElementById('addInstitutionModal');
            // Keep the viewport overlay outside application stacking/scroll containers.
            document.body.appendChild(modal);
            let previousBodyOverflow;
            let previousRootOverflow;
            const modalOverlay = document.getElementById('modalOverlay');
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
            const locationGeojsonInput = document.getElementById('modalLocationGeojson');
            const locationBoundingBoxInput = document.getElementById('modalLocationBoundingBox');
            const nominatimPlaceIdInput = document.getElementById('modalNominatimPlaceId');
            const osmTypeInput = document.getElementById('modalOsmType');
            const osmIdInput = document.getElementById('modalOsmId');

            const geocodeButton = document.getElementById('geocodeSearchBtn');
            const geocodeButtonText = document.getElementById('geocodeSearchButtonText');
            const searchResults = document.getElementById('searchResultsDropdown');

            const toggleManualCoordinatesButton = document.getElementById('toggleManualCoordinates');
            const manualCoordinatePanel = document.getElementById('manualCoordinatePanel');
            const manualLatitude = document.getElementById('manualLatitude');
            const manualLongitude = document.getElementById('manualLongitude');
            const applyManualCoordinatesButton = document.getElementById('applyManualCoordinates');

            const drawBoundaryButton = document.getElementById('drawBoundaryBtn');
            const editBoundaryButton = document.getElementById('editBoundaryBtn');
            const resetOsmBoundaryButton = document.getElementById('resetOsmBoundaryBtn');
            const clearBoundaryButton = document.getElementById('clearBoundaryBtn');
            const boundaryStatusBadge = document.getElementById('boundaryStatusBadge');
            const boundaryStatusText = document.getElementById('boundaryStatusText');

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

            let mapPicker = null;
            let pickerMarker = null;
            let pickerBoundaryLayer = null;
            let pickerExtentLayer = null;
            let drawingBoundary = false;
            let boundaryEditing = false;
            let lastFocusedElement = null;
            let lastDeleteFocusedElement = null;
            let pendingDelete = null;

            document.addEventListener('click', (event) => {
                const add = event.target.closest('[data-open-institution-modal]');
                if (add) openModal();
                const button = event.target.closest('.delete-institution-button');
                if (button) openDeleteModal(button);
            });

            closeModalButton?.addEventListener('click', closeModal);
            cancelModalButton?.addEventListener('click', closeModal);
            modalOverlay?.addEventListener('click', closeModal);

            closeDeleteModalButton?.addEventListener('click', closeDeleteModal);
            cancelDeleteModalButton?.addEventListener('click', closeDeleteModal);
            deleteModalOverlay?.addEventListener('click', closeDeleteModal);
            confirmDeleteButton?.addEventListener('click', deleteInstitution);
            geocodeButton?.addEventListener('click', searchLocations);
            toggleManualCoordinatesButton?.addEventListener('click', toggleManualCoordinates);
            applyManualCoordinatesButton?.addEventListener('click', applyManualCoordinates);
            drawBoundaryButton?.addEventListener('click', startBoundaryDrawing);
            editBoundaryButton?.addEventListener('click', enableBoundaryEditing);
            resetOsmBoundaryButton?.addEventListener('click', resetBoundaryToOsm);
            clearBoundaryButton?.addEventListener('click', () => clearBoundary(true));

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
            const formErrors = document.getElementById('institutionFormErrors');
            let submissionErrors = {};
            function renderSubmissionErrors(errors) {
                submissionErrors = errors;
                formErrors.replaceChildren();
                Object.entries(errors).forEach(([field, messages]) => {
                    const line = document.createElement('p');
                    line.textContent = (field === 'name' ? 'Institution name: ' : '') + [].concat(messages).join(' ');
                    formErrors.appendChild(line);
                });
                formErrors.classList.toggle('hidden', !Object.keys(errors).length);
                modalName.setAttribute('aria-invalid', String(Boolean(errors.name)));
                modalName.setAttribute('aria-describedby', 'institutionFormErrors');
            }
            form.addEventListener('input', event => {
                const field = event.target === modalName ? 'name' : event.target.name;
                if (field && submissionErrors[field]) {
                    const errors = { ...submissionErrors };
                    delete errors[field];
                    renderSubmissionErrors(errors);
                }
            });
            form.addEventListener('reset', () => renderSubmissionErrors({}));
            for (const input of [manualLatitude, manualLongitude]) {
                input.addEventListener('input', () => {
                    latitudeInput.value = '';
                    longitudeInput.value = '';
                    createButton.disabled = true;
                    setLocationStatus('waiting', 'Apply the updated coordinates');
                });
            }
            let institutionSubmitting = false;
            form?.addEventListener('submit', async event => {
                event.preventDefault();
                if (!gps?.canSubmit()) { event.preventDefault(); return; }
                if (institutionSubmitting) {
                    event.preventDefault();
                    return;
                }
                if (!selectedInstitutionName.value.trim()) {
                    event.preventDefault();
                    const message = 'Search and select a location, or apply manual coordinates, before creating it.';
                    showLocationValidation(message);
                    notify('error', message);

                    modalName.focus();
                    return;
                }

                if (!barangayId.value) {
                    event.preventDefault();
                    const message = 'Select a valid location or apply coordinates inside a mapped Lipa City barangay before creating the institution.';
                    showLocationValidation(message);
                    notify('error', message);

                    document
                        .getElementById('detectedLocationCard')
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                    return;
                }

                institutionSubmitting = true;
                createButton.disabled = true;
                createButton.innerHTML =
                    '<i class="fa-solid global-loading-icon"></i>' +
                    '<span>Creating Institution</span>';
                form.setAttribute('aria-busy', 'true');
                try {
                    const response = await fetch(form.action, {
                        method: 'POST', body: new FormData(form), credentials: 'same-origin',
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok || payload.success !== true) {
                        const errors = response.status === 422 && payload.errors ? payload.errors : {
                            form: [response.status === 419
                                ? 'Your session expired. Refresh the page before trying again.'
                                : 'Unable to save the institution. Check your connection and try again.']
                        };
                        renderSubmissionErrors(errors);
                        formErrors.focus();
                        formErrors.scrollIntoView({ block: 'nearest' });
                        notify('error', Object.values(errors).flat()[0]);
                        return;
                    }
                    try { sessionStorage.setItem('institutionToast', JSON.stringify({ type: 'success', message: 'Institution created successfully.' })); } catch { /* Saving succeeded even if browser storage is unavailable. */ }
                    window.location.assign(payload.redirect);
                } catch (error) {
                    console.error('Institution submission failed', error);
                    const message = 'Unable to confirm the save. Check your connection and try again.';
                    renderSubmissionErrors({ form: [message] });
                    notify('error', message);
                    formErrors.focus();
                } finally {
                    institutionSubmitting = false;
                    form.removeAttribute('aria-busy');
                    resetCreateButton();
                    createButton.disabled = !barangayId.value || !latitudeInput.value || !longitudeInput.value;
                }
            });

            async function openModal(event) {
                try {
                    await ensureGps();
                } catch (error) {
                    notify('error', error.message || 'Location tools could not be loaded. Please try again.');
                    return;
                }
                if (modal.classList.contains('hidden')) {
                    previousBodyOverflow = document.body.style.overflow;
                    previousRootOverflow = document.documentElement.style.overflow;
                }
                lastFocusedElement = event?.currentTarget || document.activeElement;
                modal.classList.remove('hidden');
                modal.removeAttribute('aria-hidden');
                modal.removeAttribute('inert');
                document.body.style.overflow = 'hidden';
                document.documentElement.style.overflow = 'hidden';

                setTimeout(() => {
                    initializeMap();
                    mapPicker?.invalidateSize();
                    modalName?.focus();
                }, 100);
            }

            function closeModal() {
                if (institutionSubmitting) return;
                if (document.activeElement && modal.contains(document.activeElement)) {
                    document.activeElement.blur();
                }

                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                modal.setAttribute('inert', '');
                document.body.style.overflow = previousBodyOverflow ?? '';
                document.documentElement.style.overflow = previousRootOverflow ?? '';

                hideSearchResults();
                clearNameError();
                form?.reset();
                resetDetectedLocation(false);

                if (mapPicker) {
                    mapPicker.remove();
                    mapPicker = null;
                }

                pickerMarker = null;
                pickerBoundaryLayer = null;
                pickerExtentLayer = null;
                drawingBoundary = false;
                boundaryEditing = false;

                manualCoordinatePanel?.classList.add('hidden');
                const options = form.querySelector('[data-location-options]');
                if (options) options.open = false;
                drawBoundaryButton.innerHTML =
                    '<i class="fa-solid fa-pen-ruler"></i> Draw Boundary';
                editBoundaryButton.innerHTML =
                    '<i class="fa-solid fa-pen-to-square"></i> Edit';
                editBoundaryButton.disabled = true;
                resetOsmBoundaryButton.disabled = true;
                clearBoundaryButton.disabled = true;
                setBoundaryStatus('none');

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

                const actionsMenuId = button.closest('[data-institution-actions-menu]')?.id;
                lastDeleteFocusedElement = actionsMenuId
                    ? document.querySelector(`[data-institution-actions-toggle][aria-controls="${actionsMenuId}"]`) || button
                    : button;
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
                            (response.status >= 500 ? 'Unable to retrieve location information. Please try again.' : data?.message) ||
                            (response.status === 419
                                ? 'Your session expired. Refresh the page and try again.'
                                : 'The institution could not be deleted.')
                        );
                    }

                    const message = (response.status >= 500 ? 'Unable to retrieve location information. Please try again.' : data?.message) || 'Institution deleted successfully.';
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
                                        type: 'deleted',
                                        message
                                    })
                                );

                                window.location.reload();
                                return;
                            }

                            notify('deleted', message);
                        }, 180);
                    } else {
                        notify('deleted', message);
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
                    ? '<i class="fa-solid global-loading-icon"></i><span>Deleting</span>'
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
                            <p class="mt-1 text-xs text-red-500">Refresh after rebuilding the Vite assets.</p>
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

                mapPicker.on('click', event => {
                    if (
                        drawingBoundary
                        || manualCoordinatePanel?.classList.contains('hidden')
                    ) {
                        return;
                    }

                    manualLatitude.value = event.latlng.lat;
                    manualLongitude.value = event.latlng.lng;

                    setPointMarker(
                        event.latlng.lat,
                        event.latlng.lng
                    );
                });

                mapPicker.on('pm:create', event => {
                    if (event.shape !== 'Polygon') {
                        return;
                    }

                    drawingBoundary = false;
                    mapPicker.pm?.disableDraw('Polygon');

                    clearBoundaryLayers();

                    pickerBoundaryLayer = event.layer;
                    applyBoundaryStyle(pickerBoundaryLayer);
                    bindBoundaryEditListeners(pickerBoundaryLayer);
                    syncCustomBoundaryMetadata();
                    fitMapToCurrentSelection();

                    drawBoundaryButton.innerHTML =
                        '<i class="fa-solid fa-pen-ruler"></i> Draw Boundary';

                    editBoundaryButton.disabled = false;
                    clearBoundaryButton.disabled = false;
                    setBoundaryStatus('custom');
                });
            }

            function setPointMarker(latitude, longitude) {
                initializeMap();

                if (!mapPicker) {
                    return;
                }

                const latLng = [latitude, longitude];
                mapPlaceholder?.classList.add('hidden');

                if (pickerMarker) {
                    pickerMarker.setLatLng(latLng);
                } else {
                    pickerMarker = L.marker(latLng, {
                        draggable: true,
                        autoPan: true
                    }).addTo(mapPicker);

                    pickerMarker.on('dragend', event => {
                        const position = event.target.getLatLng();

                        manualCoordinatePanel?.classList.remove('hidden');
                        manualLatitude.value = position.lat;
                        manualLongitude.value = position.lng;

                        applyManualCoordinates();
                    });
                }

                pickerMarker.setZIndexOffset(1000);
            }

            function setMapLocation(
                latitude,
                longitude,
                geometry = null,
                boundingBox = null
            ) {
                initializeMap();

                if (!mapPicker) {
                    return;
                }

                clearBoundaryLayers();
                setPointMarker(latitude, longitude);

                resetOsmBoundaryButton.disabled =
                    !modalName.value.trim();

                const geometryType = geometry?.type;

                if (
                    geometry
                    && ['Polygon', 'MultiPolygon'].includes(geometryType)
                ) {
                    pickerBoundaryLayer = L.geoJSON(geometry, {
                        style: {
                            color: '#dc2626',
                            weight: 3,
                            opacity: 0.95,
                            fillColor: '#ef4444',
                            fillOpacity: 0.12
                        }
                    }).addTo(mapPicker);

                    bindBoundaryEditListeners(pickerBoundaryLayer);

                    editBoundaryButton.disabled = false;
                    clearBoundaryButton.disabled = false;
                    setBoundaryStatus('osm');
                } else if (hasUsableBoundingBox(boundingBox)) {
                    const south = Number.parseFloat(boundingBox[0]);
                    const north = Number.parseFloat(boundingBox[1]);
                    const west = Number.parseFloat(boundingBox[2]);
                    const east = Number.parseFloat(boundingBox[3]);

                    pickerExtentLayer = L.rectangle(
                        [[south, west], [north, east]],
                        {
                            color: '#f59e0b',
                            weight: 2,
                            opacity: 0.9,
                            fillColor: '#fbbf24',
                            fillOpacity: 0.08,
                            dashArray: '6 6'
                        }
                    ).addTo(mapPicker);

                    clearBoundaryButton.disabled = false;
                    setBoundaryStatus('extent');
                } else {
                    setBoundaryStatus('none');
                }

                fitMapToCurrentSelection();
                setTimeout(() => mapPicker.invalidateSize(), 50);
            }

            function hasUsableBoundingBox(value) {
                if (!Array.isArray(value) || value.length !== 4) {
                    return false;
                }

                const numbers = value.map(Number.parseFloat);

                if (numbers.some(Number.isNaN)) {
                    return false;
                }

                const [south, north, west, east] = numbers;

                return north > south && east > west;
            }

            function fitMapToCurrentSelection() {
                if (!mapPicker) {
                    return;
                }

                const boundaryBounds = pickerBoundaryLayer?.getBounds?.();

                if (boundaryBounds?.isValid?.()) {
                    mapPicker.fitBounds(boundaryBounds, {
                        padding: [28, 28],
                        maxZoom: 18
                    });
                    return;
                }

                const extentBounds = pickerExtentLayer?.getBounds?.();

                if (extentBounds?.isValid?.()) {
                    mapPicker.fitBounds(extentBounds, {
                        padding: [28, 28],
                        maxZoom: 18
                    });
                    return;
                }

                if (pickerMarker) {
                    mapPicker.setView(
                        pickerMarker.getLatLng(),
                        17
                    );
                    return;
                }

                mapPicker.setView(defaultCenter, defaultZoom);
            }

            function clearBoundaryLayers() {
                if (!mapPicker) {
                    pickerBoundaryLayer = null;
                    pickerExtentLayer = null;
                    return;
                }

                if (pickerBoundaryLayer) {
                    mapPicker.removeLayer(pickerBoundaryLayer);
                    pickerBoundaryLayer = null;
                }

                if (pickerExtentLayer) {
                    mapPicker.removeLayer(pickerExtentLayer);
                    pickerExtentLayer = null;
                }

                boundaryEditing = false;
                editBoundaryButton.disabled = true;
                clearBoundaryButton.disabled = true;
                resetOsmBoundaryButton.disabled =
                    !modalName.value.trim()
                    || !latitudeInput.value
                    || !longitudeInput.value;
                editBoundaryButton.innerHTML =
                    '<i class="fa-solid fa-pen-to-square"></i> Edit';
            }

            function clearBoundary(clearStoredMetadata = true) {
                clearBoundaryLayers();

                if (clearStoredMetadata) {
                    locationGeojsonInput.value = '';
                    locationBoundingBoxInput.value = '';
                }

                setBoundaryStatus('none');
                fitMapToCurrentSelection();
            }

            async function resetBoundaryToOsm() {
                const name = modalName.value.trim();
                const latitude =
                    Number.parseFloat(latitudeInput.value);
                const longitude =
                    Number.parseFloat(longitudeInput.value);

                if (
                    !name
                    || !Number.isFinite(latitude)
                    || !Number.isFinite(longitude)
                ) {
                    showLocationValidation(
                        'Select the institution and its coordinates before resetting the boundary.'
                    );
                    return;
                }

                if (resetOsmBoundaryButton.disabled) {
                    return;
                }

                const originalHtml =
                    resetOsmBoundaryButton.innerHTML;

                resetOsmBoundaryButton.disabled = true;
                resetOsmBoundaryButton.innerHTML =
                    '<i class="fa-solid global-loading-icon"></i> Fetching map data';

                try {
                    const response = await fetch(
                        '{{ route('institutions.osm-boundary.resolve') }}',
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN':
                                    document
                                        .querySelector(
                                            'meta[name="csrf-token"]'
                                        )
                                        ?.getAttribute('content')
                                    || ''
                            },
                            body: JSON.stringify({
                                name,
                                latitude,
                                longitude,
                                osm_type:
                                    osmTypeInput.value
                                    || null,
                                osm_id:
                                    osmIdInput.value
                                    || null
                            })
                        }
                    );

                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(
                            (response.status >= 500 ? 'Unable to retrieve the institution boundary. Please try again.' : result?.message)
                            || 'The map boundary could not be retrieved. Please try again.'
                        );
                    }

                    if (
                        !result?.geojson
                        || !['Polygon', 'MultiPolygon'].includes(
                            result.geojson.type
                        )
                    ) {
                        throw new Error(
                            'OpenStreetMap did not return a verified institution boundary.'
                        );
                    }

                    if (drawingBoundary) {
                        mapPicker.pm?.disableDraw(
                            'Polygon'
                        );
                        drawingBoundary = false;
                        drawBoundaryButton.innerHTML =
                            '<i class="fa-solid fa-pen-ruler"></i> Draw Boundary';
                    }

                    disableBoundaryEditing();

                    locationGeojsonInput.value =
                        JSON.stringify(
                            result.geojson
                        );

                    locationBoundingBoxInput.value =
                        Array.isArray(
                            result.boundingbox
                        )
                            ? JSON.stringify(
                                result.boundingbox
                            )
                            : '';

                    nominatimPlaceIdInput.value =
                        result.place_id ?? '';

                    osmTypeInput.value =
                        result.osm_type ?? '';

                    osmIdInput.value =
                        result.osm_id ?? '';

                    setMapLocation(
                        latitude,
                        longitude,
                        result.geojson,
                        result.boundingbox ?? null
                    );

                    setBoundaryStatus('osm');
                    hideLocationValidation();
                } catch (error) {
                    showLocationValidation(
                        error instanceof Error
                            ? error.message
                            : 'OpenStreetMap boundary could not be fetched.'
                    );
                } finally {
                    resetOsmBoundaryButton.disabled = false;
                    resetOsmBoundaryButton.innerHTML =
                        originalHtml;
                }
            }

            function applyBoundaryStyle(layer) {
                const styleLayer = child => {
                    if (typeof child.setStyle === 'function') {
                        child.setStyle({
                            color: '#dc2626',
                            weight: 3,
                            opacity: 0.95,
                            fillColor: '#ef4444',
                            fillOpacity: 0.12
                        });
                    }
                };

                if (typeof layer.eachLayer === 'function') {
                    layer.eachLayer(styleLayer);
                } else {
                    styleLayer(layer);
                }
            }

            function bindBoundaryEditListeners(layer) {
                const bind = child => {
                    child.on?.('pm:edit', syncCustomBoundaryMetadata);
                    child.on?.('pm:dragend', syncCustomBoundaryMetadata);
                    child.on?.('pm:markerdragend', syncCustomBoundaryMetadata);
                };

                if (typeof layer.eachLayer === 'function') {
                    layer.eachLayer(bind);
                } else {
                    bind(layer);
                }
            }

            function boundaryGeometryFromLayer(layer) {
                if (!layer?.toGeoJSON) {
                    return null;
                }

                const geojson = layer.toGeoJSON();

                if (geojson?.type === 'Feature') {
                    return geojson.geometry ?? null;
                }

                if (geojson?.type !== 'FeatureCollection') {
                    return geojson?.geometry ?? null;
                }

                const geometries = geojson.features
                    .map(feature => feature?.geometry)
                    .filter(geometry =>
                        geometry
                        && ['Polygon', 'MultiPolygon'].includes(geometry.type)
                    );

                if (geometries.length === 0) {
                    return null;
                }

                if (geometries.length === 1) {
                    return geometries[0];
                }

                const coordinates = [];

                geometries.forEach(geometry => {
                    if (geometry.type === 'Polygon') {
                        coordinates.push(geometry.coordinates);
                    } else {
                        coordinates.push(...geometry.coordinates);
                    }
                });

                return {
                    type: 'MultiPolygon',
                    coordinates
                };
            }

            function syncCustomBoundaryMetadata() {
                if (!pickerBoundaryLayer) {
                    return;
                }

                const geometry = boundaryGeometryFromLayer(
                    pickerBoundaryLayer
                );

                if (!geometry) {
                    return;
                }

                locationGeojsonInput.value = JSON.stringify(geometry);

                const bounds = pickerBoundaryLayer.getBounds?.();

                if (bounds?.isValid?.()) {
                    locationBoundingBoxInput.value = JSON.stringify([
                        bounds.getSouth(),
                        bounds.getNorth(),
                        bounds.getWest(),
                        bounds.getEast()
                    ]);
                }

                /*
                 * Once the boundary is manually changed, it is no longer the
                 * untouched map data geometry associated with the original element.
                 */
                nominatimPlaceIdInput.value = '';
                osmTypeInput.value = '';
                osmIdInput.value = '';

                setBoundaryStatus('custom');
            }

            function setBoundaryStatus(status) {
                const icon = boundaryStatusBadge?.querySelector('i');

                if (!boundaryStatusText || !boundaryStatusBadge || !icon) {
                    return;
                }

                const states = {
                    osm: {
                        text: 'map data boundary',
                        classes: 'border-green-200 bg-green-50 text-green-700',
                        icon: 'fa-solid fa-draw-polygon'
                    },
                    custom: {
                        text: 'Custom boundary',
                        classes: 'border-blue-200 bg-blue-50 text-blue-700',
                        icon: 'fa-solid fa-pen-ruler'
                    },
                    extent: {
                        text: 'map data extent',
                        classes: 'border-amber-200 bg-amber-50 text-amber-700',
                        icon: 'fa-solid fa-vector-square'
                    },
                    none: {
                        text: 'No boundary',
                        classes: 'border-gray-200 bg-white text-gray-500',
                        icon: 'fa-solid fa-location-dot'
                    }
                };

                const state = states[status] ?? states.none;

                boundaryStatusBadge.className =
                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-medium '
                    + state.classes;

                icon.className = state.icon;
                boundaryStatusText.textContent = state.text;
            }

            function startBoundaryDrawing() {
                initializeMap();

                if (!mapPicker?.pm) {
                    showLocationValidation(
                        'Boundary drawing is unavailable. Refresh the page and try again.'
                    );
                    return;
                }

                if (!latitudeInput.value || !longitudeInput.value) {
                    showLocationValidation(
                        'Select or apply the institution coordinates before drawing its boundary.'
                    );
                    return;
                }

                if (drawingBoundary) {
                    mapPicker.pm.disableDraw('Polygon');
                    drawingBoundary = false;
                    drawBoundaryButton.innerHTML =
                        '<i class="fa-solid fa-pen-ruler"></i> Draw Boundary';
                    return;
                }

                disableBoundaryEditing();

                drawingBoundary = true;
                drawBoundaryButton.innerHTML =
                    '<i class="fa-solid fa-xmark"></i> Cancel Drawing';

                mapPicker.pm.enableDraw('Polygon', {
                    snappable: true,
                    allowSelfIntersection: false,
                    finishOn: 'dblclick',
                    templineStyle: {
                        color: '#dc2626',
                        weight: 2
                    },
                    hintlineStyle: {
                        color: '#ef4444',
                        dashArray: [5, 5]
                    },
                    pathOptions: {
                        color: '#dc2626',
                        weight: 3,
                        fillColor: '#ef4444',
                        fillOpacity: 0.12
                    }
                });
            }

            function forEachBoundaryLayer(callback) {
                if (!pickerBoundaryLayer) {
                    return;
                }

                if (typeof pickerBoundaryLayer.eachLayer === 'function') {
                    pickerBoundaryLayer.eachLayer(callback);
                } else {
                    callback(pickerBoundaryLayer);
                }
            }

            function enableBoundaryEditing() {
                if (!pickerBoundaryLayer) {
                    return;
                }

                if (boundaryEditing) {
                    disableBoundaryEditing();
                    syncCustomBoundaryMetadata();
                    return;
                }

                boundaryEditing = true;

                forEachBoundaryLayer(layer => {
                    layer.pm?.enable({
                        allowSelfIntersection: false,
                        snappable: true
                    });
                });

                editBoundaryButton.innerHTML =
                    '<i class="fa-solid fa-check"></i> Finish Edit';

                setBoundaryStatus('custom');
            }

            function disableBoundaryEditing() {
                if (!pickerBoundaryLayer) {
                    boundaryEditing = false;
                    return;
                }

                forEachBoundaryLayer(layer => {
                    layer.pm?.disable();
                });

                boundaryEditing = false;

                editBoundaryButton.innerHTML =
                    '<i class="fa-solid fa-pen-to-square"></i> Edit';
            }

            function toggleManualCoordinates() {
                manualCoordinatePanel?.classList.toggle('hidden');
                toggleManualCoordinatesButton?.setAttribute('aria-expanded', String(!manualCoordinatePanel?.classList.contains('hidden')));

                if (!manualCoordinatePanel?.classList.contains('hidden')) {
                    initializeMap();
                    mapPlaceholder?.classList.add('hidden');
                    setTimeout(() => mapPicker?.invalidateSize(), 50);

                    if (latitudeInput.value && longitudeInput.value) {
                        manualLatitude.value = latitudeInput.value;
                        manualLongitude.value = longitudeInput.value;
                    }
                }
            }

            let gps = null;
            let gpsInitialization = null;
            async function ensureGps() {
                if (gps) return gps;
                if (!gpsInitialization) {
                    gpsInitialization = (async () => {
                        await window.loadInstitutionLocation?.();
                        if (!window.mountInstitutionGps) throw new Error('Location tools are unavailable.');
                        gps = window.mountInstitutionGps(form, async (lat, lng, isCurrent) => {
                            manualCoordinatePanel?.classList.remove('hidden');
                            manualLatitude.value = lat;
                            manualLongitude.value = lng;
                            setMapLocation(lat, lng);
                            return await applyManualCoordinates(true, isCurrent);
                        }, () => mapPicker);
                    })();
                }
                try {
                    await gpsInitialization;
                    return gps;
                } catch (error) {
                    gpsInitialization = null;
                    throw error;
                }
            }

            async function applyManualCoordinates(fromGps = false, isCurrent = () => true) {
                if (fromGps !== true) gps.source('manual');
                if (modalType.dataset.detected === 'true') modalType.value = '';
                modalType.dataset.detected = '';
                document.getElementById('institutionTypeHelp').textContent = 'For GPS or entered coordinates, select the institution type manually. Only public schools and barangay halls are allowed.';
                const name = modalName.value.trim();
                const latitude = manualLatitude.value.trim() === '' ? NaN : Number(manualLatitude.value);
                const longitude = manualLongitude.value.trim() === '' ? NaN : Number(manualLongitude.value);

                clearNameError();
                hideLocationValidation();

                if (!name) {
                    showNameError();
                    showLocationValidation(
                        'Enter the institution name before applying coordinates.'
                    );
                    modalName.focus();
                    return;
                }

                if (
                    Number.isNaN(latitude)
                    || latitude < -90
                    || latitude > 90
                    || Number.isNaN(longitude)
                    || longitude < -180
                    || longitude > 180
                ) {
                    showLocationValidation(
                        'Enter valid latitude and longitude values.'
                    );
                    return;
                }

                applyManualCoordinatesButton.disabled = true;
                applyManualCoordinatesButton.innerHTML =
                    '<i class="fa-solid global-loading-icon"></i> Checking';

                try {
                    const response = await fetch(
                        '{{ url('/institutions/resolve-coordinates') }}',
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document
                                    .querySelector('meta[name="csrf-token"]')
                                    ?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({
                                latitude,
                                longitude
                            })
                        }
                    );

                    let data = {};

                    try {
                        data = await response.json();
                    } catch (error) {
                        data = {};
                    }

                    if (!isCurrent()) return false;
                    if (!response.ok) {
                        const firstError = data?.errors
                            ? Object.values(data.errors)[0]?.[0]
                            : null;

                        showLocationValidation(
                            firstError
                            || (response.status >= 500 ? 'Unable to retrieve location information. Please try again.' : data?.message)
                            || 'The coordinates could not be validated.'
                        );
                        return;
                    }

                    selectedInstitutionName.value = name;
                    barangayId.value = data.barangay_id;
                    latitudeInput.value = Number(data.lat);
                    longitudeInput.value = Number(data.lng);

                    detectedBarangay.textContent =
                        data.barangay || 'Not detected';
                    detectedCity.textContent =
                        data.city || 'Lipa';
                    detectedProvince.textContent =
                        data.province || 'Batangas';
                    detectedCountry.textContent =
                        data.country || 'Philippines';
                    detectedLatitude.textContent =
                        Number(data.lat).toFixed(7);
                    detectedLongitude.textContent =
                        Number(data.lng).toFixed(7);

                    clearBoundary(true);
                    nominatimPlaceIdInput.value = '';
                    osmTypeInput.value = '';
                    osmIdInput.value = '';

                    setLocationStatus('success', 'Manual coordinates');
                    setMapLocation(
                        Number(data.lat),
                        Number(data.lng)
                    );

                    createButton.disabled = false;
                    return true;
                } catch (error) {
                    console.error(
                        'Manual coordinate validation failed:',
                        error
                    );

                    showLocationValidation(
                        'The coordinates could not be validated. Check the connection and try again.'
                    );
                } finally {
                    applyManualCoordinatesButton.disabled = false;
                    applyManualCoordinatesButton.innerHTML =
                        '<i class="fa-solid fa-check"></i> Use Coordinates';
                }
            }

            function resetMap() {
                if (!mapPicker) {
                    mapPlaceholder?.classList.remove('hidden');
                    return;
                }

                if (drawingBoundary) {
                    mapPicker.pm?.disableDraw('Polygon');
                    drawingBoundary = false;
                }

                disableBoundaryEditing();
                clearBoundaryLayers();

                if (pickerMarker) {
                    mapPicker.removeLayer(pickerMarker);
                    pickerMarker = null;
                }

                drawBoundaryButton.innerHTML =
                    '<i class="fa-solid fa-pen-ruler"></i> Draw Boundary';

                mapPicker.setView(defaultCenter, defaultZoom);
                mapPlaceholder?.classList.remove('hidden');
                setBoundaryStatus('none');
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
                        showDropdownError('Unable to retrieve location information. Please try again or enter coordinates manually.');
                        return;
                    }

                    if (response.ok && Array.isArray(data) && data.length > 0) {
                        showSearchResults(data);
                        return;
                    }

                    if (response.status === 422) {
                        const firstError = data?.errors
                            ? Object.values(data.errors)[0]?.[0]
                            : null;

                        showDropdownError(
                            firstError
                            || (response.status >= 500 ? 'Unable to retrieve location information. Please try again.' : data?.message)
                            || 'The search information is invalid.'
                        );
                        return;
                    }

                    showNoLipaResults(
                        (response.status >= 500 ? 'Unable to retrieve location information. Please try again.' : data?.message)
                        || 'No matching institution locations were found inside Lipa City.'
                    );
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
                    icon.className = 'fa-solid global-loading-icon';
                } else {
                    geocodeButtonText.textContent = 'Search Location';
                    icon.className = 'fa-solid fa-magnifying-glass';
                }
            }

            function showSearchResults(results) {
                searchResults.innerHTML = '';

                results.forEach((result, index) => {
                    const hasBoundary = Boolean(result.has_boundary);
                    const hasExtent = hasUsableBoundingBox(
                        result.boundingbox
                    );

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className =
                        'flex w-full cursor-pointer items-start gap-3 border-b border-gray-100 px-4 py-3 text-left transition last:border-b-0 hover:bg-red-50';

                    const displayName = escapeHtml(
                        result.display_name
                        || `Location ${index + 1}`
                    );

                    const barangay = escapeHtml(
                        result.barangay
                        || 'Lipa City barangay'
                    );

                    const geometryBadge = hasBoundary
                        ? '<span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-[11px] font-medium text-green-700"><i class="fa-solid fa-draw-polygon"></i> map data boundary</span>'
                        : (
                            hasExtent
                                ? '<span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700"><i class="fa-solid fa-vector-square"></i> map data extent</span>'
                                : '<span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600"><i class="fa-solid fa-location-dot"></i> Point only</span>'
                        );

                    button.innerHTML = `
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-700">
                            <i class="fa-solid fa-location-dot"></i>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-medium leading-5 text-gray-800">
                                ${displayName}
                            </span>

                            <span class="mt-1 flex items-center gap-1.5 text-xs text-gray-500">
                                <i class="fa-solid fa-map-pin"></i>
                                ${barangay}, Lipa City
                            </span>

                            <span class="mt-2 block">
                                ${geometryBadge}
                            </span>
                        </span>

                        <i class="fa-solid fa-chevron-right mt-3 text-xs text-gray-300"></i>
                    `;

                    button.addEventListener(
                        'click',
                        () => selectLocation(result)
                    );

                    searchResults.appendChild(button);
                });

                searchResults.classList.remove('hidden');
            }

            function showNoLipaResults(message) {
                searchResults.innerHTML = `
                    <div class="bg-amber-50 px-4 py-4">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                                <i class="fa-solid fa-location-crosshairs"></i>
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-amber-900">
                                    No Lipa City result found
                                </p>
                                <p class="mt-1 text-xs leading-5 text-amber-800">
                                    ${escapeHtml(message)}
                                </p>

                                <button type="button"
                                    id="openManualCoordinatesFromSearch"
                                    class="mt-3 inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-200 transition hover:bg-red-50">
                                    <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                                    Show more options
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                searchResults.classList.remove('hidden');

                document
                    .getElementById('openManualCoordinatesFromSearch')
                    ?.addEventListener('click', () => {
                        const options = form.querySelector('[data-location-options]');
                        if (options) options.open = true;
                        hideSearchResults();
                        options?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                        options?.querySelector('[data-gps-button]')?.focus();
                    });
            }

            function selectLocation(result) {
                if (result.already_exists) {
                    renderSubmissionErrors({ name: ['This institution already exists in the system. Choose another institution or edit the existing record.'] });
                    formErrors.focus();
                    notify('error', 'This institution already exists in the system.');
                    return;
                }
                if (result.classification?.excluded) {
                    showDropdownError(result.classification.message);
                    return;
                }
                gps.source('nominatim');
                modalType.value = result.classification?.type || '';
                modalType.dataset.detected = result.classification?.type ? 'true' : '';
                document.getElementById('institutionTypeHelp').textContent = result.classification?.message
                    || 'Select the institution type after checking that it is a public school or barangay hall.';
                const latitude = Number.parseFloat(result.lat);
                const longitude = Number.parseFloat(result.lng);

                if (Number.isNaN(latitude) || Number.isNaN(longitude)) {
                    showDropdownError(
                        'The selected location returned invalid coordinates.'
                    );
                    return;
                }

                if (!result.barangay_id) {
                    showDropdownError(
                        'The barangay could not be detected for this location.'
                    );
                    return;
                }

                const selectedName = String(
                    result.name
                    || result.display_name?.split(',')[0]
                    || modalName.value
                ).trim();

                if (!selectedName) {
                    showDropdownError(
                        'The selected location does not contain an institution name.'
                    );
                    return;
                }

                if (submissionErrors.name) { const errors = { ...submissionErrors }; delete errors.name; renderSubmissionErrors(errors); }
                modalName.value = selectedName;
                selectedInstitutionName.value = selectedName;
                barangayId.value = result.barangay_id;
                latitudeInput.value = latitude;
                longitudeInput.value = longitude;

                manualLatitude.value = latitude;
                manualLongitude.value = longitude;

                locationGeojsonInput.value =
                    result.geojson
                        ? JSON.stringify(result.geojson)
                        : '';

                locationBoundingBoxInput.value =
                    Array.isArray(result.boundingbox)
                        ? JSON.stringify(result.boundingbox)
                        : '';

                nominatimPlaceIdInput.value =
                    result.place_id ?? '';

                osmTypeInput.value =
                    result.osm_type ?? '';

                osmIdInput.value =
                    result.osm_id ?? '';

                detectedBarangay.textContent =
                    result.barangay || 'Not detected';
                detectedCity.textContent =
                    result.city || 'Not detected';
                detectedProvince.textContent =
                    result.province || 'Not detected';
                detectedCountry.textContent =
                    result.country || 'Not detected';
                detectedLatitude.textContent =
                    latitude.toFixed(7);
                detectedLongitude.textContent =
                    longitude.toFixed(7);

                setLocationStatus('success', 'Location detected');
                hideLocationValidation();
                createButton.disabled = false;

                setMapLocation(
                    latitude,
                    longitude,
                    result.geojson ?? null,
                    result.boundingbox ?? null
                );

                hideSearchResults();
            }

            function resetDetectedLocation(resetMapLocation = true) {
                gps?.reset();
                barangayId.value = '';
                latitudeInput.value = '';
                longitudeInput.value = '';
                locationGeojsonInput.value = '';
                locationBoundingBoxInput.value = '';
                nominatimPlaceIdInput.value = '';
                osmTypeInput.value = '';
                osmIdInput.value = '';

                if (manualLatitude) {
                    manualLatitude.value = '';
                }

                if (manualLongitude) {
                    manualLongitude.value = '';
                }

                detectedBarangay.textContent = 'Not detected';
                detectedCity.textContent = 'Not detected';
                detectedProvince.textContent = 'Not detected';
                detectedCountry.textContent = 'Not detected';
                detectedLatitude.textContent = '—';
                detectedLongitude.textContent = '—';

                createButton.disabled = true;
                setLocationStatus('waiting');
                setBoundaryStatus('none');
                resetOsmBoundaryButton.disabled = true;
                hideLocationValidation();

                if (resetMapLocation) {
                    resetMap();
                }
            }

            function setLocationStatus(status, label = null) {
                const icon = locationStatusBadge.querySelector('i');

                if (status === 'success') {
                    locationStatusBadge.className =
                        'inline-flex w-fit items-center gap-1.5 rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-medium text-green-700';
                    icon.className = 'fa-solid fa-circle-check';
                    locationStatusText.textContent =
                        label || 'Location detected';
                    return;
                }

                if (status === 'error') {
                    locationStatusBadge.className =
                        'inline-flex w-fit items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-medium text-red-700';
                    icon.className = 'fa-solid fa-circle-exclamation';
                    locationStatusText.textContent =
                        label || 'Location required';
                    return;
                }

                locationStatusBadge.className =
                    'inline-flex w-fit items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-500';
                icon.className = 'fa-regular fa-clock';
                locationStatusText.textContent =
                    label || 'Waiting for selection';
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
                        <i class="fa-solid global-loading-icon text-red-600"></i>
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

            // Compatibility adapter for existing Institution actions.
            function notify(type, message) {
                window.TalastasToast?.notify(type, message);
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
            @if (old('latitude') !== null && old('longitude') !== null)
                selectedInstitutionName.value = modalName.value;
                manualLatitude.value = latitudeInput.value;
                manualLongitude.value = longitudeInput.value;
                createButton.disabled = false;
            @else
                resetDetectedLocation(false);
            @endif
            @if ($errors->any())
                renderSubmissionErrors(@json($errors->messages()));
            @endif

            @if ($errors->any() || session('open_institution_modal'))
                openModal();
            @endif
        });
    </script>


