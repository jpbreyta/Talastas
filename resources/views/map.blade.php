@extends('layouts.app')

@section('content')
<div class="w-full flex flex-col flex-1">
    <!-- Dashboard Header -->
    <div class="w-full bg-white border-b border-slate-100 flex-shrink-0">

        <!-- Single header bar: buttons + legend + system note all in one row -->
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center gap-6">

            <!-- Ghost Buttons -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <button id="toggleHeatmap"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-md border border-slate-200 text-slate-600 text-sm font-medium bg-white hover:bg-slate-50 hover:border-slate-300 transition-colors">
                    <svg class="w-4 h-4 text-[#ef4444]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                    </svg>
                    Heatmap
                </button>
                <button id="toggleBarangays"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-md border border-slate-200 text-slate-600 text-sm font-medium bg-white hover:bg-slate-50 hover:border-slate-300 transition-colors">
                    <svg class="w-4 h-4 text-[#3b82f6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V5.618a1 1 0 01-.553-.894L15 7"/>
                    </svg>
                    Barangays
                </button>
                <button id="clearDrawings"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-md border border-slate-200 text-slate-600 text-sm font-medium bg-white hover:bg-slate-50 hover:border-slate-300 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Clear
                </button>
            </div>

            <!-- Divider -->
            <div class="w-px h-6 bg-slate-200 flex-shrink-0"></div>

            <!-- Connectivity Legend -->
            <div class="flex items-center gap-1 text-xs text-slate-500 font-medium uppercase tracking-wide flex-shrink-0">
                Connectivity
            </div>
            <div class="flex items-center gap-4 flex-shrink-0">
                <div class="flex items-center gap-1.5">
                    <span class="block rounded-full flex-shrink-0" style="width:8px;height:8px;background:#22c55e;"></span>
                    <span class="text-sm text-slate-600">High</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="block rounded-full flex-shrink-0" style="width:8px;height:8px;background:#eab308;"></span>
                    <span class="text-sm text-slate-600">Moderate</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="block rounded-full flex-shrink-0" style="width:8px;height:8px;background:#f97316;"></span>
                    <span class="text-sm text-slate-600">Low</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="block rounded-full flex-shrink-0" style="width:8px;height:8px;background:#ef4444;"></span>
                    <span class="text-sm text-slate-600">Critical</span>
                </div>
            </div>

            <!-- Divider -->
            <div class="w-px h-6 bg-slate-200 flex-shrink-0"></div>

            <!-- Hover Info -->
            <div class="flex items-center gap-2 min-w-0">
                <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/>
                </svg>
                <span id="barangay-hover-info" class="text-sm text-slate-400 italic truncate">
                    Hover over a region for details
                </span>
            </div>

            <!-- System Note — info icon + popover -->
            <div class="ml-auto flex-shrink-0 relative" id="info-popover-wrap">
                <button id="info-popover-btn"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-slate-200 text-slate-500 bg-white hover:bg-slate-50 hover:border-slate-300 hover:text-[#3b82f6] transition-colors"
                    aria-label="System information" aria-expanded="false">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>

                <!-- Popover card -->
                <div id="info-popover"
                    class="hidden absolute right-0 top-full mt-2 w-80 bg-white rounded-xl z-[2000]"
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
                                <span class="text-[0.8125rem] leading-[1.5] text-slate-600">Data reflects pilot public institutions only.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-slate-300 flex-shrink-0"></span>
                                <span class="text-[0.8125rem] leading-[1.5] text-slate-600">No real-time IoT monitoring.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-slate-300 flex-shrink-0"></span>
                                <span class="text-[0.8125rem] leading-[1.5] text-slate-600">
                                    <span class="inline-block mb-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-violet-100 text-violet-600 leading-none">Pilot</span>
                                    sites are representative samples.
                                </span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-slate-300 flex-shrink-0"></span>
                                <span class="text-[0.8125rem] leading-[1.5] text-slate-600">
                                    <span class="inline-block mb-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-orange-100 text-orange-600 leading-none">Audit required</span>
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
    <div class="relative w-full flex-1">
        <div id="map" style="position:absolute;inset:0;"></div>
    </div>

</div>{{-- end .flex-col wrapper --}}
@endsection

@section('scripts')
<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WLcE=" crossorigin=""></script>
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

        // Force render after layout stabilizes
        setTimeout(() => map.invalidateSize(), 100);

        const heatmapLayer = L.layerGroup().addTo(map);
        let heatData = [];
        let barangayOverlay = null;

        const heatmapBtn = document.getElementById('toggleHeatmap');
        const barangayBtn = document.getElementById('toggleBarangays');
        const hoverInfo = document.getElementById('barangay-hover-info');
        let heatmapVisible = false;

        function updateHeatmapButtonState() {
            const icon = heatmapBtn.querySelector('svg');
            if (heatmapVisible) {
                icon.classList.remove('text-gray-600');
                icon.classList.add('text-red-600');
                heatmapBtn.title = 'Hide Heatmap';
            } else {
                icon.classList.remove('text-red-600');
                icon.classList.add('text-gray-600');
                heatmapBtn.title = 'Show Heatmap';
            }
        }

        function updateBarangayButtonState() {
            const icon = barangayBtn.querySelector('svg');
            if (barangayOverlay && map.hasLayer(barangayOverlay)) {
                icon.classList.remove('text-gray-600');
                icon.classList.add('text-blue-600');
                barangayBtn.title = 'Hide Barangays';
            } else {
                icon.classList.remove('text-blue-600');
                icon.classList.add('text-gray-600');
                barangayBtn.title = 'Show Barangays';
            }
        }

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
                        const connectivity = props.connectivity_level || props.connectivity || null;

                        // connectivity → label + dot color
                        const connectivityMap = {
                            high:     { label: 'High',     color: '#22c55e' },
                            moderate: { label: 'Moderate', color: '#eab308' },
                            low:      { label: 'Low',      color: '#f97316' },
                            critical: { label: 'Critical', color: '#ef4444' },
                        };
                        const conn = connectivity ? connectivityMap[connectivity.toLowerCase()] : null;

                        layer.bindPopup(`<strong>${name}</strong>`);

                        layer.on('mouseover', () => {
                            layer.setStyle({ weight: 3, fillOpacity: 0.1 });
                            if (conn) {
                                hoverInfo.innerHTML = `
                                    <span class="not-italic text-slate-700 font-medium">${name}</span>
                                    <span class="mx-1.5 text-slate-300">·</span>
                                    <span class="inline-flex items-center gap-1">
                                        <span class="inline-block rounded-full" style="width:7px;height:7px;background:${conn.color};"></span>
                                        <span class="text-slate-600">${conn.label}</span>
                                    </span>`;
                            } else {
                                hoverInfo.innerHTML = `<span class="not-italic text-slate-700 font-medium">${name}</span>`;
                            }
                            hoverInfo.classList.remove('text-slate-400', 'italic');
                        });

                        layer.on('mouseout', () => {
                            layer.setStyle({ weight: 2, fillOpacity: 0.05 });
                            hoverInfo.innerHTML = 'Hover over a region for details';
                            hoverInfo.classList.add('text-slate-400', 'italic');
                        });
                    }
                });
                map.fitBounds(barangayOverlay.getBounds(), { padding: [20, 20] });
                barangayOverlay.addTo(map);
                setTimeout(() => map.invalidateSize(), 150);
                updateBarangayButtonState();
            })
            .catch(err => console.error('Error loading barangays:', err));

        // Load institution data
        fetch('/api/v1/map/institutions')
            .then(r => r.json())
            .then(data => {
                const features = data.features.map(f => {
                    const props = f.properties;
                    const gapScore = props.gap_score || 0;
                    const completeness = props.data_completeness || 0;

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
                        properties: { ...props, markerColor: color, markerRadius: radius, borderColor, borderWeight: weight }
                    };
                });

                L.geoJSON({ type: 'FeatureCollection', features }, {
                    pointToLayer: (feature, latlng) => {
                        const props = feature.properties;
                        return L.circleMarker(latlng, {
                            radius: props.markerRadius,
                            fillColor: props.markerColor,
                            color: props.borderColor || '#fff',
                            weight: props.borderWeight || 2,
                            opacity: 1,
                            fillOpacity: 0.85
                        });
                    },
                    onEachFeature: (feature, layer) => {
                        const props = feature.properties;
                        const gapScore = props.gap_score || 0;
                        const gapLevel = gapScore >= 80 ? 'Critical' : (gapScore >= 50 ? 'Needs Attention' : 'Adequate');
                        const completeness = props.data_completeness || 0;
                        const qualityLabel = completeness >= 80 ? 'Good' : (completeness >= 50 ? 'Partial' : 'Incomplete');

                        const popupContent = `
                            <div class="p-3 min-w-[260px]">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-bold text-base text-gray-900">${props.name}</h3>
                                    ${props.is_pilot ? '<span class="px-2 py-0.5 text-xs bg-purple-100 text-purple-700 rounded-full">Pilot</span>' : ''}
                                </div>
                                <div class="space-y-1 text-sm mb-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Sector:</span>
                                        <span class="font-medium">${props.sector_type || props.type}</span>
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
                        layer.bindPopup(popupContent);
                        layer.on('mouseover', () => layer.setRadius(layer.getRadius() + 3));
                        layer.on('mouseout', () => layer.setRadius(props.markerRadius));
                    }
                }).addTo(map);
                window.institutionsLayer = geoJsonLayer;
                setTimeout(() => map.invalidateSize(), 200);
            })
            .catch(err => console.error('Error loading institutions:', err));

        heatmapBtn.addEventListener('click', () => {
            if (heatmapVisible) {
                map.removeLayer(heatmapLayer);
                heatmapVisible = false;
            } else {
                L.heatLayer(heatData, { radius: 25, blur: 15, maxZoom: 17, gradient: {0.4: 'blue', 0.6: 'cyan', 0.7: 'lime', 0.8: 'yellow', 1: 'red'} }).addTo(map);
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

        map.pm.addControls({
            position: 'topleft',
            drawMarker: true,
            drawPolygon: true,
            drawRectangle: true,
            drawPolyline: true,
            editMode: true,
            removalMode: true,
            rotateMode: false
        });

        document.getElementById('clearDrawings').addEventListener('click', () => {
            map.eachLayer(layer => {
                if (layer.pm && layer.pm.enabled()) layer.pm.remove();
            });
        });

        map.on('pm:create', ({ layer, shape }) => {
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
                layer.bindPopup(`<div class="p-2"><strong>Area of Interest</strong><br>Area: ${area.toFixed(2)} km²<br>Institutions within: ${count}</div>`).openPopup();
            }
        });

        L.control.scale({ position: 'bottomleft' }).addTo(map);

        // Info popover toggle
        const popoverBtn = document.getElementById('info-popover-btn');
        const popover    = document.getElementById('info-popover');

        popoverBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = !popover.classList.contains('hidden');
            popover.classList.toggle('hidden', isOpen);
            popoverBtn.setAttribute('aria-expanded', String(!isOpen));
        });

        // Close when clicking outside
        document.addEventListener('click', () => {
            popover.classList.add('hidden');
            popoverBtn.setAttribute('aria-expanded', 'false');
        });
    });
</script>
@endsection
