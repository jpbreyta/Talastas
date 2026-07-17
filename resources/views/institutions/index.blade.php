@extends('layouts.app')

@section('dashboard-layout')
@endsection

@section('content')
    <div class="flex h-full min-h-0 flex-col bg-gray-100">
        {{-- Page Header --}}
        <div class="shrink-0 bg-blue-700 px-6 py-4">
            <div class="flex items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-blue-100">
                        <i class="fa-solid fa-building"></i>
                    </div>

                    <div class="min-w-0">
                        <h1 class="text-xl font-semibold leading-tight tracking-tight text-white">
                            Institutions
                        </h1>
                        <p class="mt-0.5 text-xs text-blue-200">
                            Manage registered institutions and their ICT profiles
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    <div class="relative hidden sm:block">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-blue-300">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </div>

                        <input id="institutionSearch" type="text" placeholder="Search institutions..."
                            class="w-56 rounded-lg border border-blue-600 bg-blue-800 py-2 pl-9 pr-4 text-sm text-white placeholder-blue-300 transition focus:outline-none focus:ring-2 focus:ring-blue-300">
                    </div>

                    <button type="button" id="openAddModal"
                        class="inline-flex items-center gap-2 rounded-lg border border-blue-100 bg-white px-4 py-2 text-sm font-medium text-blue-700 shadow-sm transition hover:bg-blue-50">
                        <i class="fa-solid fa-plus"></i>
                        Add Institution
                    </button>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div
                class="mx-5 mt-4 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                <i class="fa-solid fa-circle-check mt-0.5"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mx-5 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <div>
                        <p class="font-semibold">Please correct the following:</p>
                        <ul class="mt-1 list-disc space-y-0.5 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Content Area --}}
        <div class="flex min-h-0 flex-1 flex-col">
            @if ($institutions->count() > 0)
                <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-1 pt-3">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[800px] text-sm">
                                <thead class="sticky top-0 z-10">
                                    <tr class="border-b border-gray-200 bg-gray-50 text-left">
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Institution
                                        </th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Sector
                                        </th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Barangay
                                        </th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Population
                                        </th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Status
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($institutions as $institution)
                                        <tr class="inst-row transition hover:bg-blue-50/40">
                                            <td class="px-4 py-3">
                                                <span class="font-medium text-gray-900">
                                                    {{ $institution->name }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-3 text-gray-500">
                                                {{ $institution->type }}
                                            </td>

                                            <td class="px-4 py-3 text-gray-500">
                                                {{ $institution->barangay?->name ?? '—' }}
                                            </td>

                                            <td class="px-4 py-3 text-gray-500">
                                                {{ $institution->type === 'School'
                                                    ? ($institution->student_population !== null
                                                        ? number_format($institution->student_population)
                                                        : '—')
                                                    : ($institution->staff_population !== null
                                                        ? number_format($institution->staff_population)
                                                        : '—') }}
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    @if ($institution->is_active)
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                                                            <i class="fa-solid fa-circle-check text-[10px]"></i>
                                                            Active
                                                        </span>
                                                    @else
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-500">
                                                            <i class="fa-solid fa-circle-minus text-[10px]"></i>
                                                            Inactive
                                                        </span>
                                                    @endif

                                                    @if ($institution->is_pilot_site)
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">
                                                            <i class="fa-solid fa-flask text-[10px]"></i>
                                                            Pilot
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('institutions.show', $institution) }}"
                                                        class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 transition hover:text-blue-800">
                                                        <i class="fa-solid fa-eye"></i>
                                                        View
                                                    </a>

                                                    <a href="{{ route('institutions.edit', $institution) }}"
                                                        class="inline-flex items-center gap-1 text-xs font-medium text-gray-600 transition hover:text-gray-900">
                                                        <i class="fa-solid fa-pen"></i>
                                                        Edit
                                                    </a>

                                                    <form method="POST"
                                                        action="{{ route('institutions.destroy', $institution) }}"
                                                        class="inline">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="submit"
                                                            onclick="return confirm('Delete {{ addslashes($institution->name) }}?')"
                                                            class="inline-flex items-center gap-1 text-xs font-medium text-gray-400 transition hover:text-red-600">
                                                            <i class="fa-solid fa-trash"></i>
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div id="noResults" class="hidden py-12 text-center">
                            <i class="fa-solid fa-magnifying-glass text-2xl text-gray-300"></i>
                            <p class="mt-3 text-sm text-gray-500">
                                No institutions match your search.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-200 bg-white px-5 py-2">
                    {{ $institutions->links() }}
                </div>
            @else
                <div class="flex flex-1 flex-col items-center justify-center px-5 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-500">
                        <i class="fa-solid fa-building text-xl"></i>
                    </div>

                    <h3 class="mt-4 text-sm font-semibold text-gray-900">
                        No institutions yet
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Get started by adding your first institution.
                    </p>

                    <button type="button" id="openAddModalEmpty"
                        class="mt-5 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                        <i class="fa-solid fa-plus"></i>
                        Add Institution
                    </button>
                </div>
            @endif
        </div>

        {{-- Add Institution Modal --}}
        <div id="addInstitutionModal" class="fixed inset-0 z-[3000] hidden overflow-y-auto" aria-hidden="true"
            role="dialog" aria-modal="true" aria-labelledby="addInstitutionModalTitle">
            <div class="flex min-h-screen items-center justify-center px-4 py-8">
                <div id="modalOverlay" class="fixed inset-0 bg-gray-900/60 backdrop-blur-[1px]"></div>

                <div
                    class="relative flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                    {{-- Modal Header --}}
                    <div class="shrink-0 border-b border-blue-600 bg-blue-700 px-6 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-blue-100">
                                    <i class="fa-solid fa-building-circle-check"></i>
                                </div>

                                <div class="min-w-0">
                                    <h2 id="addInstitutionModalTitle" class="text-lg font-semibold text-white">
                                        Add New Institution
                                    </h2>
                                    <p class="mt-0.5 text-xs text-blue-200">
                                        Register an institution and its ICT information
                                    </p>
                                </div>
                            </div>

                            <button type="button" id="closeAddModal"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-blue-200 transition hover:bg-blue-600 hover:text-white"
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
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
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
                                                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 transition hover:border-blue-300 hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-60">
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
                                                <i class="fa-solid fa-map-location-dot text-blue-600"></i>
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
                                                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-500">
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
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
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
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
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
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
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
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
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
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
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
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                                            <i class="fa-solid fa-layer-group"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900">Institution Classification</h3>
                                            <p class="mt-0.5 text-xs text-gray-500">Specify the institution type and
                                                population</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
                                    <div>
                                        <label for="modalType" class="mb-1.5 block text-xs font-semibold text-gray-600">
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
                                    </div>

                                    <div class="modal-population-field" data-type="school">
                                        <label for="modalStudentPop"
                                            class="mb-1.5 block text-xs font-semibold text-gray-600">
                                            Student Population
                                            <span class="text-red-500">*</span>
                                        </label>

                                        <div class="relative">
                                            <div
                                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                                <i class="fa-solid fa-user-graduate text-sm"></i>
                                            </div>
                                            <input type="number" name="student_population" id="modalStudentPop"
                                                value="{{ old('student_population') }}" min="0"
                                                class="form-input w-full pl-9" placeholder="e.g. 1200">
                                        </div>
                                    </div>

                                    <div class="modal-population-field hidden" data-type="other">
                                        <label for="modalStaffPop"
                                            class="mb-1.5 block text-xs font-semibold text-gray-600">
                                            Staff Population
                                            <span class="text-red-500">*</span>
                                        </label>

                                        <div class="relative">
                                            <div
                                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                                <i class="fa-solid fa-users text-sm"></i>
                                            </div>
                                            <input type="number" name="staff_population" id="modalStaffPop"
                                                value="{{ old('staff_population') }}" min="0"
                                                class="form-input w-full pl-9" placeholder="e.g. 45">
                                        </div>
                                    </div>
                                </div>
                            </section>

                            {{-- ICT Infrastructure --}}
                            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                                            <i class="fa-solid fa-computer"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900">ICT Infrastructure</h3>
                                            <p class="mt-0.5 text-xs text-gray-500">Select available ICT facilities and
                                                enter connectivity information</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-5 p-5">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        <label class="feature-card">
                                            <input type="checkbox" name="has_computer_lab" class="feature-checkbox"
                                                @checked(old('has_computer_lab'))>
                                            <span class="feature-icon"><i class="fa-solid fa-desktop"></i></span>
                                            <span class="text-sm font-medium text-gray-700">Computer Lab</span>
                                        </label>

                                        <label class="feature-card">
                                            <input type="checkbox" name="has_smart_tv" class="feature-checkbox"
                                                @checked(old('has_smart_tv'))>
                                            <span class="feature-icon"><i class="fa-solid fa-tv"></i></span>
                                            <span class="text-sm font-medium text-gray-700">Smart TV</span>
                                        </label>

                                        <label class="feature-card">
                                            <input type="checkbox" name="has_projected_whiteboard"
                                                class="feature-checkbox" @checked(old('has_projected_whiteboard'))>
                                            <span class="feature-icon"><i class="fa-solid fa-chalkboard"></i></span>
                                            <span class="text-sm font-medium text-gray-700">Whiteboard</span>
                                        </label>

                                        <label class="feature-card">
                                            <input type="checkbox" name="has_public_address_system"
                                                class="feature-checkbox" @checked(old('has_public_address_system'))>
                                            <span class="feature-icon"><i class="fa-solid fa-bullhorn"></i></span>
                                            <span class="text-sm font-medium text-gray-700">PA System</span>
                                        </label>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div>
                                            <label for="modalComputers"
                                                class="mb-1.5 block text-xs font-semibold text-gray-600">
                                                Number of Computers
                                            </label>
                                            <div class="relative">
                                                <div
                                                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                                    <i class="fa-solid fa-computer text-sm"></i>
                                                </div>
                                                <input type="number" name="number_of_computers" id="modalComputers"
                                                    value="{{ old('number_of_computers') }}" min="0"
                                                    class="form-input w-full pl-9" placeholder="e.g. 30">
                                            </div>
                                        </div>

                                        <div>
                                            <label for="modalInternet"
                                                class="mb-1.5 block text-xs font-semibold text-gray-600">
                                                Internet Speed
                                                <span class="font-normal text-gray-400">(Mbps)</span>
                                            </label>
                                            <div class="relative">
                                                <div
                                                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                                    <i class="fa-solid fa-gauge-high text-sm"></i>
                                                </div>
                                                <input type="number" step="0.1" name="internet_speed"
                                                    id="modalInternet" value="{{ old('internet_speed') }}"
                                                    min="0" class="form-input w-full pl-9" placeholder="e.g. 100">
                                            </div>
                                        </div>

                                        <div>
                                            <label for="modalIsp"
                                                class="mb-1.5 block text-xs font-semibold text-gray-600">
                                                ISP Provider
                                            </label>
                                            <div class="relative">
                                                <div
                                                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                                    <i class="fa-solid fa-wifi text-sm"></i>
                                                </div>
                                                <input type="text" name="isp_provider" id="modalIsp"
                                                    value="{{ old('isp_provider') }}" class="form-input w-full pl-9"
                                                    placeholder="e.g. PLDT, Globe">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            {{-- Institution Status --}}
                            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                                            <i class="fa-solid fa-clipboard-check"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900">Institution Status</h3>
                                            <p class="mt-0.5 text-xs text-gray-500">Set operational and assessment flags
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-2 lg:grid-cols-4">
                                    <label class="feature-card">
                                        <input type="checkbox" name="is_pilot_site" class="feature-checkbox"
                                            @checked(old('is_pilot_site', true))>
                                        <span class="feature-icon"><i class="fa-solid fa-flask"></i></span>
                                        <span class="text-sm font-medium text-gray-700">Pilot Site</span>
                                    </label>

                                    <label class="feature-card">
                                        <input type="checkbox" name="is_active" class="feature-checkbox"
                                            @checked(old('is_active', true))>
                                        <span class="feature-icon feature-icon-green"><i
                                                class="fa-solid fa-circle-check"></i></span>
                                        <span class="text-sm font-medium text-gray-700">Active</span>
                                    </label>

                                    <label class="feature-card">
                                        <input type="checkbox" name="needs_maintenance" class="feature-checkbox"
                                            @checked(old('needs_maintenance'))>
                                        <span class="feature-icon feature-icon-orange"><i
                                                class="fa-solid fa-screwdriver-wrench"></i></span>
                                        <span class="text-sm font-medium text-gray-700">Needs Maintenance</span>
                                    </label>

                                    <label class="feature-card">
                                        <input type="checkbox" name="requires_audit" class="feature-checkbox"
                                            @checked(old('requires_audit', true))>
                                        <span class="feature-icon feature-icon-yellow"><i
                                                class="fa-solid fa-clipboard-list"></i></span>
                                        <span class="text-sm font-medium text-gray-700">Requires Audit</span>
                                    </label>
                                </div>
                            </section>

                            {{-- Notes --}}
                            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                                            <i class="fa-solid fa-note-sticky"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900">Additional Notes</h3>
                                            <p class="mt-0.5 text-xs text-gray-500">Add other relevant information about
                                                the institution</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-5">
                                    <label for="modalNotes" class="mb-1.5 block text-xs font-semibold text-gray-600">
                                        Notes
                                        <span class="font-normal text-gray-400">(optional)</span>
                                    </label>
                                    <textarea name="notes" id="modalNotes" rows="3" class="form-input w-full resize-none"
                                        placeholder="Enter additional notes about the institution...">{{ old('notes') }}</textarea>
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
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500">
                            <i class="fa-solid fa-plus"></i>
                            <span>Create Institution</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
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
    </style>
