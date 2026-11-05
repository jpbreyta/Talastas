@extends('layouts.app')

@section('dashboard-layout', 'enabled')

@section('content')
    <div class="map-dashboard" data-dashboard-endpoint="{{ route('map.analytics.summary') }}">
        <div class="dashboard-summary-grid">
            <x-dashboard-metric label="Total Institutions" icon="fa-building-columns" metric-key="institution_count" :count="true" />
            <x-dashboard-metric label="Overall ICT Gap" icon="fa-chart-line" metric-key="average_gap_score" />
            <x-dashboard-metric label="Infrastructure Deficit" icon="fa-computer" metric-key="infrastructure_deficit_score" />
            <x-dashboard-metric label="Connectivity Divide" icon="fa-wifi" metric-key="connectivity_divide_score" />
        </div>
        <div id="mapStage" class="map-workspace relative min-h-0 w-full overflow-hidden">
            <span id="mapAnalysisBadge" class="map-analysis-badge" hidden></span>
            <div id="map" class="absolute inset-0 h-full w-full bg-slate-200"></div>
            <div id="heatmapKey" class="map-heatmap-key" role="status" aria-live="polite" hidden>
                <strong id="heatmapKeyTitle">Heatmap — Overall ICT Gap</strong>
                <div class="map-heatmap-scale" aria-hidden="true"></div>
                <div class="map-heatmap-scale-labels"><span>Lower need</span><span>Higher need</span></div>
                <p id="heatmapKeyCount"></p>
                <p>Nearby institution scores blend; colors are not barangay averages.</p>
            </div>

            <button type="button" id="mapInformationButton" class="map-information-button"
                aria-label="Map information" title="Map information" aria-expanded="false" aria-controls="mapInformationPanel">
                <span aria-hidden="true">i</span>
            </button>
        </div>
        @include('partials.map-dashboard-insights')
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

            <div id="mapInformationOverlay" class="map-information-overlay" data-open="false" aria-hidden="true" inert>
                <button type="button" id="mapInformationBackdrop" class="map-information-backdrop" tabindex="-1" aria-label="Close map information"></button>
                <section id="mapInformationPanel" class="map-information-panel" role="region" aria-labelledby="mapInformationTitle" tabindex="-1">
                    <div class="map-information-heading">
                        <h2 id="mapInformationTitle"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Map Information</h2>
                        <button type="button" id="closeMapInformation" aria-label="Close map information"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                    </div>
                    <div class="map-information-tabs" role="tablist" aria-label="Map information sections">
                        <button id="mapLegendTab" role="tab" aria-selected="true" aria-controls="mapLegendSection" type="button">Legend</button>
                        <button id="mapMetricTab" role="tab" aria-selected="false" aria-controls="mapMetricDetails" tabindex="-1" type="button">Metric Details</button>
                        <button id="mapBaselineTab" role="tab" aria-selected="false" aria-controls="mapBaselineInfo" tabindex="-1" type="button">Baseline</button>
                    </div>
                    <div class="map-information-content">
                        <section id="mapLegendSection" role="tabpanel" aria-labelledby="mapLegendTab" tabindex="0">
                        <div id="mapLegend"
                            class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <div class="flex items-center justify-between border-b border-slate-100 px-3.5 py-2.5">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-layer-group text-xs text-slate-500" aria-hidden="true"></i>
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">
                                        ICT Gap Legend
                                    </span>
                                </div>
                                <span id="legendMetricShort"
                                    class="max-w-[150px] truncate text-[10px] font-semibold text-slate-400">
                                    Overall ICT Gap
                                </span>
                            </div>

                            <div class="space-y-3 px-3.5 py-3">
                                <div>
                                    <div class="mb-1.5 flex items-center justify-between gap-3">
                                        <span id="legendMetricLabel" class="text-xs font-semibold text-slate-700">
                                            Institution color — Overall ICT Gap
                                        </span>
                                        <span class="text-[10px] text-slate-400">0–100%</span>
                                    </div>

                                    <div class="h-2.5 w-full rounded-full"
                                        style="background: linear-gradient(90deg, #2563eb 0%, #f59e0b 50%, #dc2626 100%);">
                                    </div>

                                    <div class="mt-1 grid grid-cols-3 text-[10px] font-medium text-slate-500">
                                        <span class="text-left">0%</span>
                                        <span class="text-center">50%</span>
                                        <span class="text-right">100%</span>
                                    </div>

                                    <div class="mt-0.5 flex justify-between text-[10px] text-slate-400">
                                        <span>Lower observed gap</span>
                                        <span>Higher observed gap</span>
                                    </div>

                                    <p class="mt-1.5 text-[10px] leading-4 text-slate-400">
                                        Continuous scale only; these colors are not severity categories.
                                    </p>

                                    <div class="mt-2 flex items-center gap-2 text-[11px] text-slate-500">
                                        <span class="inline-block h-3 w-3 rounded-sm border-2 border-white bg-slate-400 shadow-sm"></span>
                                        <span>Not calculated / insufficient data</span>
                                    </div>
                                </div>

                                <div class="border-t border-slate-100 pt-2.5">
                                    <div class="grid grid-cols-1 gap-1.5 text-[11px] text-slate-500">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-block h-0 w-5 border-t-2 border-slate-500"></span>
                                            <span>Barangay boundary</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-block h-0 w-5 border-t-[3px] border-red-700"></span>
                                            <span>Selected barangay</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-2 border-t border-slate-100 pt-2.5 text-[11px] text-slate-500">
                                    <p class="font-semibold text-slate-700">Institution icons (saved location)</p>
                                    <div class="flex items-center gap-2"><span class="institution-map-symbol institution-legend-symbol"><i class="fa-solid fa-school" aria-hidden="true"></i></span><span>School</span></div>
                                    <div class="flex items-center gap-2"><span class="institution-map-symbol institution-legend-symbol"><i class="fa-solid fa-landmark" aria-hidden="true"></i></span><span>Barangay Hall</span></div>
                                    <div class="flex items-center gap-2"><span class="institution-map-symbol institution-legend-symbol"><i class="fa-solid fa-building" aria-hidden="true"></i></span><span>Other institution</span></div>
                                    <p class="text-[10px]">Icon identifies institution type; background color shows the selected ICT metric.</p>
                                </div>

                                <div id="heatmapLegendInfo" class="hidden border-t border-slate-100 pt-2.5">
                                    <div class="flex items-start gap-2">
                                        <i class="fa-solid fa-fire-flame-curved mt-0.5 text-xs text-red-500" aria-hidden="true"></i>
                                        <div>
                                            <p id="heatmapLegendTitle" class="text-[11px] font-semibold text-slate-700">
                                                Gap Heatmap
                                            </p>
                                            <p id="heatmapLegendDescription" class="mt-0.5 text-[10px] leading-4 text-slate-500">
                                                Spatial blend of assessed institution scores.
                                            </p>
                                            <p id="heatmapLegendCount" class="mt-1 text-[10px] font-medium text-slate-400"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="map-data-source"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div><strong>Data source</strong><p>Based on recorded institutional data. Colors show a continuous calculated gap, rather than severity categories.</p></div></div>
                        <div class="mt-3 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                            <i class="fa-solid fa-map-location-dot mt-0.5 text-sm text-slate-500" aria-hidden="true"></i>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-700">Region details</p>
                                <p id="barangay-hover-info" class="mt-1 text-xs text-slate-500">Hover over a region for details</p>
                            </div>
                        </div>
                        </section>
                        <section id="mapMetricDetails" role="tabpanel" aria-labelledby="mapMetricTab" tabindex="0" hidden><h3 id="mapMetricDetailsTitle">Overall ICT Gap</h3><p id="mapMetricDetailsDescription" class="mt-3 text-sm text-slate-600"></p></section>
                        <section id="mapBaselineInfo" role="tabpanel" aria-labelledby="mapBaselineTab" tabindex="0" hidden>
                            <div class="px-5 pb-5 pt-4">
                                <p class="text-[10px] font-bold uppercase tracking-[0.1em] text-slate-400">ICT Gap Baseline</p>
                                <div class="mb-3 mt-2 border-t border-slate-100"></div>
                                <ul class="space-y-2.5 text-[0.8125rem] leading-[1.5] text-slate-600">
                                    <li>Infrastructure Deficit uses defective active applicable units divided by active applicable units.</li>
                                    <li>Connectivity Divide measures the shortfall between recorded bandwidth allocation and measured download speed.</li>
                                    <li>Technological Obsolescence is a retirement-based proxy using retired applicable units divided by total applicable units.</li>
                                    <li>Overall ICT Gap is the equal arithmetic mean of the three core component percentages when all are available.</li>
                                    <li>Use the <strong>Color by</strong> selector to choose which calculated metric controls institution colors and the optional heatmap.</li>
                                    <li>The heatmap is a spatial blend of institution points, not a barangay score or severity classification.</li>
                                </ul>
                            </div>
                        </section>
                    </div>
                </section>
            </div>

    </div>
