// Leaflet maxBounds is rectangular. Retain the real polygon union as well.
export function containsCityPoint(point, features) {
    function inRing(ring) {
        let inside = false;
        for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
            const [x, y] = ring[i], [px, py] = ring[j];
            if ((y > point.lat) !== (py > point.lat)
                && point.lng < (px - x) * (point.lat - y) / (py - y) + x) inside = !inside;
        }
        return inside;
    }
    return features.some(({ geometry }) => {
        const polygons = geometry?.type === 'Polygon' ? [geometry.coordinates]
            : geometry?.type === 'MultiPolygon' ? geometry.coordinates : [];
        return polygons.some(rings => inRing(rings[0]) && !rings.slice(1).some(inRing));
    });
}

export function installLipaNavigation(map, L, data) {
    const features = data.features.filter(feature => feature.geometry);
    const bounds = L.geoJSON(data).getBounds();
    if (!bounds.isValid() || !features.length) throw new Error('Lipa boundary is empty');
    // Match Leaflet's projected bounds center, including on tall mobile viewports.
    const center = map.unproject(map.project(bounds.getSouthWest(), 0)
        .add(map.project(bounds.getNorthEast(), 0)).divideBy(2), 0);
    if (!containsCityPoint(center, features)) {
        throw new Error('Lipa boundary center must lie within the city');
    }
    let lastInside = center;
    let enforcing = false;
    let minimum = 0;
    let atOverview = true;

    function constrain() {
        if (enforcing) return;
        enforcing = true;
        try {
            const current = map.getCenter();
            let target = current;
            if (map.getZoom() <= minimum) {
                target = center;
                map.dragging.disable();
            } else {
                map.dragging.enable();
                if (!containsCityPoint(current, features)) {
                    // Stop at the exact polygon crossing from the previous valid center.
                    let lo = 0, hi = 1;
                    for (let i = 0; i < 32; i++) {
                        const t = (lo + hi) / 2;
                        const candidate = L.latLng(lastInside.lat + (current.lat - lastInside.lat) * t,
                            lastInside.lng + (current.lng - lastInside.lng) * t);
                        if (containsCityPoint(candidate, features)) lo = t;
                        else hi = t;
                    }
                    // Stay two screen pixels inside the crossing: Leaflet rounds pan offsets.
                    const pixels = map.project(lastInside).distanceTo(map.project(current));
                    const t = Math.max(0, lo - 2 / Math.max(pixels, 1));
                    target = L.latLng(lastInside.lat + (current.lat - lastInside.lat) * t,
                        lastInside.lng + (current.lng - lastInside.lng) * t);
                }
            }
            if (!current.equals(target, 1e-9)) map.panTo(target, { animate: false });
            const actual = map.getCenter();
            if (containsCityPoint(actual, features)) lastInside = actual;
            atOverview = map.getZoom() <= minimum;
        } finally {
            enforcing = false;
        }
    }

    function updateSize() {
        const overview = atOverview;
        // Fit the entire city with a small margin. Clear the previous minimum
        // first because Leaflet getBoundsZoom clamps to that minimum on resize.
        map.setMinZoom(0);
        minimum = map.getBoundsZoom(bounds, false, L.point(40, 40));
        map.setMinZoom(minimum);
        if (overview) map.setView(center, minimum, { animate: false });
        constrain();
    }

    // Disable elastic overscroll, inertia and pinch overshoot; preserve detailed zoom.
    map.options.maxBoundsViscosity = 1;
    map.options.inertia = false;
    map.options.bounceAtZoomLimits = false;
    map.setMaxBounds(bounds);
    updateSize();
    map.on('move zoomend moveend', constrain);
    map.on('resize', updateSize);
    for (const handler of ['touchZoom', 'scrollWheelZoom', 'doubleClickZoom', 'boxZoom', 'keyboard']) {
        map[handler].enable();
    }
    return {
        reset() { map.stop(); map.setView(center, minimum, { animate: false }); constrain(); },
    };
}
