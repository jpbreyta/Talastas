import './bootstrap';

// Keep the global bundle intentionally small. GIS libraries are loaded only on
// map routes through resources/js/gis.js so non-map pages do not download and
// parse Leaflet, Geoman, Turf, and heat-map code.

const debouncedInputIds = new Set([
    'inventoryInstitutionSearch',
    'institutionSearch',
]);

const inputTimers = new WeakMap();

function createMapLoadingOverlay() {
    if (!document.getElementById('map') || document.getElementById('talastasMapLoading')) {
        return;
    }

    const mapContainer = document.getElementById('map')?.parentElement;
    if (!mapContainer) {
        return;
    }

    const overlay = document.createElement('div');
    overlay.id = 'talastasMapLoading';
    overlay.setAttribute('role', 'status');
    overlay.setAttribute('aria-live', 'polite');
    overlay.className = 'absolute inset-0 z-[1400] flex items-center justify-center bg-slate-100/95 transition-opacity duration-300';
    overlay.innerHTML = `
        <div class="w-full max-w-3xl px-6">
            <div class="mb-5 flex items-center justify-center gap-3 text-sm font-medium text-slate-600">
                <i class="fa-solid fa-circle-notch fa-spin text-red-700" aria-hidden="true"></i>
                <span data-map-loading-label>Loading map data...</span>
            </div>

            <div class="animate-pulse space-y-4 motion-reduce:animate-none">
                <div class="mx-auto h-10 max-w-sm rounded-lg bg-slate-200"></div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="h-16 rounded-xl bg-slate-200"></div>
                    <div class="h-16 rounded-xl bg-slate-200"></div>
                    <div class="h-16 rounded-xl bg-slate-200"></div>
                </div>
                <div class="h-72 rounded-2xl border border-slate-200 bg-slate-200"></div>
            </div>
        </div>
    `;

    mapContainer.appendChild(overlay);
}

function finishMapLoading() {
    const overlay = document.getElementById('talastasMapLoading');
    if (!overlay) {
        return;
    }

    overlay.classList.add('opacity-0', 'pointer-events-none');
    window.setTimeout(() => overlay.remove(), 320);
}

function showMapLoadingError() {
    createMapLoadingOverlay();

    const overlay = document.getElementById('talastasMapLoading');
    if (!overlay) {
        return;
    }

    overlay.classList.remove('opacity-0', 'pointer-events-none');
    overlay.innerHTML = `
        <div class="mx-auto max-w-md rounded-2xl border border-red-200 bg-white p-6 text-center shadow-sm">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            </span>
            <h2 class="mt-3 text-sm font-semibold text-slate-900">Map data could not be loaded</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Check your connection and try refreshing the page.</p>
            <button type="button" data-map-retry class="mt-4 inline-flex items-center gap-2 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-800">
                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                Retry
            </button>
        </div>
    `;

    overlay.querySelector('[data-map-retry]')?.addEventListener('click', () => {
        window.location.reload();
    });
}

// Track the two map data requests so the skeleton stays visible until both are
// finished. This works with the existing inline map code without duplicating it.
if (typeof window.fetch === 'function') {
    const nativeFetch = window.fetch.bind(window);
    const requiredMapRequests = new Set([
        '/data/lipa-city-barangays.geojson',
        '/map/data/institutions',
    ]);
    const completedMapRequests = new Set();
    let mapRequestFailed = false;

    window.fetch = async (...args) => {
        const rawUrl = args[0] instanceof Request ? args[0].url : String(args[0]);
        let pathname = '';

        try {
            pathname = new URL(rawUrl, window.location.origin).pathname;
        } catch {
            pathname = rawUrl;
        }

        const isMapRequest = requiredMapRequests.has(pathname);

        if (isMapRequest) {
            createMapLoadingOverlay();
        }

        try {
            const response = await nativeFetch(...args);

            if (isMapRequest) {
                if (!response.ok) {
                    mapRequestFailed = true;
                    showMapLoadingError();
                } else {
                    completedMapRequests.add(pathname);

                    if (!mapRequestFailed && completedMapRequests.size === requiredMapRequests.size) {
                        finishMapLoading();
                    }
                }
            }

            return response;
        } catch (error) {
            if (isMapRequest) {
                mapRequestFailed = true;
                showMapLoadingError();
            }

            throw error;
        }
    };
}

