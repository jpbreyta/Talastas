import { installLipaNavigation } from './lipa-map-navigation';
import './map-dashboard';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// The map currently renders institutions with circle markers and its drawing
// controls are disabled. Keep the critical GIS entry lean and avoid loading
// marker icon assets, Leaflet Search, Geoman, and the full Turf bundle on the
// initial Home/Map request.
window.L = L;
window.installLipaNavigation = (map, data) => installLipaNavigation(map, L, data);

let heatPluginPromise = null;

function loadHeatPlugin() {
    if (typeof L.heatLayer === 'function') {
        return Promise.resolve();
    }

    if (!heatPluginPromise) {
        heatPluginPromise = import('leaflet.heat').catch((error) => {
            heatPluginPromise = null;
            throw error;
        });
    }

    return heatPluginPromise;
}

// The existing map code calls L.heatLayer synchronously when the Heatmap button
// is clicked. Intercept only the first click, load the small plugin on demand,
// then replay the click so the existing UI behavior remains unchanged.
document.addEventListener('click', async (event) => {
    const heatmapButton = event.target.closest?.('#toggleHeatmap');

    if (!heatmapButton || typeof L.heatLayer === 'function' || event.__talastasHeatReady) {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    heatmapButton.disabled = true;
    heatmapButton.setAttribute('aria-busy', 'true');

    try {
        await loadHeatPlugin();

        const replay = new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
            view: window,
        });

        Object.defineProperty(replay, '__talastasHeatReady', {
            value: true,
        });

        heatmapButton.dispatchEvent(replay);
    } catch (error) {
        console.error('Unable to load heatmap support:', error);
    } finally {
        heatmapButton.disabled = false;
        heatmapButton.removeAttribute('aria-busy');
    }
}, true);

// Warm the optional heatmap chunk when the user signals intent without making it
// part of the initial critical request path.
document.addEventListener('mouseover', (event) => {
    if (event.target.closest?.('#toggleHeatmap')) {
        loadHeatPlugin().catch(() => {});
    }
}, { passive: true });

document.addEventListener('focusin', (event) => {
    if (event.target.closest?.('#toggleHeatmap')) {
        loadHeatPlugin().catch(() => {});
    }
});

document.addEventListener('DOMContentLoaded', () => {
    // Lighthouse flagged the map's barangay select because it had no accessible
    // name. Using aria-label avoids adding visible UI or changing the layout.
    const barangaySelect = document.getElementById('barangaySelect');

    if (barangaySelect && !barangaySelect.getAttribute('aria-label')) {
        barangaySelect.setAttribute('aria-label', 'Select a barangay to focus on the map');
    }
});
