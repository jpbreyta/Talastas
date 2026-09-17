import './bootstrap';

// Keep the global bundle intentionally small. GIS libraries are loaded only on
// map routes through resources/js/gis.js so non-map pages do not download and
// parse Leaflet, Geoman, Turf, and heat-map code.

const debouncedInputIds = new Set([
    'inventoryInstitutionSearch',
    'institutionSearch',
]);

const inputTimers = new WeakMap();

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
    installInstitutionSearch();
    installAnalyticsLoadingState();
    installSubmitLoadingState();
});
