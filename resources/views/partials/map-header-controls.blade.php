{{-- One set of controls: inline on wide desktops, wrapped below on smaller screens. --}}
<div class="map-header-controls" role="group" aria-label="Map controls">
    <details id="mapFiltersMenu" class="map-filters-menu">
        <summary class="map-header-button cursor-pointer list-none">Map Filters <i class="fa-solid fa-sliders ml-2" aria-hidden="true"></i></summary>
        <div class="map-filters-popover absolute left-0 top-full mt-2 grid grid-cols-1 items-end gap-4 rounded-xl border border-slate-200 bg-white p-4 text-slate-700 shadow-xl sm:grid-cols-2 lg:grid-cols-[repeat(4,minmax(0,1fr))_auto]">
            <div class="map-header-metric">
                <label for="mapDistrictSelect">District</label>
                <select id="mapDistrictSelect" class="map-header-centered-select rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <option value="">All districts</option>
                </select>
            </div>
            <div class="map-header-metric">
                <label for="mapEstablishmentSelect">Establishment</label>
                <select id="mapEstablishmentSelect" class="map-header-centered-select rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <option value="all">All</option>
                    <option value="School">Schools Only</option>
                    <option value="Barangay Hall">Barangay Halls Only</option>
                </select>
            </div>
            <div class="map-header-metric">
                <label for="mapMetricSelect">Color by</label>
                <select id="mapMetricSelect" class="map-header-centered-select">
                    <option value="gap_score">Overall ICT Gap</option>
                    <option value="infrastructure_deficit_score">Infrastructure Deficit</option>
                    <option value="connectivity_divide_score">Connectivity Divide</option>
                    <option value="technological_obsolescence_score">Technological Obsolescence Proxy</option>
                </select>
            </div>
            <div class="map-header-barangay">
                <label for="barangaySelect">Barangay</label>
                <select id="barangaySelect" class="map-header-centered-select" aria-label="Select a barangay to focus on the map">
                    <option value="">Select</option>
                </select>

            </div>
            <button type="button" id="resetView" class="map-header-button h-10 gap-2 self-end whitespace-nowrap" title="Reset map view" aria-label="Reset map view">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                <span>Reset view</span>
            </button>
        </div>
    </details>
    <div class="map-header-overlays" role="group" aria-label="Map overlays">
        <button type="button" id="toggleHeatmap" class="map-header-button map-ctrl-btn" aria-pressed="false">
            <i class="fa-solid fa-fire-flame-curved" aria-hidden="true"></i>
            Gap Heatmap
        </button>
        <button type="button" id="toggleBarangays" class="map-header-button map-ctrl-btn" aria-pressed="false">
            <i class="fa-solid fa-map" aria-hidden="true"></i>
            Barangays
        </button>

    </div>
</div>
