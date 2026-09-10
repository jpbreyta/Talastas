@extends('layouts.app')

@section('dashboard-layout', 'enabled')

@section('content')
    <div class="w-full flex flex-col flex-1 min-h-0 overflow-hidden">
        <!-- Dashboard Header -->
        <div class="w-full bg-white flex-shrink-0"
            style="box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.04);">

            <!-- Single header bar: buttons + legend + system note all in one row -->
            <div class="max-w-7xl mx-auto px-6 py-2.5 flex items-center justify-between gap-5">

                <!-- Left spacer for balance -->
                <div class="flex-1"></div>

                <!-- Map Controls Button Group (centered) -->
                <div class="flex items-center flex-shrink-0" role="group" aria-label="Map controls">
                    <button id="toggleHeatmap"
                        class="map-ctrl-btn inline-flex items-center gap-2 px-4 py-2 rounded-l-md border border-slate-200 text-slate-600 text-sm font-medium bg-white hover:bg-slate-50 transition-colors"
                        aria-pressed="false">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                        </svg>
                        Heatmap
                    </button>
                    <button id="toggleBarangays"
                        class="map-ctrl-btn inline-flex items-center gap-2 px-4 py-2 border-y border-r border-slate-200 text-slate-600 text-sm font-medium bg-white hover:bg-slate-50 transition-colors -ml-px"
                        aria-pressed="false">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V5.618a1 1 0 01-.553-.894L15 7" />
                        </svg>
                        Barangays
                    </button>
                    <button id="clearDrawings"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-r-md border-y border-r border-slate-200 text-slate-600 text-sm font-medium bg-white hover:bg-slate-50 hover:text-red-600 transition-colors -ml-px">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Clear
                    </button>
                </div>

                <!-- Right side: System Note — info icon + popover -->
                <div class="flex-1 flex justify-end">
                    <div class="flex-shrink-0 relative" id="info-popover-wrap">
                    <button id="info-popover-btn"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-slate-200 text-slate-500 bg-white hover:bg-slate-50 hover:border-slate-300 hover:text-[#dc2626] transition-colors"
                        aria-label="System information" aria-expanded="false">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>

                    <!-- Popover card -->
                    <div id="info-popover" class="hidden absolute right-0 top-full mt-2 w-80 bg-white rounded-xl z-[2000]"
                        style="border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.07), 0 10px 24px -4px rgba(0,0,0,0.08);">
                        <div class="px-5 pt-4 pb-5">

                            <!-- Header -->
                            <p class="text-[10px] font-bold uppercase tracking-[0.1em] text-slate-400">System Note</p>

                            <!-- Divider -->
                            <div class="mt-2 mb-3 border-t border-slate-100"></div>

                            <!-- List -->
                            <ul class="space-y-2.5">
                                <li class="flex items-start gap-2.5">
                                    <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-slate-300 flex-shrink-0"></span>
                                    <span class="text-[0.8125rem] leading-[1.5] text-slate-600">Data reflects pilot public
                                        institutions only.</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-slate-300 flex-shrink-0"></span>
                                    <span class="text-[0.8125rem] leading-[1.5] text-slate-600">No real-time IoT
                                        monitoring.</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-slate-300 flex-shrink-0"></span>
                                    <span class="text-[0.8125rem] leading-[1.5] text-slate-600">
                                        <span
                                            class="inline-block mb-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-violet-100 text-violet-600 leading-none">Pilot</span>
                                        sites are representative samples.
                                    </span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-slate-300 flex-shrink-0"></span>
                                    <span class="text-[0.8125rem] leading-[1.5] text-slate-600">
                                        <span
                                            class="inline-block mb-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-orange-100 text-orange-600 leading-none">Audit
                                            required</span>
                                        institutions need field verification.
                                    </span>
                                </li>
                            </ul>

                        </div>
                    </div>
                </div>
                </div>

            </div>

        </div>

        <!-- Main Map Container -->
        <div class="relative w-full flex-1 min-h-0 overflow-hidden">
            <div id="map" class="absolute inset-0 w-full h-full"></div>

            <!-- Sliding Side Panel -->
            <div id="sidePanel"
                class="absolute top-4 bottom-4 right-0 h-[calc(100%-2rem)] w-80 bg-white shadow-lg transform translate-x-full transition-all duration-300 ease-in-out z-[1500] overflow-y-auto invisible rounded-l-lg">
                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Institution Details</h2>
                    <button id="closePanel" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="panelContent" class="p-4">
                </div>
            </div>

            <!-- Floating Barangay Navigator Card -->
            <div class="absolute top-3 left-14 z-[1000] bg-white rounded-lg overflow-hidden"
                style="box-shadow: 0 2px 8px rgba(0,0,0,0.15); border: 1px solid rgba(0,0,0,0.08); min-width: 220px;">
                <div class="px-3 py-2 flex items-center gap-2 border-b border-slate-100">
                    <svg class="w-3 h-3 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Jump to
                        Barangay</span>
                </div>
                <div class="px-2 py-2 flex items-center gap-1.5">
                    <select id="barangaySelect"
                        class="flex-1 px-2 py-1 text-xs border border-slate-200 rounded bg-slate-50 text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent focus:bg-white transition">
                        <option value="">— Select —</option>
                    </select>
                    <button id="resetView"
                        class="inline-flex items-center justify-center w-7 h-7 rounded border border-slate-200 text-slate-500 bg-white hover:bg-slate-50 hover:border-slate-300 transition-colors flex-shrink-0"
                        title="Reset view">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
                <!-- Hover info strip -->
                <div class="px-3 py-1.5 border-t border-slate-100 flex items-center gap-1.5 min-h-[28px]">
                    <svg class="w-3 h-3 text-slate-300 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5" />
                    </svg>
                    <span id="barangay-hover-info" class="text-[11px] text-slate-400 italic truncate">
                        Hover a region for details
                    </span>
                </div>
            </div>
        </div>

    </div>{{-- end .flex-col wrapper --}}
@endsection

@section('scripts')
    <!-- Leaflet -->

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof L === 'undefined') {
                console.error('Leaflet library not loaded');
                return;
            }

            const map = L.map('map', {
                center: [13.9411, 121.1631],
                zoom: 13,
                zoomControl: true
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            // Create custom pane for institution markers to ensure they're on top
            map.createPane('institutionBoundaries');
            map.getPane('institutionBoundaries').style.zIndex = 650;
            map.getPane('institutionBoundaries').style.pointerEvents = 'none';

            map.createPane('institutionMarkers');
            map.getPane('institutionMarkers').style.zIndex = 700;

            // Force render after layout stabilizes
            setTimeout(() => map.invalidateSize(), 100);
            // Additional invalidate after data loads
            setTimeout(() => map.invalidateSize(), 500);

            let heatmapLayer = null;
            let heatData = [];
            let barangayOverlay = null;
            let activeInstitutionBoundary = null;
            // Registry: barangay name → layer, populated after GeoJSON loads
            const barangayLayerMap = {};

            const heatmapBtn = document.getElementById('toggleHeatmap');
            const barangayBtn = document.getElementById('toggleBarangays');
            const barangaySelect = document.getElementById('barangaySelect');
            const hoverInfo = document.getElementById('barangay-hover-info');
            let heatmapVisible = false;
            let activeBarangayLayer = null;

            function clearInstitutionBoundary() {
                if (!activeInstitutionBoundary) {
                    return;
                }

                map.removeLayer(activeInstitutionBoundary);
                activeInstitutionBoundary = null;
            }

            function showInstitutionBoundary(properties) {
                clearInstitutionBoundary();

                const geometry = properties.location_geojson;
                const hasBoundary = Boolean(
                    geometry &&
                    ['Polygon', 'MultiPolygon'].includes(geometry.type) &&
                    Array.isArray(geometry.coordinates)
                );

                if (!hasBoundary) {
                    map.flyTo(
                        [properties.latitude, properties.longitude],
                        18,
                        { duration: 0.8 }
                    );
                    return false;
                }

                activeInstitutionBoundary = L.geoJSON(geometry, {
                    pane: 'institutionBoundaries',
                    style: {
                        color: '#b91c1c',
                        weight: 4,
                        opacity: 1,
                        fillColor: '#dc2626',
                        fillOpacity: 0.22
                    }
                }).addTo(map);

                map.flyToBounds(activeInstitutionBoundary.getBounds(), {
                    paddingTopLeft: [50, 50],
                    paddingBottomRight: [370, 50],
                    maxZoom: 19,
                    duration: 0.8
                });

                return true;
            }

            // ── Shared fly-to logic ──────────────────────────────────────────────
            // Uses flyToBounds for a smooth animated zoom into a barangay polygon.
            // padding: [top/bottom px, left/right px] keeps the boundary off screen edges.
            function flyToBarangay(layer) {
                const bounds = layer.getBounds();
                map.flyToBounds(bounds, {
                    paddingTopLeft: [60, 60],
                    paddingBottomRight: [60, 60],
                    maxZoom: 16,
                    duration: 0.8 // seconds — Leaflet animation duration
                });
            }

            // ── Reset to city-wide view ──────────────────────────────────────────
            function resetView() {
                clearInstitutionBoundary();

                const panel = document.getElementById('sidePanel');
                panel.classList.add('translate-x-full', 'invisible');

                if (barangayOverlay) {
                    map.flyToBounds(barangayOverlay.getBounds(), {
                        paddingTopLeft: [40, 40],
                        paddingBottomRight: [40, 40],
                        duration: 0.7
                    });
                } else {
                    map.flyTo([13.9411, 121.1631], 13, {
                        duration: 0.7
                    });
                }
                // Clear active highlight
                if (activeBarangayLayer) {
                    activeBarangayLayer.setStyle({
                        weight: 2,
                        color: '#dc2626',
                        fillOpacity: 0.05
                    });
                    activeBarangayLayer = null;
                }
                // Reset dropdown
                barangaySelect.value = '';
                map.closePopup();
            }

            document.getElementById('resetView').addEventListener('click', resetView);

            function setCtrlActive(btn, active) {
                if (active) {
                    btn.classList.add('bg-red-600', 'text-white', 'border-red-600');
                    btn.classList.remove('bg-white', 'text-slate-600', 'border-slate-200', 'hover:bg-slate-50');
                    btn.setAttribute('aria-pressed', 'true');
                } else {
                    btn.classList.remove('bg-red-600', 'text-white', 'border-red-600');
                    btn.classList.add('bg-white', 'text-slate-600', 'border-slate-200', 'hover:bg-slate-50');
                    btn.setAttribute('aria-pressed', 'false');
                }
            }

            function updateHeatmapButtonState() {
                setCtrlActive(heatmapBtn, heatmapVisible);
                heatmapBtn.title = heatmapVisible ? 'Hide Heatmap' : 'Show Heatmap';
            }

            function updateBarangayButtonState() {
                const active = !!(barangayOverlay && map.hasLayer(barangayOverlay));
                setCtrlActive(barangayBtn, active);
                barangayBtn.title = active ? 'Hide Barangays' : 'Show Barangays';
            }



            updateHeatmapButtonState();
            updateBarangayButtonState();

            let outsideMask = null;

            // Load barangay boundaries
            fetch('/data/lipa-city-barangays.geojson')
                .then(r => r.json())
                .then(data => {
                    function createOutsideMask(geoJsonData) {
                        if (outsideMask) {
                            map.removeLayer(outsideMask);
                            outsideMask = null;
                        }

                        const maskRings = [
                            [
                                [-90, -180],
                                [-90, 180],
                                [90, 180],
                                [90, -180],
                                [-90, -180]
                            ]
                        ];

                        geoJsonData.features.forEach((feature) => {
                            const geometry = feature.geometry;

                            if (!geometry) {
                                return;
                            }

                            if (geometry.type === 'Polygon') {
                                const outerRing = geometry.coordinates[0];

                                maskRings.push(
                                    outerRing.map(([longitude, latitude]) => [
                                        latitude,
                                        longitude
                                    ])
                                );
                            }

                            if (geometry.type === 'MultiPolygon') {
                                geometry.coordinates.forEach((polygon) => {
                                    const outerRing = polygon[0];

                                    maskRings.push(
                                        outerRing.map(([longitude, latitude]) => [
                                            latitude,
                                            longitude
                                        ])
                                    );
                                });
                            }
                        });

                        outsideMask = L.polygon(maskRings, {
                            stroke: false,
                            fillColor: '#0f172a',
                            fillOpacity: 0.58,
                            fillRule: 'evenodd',
                            interactive: false
                        }).addTo(map);

                        outsideMask.bringToBack();
                    }

                    createOutsideMask(data);
                    barangayOverlay = L.geoJSON(data, {
                        style: {
                            color: '#dc2626',
                            weight: 2,
                            fillOpacity: 0.05,
                            fillColor: '#fee2e2'
                        },
                        onEachFeature: (feature, layer) => {
                            const props = feature.properties;
                            const name = props.NAME || props.name;
                            const connectivity = props.connectivity_level || props.connectivity ||
                                null;

                            const connectivityMap = {
                                high: {
                                    label: 'High',
                                    color: '#22c55e'
                                },
                                moderate: {
                                    label: 'Moderate',
                                    color: '#eab308'
                                },
                                low: {
                                    label: 'Low',
                                    color: '#f97316'
                                },
                                critical: {
                                    label: 'Critical',
                                    color: '#ef4444'
                                },
                            };
                            const conn = connectivity ? connectivityMap[connectivity
                                .toLowerCase()] : null;

                            // Register layer by name for dropdown lookup
                            barangayLayerMap[name] = layer;

                            // Click → fly to this barangay
                            layer.on('click', (event) => {
                                L.DomEvent.stopPropagation(event);

                                map.closePopup();

                                if (
                                    activeBarangayLayer &&
                                    activeBarangayLayer !== layer
                                ) {
                                    activeBarangayLayer.setStyle({
                                        weight: 2,
                                        color: '#dc2626',
                                        fillOpacity: 0.05
                                    });
                                }

                                activeBarangayLayer = layer;

                                layer.setStyle({
                                    weight: 3,
                                    color: '#b91c1c',
                                    fillOpacity: 0.15
                                });

                                barangaySelect.value = name;

                                flyToBarangay(layer);
                            });

                            layer.on('mouseover', () => {
                                if (layer !== activeBarangayLayer) {
                                    layer.setStyle({
                                        weight: 3,
                                        fillOpacity: 0.1
                                    });
                                }
                                if (conn) {
                                    hoverInfo.innerHTML = `
                                    <span class="not-italic text-slate-700 font-medium">${name}</span>
                                    <span class="mx-1.5 text-slate-300">·</span>
                                    <span class="inline-flex items-center gap-1">
                                        <span class="inline-block rounded-full" style="width:7px;height:7px;background:${conn.color};"></span>
                                        <span class="text-slate-600">${conn.label}</span>
                                    </span>`;
                                } else {
                                    hoverInfo.innerHTML =
                                        `<span class="not-italic text-slate-700 font-medium">${name}</span>`;
                                }
                                hoverInfo.classList.remove('text-slate-400', 'italic');
                            });

                            layer.on('mouseout', () => {
                                if (layer !== activeBarangayLayer) {
                                    layer.setStyle({
                                        weight: 2,
                                        fillOpacity: 0.05
                                    });
                                }
                                hoverInfo.innerHTML = 'Hover over a region for details';
                                hoverInfo.classList.add('text-slate-400', 'italic');
                            });
                        }
                    });
                    map.fitBounds(barangayOverlay.getBounds(), {
                        padding: [20, 20]
                    });
                    barangayOverlay.addTo(map);
                    setTimeout(() => map.invalidateSize(), 150);
                    updateBarangayButtonState();

                    // Populate the barangay dropdown from loaded features
                    const sortedNames = Object.keys(barangayLayerMap).sort();
                    sortedNames.forEach(name => {
                        const opt = document.createElement('option');
                        opt.value = name;
                        opt.textContent = name;
                        barangaySelect.appendChild(opt);
                    });

                    // Dropdown → fly to selected barangay
                    barangaySelect.addEventListener('change', () => {
                        const selected = barangaySelect.value;
                        if (!selected) {
                            resetView();
                            return;
                        }
                        const layer = barangayLayerMap[selected];
                        if (!layer) return;
                        // Ensure barangay overlay is visible
                        if (!map.hasLayer(barangayOverlay)) {
                            map.addLayer(barangayOverlay);
                            updateBarangayButtonState();
                        }
                        // Highlight + zoom
                        if (activeBarangayLayer && activeBarangayLayer !== layer) {
                            activeBarangayLayer.setStyle({
                                weight: 2,
                                color: '#dc2626',
                                fillOpacity: 0.05
                            });
                        }
                        activeBarangayLayer = layer;
                        layer.setStyle({
                            weight: 3,
                            color: '#b91c1c',
                            fillOpacity: 0.15
                        });
                        flyToBarangay(layer);
                    });
                })
                .catch(err => console.error('Error loading barangays:', err));

            // Load institution data
            fetch(@json(route('map.data.institutions')), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(r => {
                    if (!r.ok) {
                        throw new Error('API error: ' + r.status);
                    }
                    return r.json();
                })
                .then(data => {
                    console.log('Number of institution features:', data.features ? data.features.length : 0);
                    const features = data.features.map(f => {
                        const props = f.properties;
                        const hasGapScore =
                            props.gap_score !== null &&
                            props.gap_score !== undefined;

                        const gapScore = hasGapScore ?
                            Number(props.gap_score) :
                            null;
                        const completeness = props.data_completeness || 0;
                        const qualityLabel = completeness >= 80 ? 'Good' : (completeness >= 50 ?
                            'Partial' : 'Incomplete');

                        let color = '#94a3b8';
                        let radius = 10;

                        if (gapScore !== null) {
                            if (gapScore < 30) {
                                color = '#22c55e';
                                radius = 10;
                            } else if (gapScore < 50) {
                                color = '#eab308';
                                radius = 11;
                            } else if (gapScore < 70) {
                                color = '#f97316';
                                radius = 12;
                            } else {
                                color = '#dc2626';
                                radius = 13;
                            }
                        }

                        const weight = completeness < 50 ? 4 : 2;
                        const borderColor = completeness < 50 ? '#000000' : '#ffffff';
                        if (gapScore !== null) {
                            heatData.push([
                                Number(props.latitude),
                                Number(props.longitude),
                                gapScore
                            ]);
                        }

                        return {
                            ...f,
                            properties: {
                                ...props,
                                markerColor: color,
                                markerRadius: radius,
                                borderColor,
                                borderWeight: weight
                            }
                        };
                    });

                    window.institutionsLayer = L.geoJSON({
                        type: 'FeatureCollection',
                        features
                    }, {
                        pointToLayer: (feature, latlng) => {
                            const props = feature.properties;
                            const marker = L.circleMarker(latlng, {
                                radius: props.markerRadius,
                                fillColor: props.markerColor,
                                color: props.borderColor || '#fff',
                                weight: props.borderWeight || 2,
                                opacity: 1,
                                fillOpacity: 0.85,
                                pane: 'institutionMarkers'
                            });
                            marker.properties = props; // Attach properties for later use
                            return marker;
                        }
                    }).addTo(map);

                    // Attach click events and popups after adding to map
                    window.institutionsLayer.eachLayer(function(layer) {
                        if (layer instanceof L.CircleMarker) {
                            const props = layer.properties;
                            const hasGapScore =
                                props.gap_score !== null &&
                                props.gap_score !== undefined;

                            const gapScore = hasGapScore ?
                                Number(props.gap_score) :
                                null;
                            const gapLevel = gapScore === null ?
                                'Insufficient Data' :
                                gapScore >= 80 ?
                                'Critical' :
                                gapScore >= 50 ?
                                'Needs Attention' :
                                'Adequate';
                            const completeness = props.data_completeness || 0;
                            const qualityLabel = completeness >= 80 ? 'Good' : (completeness >= 50 ?
                                'Partial' : 'Incomplete');

                            const escapeHtml = (value) => String(value ?? '')
                                .replaceAll('&', '&amp;')
                                .replaceAll('<', '&lt;')
                                .replaceAll('>', '&gt;')
                                .replaceAll('"', '&quot;')
                                .replaceAll("'", '&#039;');

                            const truncateText = (value, limit) => {
                                const text = String(value ?? '').trim();

                                if (text.length <= limit) {
                                    return text;
                                }

                                return text.substring(0, limit).trimEnd() + '…';
                            };

                            const analysisPreview = props.infrastructure_narrative
                                ? escapeHtml(truncateText(props.infrastructure_narrative, 165))
                                : '';

                            const recommendationPreview = props.policy_recommendation
                                ? escapeHtml(truncateText(props.policy_recommendation, 145))
                                : '';

                            const popupContent = `
                            <div class="p-3 min-w-[280px]">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-bold text-base text-gray-900">${props.name}</h3>
                                    <div class="flex gap-1">
                                        ${props.analysis_method ? '<span class="px-2 py-0.5 text-xs bg-red-100 text-red-700 rounded-full">AI</span>' : ''}
                                        ${props.is_pilot ? '<span class="px-2 py-0.5 text-xs bg-purple-100 text-purple-700 rounded-full">Pilot</span>' : ''}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <button class="zoom-btn w-full px-3 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700 transition-colors" data-lat="${props.latitude}" data-lng="${props.longitude}">
                                        ${props.has_location_boundary ? 'Focus Institution Boundary' : 'Zoom to Location'}
                                    </button>
                                </div>
                                <div class="mb-3 rounded-lg border ${
                                    props.has_location_boundary
                                        ? 'border-red-200 bg-red-50 text-red-700'
                                        : 'border-gray-200 bg-gray-50 text-gray-600'
                                } px-3 py-2 text-xs">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid ${props.has_location_boundary ? 'fa-draw-polygon' : 'fa-location-dot'}"></i>
                                        <span>${
                                            props.has_location_boundary
                                                ? `${props.location_geojson.type} boundary from OpenStreetMap`
                                                : 'Exact institution boundary is unavailable; showing the saved point.'
                                        }</span>
                                    </div>
                                </div>
                                <div class="space-y-1 text-sm mb-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Sector:</span>
                                        <span class="font-medium">${props.type || 'Not Available'}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Barangay:</span>
                                        <span class="font-medium">${props.barangay}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Internet:</span>
                                        <span class="font-medium">${props.internet_speed ? props.internet_speed + ' Mbps' : 'Not Available'}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Computer Lab:</span>
                                        <span class="font-medium">${props.has_computer_lab ? 'Yes' : 'No'}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Computers:</span>
                                        <span class="font-medium">${props.number_of_computers || 0}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Data Quality:</span>
                                        <span class="px-2 py-0.5 text-xs rounded-full ${
                                            completeness >= 80 ? 'bg-green-100 text-green-700' :
                                            (completeness >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')
                                        }">${qualityLabel} (${completeness}%)</span>
                                    </div>
                                </div>
                                <div class="border-t border-gray-200 my-2"></div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-800 font-semibold">Gap Score:</span>
                                    <span class="px-2 py-1 text-xs font-bold rounded-full ${
                                        gapScore >= 80 ? 'bg-red-100 text-red-700' :
                                        (gapScore >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700')
                                    }">
                                        ${gapScore !== null
                                            ? `${gapScore.toFixed(1)}% (${gapLevel})`
                                            : gapLevel}
                                    </span>
                                </div>
                                ${analysisPreview ? `
                                    <div class="mb-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fa-solid fa-file-waveform text-[10px] text-gray-400"></i>
                                            <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                                Planning Assessment
                                            </span>
                                        </div>
                                        <p class="mt-1.5 text-xs leading-relaxed text-gray-600">${analysisPreview}</p>
                                    </div>
                                ` : ''}
                                ${recommendationPreview ? `
                                    <div class="mb-2 rounded-lg border border-red-100 bg-red-50 px-3 py-2.5">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fa-solid fa-bullseye text-[10px] text-red-500"></i>
                                            <span class="text-[10px] font-semibold uppercase tracking-wide text-red-600">
                                                Recommended First Action
                                            </span>
                                        </div>
                                        <p class="mt-1.5 text-xs font-medium leading-relaxed text-red-800">${recommendationPreview}</p>
                                    </div>
                                ` : ''}
                                <div class="border-t border-gray-200 my-2"></div>
                                ${props.requires_audit ? '<p class="text-xs text-orange-600"><strong>⚠️ Audit required</strong> — Field verification needed</p>' : ''}
                                ${props.last_audited ? '<p class="text-xs text-gray-500">Last audited: ' + props.last_audited + '</p>' : ''}
                            </div>
                        `;

                            layer.on('click', function(e) {
                                console.log('Institution clicked:', props.name);
                                e.originalEvent.stopPropagation();

                                showInstitutionBoundary(props);

                                // Populate side panel
                                document.getElementById('panelContent').innerHTML =
                                    popupContent;
                                // Show side panel
                                const panel = document.getElementById('sidePanel');
                                panel.classList.remove('translate-x-full', 'invisible');
                                // Add zoom button event after content is inserted
                                setTimeout(() => {
                                    const zoomBtn = document.querySelector(
                                        '#panelContent .zoom-btn');
                                    if (zoomBtn) {
                                        zoomBtn.addEventListener('click', function() {
                                            const lat = parseFloat(this
                                                .getAttribute('data-lat'));
                                            const lng = parseFloat(this
                                                .getAttribute('data-lng'));
                                            if (!showInstitutionBoundary(props)) {
                                                map.flyTo([lat, lng], 18, {
                                                    duration: 0.8
                                                });
                                            }
                                        });
                                    }
                                }, 10);
                            });
                            layer.on('mouseover', () => layer.setRadius(layer.getRadius() + 3));
                            layer.on('mouseout', () => layer.setRadius(props.markerRadius));
                        }
                    });
                    console.log('Institutions layer added to map with', features.length, 'features');
                    setTimeout(() => map.invalidateSize(), 200);
                })
                .catch(err => console.error('Error loading institutions:', err));

            heatmapBtn.addEventListener('click', () => {
                if (heatmapVisible) {
                    if (heatmapLayer) {
                        map.removeLayer(heatmapLayer);
                        heatmapLayer = null;
                    }
                    heatmapVisible = false;
                } else {
                    heatmapLayer = L.heatLayer(heatData, {
                        radius: 25,
                        blur: 15,
                        maxZoom: 17,
                        gradient: {
                            0.4: 'red',
                            0.6: 'cyan',
                            0.7: 'lime',
                            0.8: 'yellow',
                            1: 'red'
                        }
                    }).addTo(map);
                    heatmapVisible = true;
                }
                updateHeatmapButtonState();
            });

            barangayBtn.addEventListener('click', () => {
                if (barangayOverlay) {
                    if (map.hasLayer(barangayOverlay)) {
                        map.removeLayer(barangayOverlay);
                    } else {
                        map.addLayer(barangayOverlay);
                    }
                    updateBarangayButtonState();
                }
            });

            // Temporarily disable pm controls to test if they interfere with clicks
            // map.pm.addControls({
            //     position: 'topleft',
            //     drawMarker: true,
            //     drawPolygon: true,
            //     drawRectangle: true,
            //     drawPolyline: true,
            //     editMode: true,
            //     removalMode: true,
            //     rotateMode: false
            // });

            document.getElementById('clearDrawings').addEventListener('click', () => {
                map.eachLayer(layer => {
                    if (layer.pm && layer.pm.enabled()) layer.pm.remove();
                });
                resetView();
            });

            map.on('pm:create', ({
                layer,
                shape
            }) => {
                if (shape === 'Polygon' || shape === 'Rectangle') {
                    const latLngs = layer.getLatLngs()[0].map(ll => [ll.lng, ll.lat]);
                    const poly = turf.polygon([latLngs]);
                    const area = turf.area(poly) / 1000000;
                    let count = 0;
                    if (window.institutionsLayer) {
                        window.institutionsLayer.eachLayer(l => {
                            const pt = turf.point([l.getLatLng().lng, l.getLatLng().lat]);
                            if (turf.booleanPointInPolygon(pt, poly)) count++;
                        });
                    }
                    layer.bindPopup(
                        `<div class="p-2"><strong>Area of Interest</strong><br>Area: ${area.toFixed(2)} km²<br>Institutions within: ${count}</div>`
                    ).openPopup();
                }
            });

            L.control.scale({
                position: 'bottomleft'
            }).addTo(map);

            // Info popover toggle
            const popoverBtn = document.getElementById('info-popover-btn');
            const popover = document.getElementById('info-popover');

            popoverBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = !popover.classList.contains('hidden');
                popover.classList.toggle('hidden', isOpen);
                popoverBtn.setAttribute('aria-expanded', String(!isOpen));
            });

            // Close side panel
            document.getElementById('closePanel').addEventListener('click', (e) => {
                e.stopPropagation();
                const panel = document.getElementById('sidePanel');
                panel.classList.add('translate-x-full', 'invisible');
                clearInstitutionBoundary();
            });

            // Close popovers when clicking outside
            document.addEventListener('click', () => {
                popover.classList.add('hidden');
                popoverBtn.setAttribute('aria-expanded', 'false');
                dataMgmtMenu.classList.add('hidden');
                dataMgmtBtn.setAttribute('aria-expanded', 'false');
            });
        });
    </script>

    <style>
        /* Override Leaflet's default popup chrome to match the dashboard */
        .map-popup .leaflet-popup-content-wrapper {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 10px 24px -4px rgba(0, 0, 0, 0.08);
            padding: 0;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
        }

        .map-popup .leaflet-popup-content {
            margin: 0;
        }

        .map-popup .leaflet-popup-tip {
            background: #ffffff;
            box-shadow: none;
        }

        .map-popup .leaflet-popup-close-button {
            color: #94a3b8;
            font-size: 16px;
            top: 8px;
            right: 8px;
        }

        .map-popup .leaflet-popup-close-button:hover {
            color: #1e293b;
        }
    </style>
@endsection
