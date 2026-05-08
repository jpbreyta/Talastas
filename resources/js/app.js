import './bootstrap';

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.heat'; // Heat layer plugin

// Geoman drawing tools
import '@geoman-io/leaflet-geoman-free';
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css';

// Turf.js for spatial analysis
import * as turf from '@turf/turf';

// Leaflet Search plugin
import 'leaflet-search';

// Fix for default marker icons not showing in build
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: '/images/vendor/leaflet/dist/marker-icon-2x.png',
    iconUrl: '/images/vendor/leaflet/dist/marker-icon.png',
    shadowUrl: '/images/vendor/leaflet/dist/marker-shadow.png',
});

// Export for use in inline scripts
window.L = L;
window.turf = turf;
window.leafletGeoman = L.Geoman;
