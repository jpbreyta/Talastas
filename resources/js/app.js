import './bootstrap';

// Keep the global bundle intentionally small. GIS libraries are loaded only on
// map routes through resources/js/gis.js so non-map pages do not download and
// parse Leaflet, Geoman, Turf, and heat-map code.
