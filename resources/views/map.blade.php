@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg p-6">
            <div class="mb-4 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">GIS Map - ICT Infrastructure Coverage</h1>
                    <p class="text-gray-600 mt-1">Lipa City Public Institutions & Barangay Spatial Analysis</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button id="toggleHeatmap" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 font-medium text-sm">
                        Toggle Heatmap
                    </button>
                    <button id="toggleBarangays" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-medium text-sm">
                        Toggle Barangays
                    </button>
                    <button id="clearDrawings" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 font-medium text-sm">
                        Clear Drawings
                    </button>
                </div>
            </div>

            <!-- System Scope Notice (1.4) -->
            <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm">
                <div class="flex items-start">
                    <svg class="w-4 h-4 text-blue-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-blue-800">
                        <strong>System Note:</strong> Data reflects pilot public institutions only. No real-time IoT monitoring. Marked 
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">Pilot</span> 
                        sites are representative sample locations. Institutions with 
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">⚠️ Audit required</span> 
                        need field verification. Regular updates required to maintain accuracy.
                    </div>
                </div>
            </div>

            <!-- Map Legend -->
            <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Legend</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-green-500 border-2 border-white shadow"></span>
                        <span class="text-gray-600">High Connectivity (>50 Mbps)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-yellow-500 border-2 border-white shadow"></span>
                        <span class="text-gray-600">Moderate (25-50 Mbps)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-orange-500 border-2 border-white shadow"></span>
                        <span class="text-gray-600">Low (10-25 Mbps)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-red-500 border-2 border-white shadow"></span>
                        <span class="text-gray-600">Critical (<10 Mbps)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-1 bg-blue-700"></span>
                        <span class="text-gray-600">Barangay Boundaries</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-gray-300 border-2 border-black"></span>
                        <span class="text-gray-600">Incomplete Data</span>
                    </div>
                </div>
                <div class="mt-2 pt-2 border-t border-gray-200 text-xs text-gray-500">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full bg-purple-100 text-purple-700 font-semibold mr-2">PILOT</span>
                    indicates pilot test site — representative sample of full 72-barangay coverage.
                </div>
            </div>

            <!-- Interactive Barangay Sidebar -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-1 bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Barangay Navigator</h3>
                    <input type="text" id="barangaySearch" placeholder="Search barangay..." class="w-full px-3 py-2 border border-gray-300 rounded-md mb-3">
                    <div id="barangayList" class="max-h-96 overflow-y-auto space-y-1">
                        <!-- Barangay list will be populated by JavaScript -->
                    </div>
                </div>
                <div class="lg:col-span-3">
                    <div id="map" class="w-full h-96 rounded-lg shadow-md border border-gray-300"></div>
                </div>
            </div>

            <!-- Layer Controls -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-gray-600">Click on institution markers to view detailed ICT specs and gap analysis</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-gray-600">Use drawing tools to outline areas for infrastructure planning</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const map = L.map('map');

        // Add base tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Layer groups for toggle control
        const heatmapLayer = L.layerGroup().addTo(map);
        const barangayLayer = L.layerGroup().addTo(map);
        let heatData = [];
        
        // Store barangay layers for navigation
        let barangayLayers = [];

        // Load Lipa City barangay boundaries permanently
        fetch('/data/lipa-city-barangays.geojson')
            .then(r => r.json())
            .then(data => {
                window.barangayOverlay = L.geoJSON(data, {
                    style: {
                        color: '#2563eb',
                        weight: 2,
                        fillOpacity: 0.05,
                        fillColor: '#dbeafe'
                    },
                    onEachFeature: (feature, layer) => {
                        const props = feature.properties;
                        const name = props.NAME || props.name;
                        const district = props.DISTRICT || props.district;
                        layer.bindPopup(`<strong>${name}</strong><br>District: ${district}`);

                        // Store layer reference for navigation
                        barangayLayers.push({ name, district, layer });

                        // Add click event to zoom to barangay bounds
                        layer.on('click', function () {
                            map.fitBounds(layer.getBounds(), { padding: [20, 20] });
                        });

                        // Highlight on hover
                        layer.on('mouseover', function () {
                            this.setStyle({ weight: 3, fillOpacity: 0.1 });
                        });
                        layer.on('mouseout', function () {
                            this.setStyle({ weight: 2, fillOpacity: 0.05 });
                        });
                    }
                }).addTo(map);

                // Populate barangay list
                const listContainer = document.getElementById('barangayList');
                barangayLayers.forEach(item => {
                    const li = document.createElement('div');
                    li.className = 'p-2 bg-white rounded border border-gray-200 cursor-pointer hover:bg-blue-50 transition-colors';
                    li.innerHTML = `<div class="font-medium text-gray-800">${item.name}</div><div class="text-xs text-gray-500">${item.district}</div>`;
                    li.addEventListener('click', () => {
                        map.fitBounds(item.layer.getBounds(), { padding: [20, 20] });
                        item.layer.openPopup();
                    });
                    listContainer.appendChild(li);
                });

                // Add search functionality
                document.getElementById('barangaySearch').addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const items = listContainer.children;
                    for (let item of items) {
                        const name = item.querySelector('.font-medium').textContent.toLowerCase();
                        item.style.display = name.includes(searchTerm) ? 'block' : 'none';
                    }
                });

                // Fit map to city bounds on load
                map.fitBounds(window.barangayOverlay.getBounds(), { padding: [20, 20] });
            })
            .catch(err => console.error('Error loading barangay boundaries:', err));

        // Load institution data from API
        fetch('/api/v1/map/institutions')
            .then(response => response.json())
            .then(data => {
                const features = data.features.map(f => {
                    const props = f.properties;
                    const gapScore = props.gap_score || 0;
                    const completeness = props.data_completeness || 0;

                    // Determine marker color based on gap score (red = high gap = poor infrastructure)
                    let color = '#ef4444'; // red
                    let radius = 10;
                    if (gapScore < 30) {
                        color = '#22c55e'; // green
                        radius = 10;
                    } else if (gapScore < 50) {
                        color = '#eab308'; // yellow
                        radius = 11;
                    } else if (gapScore < 70) {
                        color = '#f97316'; // orange
                        radius = 12;
                    } else {
                        color = '#dc2626'; // red
                        radius = 13;
                    }

                    // Add black border if data completeness is poor (<50%)
                    const weight = completeness < 50 ? 4 : 2;
                    const borderColor = completeness < 50 ? '#000000' : '#ffffff';

                    // Prepare heat data [lat, lng, intensity] where intensity = gap score
                    heatData.push([props.latitude, props.longitude, gapScore]);

                    return {
                        ...f,
                        properties: {
                            ...props,
                            markerColor: color,
                            markerRadius: radius,
                            borderColor: borderColor,
                            borderWeight: weight
                        }
                    };
                });

                // Create GeoJSON layer
                const geoJsonLayer = L.geoJSON({
                    type: 'FeatureCollection',
                    features: features
                }, {
                    pointToLayer: function (feature, latlng) {
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
                    onEachFeature: function (feature, layer) {
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

                        // Highlight on hover
                        layer.on('mouseover', function () {
                            this.setRadius(this.getRadius() + 3);
                            this.setStyle({ weight: 3 });
                        });
                        layer.on('mouseout', function () {
                            this.setRadius(props.markerRadius);
                            this.setStyle({ weight: 2 });
                        });
                    }
                }).addTo(map);

                // Store layer for filtering
                window.institutionsLayer = geoJsonLayer;
            })
            .catch(err => console.error('Error loading institutions:', err));

        // Heatmap toggle
        let heatmapVisible = false;
        let heatLayer = null;
        document.getElementById('toggleHeatmap').addEventListener('click', () => {
            if (heatmapVisible) {
                map.removeLayer(heatLayer);
                heatmapVisible = false;
            } else {
                heatLayer = L.heatLayer(heatData, {
                    radius: 25,
                    blur: 15,
                    maxZoom: 17,
                    gradient: {0.4: 'blue', 0.6: 'cyan', 0.7: 'lime', 0.8: 'yellow', 1: 'red'}
                }).addTo(map);
                heatmapVisible = true;
            }
        });

        // Barangay boundary toggle
        document.getElementById('toggleBarangays').addEventListener('click', () => {
            if (window.barangayOverlay) {
                if (map.hasLayer(window.barangayOverlay)) {
                    map.removeLayer(window.barangayOverlay);
                    document.getElementById('toggleBarangays').textContent = 'Show Barangays';
                } else {
                    map.addLayer(window.barangayOverlay);
                    document.getElementById('toggleBarangays').textContent = 'Hide Barangays';
                }
            }
        });

        // Geoman drawing tools
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
                if (layer.pm && layer.pm.enabled()) {
                    layer.pm.remove();
                }
            });
        });

        // Turf.js spatial analysis on drawn shapes
        map.on('pm:create', ({ layer, shape }) => {
            if (shape === 'Polygon' || shape === 'Rectangle') {
                const latLngs = layer.getLatLngs()[0].map(ll => [ll.lng, ll.lat]);
                const poly = turf.polygon([latLngs]);
                const area = turf.area(poly) / 1000000; // km²

                const centroid = turf.centroid(poly);
                const center = L.latLng(centroid.geometry.coordinates[1], centroid.geometry.coordinates[0]);
                
                // Count institutions within the drawn area
                let count = 0;
                if (window.institutionsLayer) {
                    window.institutionsLayer.eachLayer(l => {
                        const pt = turf.point([l.getLatLng().lng, l.getLatLng().lat]);
                        if (turf.booleanPointInPolygon(pt, poly)) count++;
                    });
                }

                layer.bindPopup(`
                    <div class="p-2">
                        <strong>Area of Interest</strong><br>
                        Area: ${area.toFixed(2)} km²<br>
                        Institutions within: ${count}
                    </div>
                `).openPopup();
            }
        });

        // Add scales and attribution
        L.control.scale({ position: 'bottomleft' }).addTo(map);
    });
</script>
@endsection
