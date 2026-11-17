@extends('layouts.app')

@section('dashboard-layout', 'enabled')

@section('content')
    <div class="map-dashboard map-focused-dashboard flex min-h-0 flex-1 flex-col" aria-label="GIS map workspace">
        <div id="mapStage" class="map-workspace relative min-h-0 w-full overflow-hidden">
            <div id="map" class="absolute inset-0 h-full w-full bg-slate-200"></div>
            <x-loading-overlay id="mapLoadingOverlay" label="Updating map…" />
            <button type="button" id="toggleMapFullscreen"
                class="absolute right-3 top-3 z-[1000] inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-md hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-blue-600"
                aria-controls="mapStage" aria-pressed="false" aria-label="Enlarge map" title="Enlarge map">
                <i class="fa-solid fa-expand" aria-hidden="true"></i>
                <span>Enlarge map</span>
            </button>
            <p id="mapBackgroundStatus" class="sr-only" role="status"></p>
        </div>
            <div id="sidePanel"
                class="institution-details-panel invisible translate-x-full" role="region" aria-label="Institution details">
                <div class="flex items-center justify-between border-b border-gray-200 p-4">
                    <h2 class="text-lg font-semibold text-gray-900">Institution Details</h2>
                    <button id="closePanel"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                        aria-label="Close institution details">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div id="panelContent" class="institution-details-content"></div>
            </div>


    </div>
@endsection

