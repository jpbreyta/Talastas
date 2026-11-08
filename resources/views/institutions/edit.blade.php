@extends('layouts.app')

@section('page-title', 'Edit Institution')
@section('page-icon', 'fa-building')

@section('content')
@include('institutions._form-styles')
<div class="institution-form min-w-0 bg-gray-100 px-4 py-6 sm:px-6">
    <form method="POST" action="{{ route('institutions.update', $institution) }}" id="editForm"
        class="mx-auto w-full max-w-5xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        @csrf
        @method('PUT')
        @if ($errors->any())
            <div role="alert" class="m-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $message)
                    <p>{{ $message }}</p>
                @endforeach
            </div>
        @endif

        <x-page-header title="Edit Institution"  class="border-b border-gray-200 bg-white px-4 sm:px-6">

        </x-page-header>

        <div class="space-y-6 p-4 sm:p-6">
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-700">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-900">Institution Classification</h2>
                    </div>
                </div>
                <div class="p-5">
                    @include('institutions._sector-field', ['fieldId' => 'type', 'sectorValue' => $institution->type])
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <button type="button" onclick="toggleSection('sec-location')"
                    class="flex w-full items-center justify-between gap-3 bg-gray-50 px-5 py-4 text-left transition hover:bg-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-700">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-900">Institution and Location</h2>
                    </div>
                    <i class="fa-solid fa-chevron-down section-chevron text-xs text-gray-400 transition-transform"></i>
                </button>
                <div id="sec-location" class="space-y-5 border-t border-gray-200 p-5">
                    <div>
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
                    <div class="grid grid-cols-1 gap-5">
                        <div>
                            <label for="barangay_id" class="mb-1.5 block text-xs font-semibold text-gray-600">
                                Barangay <span class="text-red-500">*</span>
                            </label>

                            <select name="barangay_id" id="barangay_id" required
                                class="form-input w-full @error('barangay_id') border-red-400 @enderror">
                                <option value="">Select Barangay</option>
                                @foreach($barangays as $barangay)
                                    <option value="{{ $barangay->id }}"
                                        {{ old('barangay_id', $institution->barangay_id) == $barangay->id ? 'selected' : '' }}>
                                        {{ $barangay->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('barangay_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>


                    </div>

                    <input type="hidden" name="location_geojson" id="editLocationGeojson"
                        value="{{ old('location_geojson', $institution->location_geojson ? json_encode($institution->location_geojson) : '') }}">
                    <input type="hidden" name="location_bounding_box" id="editLocationBoundingBox"
                        value="{{ old('location_bounding_box', $institution->location_bounding_box ? json_encode($institution->location_bounding_box) : '') }}">
                    <input type="hidden" name="nominatim_place_id" id="editNominatimPlaceId"
                        value="{{ old('nominatim_place_id', $institution->nominatim_place_id) }}">
                    <input type="hidden" name="osm_type" id="editOsmType"
                        value="{{ old('osm_type', $institution->osm_type) }}">
                    <input type="hidden" name="osm_id" id="editOsmId"
                        value="{{ old('osm_id', $institution->osm_id) }}">

                    <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
                        <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-3.5 py-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-center gap-2">
                                <span id="editBoundaryStatus"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-500">
                                    <i class="fa-solid fa-location-dot"></i>
                                    <span>No boundary</span>
                                </span>

                                <span class="text-[11px] text-slate-400">
                                    Double-click to finish drawing.
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" id="editDrawBoundaryBtn"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-100">
                                    <i class="fa-solid fa-pen-ruler"></i>
                                    Draw Boundary
                                </button>

                                <button type="button" id="editBoundaryEditBtn" disabled
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    Edit
                                </button>

                                <button type="button" id="editResetOsmBoundaryBtn"
                                    data-url="{{ route('institutions.osm-boundary.fetch', $institution) }}"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 transition hover:bg-blue-100">
                                    <i class="fa-solid fa-rotate-left"></i>
                                    Reset to map data
                                </button>

                                <button type="button" id="editClearBoundaryBtn" disabled
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40">
                                    <i class="fa-solid fa-eraser"></i>
                                    Clear
                                </button>
                            </div>
                        </div>

                        <div id="editInstitutionMap" class="h-80 w-full bg-slate-100"></div>

                        <div id="editBoundaryMessage"
                            class="hidden border-t px-3.5 py-2.5 text-xs">
                        </div>
                    <details data-location-options class="location-options mt-5" @if (old('latitude', $institution->latitude) !== null || $errors->any()) open @endif>
                        <summary class="location-options-toggle"><i class="fa-solid fa-sliders" aria-hidden="true"></i>Show more options<i class="fa-solid fa-chevron-down location-options-chevron" aria-hidden="true"></i></summary>
                        <div class="location-method-grid">
                            @include('institutions._gps')
                            <div class="location-method-card">
                                <h4><i class="fa-solid fa-map-pin" aria-hidden="true"></i>Enter Exact Coordinates</h4>
                                <p class="location-method-help">Review or update the institution's saved coordinates.</p>
                                <details class="manual-coordinate-option" open>
                                    <summary class="location-method-button"><i class="fa-solid fa-map-pin" aria-hidden="true"></i>Enter Exact Coordinates</summary>
                                    <div class="mt-4 grid grid-cols-1 gap-3 xl:grid-cols-2">
                        <div>
                            <label for="latitude" class="mb-1.5 block text-xs font-semibold text-gray-600">
                                Latitude <span class="text-red-500">*</span>
                            </label>

                            <input type="text" inputmode="decimal" pattern="-?[0-9]+([.][0-9]+)?" name="latitude" id="latitude" min="-90" max="90"
                                value="{{ old('latitude', $institution->latitude) }}" required
                                class="form-input w-full @error('latitude') border-red-400 @enderror"
                                placeholder="e.g. 13.9411">

                            @error('latitude')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="longitude" class="mb-1.5 block text-xs font-semibold text-gray-600">
                                Longitude <span class="text-red-500">*</span>
                            </label>

                            <input type="text" inputmode="decimal" pattern="-?[0-9]+([.][0-9]+)?" name="longitude" id="longitude" min="-180" max="180"
                                value="{{ old('longitude', $institution->longitude) }}" required
                                class="form-input w-full @error('longitude') border-red-400 @enderror"
                                placeholder="e.g. 121.1644">

                            @error('longitude')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </details>
                    </div>
                </div>
            </section>



            {{-- ICT inventory is maintained outside the institution profile form. --}}
            <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                            <i class="fa-solid fa-layer-group"></i>
                        </span>

                        <div>
                            <h2 class="text-sm font-semibold text-gray-900">
                                ICT Inventory
                            </h2>
                            <p class="mt-1 text-xs leading-5 text-gray-600">
                                Hardware, software, network infrastructure, and Internet connectivity are managed together in one tabbed inventory form.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('ict-inventory.create', ['institution_id' => $institution->id]) }}"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-red-700">
                        <i class="fa-solid fa-pen"></i>
                        Manage Inventory
                    </a>
                </div>
            </div>


            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <button type="button" onclick="toggleSection('sec-status')"
                        class="w-full flex items-center justify-between px-5 py-4 text-left bg-gray-50 hover:bg-gray-100 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 shrink-0 rounded-lg bg-red-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-gray-800">Status & Flags</span>
                            <span class="mt-0.5 block text-xs text-gray-500">Controls whether the institution participates in active analytics</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 section-chevron transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="sec-status" class="border-t border-gray-200 px-5 py-5">
                    <div class="max-w-md">
                        <x-toggle-card name="is_active" label="Active Institution"
                            :checked="old('is_active', $institution->is_active)" color="green"/>
                    </div>
                </div>
            </div>

        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
            <a href="{{ route('institutions.show', $institution) }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                <i class="fa-solid fa-xmark"></i>
                Cancel
            </a>
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                <i class="fa-solid fa-check"></i>
                Update Institution
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
// â”€â”€ Toggle cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

// â”€â”€ Accordion sections â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function toggleSection(id) {
    const body    = document.getElementById(id);
    const btn     = body.previousElementSibling;
    const chevron = btn.querySelector('.section-chevron');
    const isOpen  = !body.classList.contains('hidden');

    body.classList.toggle('hidden', isOpen);
    chevron.style.transform = isOpen ? 'rotate(-90deg)' : 'rotate(0deg)';
}