// Debounce high-frequency client-side search handlers without coupling the
// shared bundle to page-specific filtering implementations. The synthetic event
// is marked so it can pass through to the page's existing input listener once.
document.addEventListener('input', (event) => {
    const input = event.target;

    if (!(input instanceof HTMLInputElement)) {
        return;
    }

    if (!debouncedInputIds.has(input.id) || event.__talastasDebounced) {
        return;
    }

    event.stopImmediatePropagation();

    const previousTimer = inputTimers.get(input);
    if (previousTimer) {
        window.clearTimeout(previousTimer);
    }

    const timer = window.setTimeout(() => {
        const debouncedEvent = new Event('input', {
            bubbles: true,
            cancelable: false,
        });

        Object.defineProperty(debouncedEvent, '__talastasDebounced', {
            value: true,
        });

        input.dispatchEvent(debouncedEvent);
        inputTimers.delete(input);
    }, 250);

    inputTimers.set(input, timer);
}, true);

function installInstitutionSearch() {
    const searchInput = document.getElementById('institutionSearch');
    const rows = Array.from(document.querySelectorAll('.inst-row'));
    const noResults = document.getElementById('noResults');

    if (!searchInput || rows.length === 0) {
        return;
    }

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        rows.forEach((row) => {
            const visible = query === '' || row.textContent.toLowerCase().includes(query);
            row.classList.toggle('hidden', !visible);

            if (visible) {
                visibleCount++;
            }
        });

        noResults?.classList.toggle('hidden', visibleCount > 0);
    });
}

function createAnalyticsSkeleton() {
    if (document.getElementById('talastasAnalyticsLoading')) {
        return;
    }

    const overlay = document.createElement('div');
    overlay.id = 'talastasAnalyticsLoading';
    overlay.setAttribute('role', 'status');
    overlay.setAttribute('aria-live', 'polite');
    overlay.className = 'fixed inset-x-0 bottom-0 top-16 z-[4900] overflow-hidden bg-slate-50/95 backdrop-blur-[1px]';
    overlay.innerHTML = `
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center gap-3 text-sm font-medium text-slate-600">
                <i class="fa-solid fa-circle-notch fa-spin text-red-700" aria-hidden="true"></i>
                <span>Updating analytics...</span>
            </div>

            <div class="animate-pulse space-y-6 motion-reduce:animate-none">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="h-40 rounded-xl bg-slate-200"></div>
                    <div class="h-40 rounded-xl bg-slate-200"></div>
                    <div class="h-40 rounded-xl bg-slate-200"></div>
                </div>

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <div class="h-72 rounded-xl bg-slate-200"></div>
                    <div class="h-72 rounded-xl bg-slate-200"></div>
                </div>

                <div class="h-80 rounded-xl bg-slate-200"></div>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);
}

function installAnalyticsLoadingState() {
    if (window.location.pathname !== '/analytics') {
        return;
    }

    document.querySelectorAll('form[method="GET"], form[method="get"]').forEach((form) => {
        form.addEventListener('submit', () => {
            createAnalyticsSkeleton();
        });
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link) {
            return;
        }

        let destination;
        try {
            destination = new URL(link.href, window.location.href);
        } catch {
            return;
        }

        if (
            destination.origin === window.location.origin
            && destination.pathname === '/analytics'
            && !link.target
            && !event.ctrlKey
            && !event.metaKey
            && !event.shiftKey
            && !event.altKey
        ) {
            createAnalyticsSkeleton();
        }
    });
}

function installSubmitLoadingState() {
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        queueMicrotask(() => {
            if (event.defaultPrevented) {
                return;
            }

            const submitters = form.querySelectorAll('button[type="submit"], input[type="submit"]');

            submitters.forEach((submitter) => {
                submitter.disabled = true;
                submitter.setAttribute('aria-busy', 'true');
                submitter.classList.add('cursor-wait', 'opacity-70');

                if (submitter instanceof HTMLButtonElement && !submitter.querySelector('[data-submit-spinner]')) {
                    const spinner = document.createElement('i');
                    spinner.dataset.submitSpinner = 'true';
                    spinner.className = 'fa-solid fa-circle-notch fa-spin mr-2';
                    spinner.setAttribute('aria-hidden', 'true');
                    submitter.prepend(spinner);
                }
            });
        });
    });

    // Browsers can restore a page from the back-forward cache with controls in
    // their previous disabled state. Re-enable them when that happens.
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[aria-busy="true"]').forEach((submitter) => {
            submitter.disabled = false;
            submitter.removeAttribute('aria-busy');
            submitter.classList.remove('cursor-wait', 'opacity-70');
            submitter.querySelector?.('[data-submit-spinner]')?.remove();
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    createMapLoadingOverlay();
    installInstitutionSearch();
    installAnalyticsLoadingState();
    installSubmitLoadingState();
});
