import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync('resources/views/map.blade.php', 'utf8');
const slice = (start, end) => source.slice(source.indexOf(start), source.indexOf(end, source.indexOf(start)));
let checks = 0;
const check = (condition, message) => { assert.ok(condition, message); checks++; };
class Marker {
    constructor(properties) {
        this.feature = { properties };
        this.options = { icon: { options: {} } };
        this.element = { querySelector: () => ({ style: {} }), classList: { toggle() {} } };
    }
    getElement() { return this.element; }
}
const members = new Set();
const group = {
    hasLayer: marker => members.has(marker),
    removeLayers: layers => layers.forEach(marker => members.delete(marker)),
    addLayers: layers => layers.forEach(marker => members.add(marker)),
};
const markers = [
    new Marker({ district: 'I', type: 'School', gap_score: 0, connectivity_divide_score: 25 }),
    new Marker({ district: 'II', type: 'Barangay Hall', gap_score: 80, connectivity_divide_score: null }),
    new Marker({ barangay: 'Fallback', type: 'School', gap_score: 50, connectivity_divide_score: 50 }),
    new Marker({ district: 'I', type: 'School', gap_score: null, connectivity_divide_score: 75 }),
];
let cleared = 0;
const panel = { classList: { add() {} } };
const context = vm.createContext({
    L: { Marker }, window: { institutionsLayer: group }, institutionLayers: markers,
    institutionFeatures: markers.map(marker => ({ properties: { ...marker.feature.properties, latitude: 13.94, longitude: 121.16 } })),
    activeDistrict: '', activeEstablishment: 'all', activeMetric: 'gap_score',
    barangayDistricts: new Map([['fallback', 'I']]),
    normalizeDistrict: value => String(value ?? '').trim().toUpperCase(),
    normalizeBarangay: value => String(value ?? '').trim().toLowerCase(),
    selectedInstitutionMarker: null, stage: { dataset: {} },
    document: { getElementById: () => panel }, resizeMap() {},
    clearInstitutionBoundary() { cleared++; },
    getGapColor: value => `score-${value}`, institutionSymbol: (_, color) => color,
    institutionIconClass: () => 'school',
});
vm.runInContext([
    slice('            function metricValue(', '            function interpolateColor('),
    slice('            function districtOf(', '            function refreshDistrictOptions('),
    slice('            function buildHeatData(', '            const legendControl'),
    slice('            function updateInstitutionMarkerStyles(', '            function rebuildHeatmap('),
].join('\n'), context);
const update = () => vm.runInContext('updateInstitutionMarkerStyles()', context);
update();
check(members.size === 3 && members.has(markers[0]), 'Zero scores stay visible; missing selected metrics are excluded');
for (let i = 0; i < 10; i++) update();
check(members.size === 3, 'Repeated updates do not duplicate markers');
context.activeDistrict = 'I';
update();
check(members.size === 2 && members.has(markers[2]), 'District filtering supports the barangay district fallback');
context.activeEstablishment = 'Barangay Hall';
update();
check(members.size === 0, 'Combined filters may produce an empty cluster group');
context.activeDistrict = '';
update();
check(members.size === 1 && members.has(markers[1]), 'Changing filters restores the original cached marker');
context.selectedInstitutionMarker = markers[1];
context.activeEstablishment = 'School';
update();
check(context.selectedInstitutionMarker === null && cleared === 1, 'Filtering out the selection clears its boundary and details');
context.activeEstablishment = 'all';
context.activeMetric = 'connectivity_divide_score';
update();
check(members.size === 3 && !members.has(markers[1]) && members.has(markers[3]), 'Metric changes update cluster membership for available data');
check(vm.runInContext('buildHeatData().length', context) === members.size, 'Heatmap and cluster membership use the same filters');
// A refreshed cache replaces old membership on the next synchronization.
group.removeLayers([...members]);
context.institutionLayers = [new Marker({ district: 'I', type: 'School', connectivity_divide_score: 0 })];
update();
check(members.size === 1 && members.has(context.institutionLayers[0]), 'Refreshed marker cache contains no stale members');
for (const script of source.matchAll(/<script[^>]*>([\s\S]*?)<\/script>/g)) {
    new vm.Script(script[1].replace(/@json\(route\('[^']+'\)\)/g, '"/fixture"'));
}
check(true, 'Dashboard inline JavaScript parses');
console.log(`Map UI: ${checks} checks passed.`);
