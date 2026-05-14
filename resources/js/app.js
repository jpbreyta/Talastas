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

import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;

L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

// Export for use in inline scripts
window.L = L;
window.turf = turf;
window.leafletGeoman = L.Geoman;