document.addEventListener('DOMContentLoaded', () => {
    const mapElement =
        document.getElementById('editInstitutionMap');

    if (!mapElement || typeof L === 'undefined') {
        return;
    }

    const latitudeInput =
        document.getElementById('latitude');
    const longitudeInput =
        document.getElementById('longitude');
    const geojsonInput =
        document.getElementById('editLocationGeojson');
    const boundingBoxInput =
        document.getElementById('editLocationBoundingBox');
    const placeIdInput =
        document.getElementById('editNominatimPlaceId');
    const osmTypeInput =
        document.getElementById('editOsmType');
    const osmIdInput =
        document.getElementById('editOsmId');

    const drawButton =
        document.getElementById('editDrawBoundaryBtn');
    const editButton =
        document.getElementById('editBoundaryEditBtn');
    const resetButton =
        document.getElementById('editResetOsmBoundaryBtn');
    const clearButton =
        document.getElementById('editClearBoundaryBtn');
    const status =
        document.getElementById('editBoundaryStatus');
    const message =
        document.getElementById('editBoundaryMessage');

    const initialLatitude =
        (latitudeInput.value.trim() === '' ? NaN : Number(latitudeInput.value));
    const initialLongitude =
        (longitudeInput.value.trim() === '' ? NaN : Number(longitudeInput.value));

    const defaultCenter = [
        Number.isFinite(initialLatitude)
            ? initialLatitude
            : 13.9411,
        Number.isFinite(initialLongitude)
            ? initialLongitude
            : 121.1631
    ];

    const map = L.map(mapElement, {
        center: defaultCenter,
        zoom: 17,
        zoomControl: true
    });

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: 'Â© OpenStreetMap contributors'
        }
    ).addTo(map);

    let markerLayer = null;
    let boundaryLayer = null;
    let drawing = false;
    let editing = false;

    const parseJson = value => {
        if (!value) {
            return null;
        }

        try {
            return JSON.parse(value);
        } catch (error) {
            return null;
        }
    };

    const showMessage = (type, text) => {
        message.className =
            'border-t px-3.5 py-2.5 text-xs '
            + (
                type === 'error'
                    ? 'border-red-200 bg-red-50 text-red-700'
                    : type === 'warning'
                        ? 'border-amber-200 bg-amber-50 text-amber-700'
                        : 'border-emerald-200 bg-emerald-50 text-emerald-700'
            );

        message.textContent = text;
    };

    const hideMessage = () => {
        message.className =
            'hidden border-t px-3.5 py-2.5 text-xs';
        message.textContent = '';
    };

    const setStatus = (mode) => {
        const states = {
            osm: [
                'map data boundary',
                'border-emerald-200 bg-emerald-50 text-emerald-700',
                'fa-draw-polygon'
            ],
            custom: [
                'Custom boundary',
                'border-blue-200 bg-blue-50 text-blue-700',
                'fa-pen-ruler'
            ],
            none: [
                'No boundary',
                'border-slate-200 bg-white text-slate-500',
                'fa-location-dot'
            ]
        };

        const state = states[mode] || states.none;

        status.className =
            'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-medium '
            + state[1];

        status.innerHTML = `
            <i class="fa-solid ${state[2]}"></i>
            <span>${state[0]}</span>
        `;
    };

    const currentPoint = () => {
        const latitude =
            (latitudeInput.value.trim() === '' ? NaN : Number(latitudeInput.value));
        const longitude =
            (longitudeInput.value.trim() === '' ? NaN : Number(longitudeInput.value));

        if (
            !Number.isFinite(latitude)
            || !Number.isFinite(longitude)
        ) {
            return null;
        }

        return [latitude, longitude];
    };

    const setMarker = () => {
        const point = currentPoint();

        if (!point) {
            if (markerLayer) {
                map.removeLayer(markerLayer);
                markerLayer = null;
            }

            return;
        }

        if (!markerLayer) {
            markerLayer = L.circleMarker(
                point,
                {
                    radius: 7,
                    color: '#ffffff',
                    weight: 3,
                    fillColor: '#dc2626',
                    fillOpacity: 1
                }
            ).addTo(map);
        } else {
            markerLayer.setLatLng(point);
        }

        markerLayer.bringToFront();
    };

    const forEachBoundaryLayer = callback => {
        if (!boundaryLayer) {
            return;
        }

        if (typeof boundaryLayer.eachLayer === 'function') {
            boundaryLayer.eachLayer(callback);
            return;
        }

        callback(boundaryLayer);
    };

    const stopEditing = () => {
        forEachBoundaryLayer(layer => {
            layer.pm?.disable();
        });

        editing = false;
        editButton.innerHTML =
            '<i class="fa-solid fa-pen-to-square"></i> Edit';
    };

    const removeBoundaryLayer = () => {
        stopEditing();

        if (boundaryLayer) {
            map.removeLayer(boundaryLayer);
            boundaryLayer = null;
        }

        editButton.disabled = true;
        clearButton.disabled = true;
    };

    const fitMap = () => {
        if (boundaryLayer) {
            const bounds = boundaryLayer.getBounds?.();

            if (bounds?.isValid?.()) {
                map.fitBounds(bounds, {
                    padding: [24, 24],
                    maxZoom: 18
                });
                markerLayer?.bringToFront();
                return;
            }
        }

        const point = currentPoint();

        if (point) {
            map.setView(point, 17);
        }
    };

    const geometryFromLayer = () => {
        if (!boundaryLayer?.toGeoJSON) {
            return null;
        }

        const geojson = boundaryLayer.toGeoJSON();

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
                && ['Polygon', 'MultiPolygon'].includes(
                    geometry.type
                )
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
                coordinates.push(
                    geometry.coordinates
                );
            } else {
                coordinates.push(
                    ...geometry.coordinates
                );
            }
        });

        return {
            type: 'MultiPolygon',
            coordinates
        };
    };

    const dispatchBoundaryChange = () => {
        geojsonInput.dispatchEvent(
            new Event('change', {
                bubbles: true
            })
        );
    };

    const syncCustomBoundary = () => {
        const geometry = geometryFromLayer();

        if (!geometry) {
            return;
        }

        geojsonInput.value =
            JSON.stringify(geometry);

        const bounds = boundaryLayer.getBounds?.();

        if (bounds?.isValid?.()) {
            boundingBoxInput.value =
                JSON.stringify([
                    bounds.getSouth(),
                    bounds.getNorth(),
                    bounds.getWest(),
                    bounds.getEast()
                ]);
        }

        placeIdInput.value = '';
        osmTypeInput.value = '';
        osmIdInput.value = '';

        setStatus('custom');
        dispatchBoundaryChange();
    };

    const bindEditListeners = () => {
        forEachBoundaryLayer(layer => {
            layer.on?.(
                'pm:edit',
                syncCustomBoundary
            );
            layer.on?.(
                'pm:dragend',
                syncCustomBoundary
            );
            layer.on?.(
                'pm:markerdragend',
                syncCustomBoundary
            );
        });
    };

    const renderBoundary = (
        geometry,
        mode = 'custom'
    ) => {
        removeBoundaryLayer();

        if (
            !geometry
            || !['Polygon', 'MultiPolygon'].includes(
                geometry.type
            )
        ) {
            setStatus('none');
            fitMap();
            return;
        }

        boundaryLayer = L.geoJSON(
            geometry,
            {
                style: {
                    color: '#dc2626',
                    weight: 3,
                    opacity: 0.95,
                    fillColor: '#ef4444',
                    fillOpacity: 0.12
                }
            }
        ).addTo(map);

        bindEditListeners();

        editButton.disabled = false;
        clearButton.disabled = false;

        setStatus(mode);
        fitMap();
    };

    const updateResetAvailability = () => {
        resetButton.disabled =
            !currentPoint()
            || !document
                .getElementById('name')
                ?.value
                ?.trim();
    };

    drawButton.addEventListener('click', () => {
        if (!map.pm || !currentPoint()) {
            showMessage(
                'error',
                'Valid institution coordinates are required before drawing a boundary.'
            );
            return;
        }

        hideMessage();

        if (drawing) {
            map.pm.disableDraw('Polygon');
            drawing = false;
            drawButton.innerHTML =
                '<i class="fa-solid fa-pen-ruler"></i> Draw Boundary';
            return;
        }

        stopEditing();

        drawing = true;
        drawButton.innerHTML =
            '<i class="fa-solid fa-xmark"></i> Cancel Drawing';

        map.pm.enableDraw('Polygon', {
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
    });

    map.on('pm:create', event => {
        if (event.shape !== 'Polygon') {
            return;
        }

        drawing = false;
        map.pm.disableDraw('Polygon');
        drawButton.innerHTML =
            '<i class="fa-solid fa-pen-ruler"></i> Draw Boundary';

        removeBoundaryLayer();

        boundaryLayer = event.layer;
        bindEditListeners();

        editButton.disabled = false;
        clearButton.disabled = false;

        syncCustomBoundary();
        fitMap();
    });

    editButton.addEventListener('click', () => {
        if (!boundaryLayer) {
            return;
        }

        if (editing) {
            stopEditing();
            syncCustomBoundary();
            return;
        }

        editing = true;

        forEachBoundaryLayer(layer => {
            layer.pm?.enable({
                allowSelfIntersection: false,
                snappable: true
            });
        });

        editButton.innerHTML =
            '<i class="fa-solid fa-check"></i> Finish Edit';
        setStatus('custom');
    });

    clearButton.addEventListener('click', () => {
        removeBoundaryLayer();

        geojsonInput.value = '';
        boundingBoxInput.value = '';
        placeIdInput.value = '';
        osmTypeInput.value = '';
        osmIdInput.value = '';

        setStatus('none');
        fitMap();
        dispatchBoundaryChange();
        hideMessage();
    });

    resetButton.addEventListener('click', async () => {
        if (resetButton.disabled) {
            return;
        }

        const originalHtml =
            resetButton.innerHTML;

        resetButton.disabled = true;
        resetButton.innerHTML =
            '<span class="global-loading-icon"><i class="fa-solid"></i></span> Fetching map data';

        try {
            const response = await fetch(
                resetButton.dataset.url,
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN':
                            document
                                .querySelector(
                                    'meta[name="csrf-token"]'
                                )
                                ?.getAttribute('content')
                            || ''
                    },
                    credentials: 'same-origin'
                }
            );

            const data = await response.json();

            if (!response.ok) {
                throw new Error(
                    (response.status >= 500 ? 'Unable to retrieve location information. Please try again.' : data?.message)
                    || 'OpenStreetMap boundary could not be fetched.'
                );
            }

            geojsonInput.value =
                JSON.stringify(data.geojson);

            boundingBoxInput.value =
                Array.isArray(data.boundingbox)
                    ? JSON.stringify(
                        data.boundingbox
                    )
                    : '';

            placeIdInput.value =
                data.place_id ?? '';
            osmTypeInput.value =
                data.osm_type ?? '';
            osmIdInput.value =
                data.osm_id ?? '';

            renderBoundary(
                data.geojson,
                'osm'
            );

            dispatchBoundaryChange();

            showMessage(
                data.saved
                    ? 'success'
                    : 'warning',
                data.saved
                    ? 'The map data boundary was fetched and stored because no boundary was previously saved.'
                    : 'The map data boundary was restored in this form. The existing saved boundary stays the same until you save the form.'
            );
        } catch (error) {
            showMessage(
                'error',
                error instanceof Error
                    ? error.message
                    : 'OpenStreetMap boundary could not be fetched.'
            );
        } finally {
            resetButton.innerHTML =
                originalHtml;
            updateResetAvailability();
        }
    });

    const gps = window.mountInstitutionGps(latitudeInput.form, async (lat, lng) => {
        latitudeInput.value = lat;
        longitudeInput.value = lng;
        setMarker();
        map.setView([lat, lng], 18);
        return true;
    }, () => map);
    let institutionUpdating = false;
    latitudeInput.form.addEventListener('submit', event => {
        if (institutionUpdating) { event.preventDefault(); return; }
        if (!gps.canSubmit()) { event.preventDefault(); return; }
        const lat = Number(latitudeInput.value), lng = Number(longitudeInput.value);
        if (!latitudeInput.value.trim() || !longitudeInput.value.trim()
            || !Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
            event.preventDefault();
            showMessage('error', 'Please enter valid latitude and longitude values within Lipa City.');
            return;
        }
        institutionUpdating = true;
        latitudeInput.form.querySelector('button[type="submit"]').disabled = true;
    });
    const syncPointFromInputs = () => {
        gps.source('manual');
        setMarker();
        updateResetAvailability();

        if (!boundaryLayer) {
            fitMap();
        }
    };

    latitudeInput.addEventListener(
        'change',
        syncPointFromInputs
    );

    longitudeInput.addEventListener(
        'change',
        syncPointFromInputs
    );

    document
        .getElementById('name')
        ?.addEventListener(
            'input',
            updateResetAvailability
        );

    setMarker();

    const initialGeometry =
        parseJson(geojsonInput.value);

    renderBoundary(
        initialGeometry,
        osmTypeInput.value && osmIdInput.value
            ? 'osm'
            : 'custom'
    );

    updateResetAvailability();

    setTimeout(
        () => map.invalidateSize(),
        50
    );
});


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