@section('scripts')
    <!-- Leaflet -->

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const stage = document.getElementById('mapStage');
            const mapLoadingOverlay = document.getElementById('mapLoadingOverlay');
            let mapLoadingCount = 0;
            const afterMapPaint = () => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
            function beginMapLoading() {
                const releaseGlobalLoader = window.showGlobalLoader?.('Updating map…');
                mapLoadingCount++;
                mapLoadingOverlay.hidden = typeof releaseGlobalLoader === 'function';
                stage.setAttribute('aria-busy', 'true');
                return async () => {
                    await afterMapPaint();
                    mapLoadingCount--;
                    mapLoadingOverlay.hidden = mapLoadingCount === 0 || typeof releaseGlobalLoader === 'function';
                    stage.setAttribute('aria-busy', String(mapLoadingCount > 0));
                    releaseGlobalLoader?.();
                };
            }
            async function withMapLoading(work) {
                const finish = beginMapLoading();
                try {
                    // Give the browser a paint before synchronous Leaflet work.
                    await afterMapPaint();
                    await work();
                } catch (error) {
                    console.error('Unable to update map:', error);
                    window.TalastasToast?.notify('error', 'The map could not be updated. Please try again.');
                } finally {
                    await finish();
                }
            }
            const resizeMap = () => window.dispatchEvent(new Event('map-dashboard-resize'));
            const filtersMenu = document.getElementById('mapFiltersMenu');
            document.addEventListener('click', event => {
                if (!filtersMenu.contains(event.target)) filtersMenu.open = false;
            });
            filtersMenu.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    filtersMenu.open = false;
                    filtersMenu.querySelector('summary').focus();
                    event.stopPropagation();
                }
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && stage.dataset.panel === 'institution') {
                    document.getElementById('closePanel').click();
                }
            });

            if (typeof L === 'undefined') {
                console.error('Leaflet library not loaded');
                return;
            }

            // At Lipa's latitude, zoom 18 shows roughly 50 m / 100 ft; zoom 19 shows 20 m.
            const backgroundMaxZoom = { default: 19, satellite: 18, terrain: 19, blank: 19 };

            const map = L.map('map', {
                center: [13.9411, 121.1631],
                zoom: 13,
                maxZoom: backgroundMaxZoom.default,
                inertia: false,
                bounceAtZoomLimits: false,
                // Enable navigation only after the exact city geometry has loaded.
                dragging: false,
                touchZoom: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false,
                zoomControl: false
            });

            const backgrounds = {
                default: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors', maxZoom: 19
                }),
                satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    attribution: 'Tiles &copy; Esri; imagery sources: Esri, Maxar, Earthstar Geographics and the GIS User Community', maxNativeZoom: 19, maxZoom: backgroundMaxZoom.satellite
                }),
                terrain: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Elevation/World_Hillshade/MapServer/tile/{z}/{y}/{x}', {
                    attribution: 'Tiles &copy; Esri; terrain sources: Esri, USGS and the GIS User Community', maxNativeZoom: 16, maxZoom: 19
                }),
                blank: L.layerGroup()
            };
            const mapStage = document.getElementById('mapStage');
            let currentBackground = null;
            const backgroundStatus = document.getElementById('mapBackgroundStatus');
            Object.values(backgrounds).filter(Boolean).forEach(layer => layer.on('tileerror', () => {
                if (layer === currentBackground) backgroundStatus.textContent = 'Some map tiles could not load. Try Default streets or retry later.';
            }));
            function selectBackground(key) {
                if (!Object.hasOwn(backgrounds, key)) key = 'default';
                // Stop in-flight institution zooms before applying the selected layer's limit.
                map.stop();
                map.setMaxZoom(backgroundMaxZoom[key]);
                if (map.getZoom() > backgroundMaxZoom[key]) {
                    map.setZoom(backgroundMaxZoom[key], { animate: false });
                }
                if (currentBackground && currentBackground !== backgrounds[key]) map.removeLayer(currentBackground);
                currentBackground = backgrounds[key];
                if (currentBackground) {
                    currentBackground.setOpacity?.(mapStage.classList.contains('heatmap-active') ? 0.45 : 1);
                    if (!map.hasLayer(currentBackground)) currentBackground.addTo(map);
                }
                backgroundStatus.textContent = key === 'blank' ? 'Plain background; institution markers and boundaries remain visible.' : '';
                try { localStorage.setItem('talastas.map.background', key); } catch {}
            }
            L.control.layers({ Default: backgrounds.default, Satellite: backgrounds.satellite, Terrain: backgrounds.terrain, Blank: backgrounds.blank }, null, { position: 'topleft', collapsed: true }).addTo(map);
            map.on('baselayerchange', event => {
                const key = Object.keys(backgrounds).find(key => backgrounds[key] === event.layer);
                if (key) selectBackground(key);
            });
            let savedBackground = 'default';
            try { savedBackground = localStorage.getItem('talastas.map.background') || 'default'; } catch {}
            selectBackground(savedBackground);

            // Create custom pane for institution markers to ensure they're on top
            map.createPane('institutionBoundaries');
            map.getPane('institutionBoundaries').style.zIndex = 650;
            map.getPane('institutionBoundaries').style.pointerEvents = 'none';

            map.createPane('institutionMarkers');
            map.getPane('institutionMarkers').style.zIndex = 700;

            const syncMapSize = () => {
                window.requestAnimationFrame(() => {
                    map.invalidateSize({
                        pan: false,
                        animate: false
                    });
                });
            };

            const mapResizeObserver =
                typeof ResizeObserver !== 'undefined'
                    ? new ResizeObserver(syncMapSize)
                    : null;

            // CSS fullscreen keeps the map and its existing Leaflet instance alive.
            const fullscreenButton = document.getElementById('toggleMapFullscreen');
            const dashboard = mapStage.closest('.map-dashboard');
            let mapEnlarged = false;
            function setMapEnlarged(enlarged) {
                mapEnlarged = enlarged;
                mapStage.classList.toggle('relative', !enlarged);
                ['fixed', 'inset-0', 'z-[2000]'].forEach(name => mapStage.classList.toggle(name, enlarged));
                dashboard.classList.toggle('map-enlarged', enlarged);
                fullscreenButton.setAttribute('aria-pressed', String(enlarged));
                const label = enlarged ? 'Minimize map' : 'Enlarge map';
                fullscreenButton.setAttribute('aria-label', label);
                fullscreenButton.title = label;
                fullscreenButton.querySelector('span').textContent = label;
                fullscreenButton.querySelector('i').className = enlarged ? 'fa-solid fa-compress' : 'fa-solid fa-expand';
                // Wait for layout before Leaflet measures the new container size.
                syncMapSize();
                fullscreenButton.focus({ preventScroll: true });
            }
            fullscreenButton.addEventListener('click', () => setMapEnlarged(!mapEnlarged));
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && mapEnlarged) {
                    event.preventDefault();
                    setMapEnlarged(false);
                }
            });

            mapResizeObserver?.observe(mapStage);
            window.addEventListener('resize', syncMapSize);
            window.addEventListener('map-dashboard-resize', () => { syncMapSize(); setTimeout(syncMapSize, 260); });

            setTimeout(syncMapSize, 0);
            setTimeout(syncMapSize, 150);
            setTimeout(syncMapSize, 500);

            let heatmapLayer = null;
            let institutionFeatures = [];
            // Keep every layer so filtering can restore previously hidden institutions.
            let institutionLayers = [];
            let barangayOverlay = null;
            let activeInstitutionBoundary = null;
            // Registry: barangay name → layer, populated after GeoJSON loads
            const barangayLayerMap = {};

            const heatmapBtn = document.getElementById('toggleHeatmap');
            const barangayBtn = document.getElementById('toggleBarangays');
            const barangaySelect = document.getElementById('barangaySelect');
            const metricSelect = document.getElementById('mapMetricSelect');
            const establishmentSelect = document.getElementById('mapEstablishmentSelect');
            const districtSelect = document.getElementById('mapDistrictSelect');
            const barangayDistricts = new Map();
            const normalizeDistrict = value => String(value ?? '').trim().toUpperCase();
            const normalizeBarangay = value => String(value ?? '').trim().toLowerCase();
            let activeDistrict = '';


            const metricDefinitions = {
                gap_score: {
                    label: 'Overall ICT Gap',
                    shortLabel: 'Overall ICT Gap'
                },
                infrastructure_deficit_score: {
                    label: 'Infrastructure Deficit',
                    shortLabel: 'Infrastructure'
                },
                connectivity_divide_score: {
                    label: 'Connectivity Divide',
                    shortLabel: 'Connectivity'
                },
                technological_obsolescence_score: {
                    label: 'Technological Obsolescence Proxy',
                    shortLabel: 'Obsolescence'
                }
            };

            let activeMetric = metricSelect?.value || 'gap_score';
            let activeEstablishment = establishmentSelect?.value || 'all';
            let heatmapVisible = false;
            let activeBarangayLayer = null;

            function metricValue(properties, metricKey = activeMetric) {
                const rawValue = properties?.[metricKey];

                if (
                    !['number', 'string'].includes(typeof rawValue)
                    || (typeof rawValue === 'string' && rawValue.trim() === '')
                ) {
                    return null;
                }

                const value = Number(rawValue);

                return Number.isFinite(value)
                    ? Math.max(0, Math.min(100, value))
                    : null;
            }

            function interpolateColor(start, end, ratio) {
                const channel = (index) => Math.round(
                    start[index] + (end[index] - start[index]) * ratio
                );

                return `rgb(${channel(0)}, ${channel(1)}, ${channel(2)})`;
            }

            function getGapColor(score) {
                if (score === null || score === undefined || String(score).trim() === '' || !Number.isFinite(Number(score))) {
                    return '#94a3b8';
                }

                const normalized = Math.max(0, Math.min(100, Number(score))) / 100;

                if (normalized <= 0.5) {
                    return interpolateColor(
                        [37, 99, 235],
                        [245, 158, 11],
                        normalized / 0.5
                    );
                }

                return interpolateColor(
                    [245, 158, 11],
                    [220, 38, 38],
                    (normalized - 0.5) / 0.5
                );
            }

            // One predicate keeps markers, boundaries, and the heatmap in sync.
            function districtOf(properties) {
                return normalizeDistrict(properties?.district || properties?.DISTRICT)
                    || barangayDistricts.get(normalizeBarangay(properties?.barangay || properties?.NAME || properties?.name))
                    || '';
            }

            function matchesDistrict(properties) {
                return !activeDistrict || districtOf(properties) === activeDistrict;
            }

            function matchesInstitutionFilters(properties) {
                return matchesDistrict(properties)
                    && (activeEstablishment === 'all' || properties?.type === activeEstablishment)
                    && metricValue(properties) !== null;
            }

            function refreshDistrictOptions() {
                const districts = new Set([
                    ...barangayDistricts.values(),
                    ...institutionFeatures.map(feature => districtOf(feature.properties))
                ]);
                districtSelect.replaceChildren(new Option('All districts', ''));
                [...districts].filter(Boolean).sort().forEach(district => {
                    districtSelect.add(new Option(district, district));
                });
                districtSelect.value = activeDistrict;
            }

            function filterDistrictBoundaries() {
                if (!barangayOverlay) return;
                Object.entries(barangayLayerMap).forEach(([name, layer]) => {
                    const visible = matchesDistrict(layer.feature.properties);
                    if (visible) barangayOverlay.addLayer(layer);
                    else barangayOverlay.removeLayer(layer);
                    const option = [...barangaySelect.options].find(option => option.value === name);
                    if (option) { option.hidden = !visible; option.disabled = !visible; }
                    if (!visible && layer === activeBarangayLayer) {
                        layer.setStyle({ weight: 1.5, color: '#64748b', fillOpacity: 0.04 });
                        activeBarangayLayer = null;
                        barangaySelect.value = '';
                    }
                });
            }

            function fitDistrict() {
                map.stop();
                const bounds = L.latLngBounds([]);
                window.institutionsLayer?.eachLayer(layer => {
                    if (layer.getLatLng) bounds.extend(layer.getLatLng());
                    else if (layer.getBounds) bounds.extend(layer.getBounds());
                });
                // District polygons provide a useful destination when no markers match.
                if (!bounds.isValid()) {
                    Object.values(barangayLayerMap).forEach(layer => {
                        if (matchesDistrict(layer.feature.properties)) bounds.extend(layer.getBounds());
                    });
                }
                if (bounds.isValid()) map.fitBounds(bounds, { padding: [48, 48], maxZoom: 16, animate: false });
            }

            function buildHeatData() {
                return institutionFeatures
                    .filter(feature => matchesInstitutionFilters(feature.properties))
                    .map((feature) => {
                        const properties = feature.properties || {};
                        const value = metricValue(properties);

                        if (value === null) {
                            return null;
                        }

                        const latitude = Number(properties.latitude);
                        const longitude = Number(properties.longitude);

                        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                            return null;
                        }

                        const intensity = 0.05 + (value / 100) * 0.95;

                        return [
                            latitude,
                            longitude,
                            intensity
                        ];
                    })
                    .filter(Boolean);
            }

            const legendControl = L.control({ position: 'bottomright' });
            let legendMetricLabel;
            legendControl.onAdd = () => {
                const container = L.DomUtil.create('div', 'leaflet-control map-compact-legend');
                container.innerHTML = `<details><summary>Legend</summary><div class="map-legend-content text-slate-700">
                    <section aria-label="Color key" class="rounded-lg bg-gray-50 p-2">
                        <div class="mb-1.5 flex flex-wrap items-center gap-1">
                            <h3 class="map-legend-metric text-xs font-semibold text-gray-900"></h3>
                            <span class="map-gap-help">
                                <button type="button" aria-label="About the selected gap metric" aria-describedby="mapGapTooltip"
                                    class="inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-600 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-500">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM10 5a1 1 0 1 0 0 2 1 1 0 0 0 0-2ZM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <span id="mapGapTooltip" role="tooltip"
                                    class="invisible absolute bottom-full right-0 z-10 w-48 rounded-md bg-slate-800 px-3 py-2 text-[11px] font-normal leading-relaxed text-white opacity-0 shadow-lg group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100"></span>
                            </span>
                        </div>
                        <div class="map-legend-gradient" role="img" aria-label="Score scale: blue at 0%, orange at 50%, red at 100%"></div>
                        <div class="mt-1 flex justify-between text-[10px] text-gray-500"><span>0%</span><span>50%</span><span>100%</span></div>
                        <div class="mt-0.5 flex justify-between text-[10px] leading-tight text-gray-600"><span>Low Gap</span><span>High Gap</span></div>
                    </section>
                    <section aria-label="Symbol key" class="mt-2 rounded-lg bg-gray-50 p-2">
                        <h3 class="mb-1.5 text-xs font-semibold text-gray-900">Establishment Type</h3>
                        <ul class="m-0 list-none space-y-1 p-0">
                            ${[['fa-book-open', 'Elementary School'], ['fa-graduation-cap', 'High School'], ['fa-landmark', 'Barangay Hall'], ['fa-school', 'Other / unspecified school']].map(([icon, label]) => `<li class="flex items-center gap-2 leading-tight"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-gray-400 text-white" aria-hidden="true">${institutionSvg(icon)}</span><span>${label}</span></li>`).join('')}
                        </ul>
                    </section>
                    <section aria-label="Map Marker Clusters" class="mt-2 rounded-lg bg-gray-50 p-2">
                        <h3 class="mb-1.5 text-xs font-semibold text-gray-900">Map Marker Clusters</h3>
                        <div class="flex items-start gap-2">
                            <span class="institution-map-cluster h-8 w-8 shrink-0" aria-hidden="true"><span>4</span></span>
                            <div class="min-w-0">
                                <p class="m-0 font-semibold leading-tight">Grouped establishments</p>
                                <p class="mt-1 text-[11px] leading-relaxed">Number indicates nearby locations. Click to zoom in or expand.</p>
                            </div>
                        </div>
                    </section>
                    <div class="mt-2 flex items-center gap-2 text-[10px] text-gray-500"><span class="inline-block w-5 border-t border-slate-500" aria-hidden="true"></span>Barangay boundary</div>
                </div></details>`;
                legendMetricLabel = container.querySelector('.map-legend-metric');
                L.DomEvent.disableClickPropagation(container);
                L.DomEvent.disableScrollPropagation(container);
                const details = container.querySelector('details');
                container.addEventListener('mouseenter', () => { if (matchMedia('(hover: hover)').matches) details.open = true; });
                container.addEventListener('mouseleave', () => { if (!container.contains(document.activeElement)) details.open = false; });
                container.addEventListener('keydown', event => { if (event.key === 'Escape') { details.open = false; details.querySelector('summary').focus(); } });
                return container;
            };
            legendControl.addTo(map);
            function updateLegend() {
                legendMetricLabel.textContent = (metricDefinitions[activeMetric] || metricDefinitions.gap_score).shortLabel;
                const explanations = {
                    gap_score: 'Average of infrastructure, connectivity, and retirement-based scores, available only when all three can be calculated. Higher percentages mean a larger ICT gap.',
                    infrastructure_deficit_score: 'Defective units within active applicable equipment, as a percentage of active applicable equipment. Higher percentages mean a larger gap.',
                    connectivity_divide_score: 'Average download-speed shortfall against advertised speeds across recorded Internet connections. Confirmed no Internet service scores 100%. Higher percentages mean a larger gap.',
                    technological_obsolescence_score: 'Retired applicable units as a percentage of total applicable units. This is a retirement proxy, not equipment age. Higher percentages mean a larger gap.'
                };
                document.getElementById('mapGapTooltip').textContent = explanations[activeMetric] || explanations.gap_score;
            }

            let selectedInstitutionMarker = null;
            function updateInstitutionMarkerStyles() {
                if (!window.institutionsLayer) {
                    return;
                }

                // Clear a filtered-out selection before styling any layer, so
                // every remaining marker restores its color in the same pass.
                if (selectedInstitutionMarker && !matchesInstitutionFilters(
                    selectedInstitutionMarker.feature?.properties || selectedInstitutionMarker.properties
                )) {
                    selectedInstitutionMarker = null;
                    clearInstitutionBoundary();
                    document.getElementById('sidePanel').classList.add('translate-x-full', 'invisible');
                    delete stage.dataset.panel;
                    resizeMap();
                }

                const removed = [];
                const added = [];
                institutionLayers.forEach((layer) => {
                    const properties = layer.feature?.properties || layer.properties;
                    const value = metricValue(properties);
                    if (!matchesInstitutionFilters(properties)) {
                        if (window.institutionsLayer.hasLayer(layer)) removed.push(layer);
                        return;
                    }

                    const color = selectedInstitutionMarker && layer !== selectedInstitutionMarker
                        ? '#94a3b8'
                        : getGapColor(value);

                    // Leaflet recreates a marker's DOM when it is restored. Keep
                    // its icon HTML current, including while the group is off-map.
                    if (layer instanceof L.Marker) {
                        layer.options.icon.options.html = institutionSymbol(
                            institutionIconClass(properties), color
                        );
                    }
                    if (!window.institutionsLayer.hasLayer(layer)) {
                        added.push(layer);
                    }

                    if (!(layer instanceof L.Marker)) {
                        layer.setStyle?.({ color, fillColor: color });
                        return;
                    }

                    const symbol = layer.getElement()?.querySelector('.institution-map-symbol');
                    if (symbol) symbol.style.backgroundColor = color;

                    layer.getElement()?.classList.toggle('selected-institution-marker', layer === selectedInstitutionMarker);
                });
                // Batch membership changes so the cluster tree updates once per filter.
                window.institutionsLayer.removeLayers(removed);
                window.institutionsLayer.addLayers(added);
            }

            function rebuildHeatmap() {
                mapStage.classList.toggle('heatmap-active', heatmapVisible);
                currentBackground?.setOpacity?.(heatmapVisible ? 0.45 : 1);
                if (heatmapLayer) {
                    map.removeLayer(heatmapLayer);
                    heatmapLayer = null;
                }

                if (!heatmapVisible) {
                    updateLegend();
                    return;
                }

                const heatData = buildHeatData();

                if (heatData.length > 0) {
                    heatmapLayer = L.heatLayer(heatData, {
                        minOpacity: 0.18,
                        radius: 46,
                        blur: 18,
                        // Keep percentage weights visible at city and street zooms.
                        // Leaflet.heat otherwise divides them by 2^(maxZoom - zoom).
                        maxZoom: 0,
                        max: 1,
                        gradient: {
                            0.0: '#2563eb',
                            0.5: '#f59e0b',
                            1.0: '#dc2626'
                        }
                    }).addTo(map);
                }

                updateLegend();
            }

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
                    maxZoom: map.getMaxZoom(),
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

            let cityNavigation = null;

            // ── Reset to city-wide view ──────────────────────────────────────────
            function resetView() {
                activeDistrict = '';
                districtSelect.value = '';
                filterDistrictBoundaries();
                selectedInstitutionMarker = null;
                updateInstitutionMarkerStyles();
                rebuildHeatmap();
                delete stage.dataset.panel;
                resizeMap();
                clearInstitutionBoundary();

                const panel = document.getElementById('sidePanel');
                panel.classList.add('translate-x-full', 'invisible');

                cityNavigation?.reset();
                // Clear active highlight
                if (activeBarangayLayer) {
                    activeBarangayLayer.setStyle({
                        weight: 1.5,
                        color: '#64748b',
                        fillOpacity: 0.04
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
                heatmapBtn.title = heatmapVisible
                    ? 'Hide selected ICT gap heatmap'
                    : 'Show selected ICT gap heatmap';
            }

            function updateBarangayButtonState() {
                const active = !!(barangayOverlay && map.hasLayer(barangayOverlay));
                setCtrlActive(barangayBtn, active);
                barangayBtn.title = active ? 'Hide Barangays' : 'Show Barangays';
            }



            updateHeatmapButtonState();
            updateBarangayButtonState();
            updateLegend();

            let outsideMask = null;

            // Load barangay boundaries
            const finishBoundaryLoading = beginMapLoading();
            fetch('/data/lipa-city-barangays.geojson')
                .then(r => { if (!r.ok) throw new Error('Boundary request failed: ' + r.status); return r.json(); })
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

                    cityNavigation = window.installLipaNavigation(map, data);
                    L.control.zoom().addTo(map);
                    createOutsideMask(data);
                    barangayOverlay = L.geoJSON(data, {
                        style: {
                            color: '#64748b',
                            weight: 1.5,
                            fillOpacity: 0.04,
                            fillColor: '#f8fafc'
                        },
                        onEachFeature: (feature, layer) => {
                            const props = feature.properties;
                            const name = props.NAME || props.name;
                            // Register layer by name for dropdown lookup
                            barangayLayerMap[name] = layer;
                            barangayDistricts.set(normalizeBarangay(name), normalizeDistrict(props.district || props.DISTRICT));

                            // Click → fly to this barangay
                            layer.on('click', (event) => {
                                L.DomEvent.stopPropagation(event);

                                map.closePopup();

                                if (
                                    activeBarangayLayer &&
                                    activeBarangayLayer !== layer
                                ) {
                                    activeBarangayLayer.setStyle({
                                        weight: 1.5,
                                        color: '#64748b',
                                        fillOpacity: 0.04
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
                                        weight: 2,
                                        color: '#475569',
                                        fillOpacity: 0.08
                                    });
                                }
                            });

                            layer.on('mouseout', () => {
                                if (layer !== activeBarangayLayer) {
                                    layer.setStyle({
                                        weight: 1.5,
                                        color: '#64748b',
                                        fillOpacity: 0.04
                                    });
                                }
                            });
                        }
                    });
                    cityNavigation.reset();
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
                    refreshDistrictOptions();
                    filterDistrictBoundaries();
                    updateInstitutionMarkerStyles();
                    rebuildHeatmap();
                    if (activeDistrict) fitDistrict();

                    // Dropdown → fly to selected barangay
                    barangaySelect.addEventListener('change', () => {
                        const selected = barangaySelect.value;
                        if (!selected) {
                            resetView();
                            return;
                        }
                        const layer = barangayLayerMap[selected];
                        if (!layer || !matchesDistrict(layer.feature.properties)) return;
                        // Ensure barangay overlay is visible
                        if (!map.hasLayer(barangayOverlay)) {
                            map.addLayer(barangayOverlay);
                            updateBarangayButtonState();
                        }
                        // Highlight + zoom
                        if (activeBarangayLayer && activeBarangayLayer !== layer) {
                            activeBarangayLayer.setStyle({
                                weight: 1.5,
                                color: '#64748b',
                                fillOpacity: 0.04
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
                .catch(err => console.error('Error loading barangays:', err))
                .finally(finishBoundaryLoading);

            // Load institution data
            // Shared artwork for both Leaflet markers and the legend; no external images.
            function institutionSvg(icon) {
                const paths = {
                    'fa-book-open': '<path d="M3 4c3-.8 5.8-.2 8 1.3V21c-2.2-1.5-5-2.1-8-1.3V4Zm10 1.3C15.2 3.8 18 3.2 21 4v15.7c-3-.8-5.8-.2-8 1.3V5.3Z"/>',
                    'fa-graduation-cap': '<path d="m12 3 11 5-11 5L1 8l11-5Zm-7 9 7 3.2 7-3.2v5c-4.2 3.2-9.8 3.2-14 0v-5Zm16-1 2-.9V19h-2v-8Z"/>',
                    'fa-landmark': '<path d="m12 2 10 6v2H2V8l10-6ZM4 12h3v7H4v-7Zm6.5 0h3v7h-3v-7Zm6.5 0h3v7h-3v-7ZM2 20h20v3H2v-3Z"/>',
                    'fa-school': '<path fill-rule="evenodd" d="m12 2 10 7v13H2V9l10-7Zm-1 5v4h2V7h-2Zm-5 6v3h3v-3H6Zm9 0v3h3v-3h-3Zm-5 4v5h4v-5h-4Z" clip-rule="evenodd"/>',
                    'fa-building': '<path fill-rule="evenodd" d="M4 2h16v20H4V2Zm4 4v3h2V6H8Zm6 0v3h2V6h-2Zm-6 6v3h2v-3H8Zm6 0v3h2v-3h-2Zm-4 6v4h4v-4h-4Z" clip-rule="evenodd"/>'
                };
                return `<svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">${paths[icon] || paths['fa-building']}</svg>`;
            }

            function institutionSymbol(icon, metricBackground = '#0F2C59') {
                return `<span class="institution-map-symbol" style="background-color: ${metricBackground}"><span class="flex h-6 w-6 items-center justify-center text-white">${institutionSvg(icon)}</span></span>`;
            }

            function institutionIconClass(properties) {
                const normalize = value => String(value ?? '').trim().toLowerCase()
                    .replace(/[_-]+/g, ' ').replace(/\s+/g, ' ');
                const type = normalize(properties.type);
                if (type === 'barangay hall') return 'fa-landmark';
                const level = normalize(properties.school_level) || type;
                if (['elementary', 'elementary school', 'primary'].includes(level)) return 'fa-book-open';
                if (['high school', 'junior high', 'junior high school', 'senior high', 'senior high school', 'secondary'].includes(level)) return 'fa-graduation-cap';
                return type === 'school' ? 'fa-school' : 'fa-building';
            }

            const finishInstitutionLoading = beginMapLoading();
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
                        const selectedMetricValue =
                            metricValue(props);

                        const color =
                            getGapColor(selectedMetricValue);


                        return {
                            ...f,
                            properties: {
                                ...props,
                                markerColor: color
                            }
                        };
                    });

                    institutionFeatures = features;
                    refreshDistrictOptions();
                    document.dispatchEvent(new CustomEvent('map-institutions-loaded', { detail: features }));

                    window.institutionsLayer = L.geoJSON({
                        type: 'FeatureCollection',
                        features
                    }, {
                        pointToLayer: (feature, latlng) => {
                            const props = feature.properties;
                            const iconClass = institutionIconClass(props);
                            const marker = L.marker(latlng, {
                                icon: L.divIcon({
                                    className: 'institution-map-marker',
                                    html: institutionSymbol(iconClass, props.markerColor),
                                    iconSize: [36, 36],
                                    iconAnchor: [18, 18],
                                    popupAnchor: [0, -20]
                                }),
                                keyboard: false,
                                title: `${props.name} (${props.type || 'Other institution'})`,
                                pane: 'institutionMarkers'
                            });
                            marker.properties = props; // Attach properties for later use
                            return marker;
                        }
                    });

                    institutionLayers = window.institutionsLayer.getLayers();

                    // Attach click events and popups after adding to map
                    window.institutionsLayer.eachLayer(function(layer) {
                        if (layer instanceof L.Marker) {
                            const props = layer.properties;
                            const hasGapScore =
                                props.gap_score !== null &&
                                props.gap_score !== undefined;

                            const gapScore = hasGapScore ?
                                Number(props.gap_score) :
                                null;
                            const gapLevel = gapScore === null
                                ? 'Insufficient Data'
                                : 'Assessed';
    
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
                            <div class="institution-detail-body">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="institution-details-name">${escapeHtml(props.name)}</h3>
                                    <div class="flex gap-1">
                                        ${props.analysis_method === 'generative_ai_prescriptive' ? '<span class="px-2 py-0.5 text-xs bg-red-100 text-red-700 rounded-full">AI</span>' : ''}
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
                                        <span class="font-medium">${escapeHtml(props.type || 'Not Available')}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Barangay:</span>
                                        <span class="font-medium">${escapeHtml(props.barangay)}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">District:</span>
                                        <span class="font-medium">${escapeHtml(districtOf(props) || 'Not recorded')}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Internet Connection:</span>
                                        <span class="font-medium">${props.has_internet_connection === true ? 'Yes' : (props.has_internet_connection === false ? 'No' : 'Not recorded')}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Bandwidth Allocation:</span>
                                        <span class="font-medium">${props.bandwidth_allocation_mbps !== null && props.bandwidth_allocation_mbps !== undefined ? props.bandwidth_allocation_mbps + ' Mbps' : 'Not recorded'}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Measured Download:</span>
                                        <span class="font-medium">${props.download_speed_mbps !== null && props.download_speed_mbps !== undefined ? props.download_speed_mbps + ' Mbps' : 'Not recorded'}</span>
                                    </div>
                                </div>
                                <div class="border-t border-gray-200 my-2"></div>
                                <div class="space-y-2 text-sm mb-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-gray-700 font-semibold">
                                            Overall ICT Gap
                                            <span class="ml-1 cursor-help text-[10px] text-gray-400"
                                                title="Formula: (Infrastructure Deficit + Connectivity Divide + Technological Obsolescence Proxy) / 3">i</span>
                                        </span>
                                        <span class="font-bold ${gapScore === null ? 'institution-missing-badge' : ''}">${gapScore !== null ? gapScore.toFixed(1) + '%' : gapLevel}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-gray-600">
                                            Infrastructure Deficit
                                            <span class="ml-1 cursor-help text-[10px] text-gray-400"
                                                title="Formula: (Defective Active Applicable Units / Active Applicable Units) x 100">i</span>
                                        </span>
                                        <span class="font-medium">${props.infrastructure_deficit_score !== null && props.infrastructure_deficit_score !== undefined ? Number(props.infrastructure_deficit_score).toFixed(1) + '%' : '—'}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-gray-600">
                                            Connectivity Divide
                                            <span class="ml-1 cursor-help text-[10px] text-gray-400"
                                                title="Average per-connection download-speed shortfall against advertised speed; confirmed no Internet service scores 100%.">i</span>
                                        </span>
                                        <span class="font-medium">${props.connectivity_divide_score !== null && props.connectivity_divide_score !== undefined ? Number(props.connectivity_divide_score).toFixed(1) + '%' : '—'}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-gray-600">
                                            Technological Obsolescence Proxy
                                            <span class="ml-1 cursor-help text-[10px] text-gray-400"
                                                title="Formula: (Retired Applicable Units / Total Applicable Units) x 100">i</span>
                                        </span>
                                        <span class="font-medium">${props.technological_obsolescence_score !== null && props.technological_obsolescence_score !== undefined ? Number(props.technological_obsolescence_score).toFixed(1) + '%' : '—'}</span>
                                    </div>
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
                            </div>
                        `;

                            layer.on('click', function(e) {
                                e.originalEvent?.stopPropagation();

                                                selectedInstitutionMarker = layer;
                                updateInstitutionMarkerStyles();
                                stage.dataset.panel = 'institution';
                                document.getElementById('panelContent').scrollTop = 0;
                                resizeMap();
                                if (!showInstitutionBoundary(props)) map.flyTo(layer.getLatLng(), 17, { duration: 0.8 });

                                // Populate side panel
                                document.getElementById('panelContent').innerHTML =
                                    popupContent;
                                // Show side panel
                                const panel = document.getElementById('sidePanel');
                                panel.classList.remove('translate-x-full', 'invisible');
                                if (e.originalEvent instanceof KeyboardEvent) document.getElementById('closePanel').focus();
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
                            const configureMarkerElement = () => {
                                const markerElement = layer.getElement();
                                if (markerElement) {
                                    markerElement.setAttribute('tabindex', '0');
                                    markerElement.setAttribute('role', 'button');
                                    markerElement.setAttribute('aria-label', `Show institution details for ${props.name}`);
                                    markerElement.classList.toggle('selected-institution-marker', layer === selectedInstitutionMarker);
                                    markerElement.onkeydown = event => {
                                        if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); layer.fire('click', { originalEvent: event }); }
                                    };
                                }
                            };
                            layer.on('add', configureMarkerElement);
                            configureMarkerElement();
                        }
                    });
                    // The GeoJSON group constructs the original icons and handlers only.
                    // A single cluster group owns the displayed, filtered markers.
                    window.institutionsLayer.clearLayers();
                    window.institutionsLayer = L.markerClusterGroup({
                        maxClusterRadius: 52,
                        // Keep each ungrouped establishment's original icon and metric color.
                        singleMarkerMode: false,
                        clusterPane: 'institutionMarkers',
                        showCoverageOnHover: false,
                        zoomToBoundsOnClick: true,
                        spiderfyOnMaxZoom: true,
                        animate: !matchMedia('(prefers-reduced-motion: reduce)').matches,
                        iconCreateFunction: cluster => {
                            const count = cluster.getChildCount();
                            const size = count >= 100 ? 44 : 42;
                            return L.divIcon({
                                className: 'institution-map-cluster',
                                html: `<span aria-label="${count} establishments grouped here. Zoom in or expand group.">${count}</span>`,
                                iconSize: [size, size],
                                iconAnchor: [size / 2, size / 2]
                            });
                        }
                    });
                    updateInstitutionMarkerStyles();
                    // Filter before displaying: insufficient-data layers never
                    // enter the map, even for the initial render.
                    window.institutionsLayer.addTo(map);
                    rebuildHeatmap();

                    console.log('Institutions layer added to map with', features.length, 'features');
                    if (activeDistrict) fitDistrict();
                    setTimeout(() => map.invalidateSize(), 200);
                })
                .catch(err => { console.error('Error loading institutions:', err); document.dispatchEvent(new Event('map-institutions-error')); })
                .finally(finishInstitutionLoading);

            heatmapBtn.addEventListener('click', () => withMapLoading(() => {
                heatmapVisible = !heatmapVisible;
                rebuildHeatmap();
                updateHeatmapButtonState();
            }));

            metricSelect?.addEventListener('change', () => withMapLoading(() => {
                activeMetric = metricSelect.value;
                updateInstitutionMarkerStyles();
                rebuildHeatmap();
                updateLegend();
            }));

            establishmentSelect?.addEventListener('change', () => withMapLoading(() => {
                activeEstablishment = establishmentSelect.value;
                // Reuse cached layers and their event handlers; no additional fetch.
                updateInstitutionMarkerStyles();
                rebuildHeatmap();
            }));

            districtSelect.addEventListener('change', () => withMapLoading(() => {
                activeDistrict = districtSelect.value;
                filterDistrictBoundaries();
                updateInstitutionMarkerStyles();
                rebuildHeatmap();
                fitDistrict();
            }));

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

            document.getElementById('clearDrawings')?.addEventListener('click', () => {
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
                position: 'bottomleft',
                maxWidth: 100,
                metric: true,
                imperial: true
            }).addTo(map);


            // Close side panel
            document.getElementById('closePanel').addEventListener('click', (e) => {
                e.stopPropagation();
                const panel = document.getElementById('sidePanel');
                panel.classList.add('translate-x-full', 'invisible');
                delete stage.dataset.panel;
                resizeMap();
                clearInstitutionBoundary();
                const previouslySelected = selectedInstitutionMarker;
                selectedInstitutionMarker = null;
                updateInstitutionMarkerStyles();
                previouslySelected?.getElement()?.focus();
            });
        });
    </script>

    <style>
        .map-workspace.heatmap-active #map { background: #f1f5f9; }
        #map .leaflet-heatmap-layer { pointer-events: none; z-index: 450; }
        .map-heatmap-key {
            position: absolute;
            bottom: 1.5rem;
            left: 0.75rem;
            z-index: 800;
            width: 245px;
            max-width: calc(100% - 1.5rem);
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: rgb(255 255 255 / 96%);
            box-shadow: 0 2px 8px rgb(15 23 42 / 18%);
            color: #334155;
            font-size: 11px;
            line-height: 1.4;
            pointer-events: none;
        }
        .map-heatmap-key strong { display: block; color: #0f172a; }
        .map-heatmap-scale { height: 10px; margin-top: 0.5rem; border-radius: 4px; background: linear-gradient(to right, #2563eb, #f59e0b, #dc2626); }
        .map-heatmap-scale-labels { display: flex; justify-content: space-between; margin-top: 0.25rem; font-weight: 600; }
        .map-heatmap-key p { margin: 0.375rem 0 0; }
        .institution-map-marker { background: transparent; border: 0; }
        .institution-map-symbol {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            box-sizing: border-box;
            border: 2px solid white;
            border-radius: 10px;
            background: #0F2C59;
            color: white;
            font-size: 12px;
            box-shadow: 0 2px 6px rgb(15 23 42 / 35%);
            transition: transform 150ms ease;
        }
        .institution-legend-symbol { width: 20px; height: 20px; font-size: 10px; flex-shrink: 0; }
        .institution-map-marker:hover .institution-map-symbol,
        .institution-map-marker:focus-visible .institution-map-symbol { transform: scale(1.15); }
        .institution-map-marker:focus-visible { outline: 2px solid #0f172a; outline-offset: 3px; border-radius: 6px; }
        .institution-map-marker.selected-institution-marker .institution-map-symbol { outline: 3px solid #2589df; outline-offset: 2px; }
        @media (prefers-reduced-motion: reduce) {
            .institution-map-symbol { transition: none; }
        }
        /* Override Leaflet's default popup chrome to match the dashboard */
        .map-popup .leaflet-popup-content-wrapper {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 10px 24px -4px rgba(0, 0, 0, 0.08);
            padding: 0;
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
