@extends('layouts.app')

@section('content')
    <div class="w-full flex flex-col flex-1">
        <!-- Dashboard Header -->
        <div class="w-full bg-white flex-shrink-0"
            style="box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.04);">

            <!-- Single header bar: buttons + legend + system note all in one row -->
            <div class="max-w-7xl mx-auto px-6 py-2.5 flex items-center gap-5">

                <!-- Map Controls Button Group -->
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

                <!-- Divider -->
                <div class="w-px h-6 bg-slate-200 flex-shrink-0"></div>

                <!-- Data Management Dropdown -->
                <div class="relative flex-shrink-0" id="data-mgmt-wrap">
                    <button id="data-mgmt-btn"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-md border border-slate-200 text-slate-600 text-sm font-medium bg-white hover:bg-slate-50 hover:border-slate-300 transition-colors"
                        aria-haspopup="true" aria-expanded="false">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        Data Management
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div id="data-mgmt-menu"
                        class="hidden absolute left-0 top-full mt-1.5 w-44 bg-white rounded-lg border border-slate-200 z-[2000]"
                        style="box-shadow: 0 4px 6px -1px rgba(0,0,0,0.07), 0 10px 24px -4px rgba(0,0,0,0.08);">
                        <div class="py-1">
                            <a href="{{ route('institutions.index') }}"
                                class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Institutions
                            </a>
                            <a href="{{ route('ict-inventory.index') }}"
                                class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Inventory
                            </a>
                            <div class="my-1 border-t border-slate-100"></div>
                            <a href="{{ route('analytics') }}"
                                class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Analytics
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Connectivity Legend -->
                <div
                    class="flex items-center gap-1 text-[10px] text-slate-400 font-semibold uppercase tracking-widest flex-shrink-0">
                    Connectivity
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <div class="flex items-center gap-1">
                        <span class="block rounded-full flex-shrink-0"
                            style="width:7px;height:7px;background:#22c55e;"></span>
                        <span class="text-xs text-slate-500">High</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="block rounded-full flex-shrink-0"
                            style="width:7px;height:7px;background:#eab308;"></span>
                        <span class="text-xs text-slate-500">Moderate</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="block rounded-full flex-shrink-0"
                            style="width:7px;height:7px;background:#f97316;"></span>
                        <span class="text-xs text-slate-500">Low</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="block rounded-full flex-shrink-0"
                            style="width:7px;height:7px;background:#ef4444;"></span>
                        <span class="text-xs text-slate-500">Critical</span>
                    </div>
                </div>

                <!-- System Note — info icon + popover (far right) -->
                <div class="ml-auto flex-shrink-0 relative" id="info-popover-wrap">
                    <button id="info-popover-btn"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-slate-200 text-slate-500 bg-white hover:bg-slate-50 hover:border-slate-300 hover:text-[#3b82f6] transition-colors"
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

        <!-- Main Map Container -->
        <div class="relative w-full flex-1 min-h-[600px]">
            <div id="map" style="position:absolute;inset:0;"></div>

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
                        class="flex-1 px-2 py-1 text-xs border border-slate-200 rounded bg-slate-50 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition">
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
            map.createPane('institutionMarkers');
            map.getPane('institutionMarkers').style.zIndex = 700;

            // Force render after layout stabilizes
            setTimeout(() => map.invalidateSize(), 100);
            // Additional invalidate after data loads
            setTimeout(() => map.invalidateSize(), 500);

            const heatmapLayer = L.layerGroup().addTo(map);
            let heatData = [];
            let barangayOverlay = null;
            // Registry: barangay name → layer, populated after GeoJSON loads
            const barangayLayerMap = {};

            const heatmapBtn = document.getElementById('toggleHeatmap');
            const barangayBtn = document.getElementById('toggleBarangays');
            const barangaySelect = document.getElementById('barangaySelect');
            const hoverInfo = document.getElementById('barangay-hover-info');
            let heatmapVisible = false;
            let activeBarangayLayer = null;

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
                        color: '#2563eb',
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
                    btn.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
                    btn.classList.remove('bg-white', 'text-slate-600', 'border-slate-200', 'hover:bg-slate-50');
                    btn.setAttribute('aria-pressed', 'true');
                } else {
                    btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
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

            // Data Management dropdown
            const dataMgmtBtn = document.getElementById('data-mgmt-btn');
            const dataMgmtMenu = document.getElementById('data-mgmt-menu');
            dataMgmtBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = !dataMgmtMenu.classList.contains('hidden');
                dataMgmtMenu.classList.toggle('hidden', isOpen);
                dataMgmtBtn.setAttribute('aria-expanded', String(!isOpen));
            });

            updateHeatmapButtonState();
            updateBarangayButtonState();

            // Load barangay boundaries
            fetch('/data/lipa-city-barangays.geojson')
                .then(r => r.json())
                .then(data => {
                    barangayOverlay = L.geoJSON(data, {
                        style: {
                            color: '#2563eb',
                            weight: 2,
                            fillOpacity: 0.05,
                            fillColor: '#dbeafe'
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

                            // Styled barangay popup
                            const connBadge = conn ?
                                `<span style="display:inline-flex;align-items:center;gap:4px;">
                                 <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:${conn.color};"></span>
                                 <span style="color:#475569;">${conn.label}</span>
                               </span>` :
                                '<span style="color:#94a3b8;">No data</span>';

                            layer.bindPopup(`
                            <div style="min-width:180px;font-family:inherit;">
                                <p style="margin:0 0 6px;font-size:13px;font-weight:600;color:#1e293b;">${name}</p>
                                <div style="border-top:1px solid #f1f5f9;padding-top:6px;display:flex;justify-content:space-between;align-items:center;font-size:12px;">
                                    <span style="color:#64748b;">Connectivity</span>
                                    ${connBadge}
                                </div>
                            </div>
                        `, {
                                className: 'map-popup'
                            });

                            // Click → fly to this barangay
                            layer.on('click', () => {
                                // Reset previous highlight
                                if (activeBarangayLayer && activeBarangayLayer !== layer) {
                                    activeBarangayLayer.setStyle({
                                        weight: 2,
                                        color: '#2563eb',
                                        fillOpacity: 0.05
                                    });
                                }
                                activeBarangayLayer = layer;
                                layer.setStyle({
                                    weight: 3,
                                    color: '#1d4ed8',
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
                                color: '#2563eb',
                                fillOpacity: 0.05
                            });
                        }
                        activeBarangayLayer = layer;
                        layer.setStyle({
                            weight: 3,
                            color: '#1d4ed8',
                            fillOpacity: 0.15
                        });
                        flyToBarangay(layer);
                    });
                })
                .catch(err => console.error('Error loading barangays:', err));

            // Load institution data
            fetch('/api/v1/map/institutions')
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
                        const gapScore = props.gap_score || 0;
                        const completeness = props.data_completeness || 0;
                        const qualityLabel = completeness >= 80 ? 'Good' : (completeness >= 50 ?
                            'Partial' : 'Incomplete');

                        let color = '#ef4444';
                        let radius = 10;
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

                        const weight = completeness < 50 ? 4 : 2;
                        const borderColor = completeness < 50 ? '#000000' : '#ffffff';
                        heatData.push([props.latitude, props.longitude, gapScore]);

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
                            const gapScore = props.gap_score || 0;
                            const gapLevel = gapScore >= 80 ? 'Critical' : (gapScore >= 50 ?
                                'Needs Attention' : 'Adequate');
                            const completeness = props.data_completeness || 0;
                            const qualityLabel = completeness >= 80 ? 'Good' : (completeness >= 50 ?
                                'Partial' : 'Incomplete');

                            const popupContent = `
                            <div class="p-3 min-w-[260px]">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-bold text-base text-gray-900">${props.name}</h3>
                                    ${props.is_pilot ? '<span class="px-2 py-0.5 text-xs bg-purple-100 text-purple-700 rounded-full">Pilot</span>' : ''}
                                </div>
                                <div class="mb-3">
                                    <button class="zoom-btn w-full px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors" data-lat="${props.latitude}" data-lng="${props.longitude}">
                                        Zoom to Location
                                    </button>
                                </div>
                                <div class="space-y-1 text-sm mb-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Sector:</span>
                                        <span class="font-medium">${props.type || props.type}</span>
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
                                        <span class="font-medium">${props.has_lab ? 'Yes' : 'No'}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Computers:</span>
                                        <span class="font-medium">${props.computers || 0}</span>
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
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-800 font-semibold">Gap Score:</span>
                                    <span class="px-2 py-1 text-xs font-bold rounded-full ${
                                        gapScore >= 80 ? 'bg-red-100 text-red-700' :
                                        (gapScore >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700')
                                    }">
                                        ${gapScore.toFixed(1)}% (${gapLevel})
                                    </span>
                                </div>
                                ${props.requires_audit ? '<p class="text-xs text-orange-600 mt-2"><strong>⚠️ Audit required</strong> — Field verification needed</p>' : ''}
                                ${props.last_audited ? '<p class="text-xs text-gray-500 mt-1">Last audited: ' + props.last_audited + '</p>' : ''}
                            </div>
                        `;

                            layer.on('click', function(e) {
                                console.log('Institution clicked:', props.name);
                                e.originalEvent.stopPropagation(); // Prevent map click
                                layer.openPopup();
                            });
                            layer.bindPopup(popupContent, {
                                className: 'map-popup',
                                maxWidth: 300
                            });
                            layer.on('popupopen', function() {
                                const zoomBtn = document.querySelector(
                                    '.leaflet-popup-content .zoom-btn');
                                if (zoomBtn) {
                                    zoomBtn.addEventListener('click', function() {
                                        const lat = parseFloat(this.getAttribute(
                                            'data-lat'));
                                        const lng = parseFloat(this.getAttribute(
                                            'data-lng'));
                                        map.flyTo([lat, lng], 18, {
                                            duration: 0.8
                                        });
                                        layer.closePopup();
                                    });
                                }
                            });
                            layer.on('mouseover', () => layer.setRadius(layer.getRadius() + 3));
                            layer.on('mouseout', () => layer.setRadius(props.markerRadius));
                        }
                    });
                    // Fix: assign the returned layer, not an undefined variable
                    window.institutionsLayer = L.geoJSON({
                        type: 'FeatureCollection',
                        features
                    });
                    console.log('Institutions layer added to map with', features.length, 'features');
                    setTimeout(() => map.invalidateSize(), 200);
                })
                .catch(err => console.error('Error loading institutions:', err));

            heatmapBtn.addEventListener('click', () => {
                if (heatmapVisible) {
                    map.removeLayer(heatmapLayer);
                    heatmapVisible = false;
                } else {
                    L.heatLayer(heatData, {
                        radius: 25,
                        blur: 15,
                        maxZoom: 17,
                        gradient: {
                            0.4: 'blue',
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