@endsection

@section('scripts')
    <script>
        (function() {
            'use strict';

            const defaultCenter = [13.9411, 121.1631];
            const defaultZoom = 13;

            const institutionSearch = document.getElementById('institutionSearch');
            const modal = document.getElementById('addInstitutionModal');
            const modalOverlay = document.getElementById('modalOverlay');
            const openModalButtons = document.querySelectorAll('#openAddModal, #openAddModalEmpty');
            const closeModalButton = document.getElementById('closeAddModal');
            const cancelModalButton = document.getElementById('cancelAddModal');
            const form = document.getElementById('addInstitutionForm');
            const createButton = document.getElementById('createInstitutionBtn');
            const modalName = document.getElementById('modalName');
            const selectedInstitutionName = document.getElementById('selectedInstitutionName');
            const modalType = document.getElementById('modalType');
            const studentPopulation = document.getElementById('modalStudentPop');
            const staffPopulation = document.getElementById('modalStaffPop');
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

            let mapPicker = null;
            let pickerMarker = null;
            let lastFocusedElement = null;

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

            closeModalButton?.addEventListener('click', closeModal);
            cancelModalButton?.addEventListener('click', closeModal);
            modalOverlay?.addEventListener('click', closeModal);
            modalType?.addEventListener('change', updatePopulationFields);
            geocodeButton?.addEventListener('click', searchLocations);

            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
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
                document.body.style.overflow = 'hidden';
                updatePopulationFields();

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
                document.body.style.overflow = '';

                hideSearchResults();
                clearNameError();
                form?.reset();
                updatePopulationFields();
                resetDetectedLocation(false);

                if (mapPicker) {
                    mapPicker.remove();
                    mapPicker = null;
                    pickerMarker = null;
                }

                resetCreateButton();
                lastFocusedElement?.focus();
            }

            function updatePopulationFields() {
                const isSchool = modalType.value === 'School';

                document.querySelectorAll('.modal-population-field').forEach(field => {
                    const type = field.dataset.type;
                    const shouldHide =
                        (type === 'school' && !isSchool) ||
                        (type === 'other' && isSchool);

                    field.classList.toggle('hidden', shouldHide);
                });

                studentPopulation.required = isSchool;
                staffPopulation.required = !isSchool;

                if (isSchool) {
                    staffPopulation.value = '';
                } else {
                    studentPopulation.value = '';
                }
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
                        'cursor-pointer hover:bg-blue-50' :
                        'cursor-not-allowed bg-gray-50 opacity-60'
                    ].join(' ');

                    const displayName = escapeHtml(result.display_name || `Location ${index + 1}`);
                    const barangay = escapeHtml(result.barangay || 'Barangay not detected');
                    const city = escapeHtml(result.city || 'City not detected');

                    button.innerHTML = `
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${
                            hasBarangay ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-500'
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
                        <i class="fa-solid fa-spinner fa-spin text-blue-600"></i>
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

            function escapeHtml(value) {
                const element = document.createElement('div');
                element.textContent = String(value ?? '');
                return element.innerHTML;
            }

            updatePopulationFields();
            resetDetectedLocation(false);

            @if ($errors->any())
                openModal();
            @endif
        })();
    </script>
@endsection