@endsection

@section('scripts')
    <!-- Leaflet -->

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const informationButton = document.getElementById('mapInformationButton');
            const headerInformationButton = document.getElementById('headerMapInformationButton');
            const informationOverlay = document.getElementById('mapInformationOverlay');
            const informationPanel = document.getElementById('mapInformationPanel');
            const closeInformation = document.getElementById('closeMapInformation');
            const stage = document.getElementById('mapStage');
            const compactScreen = window.matchMedia('(max-width: 1023px)');
            let informationOpener = informationButton;
            const informationIsOpen = () => informationOverlay.dataset.open === 'true';
            const resizeMap = () => { window.dispatchEvent(new Event('map-dashboard-resize')); };
            function closeMapInformation(restoreFocus = true) {
                informationOverlay.dataset.open = 'false';
                informationOverlay.setAttribute('aria-hidden', 'true');
                informationOverlay.inert = true;
                informationButton.setAttribute('aria-expanded', 'false');
                headerInformationButton?.setAttribute('aria-expanded', 'false');
                document.getElementById('map').inert = false;
                if (stage.dataset.panel === 'information') delete stage.dataset.panel;
                resizeMap();
                if (restoreFocus) informationOpener.focus();
            }
            function openMapInformation(event, focus = true) {
                if (informationIsOpen()) { closeMapInformation(); return; }
                informationOpener = event?.currentTarget || informationButton;
                document.getElementById('sidePanel').classList.add('translate-x-full', 'invisible');
                stage.dataset.panel = 'information';
                informationOverlay.inert = false;
                informationOverlay.setAttribute('aria-hidden', 'false');
                informationOverlay.dataset.open = 'true';
                informationPanel.setAttribute('role', compactScreen.matches ? 'dialog' : 'region');
                informationPanel.setAttribute('aria-modal', String(compactScreen.matches));
                informationButton.setAttribute('aria-expanded', 'true');
                headerInformationButton?.setAttribute('aria-expanded', 'true');
                document.getElementById('map').inert = compactScreen.matches;
                resizeMap(); if (focus) closeInformation.focus();
            }
            informationButton.addEventListener('click', openMapInformation);
            headerInformationButton?.addEventListener('click', openMapInformation);
            closeInformation.addEventListener('click', () => closeMapInformation());
            document.getElementById('mapInformationBackdrop').addEventListener('click', () => closeMapInformation());
            compactScreen.addEventListener('change', () => {
                document.getElementById('map').inert = informationIsOpen() && compactScreen.matches;
                informationPanel.setAttribute('role', compactScreen.matches ? 'dialog' : 'region');
                informationPanel.setAttribute('aria-modal', String(compactScreen.matches));
                resizeMap();
            });
            const tabs = [...document.querySelectorAll('.map-information-tabs [role="tab"]')];
            function activateInformationTab(tab) {
                tabs.forEach(item => {
                    const active = item === tab;
                    item.setAttribute('aria-selected', String(active)); item.tabIndex = active ? 0 : -1;
                    document.getElementById(item.getAttribute('aria-controls')).hidden = !active;
                });
            }
            tabs.forEach((tab, index) => {
                tab.addEventListener('click', () => activateInformationTab(tab));
                tab.addEventListener('keydown', event => {
                    let next;
                    if (event.key === 'ArrowRight') next = tabs[(index + 1) % tabs.length];
                    if (event.key === 'ArrowLeft') next = tabs[(index + tabs.length - 1) % tabs.length];
                    if (event.key === 'Home') next = tabs[0];
                    if (event.key === 'End') next = tabs[tabs.length - 1];
                    if (next) { event.preventDefault(); activateInformationTab(next); next.focus(); }
                });
            });
            document.addEventListener('keydown', event => {
                if (!informationIsOpen() && stage.dataset.panel === 'institution' && event.key === 'Escape') {
                    event.preventDefault(); document.getElementById('closePanel').click(); return;
                }
                if (!informationIsOpen()) return;
                if (event.key === 'Escape') { event.preventDefault(); closeMapInformation(); }
                if (event.key === 'Tab' && compactScreen.matches) {
                    const controls = [...informationPanel.querySelectorAll('button, [tabindex="0"]')].filter(item => item.tabIndex >= 0 && !item.closest('[hidden]'));
                    const first = controls[0], last = controls[controls.length - 1];
                    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
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
                blank: null
            };
            const mapStage = document.getElementById('mapStage');
            let currentBackground = null;
            const backgroundStatus = document.getElementById('mapBackgroundStatus');
            const backgroundChoices = [...document.querySelectorAll('[data-map-background]')];
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
                if (currentBackground) map.removeLayer(currentBackground);
                currentBackground = backgrounds[key];
                if (currentBackground) {
                    currentBackground.setOpacity(mapStage.classList.contains('heatmap-active') ? 0.45 : 1);
                    currentBackground.addTo(map);
                }
                backgroundChoices.forEach(input => { input.checked = input.value === key; });
                backgroundStatus.textContent = key === 'blank' ? 'Plain background; institution markers and boundaries remain visible.' : '';
                try { localStorage.setItem('talastas.map.background', key); } catch {}
            }
            backgroundChoices.forEach(input => input.addEventListener('change', () => selectBackground(input.value)));
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

            mapResizeObserver?.observe(mapStage);
            window.addEventListener('resize', syncMapSize);
            window.addEventListener('map-dashboard-resize', () => { syncMapSize(); setTimeout(syncMapSize, 260); });

            setTimeout(syncMapSize, 0);
            setTimeout(syncMapSize, 150);
            setTimeout(syncMapSize, 500);

            let heatmapLayer = null;
            let institutionFeatures = [];
            let barangayOverlay = null;
            let activeInstitutionBoundary = null;
            // Registry: barangay name → layer, populated after GeoJSON loads
            const barangayLayerMap = {};

            const heatmapBtn = document.getElementById('toggleHeatmap');
            const barangayBtn = document.getElementById('toggleBarangays');
            const barangaySelect = document.getElementById('barangaySelect');
            const metricSelect = document.getElementById('mapMetricSelect');
            const hoverInfo = document.getElementById('barangay-hover-info');
            const legendMetricLabel = document.getElementById('legendMetricLabel');
            const legendMetricShort = document.getElementById('legendMetricShort');
            const heatmapLegendInfo = document.getElementById('heatmapLegendInfo');
            const heatmapLegendTitle = document.getElementById('heatmapLegendTitle');
            const heatmapLegendDescription = document.getElementById('heatmapLegendDescription');
            const heatmapLegendCount = document.getElementById('heatmapLegendCount');
            const heatmapKey = document.getElementById('heatmapKey');

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
            let heatmapVisible = false;
            let activeBarangayLayer = null;

            function metricValue(properties, metricKey = activeMetric) {
                const rawValue = properties?.[metricKey];

                if (rawValue === null || rawValue === undefined || rawValue === '') {
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

            function metricColor(value) {
                if (value === null) {
                    return '#94a3b8';
                }

                const normalized = Math.max(0, Math.min(100, value)) / 100;

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

            function buildHeatData() {
                return institutionFeatures
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

            function updateLegend() {
                const metric = metricDefinitions[activeMetric]
                    || metricDefinitions.gap_score;
                const heatData = buildHeatData();

                legendMetricLabel.textContent =
                    `Institution color — ${metric.label}`;

                legendMetricShort.textContent = metric.shortLabel;
                document.getElementById('mapMetricDetailsTitle').textContent = metric.label;
                const descriptions = [...document.querySelectorAll('#mapBaselineInfo li')];
                const metricIndex = { gap_score: 3, infrastructure_deficit_score: 0, connectivity_divide_score: 1, technological_obsolescence_score: 2 };
                document.getElementById('mapMetricDetailsDescription').textContent = descriptions[metricIndex[activeMetric] ?? 3].textContent;

                heatmapLegendInfo.classList.toggle(
                    'hidden',
                    !heatmapVisible
                );

                heatmapLegendTitle.textContent =
                    `Heatmap — ${metric.label}`;

                heatmapLegendDescription.textContent =
                    'Spatial blend of assessed institution scores. It is not a barangay average; nearby points can combine visually.';

                heatmapLegendCount.textContent = heatData.length > 0
                    ? `${heatData.length} assessed institution point${heatData.length === 1 ? '' : 's'} included`
                    : 'No calculated institution scores are available for this metric.';
                heatmapKey.hidden = !heatmapVisible;
                document.getElementById('heatmapKeyTitle').textContent = heatmapLegendTitle.textContent;
                document.getElementById('heatmapKeyCount').textContent = heatmapLegendCount.textContent;
            }

            let selectedInstitutionMarker = null;
            function updateInstitutionMarkerStyles() {
                if (!window.institutionsLayer) {
                    return;
                }

                window.institutionsLayer.eachLayer((layer) => {
                    if (!(layer instanceof L.Marker)) {
                        return;
                    }

                    const value = metricValue(layer.properties);

                    const symbol = layer.getElement()?.querySelector('.institution-map-symbol');
                    if (symbol) symbol.style.backgroundColor = metricColor(value);

                    layer.getElement()?.classList.toggle('selected-institution-marker', layer === selectedInstitutionMarker);
                });
            }

            function rebuildHeatmap() {
                mapStage.classList.toggle('heatmap-active', heatmapVisible);
                currentBackground?.setOpacity(heatmapVisible ? 0.45 : 1);
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
                selectedInstitutionMarker = null;
                updateInstitutionMarkerStyles();
                closeMapInformation(false);
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
                                hoverInfo.innerHTML =
                                    `<span class="not-italic text-slate-700 font-medium">${name}</span>`;
                                hoverInfo.classList.remove('text-slate-400', 'italic');
                            });

                            layer.on('mouseout', () => {
                                if (layer !== activeBarangayLayer) {
                                    layer.setStyle({
                                        weight: 1.5,
                                        color: '#64748b',
                                        fillOpacity: 0.04
                                    });
                                }
                                hoverInfo.innerHTML = 'Hover over a region for details';
                                hoverInfo.classList.add('text-slate-400', 'italic');
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
                        const selectedMetricValue =
                            metricValue(props);

                        const color =
                            metricColor(selectedMetricValue);


                        return {
                            ...f,
                            properties: {
                                ...props,
                                markerColor: color
                            }
                        };
                    });

                    institutionFeatures = features;
                    document.dispatchEvent(new CustomEvent('map-institutions-loaded', { detail: features }));

                    window.institutionsLayer = L.geoJSON({
                        type: 'FeatureCollection',
                        features
                    }, {
                        pointToLayer: (feature, latlng) => {
                            const props = feature.properties;
                            const iconClass = props.type === 'School' ? 'fa-school'
                                : props.type === 'Barangay Hall' ? 'fa-landmark' : 'fa-building';
                            const marker = L.marker(latlng, {
                                icon: L.divIcon({
                                    className: 'institution-map-marker',
                                    html: `<span class="institution-map-symbol" style="background-color: ${props.markerColor}"><i class="fa-solid ${iconClass}" aria-hidden="true"></i></span>`,
                                    iconSize: [24, 24],
                                    iconAnchor: [12, 12],
                                    popupAnchor: [0, -14]
                                }),
                                keyboard: false,
                                title: `${props.name} (${props.type || 'Other institution'})`,
                                pane: 'institutionMarkers'
                            });
                            marker.properties = props; // Attach properties for later use
                            return marker;
                        }
                    }).addTo(map);

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
                                                title="Formula: [max(Bandwidth Allocation - Measured Download Speed, 0) / Bandwidth Allocation] x 100">i</span>
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
                                console.log('Institution clicked:', props.name);
                                e.originalEvent.stopPropagation();

                                closeMapInformation(false);
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
                            const markerElement = layer.getElement();
                            if (markerElement) {
                                markerElement.setAttribute('tabindex', '0');
                                markerElement.setAttribute('role', 'button');
                                markerElement.setAttribute('aria-label', `Show institution details for ${props.name}`);
                                markerElement.addEventListener('keydown', event => {
                                    if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); layer.fire('click', { originalEvent: event }); }
                                });
                            }
                        }
                    });
                    updateInstitutionMarkerStyles();
                    rebuildHeatmap();

                    console.log('Institutions layer added to map with', features.length, 'features');
                    setTimeout(() => map.invalidateSize(), 200);
                })
                .catch(err => { console.error('Error loading institutions:', err); document.dispatchEvent(new Event('map-institutions-error')); });

            heatmapBtn.addEventListener('click', () => {
                heatmapVisible = !heatmapVisible;
                rebuildHeatmap();
                updateHeatmapButtonState();
            });

            metricSelect?.addEventListener('change', () => {
                activeMetric = metricSelect.value;
                updateInstitutionMarkerStyles();
                rebuildHeatmap();
                updateLegend();
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
                position: 'bottomleft',
                maxWidth: 100,
                metric: true,
                imperial: true
            }).addTo(map);

            if (!compactScreen.matches) openMapInformation(undefined, false);

            // Close side panel
            document.getElementById('closePanel').addEventListener('click', (e) => {
                e.stopPropagation();
                const panel = document.getElementById('sidePanel');
                panel.classList.add('translate-x-full', 'invisible');
                delete stage.dataset.panel;
                resizeMap();
                clearInstitutionBoundary();
                selectedInstitutionMarker?.getElement()?.focus();
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
            width: 24px;
            height: 24px;
            box-sizing: border-box;
            border: 2px solid white;
            border-radius: 6px;
            background: #94a3b8;
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
