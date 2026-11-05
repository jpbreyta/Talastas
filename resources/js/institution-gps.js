export function validPosition(coords) {
    return Number.isFinite(coords.latitude) && Math.abs(coords.latitude) <= 90
        && Number.isFinite(coords.longitude) && Math.abs(coords.longitude) <= 180
        && Number.isFinite(coords.accuracy) && coords.accuracy >= 0;
}

export function mountInstitutionGps(form, apply, getMap) {
    const root = form.querySelector('[data-gps-controls]');
    const field = name => root.querySelector(`[name="${name}"]`);
    const button = root.querySelector('[data-gps-button]');
    const message = root.querySelector('[data-gps-message]');
    const confirm = root.querySelector('[data-gps-confirm]');
    const review = root.querySelector('[data-gps-review]');
    let circle, pending = false, generation = 0;
    const statusIcon = root.querySelector('[data-gps-status-icon]');
    const captureIcon = button.querySelector?.('i');
    const setMessage = (text, state = 'error') => {
        message.textContent = text;
        root.dataset.gpsState = text ? state : 'idle';
        if (statusIcon) statusIcon.className = 'fa-solid ' + (state === 'loading'
            ? 'fa-spinner fa-spin' : state === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation');
    };
    const options = root.closest?.('[data-location-options]');
    const manualPanel = form.querySelector('#manualCoordinatePanel');
    const manualToggle = form.querySelector('#toggleManualCoordinates');
    if (options && manualPanel) {
        const syncOptions = () => {
            const expanded = !manualPanel.classList.contains('hidden');
            manualToggle?.setAttribute('aria-expanded', String(expanded));
            if (expanded) options.open = true;
        };
        new MutationObserver(syncOptions).observe(manualPanel, { attributes: true, attributeFilter: ['class'] });
        syncOptions();
    }

    const source = value => {
        generation++;
        field('location_source').value = value;
        root.querySelector('[data-location-source-label]').textContent = value === 'device_gps' ? 'Device GPS' : value === 'nominatim' ? 'Map search' : 'Entered coordinates';
        confirm.checked = false;
        field('gps_verified').value = '0';
    };
    source(field('location_source').value);
    confirm.addEventListener('change', () => field('gps_verified').value = confirm.checked ? '1' : '0');
    button.addEventListener('click', () => {
        if (pending) return;
        if (!window.isSecureContext) { setMessage('Location requires HTTPS or localhost. You can enter coordinates manually.', 'error'); return; }
        if (!navigator.geolocation) { setMessage('Browser location is unavailable. You can enter coordinates manually.', 'error'); return; }
        pending = true;
        button.disabled = true;
        button.setAttribute?.('aria-busy', 'true');
        if (captureIcon) captureIcon.className = 'fa-solid fa-spinner fa-spin';
        const request = ++generation;
        setMessage('Requesting device location...', 'loading');
        const finish = () => {
            pending = false;
            button.disabled = false;
            button.setAttribute?.('aria-busy', 'false');
            if (captureIcon) captureIcon.className = 'fa-solid fa-location-crosshairs';
        };
        navigator.geolocation.getCurrentPosition(async position => {
            try {
                if (request !== generation) return;
                if (!validPosition(position.coords)) { setMessage('The device returned an invalid location or accuracy. Try again or enter coordinates manually.', 'error'); return; }
                const { latitude, longitude, accuracy } = position.coords;
                setMessage('Checking whether your location is within Lipa City...', 'loading');
                const response = await fetch(root.dataset.gpsResolveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ latitude, longitude }),
                });
                if (request !== generation) return;
                if (!response.ok) {
                    setMessage(response.status === 422
                        ? 'Your GPS location is outside the mapped Lipa City boundaries. Only locations within Lipa City are allowed.'
                        : 'Unable to verify the Lipa City boundary. Please try again.', 'error');
                    return;
                }
                source('device_gps');
                field('actor_latitude').value = latitude;
                field('actor_longitude').value = longitude;
                field('gps_accuracy').value = accuracy;
                review.classList.remove('hidden');
                setMessage(`Device: ${latitude.toFixed(7)}, ${longitude.toFixed(7)}. Reported accuracy: approximately ${accuracy} meters. This is an estimate, not an exact building coordinate. Verify and correct the pin before saving.`, 'loading');
                const appliedGeneration = generation;
                const accepted = await apply(latitude, longitude, () => appliedGeneration === generation);
                if (appliedGeneration !== generation) return;
                if (!accepted) {
                    source('manual');
                    setMessage(message.textContent + ' The point was not accepted. Correct the coordinates and apply them manually.', 'warning');
                    return;
                }
                setMessage(message.textContent, 'success');
                if (circle) circle.remove();
                circle = window.L.circle([latitude, longitude], { radius: accuracy, weight: 1, fillOpacity: 0.1 }).addTo(getMap());
                getMap().fitBounds(circle.getBounds(), { maxZoom: 18 });
            } catch (error) {
                setMessage('Unable to apply this location. Try again or enter coordinates manually.', 'error');
            } finally { finish(); }
        }, error => {
            if (request === generation) setMessage(error.code === 1
                ? 'Location permission was denied. You can enter coordinates manually.'
                : error.code === 3 ? 'Location request timed out. Try again or enter coordinates manually.'
                : 'Unable to determine your location. Try again or enter coordinates manually.', 'error');
            finish();
        }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    });
    return {
        source,
        reset() {
            source('manual');
            for (const name of ['actor_latitude', 'actor_longitude', 'gps_accuracy']) field(name).value = '';
            review.classList.add('hidden');
            setMessage('', 'idle');
            if (circle) { circle.remove(); circle = null; }
        },
        canSubmit() {
            if (pending || (field('actor_latitude').value && !confirm.checked)) {
                setMessage(message.textContent + ' Please wait for capture to finish and verify the pin before saving.', pending ? 'loading' : 'warning');
                review.scrollIntoView({ block: 'center' });
                return false;
            }
            return true;
        }
    };
}
