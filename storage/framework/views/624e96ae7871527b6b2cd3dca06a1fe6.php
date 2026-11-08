
<div class="map-header-controls" role="group" aria-label="Map controls">
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
        <button type="button" id="resetView" class="map-header-button" title="Reset view" aria-label="Reset map view">
            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
        </button>
    </div>
    <div class="map-header-overlays" role="group" aria-label="Map overlays">
        <button type="button" id="toggleHeatmap" class="map-header-button map-ctrl-btn" aria-pressed="false">
            <i class="fa-solid fa-fire-flame-curved" aria-hidden="true"></i>
            Gap Heatmap
        </button>
        <button type="button" id="toggleBarangays" class="map-header-button map-ctrl-btn" aria-pressed="false">
            <i class="fa-solid fa-map" aria-hidden="true"></i>
            Barangays
        </button>
        <button type="button" id="clearDrawings" class="map-header-button map-header-clear">
            <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
            Clear
        </button>
    </div>
</div>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/partials/map-header-controls.blade.php ENDPATH**/ ?>