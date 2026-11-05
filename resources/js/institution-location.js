import { mountInstitutionGps } from './institution-gps';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import '@geoman-io/leaflet-geoman-free';
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css';

// Institution create/edit screens need the same Leaflet instance for their
// inline location picker and optional boundary editing tools.
window.L = L;

window.mountInstitutionGps = mountInstitutionGps;
