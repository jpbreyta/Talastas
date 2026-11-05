import './bootstrap';
import { installInstitutionList } from './institution-list';
import { installInstitutionActions } from './institution-actions';
import { installNotifications } from './notifications';
import { installNotificationPage } from './notification-page';

installNotifications();

// Keep the global bundle intentionally small. GIS libraries are loaded only on
// map routes through resources/js/gis.js so non-map pages do not download and
// parse Leaflet, Geoman, Turf, and heat-map code.

const debouncedInputIds = new Set([
    'inventoryInstitutionSearch',
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


function installInstitutionRowNavigation() {
    // Shared by Institution and Inventory rows. The name remains a native link for keyboard access and browser link
    // actions. Only clicks on non-interactive row content use this shortcut.
    document.addEventListener('click', (event) => {
        if (
            !(event.target instanceof Element)
            || event.defaultPrevented
            || event.button !== 0
            || event.ctrlKey
            || event.metaKey
            || event.shiftKey
            || event.altKey
        ) {
            return;
        }

        const row = event.target.closest('[data-institution-show-url], [data-inventory-show-url]');
        if (!row || event.target.closest(
            'a, button, input, select, textarea, label, summary, details, '
            + '[role="button"], [role="link"], [role="menu"], [role="menuitem"], '
            + '[contenteditable]:not([contenteditable="false"]), [data-stop-row-navigation]'
        )) {
            return;
        }

        if (window.getSelection()?.toString().trim()) {
            return;
        }

        window.location.assign(row.dataset.institutionShowUrl || row.dataset.inventoryShowUrl);
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

        // The infrastructure analytics form runs its own busy/progress state.
        // This generic handler would disable the submit button in a microtask
        // that lands before the analytics handler reads `button.disabled`,
        // making it bail out and never issue the request.
        if (form.matches('[data-infrastructure-analytics-form]')) {
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


function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function installNotificationRealtime() {
    const dropdown = document.querySelector('[data-notification-dropdown]');

    if (!(dropdown instanceof HTMLDetailsElement)) {
        return;
    }

    const feedUrl = dropdown.dataset.feedUrl;
    const streamUrl = dropdown.dataset.streamUrl;
    const readAllUrl = dropdown.dataset.readAllUrl;

    const list = dropdown.querySelector('[data-notification-list]');
    const badge = dropdown.querySelector('[data-notification-badge]');
    const unreadCount = dropdown.querySelector('[data-notification-unread-count]');
    const readAllButton = dropdown.querySelector('[data-notification-read-all]');
    const loading = dropdown.querySelector('[data-notification-loading]');

    let source = null;
    let latestSnapshot = null;

    function stopStream() {
        source?.close();
        source = null;
    }

    function buildNotificationButton(notification) {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.notificationItem = 'true';
        button.dataset.readUrl = notification.read_url || '';
        button.dataset.actionUrl = notification.action_url || '';
        button.className = [
            'block w-full border-b border-slate-100 px-4 py-3 text-left transition hover:bg-slate-50',
            notification.read ? 'bg-white' : 'bg-red-50/40',
        ].join(' ');

        const row = document.createElement('div');
        row.className = 'flex items-start gap-3';

        const dot = document.createElement('span');
        dot.className = [
            'mt-1.5 h-2 w-2 flex-shrink-0 rounded-full',
            notification.read ? 'bg-slate-300' : 'bg-red-700',
        ].join(' ');

        const body = document.createElement('span');
        body.className = 'min-w-0 flex-1';

        const title = document.createElement('span');
        title.className = 'block truncate text-sm font-semibold text-slate-900';
        title.textContent = notification.title || 'System notification';

        const message = document.createElement('span');
        message.className = 'mt-0.5 block text-xs leading-5 text-slate-600';
        message.textContent = notification.message || '';

        const time = document.createElement('span');
        time.className = 'mt-1 block text-[11px] text-slate-400';
        time.textContent = notification.created_at_human || '';

        body.append(title, message, time);
        row.append(dot, body);
        button.appendChild(row);

        return button;
    }

    function renderSnapshot(snapshot) {
        if (!snapshot || !Array.isArray(snapshot.notifications)) {
            return;
        }

        latestSnapshot = snapshot;

        const count = Number(snapshot.unread_count || 0);

        if (badge) {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.classList.toggle('hidden', count === 0);
        }

        if (unreadCount) {
            unreadCount.textContent = String(count);
        }

        readAllButton?.classList.toggle('hidden', count === 0);

        document.dispatchEvent(new CustomEvent('talastas:notifications-snapshot', { detail: snapshot }));

        if (!list) {
            return;
        }

        list.replaceChildren();

        if (snapshot.notifications.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'px-4 py-10 text-center';

            const title = document.createElement('p');
            title.className = 'text-sm font-medium text-slate-700';
            title.textContent = 'No notifications yet';

            const text = document.createElement('p');
            text.className = 'mt-1 text-xs text-slate-500';
            text.textContent = 'System updates will appear here automatically.';

            empty.append(title, text);
            list.appendChild(empty);
            return;
        }

        snapshot.notifications.forEach((notification) => {
            list.appendChild(
                buildNotificationButton(notification)
            );
        });
    }

    async function fetchLatest({ showLoading = false } = {}) {
        if (!feedUrl) {
            return;
        }

        loading?.classList.toggle('hidden', !showLoading);

        try {
            const response = await fetch(feedUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                cache: 'no-store',
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Notifications could not be loaded.');
            }

            renderSnapshot(await response.json());
        } catch (error) {
            console.error(error);
        } finally {
            loading?.classList.add('hidden');
        }
    }

    function connectStream() {
        if (
            !dropdown.open
            || !streamUrl
            || typeof EventSource === 'undefined'
            || source
        ) {
            return;
        }

        source = new EventSource(streamUrl);

        source.addEventListener('notifications', (event) => {
            try {
                renderSnapshot(JSON.parse(event.data));
            } catch (error) {
                console.error('Invalid notification stream payload.', error);
            }
        });

        source.addEventListener('error', () => {
            // EventSource reconnects automatically while the dropdown remains open.
            if (!dropdown.open) {
                stopStream();
            }
        });
    }

    async function reopenStreamAfterMutation() {
        if (!dropdown.open) {
            return;
        }

        await fetchLatest();
        connectStream();
    }

    document.addEventListener('talastas:notifications-page-start', stopStream);
    document.addEventListener('talastas:notifications-page-finish', (event) => {
        if (event.detail) renderSnapshot(event.detail);
        void reopenStreamAfterMutation();
    });

    dropdown.addEventListener('toggle', async () => {
        if (!dropdown.open) {
            stopStream();
            return;
        }

        // Always query the latest state at the exact moment the panel opens.
        await fetchLatest({ showLoading: true });
        connectStream();
    });

    list?.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-notification-item]');

        if (!button) {
            return;
        }

        const readUrl = button.dataset.readUrl;
        const actionUrl = button.dataset.actionUrl;

        if (!readUrl) {
            if (actionUrl) {
                window.location.assign(actionUrl);
            }
            return;
        }

        stopStream();
        button.disabled = true;

        try {
            const response = await fetch(readUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Notification could not be marked as read.');
            }

            const payload = await response.json();

            if (payload.notifications) {
                renderSnapshot(payload.notifications);
            }

            if (payload.action_url || actionUrl) {
                window.location.assign(
                    payload.action_url || actionUrl
                );
                return;
            }
        } catch (error) {
            console.error(error);
        } finally {
            button.disabled = false;
        }

        await reopenStreamAfterMutation();
    });

    readAllButton?.addEventListener('click', async () => {
        if (!readAllUrl) {
            return;
        }

        stopStream();
        readAllButton.disabled = true;

        try {
            const response = await fetch(readAllUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Notifications could not be updated.');
            }

            const payload = await response.json();

            if (payload.notifications) {
                renderSnapshot(payload.notifications);
            }
        } catch (error) {
            console.error(error);
        } finally {
            readAllButton.disabled = false;
        }

        await reopenStreamAfterMutation();
    });

    // Keep the server-rendered initial state as the no-JS fallback.
    if (latestSnapshot) {
        renderSnapshot(latestSnapshot);
    }
}

function installMetricInfoTooltips() {
    if (document.body.dataset.metricInfoInstalled === '1') {
        return;
    }

    document.body.dataset.metricInfoInstalled = '1';

    const popover = document.createElement('div');
    popover.id = 'metricInfoPopover';
    popover.className = 'metric-info-popover';
    popover.hidden = true;
    popover.setAttribute('role', 'dialog');
    popover.setAttribute('aria-live', 'polite');
    popover.innerHTML = [
        '<p class="metric-info-popover__title" data-info-popover-title></p>',
        '<p class="metric-info-popover__description" data-info-popover-description></p>',
        '<p class="metric-info-popover__formula"><strong>Formula:</strong> <span data-info-popover-formula></span></p>',
    ].join('');

    document.body.appendChild(popover);

    let activeTrigger = null;

    const closePopover = () => {
        if (activeTrigger) {
            activeTrigger.setAttribute('aria-expanded', 'false');
        }

        activeTrigger = null;
        popover.hidden = true;
    };

    const positionPopover = () => {
        if (!activeTrigger || popover.hidden) {
            return;
        }

        if (!document.documentElement.contains(activeTrigger)) {
            closePopover();
            return;
        }

        const triggerRect = activeTrigger.getBoundingClientRect();
        const popoverRect = popover.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const gap = 10;
        const edge = 12;

        const placements = [
            {
                left: triggerRect.left,
                top: triggerRect.bottom + gap,
            },
            {
                left: triggerRect.right - popoverRect.width,
                top: triggerRect.bottom + gap,
            },
            {
                left: triggerRect.left,
                top: triggerRect.top - popoverRect.height - gap,
            },
            {
                left: triggerRect.right - popoverRect.width,
                top: triggerRect.top - popoverRect.height - gap,
            },
            {
                left: triggerRect.right + gap,
                top: triggerRect.top + (triggerRect.height - popoverRect.height) / 2,
            },
            {
                left: triggerRect.left - popoverRect.width - gap,
                top: triggerRect.top + (triggerRect.height - popoverRect.height) / 2,
            },
        ];

        const selected = placements.find((placement) => (
            placement.left >= edge
            && placement.top >= edge
            && placement.left + popoverRect.width <= viewportWidth - edge
            && placement.top + popoverRect.height <= viewportHeight - edge
        )) || placements[0];

        const left = Math.min(
            Math.max(selected.left, edge),
            Math.max(edge, viewportWidth - popoverRect.width - edge)
        );

        const top = Math.min(
            Math.max(selected.top, edge),
            Math.max(edge, viewportHeight - popoverRect.height - edge)
        );

        popover.style.left = `${Math.round(left)}px`;
        popover.style.top = `${Math.round(top)}px`;
    };

    const openPopover = (trigger) => {
        if (activeTrigger === trigger && !popover.hidden) {
            closePopover();
            return;
        }

        if (activeTrigger) {
            activeTrigger.setAttribute('aria-expanded', 'false');
        }

        activeTrigger = trigger;
        activeTrigger.setAttribute('aria-expanded', 'true');

        popover.querySelector('[data-info-popover-title]').textContent =
            trigger.dataset.infoTitle || 'Metric information';

        popover.querySelector('[data-info-popover-description]').textContent =
            trigger.dataset.infoDescription || '';

        popover.querySelector('[data-info-popover-formula]').textContent =
            trigger.dataset.infoFormula || '';

        popover.hidden = false;
        positionPopover();
    };

    document.addEventListener('click', (event) => {
        const eventTarget =
            event.target instanceof Element
                ? event.target
                : null;

        const trigger =
            eventTarget?.closest('[data-metric-info-trigger]');

        if (trigger instanceof HTMLElement) {
            event.preventDefault();
            event.stopPropagation();
            openPopover(trigger);
            return;
        }

        if (!popover.contains(event.target)) {
            closePopover();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closePopover();
        }
    });

    window.addEventListener('resize', positionPopover);
    window.addEventListener('scroll', positionPopover, true);
}

function applyAnalyticsMetricSnapshot(metrics) {
    if (!metrics || typeof metrics !== 'object') {
        return;
    }

    Object.entries(metrics).forEach(([key, rawValue]) => {
        document
            .querySelectorAll(`[data-analytics-metric="${key}"]`)
            .forEach((container) => {
                const valueNode = container.querySelector('[data-metric-value]');
                const unitNode = container.querySelector('[data-metric-unit]');
                const emptyNode = container.querySelector('[data-metric-empty]');

                const numericValue = Number(rawValue);
                const hasValue =
                    rawValue !== null
                    && rawValue !== ''
                    && Number.isFinite(numericValue);

                if (valueNode) {
                    valueNode.textContent = hasValue
                        ? numericValue.toFixed(1)
                        : '';
                }

                unitNode?.classList.toggle('hidden', !hasValue);
                emptyNode?.classList.toggle('hidden', hasValue);

                container.dataset.metricAvailable = hasValue ? '1' : '0';
            });
    });
}

function installInstitutionAnalyticsRealtime() {
    let analyticsSource = null;
    let pollTimer = null;
    let queueWarningTimer = null;
    let completed = false;
    let analyticsRunning = false;
    let pollFailures = 0;
    let currentAnalyticsStage = null;

    function stopWatchers() {
        analyticsSource?.close();
        analyticsSource = null;

        if (pollTimer) {
            window.clearInterval(pollTimer);
            pollTimer = null;
        }

        if (queueWarningTimer) {
            window.clearTimeout(queueWarningTimer);
            queueWarningTimer = null;
        }
    }

    function processingOverlay() {
        return document.querySelector(
            '#institutionAnalyticsReport [data-analytics-processing]'
        );
    }

    const analyticsStages = [
        'queued',
        'loading_data',
        'python_scoring',
        'ai_prescriptive',
        'saving_results',
    ];

    const analyticsProgress = {
        queued: 8,
        loading_data: 25,
        python_scoring: 50,
        ai_prescriptive: 75,
        saving_results: 92,
        completed: 100,
    };

    function updateProcessingVisuals(stage = 'queued') {
        const overlay = processingOverlay();
        if (!overlay) {
            return;
        }

        const normalizedStage =
            stage === 'completed' || analyticsStages.includes(stage)
                ? stage
                : 'queued';

        const progressBar = overlay.querySelector('[data-analytics-progress-bar]');
        if (progressBar instanceof HTMLElement) {
            progressBar.style.width = (analyticsProgress[normalizedStage] ?? 8) + '%';
        }
    }

    function showProcessing(
        message = 'Processing the latest ICT inventory data...',
        stage = currentAnalyticsStage || 'queued'
    ) {
        const overlay = processingOverlay();
        if (!overlay) {
            return;
        }

        overlay.classList.remove('hidden');
        updateProcessingVisuals(stage);

        const report = document.getElementById(
            'institutionAnalyticsReport'
        );
        report?.setAttribute('aria-busy', 'true');

        const label = overlay.querySelector(
            '[data-analytics-processing-label]'
        );

        if (label) {
            label.textContent = message;
        }

    }

    function hideProcessing() {
        processingOverlay()?.classList.add('hidden');
        document.getElementById('institutionAnalyticsReport')?.setAttribute('aria-busy', 'false');
    }

    function showProcessingError(message) {
        hideProcessing();
        setRunButtonBusy(false);
        window.TalastasToast?.notify('error', message);
    }

    function setRunButtonBusy(busy) {
        analyticsRunning = busy;
        const button = document.getElementById(
            'runInfrastructureAnalyticsButton'
        );

        if (!button) {
            return;
        }

        const inventoryBlocked =
            button.dataset.analyticsInventoryBlocked === '1';

        button.disabled = busy || inventoryBlocked;
        button.setAttribute('aria-busy', busy ? 'true' : 'false');

        if (busy) {
            button.innerHTML = [
                '<i class="fa-solid fa-circle-notch fa-spin motion-reduce:animate-none" aria-hidden="true"></i>',
                '<span>Analyzing?</span>',
            ].join('');
            return;
        }

        button.innerHTML = [
            '<i class="fa-solid fa-wand-magic-sparkles"></i>',
            '<span>Run Infrastructure Analytics</span>',
        ].join('');
    }

    async function refreshInstitutionFragments() {
        const response = await fetch(window.location.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            cache: 'no-store',
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('The updated analytics report could not be loaded.');
        }

        const html = await response.text();
        const documentCopy = new DOMParser().parseFromString(
            html,
            'text/html'
        );

        const currentSummary = document.getElementById(
            'institutionSummaryMetrics'
        );
        const updatedSummary = documentCopy.getElementById(
            'institutionSummaryMetrics'
        );

        if (currentSummary && updatedSummary) {
            currentSummary.replaceWith(updatedSummary);
        }

        const currentReport = document.getElementById(
            'institutionAnalyticsReport'
        );
        const updatedReport = documentCopy.getElementById(
            'institutionAnalyticsReport'
        );

        if (!updatedReport) {
            throw new Error('The updated analytics report could not be loaded. Refresh the page and try again.');
        }

        if (currentReport && updatedReport) {
            currentReport.replaceWith(updatedReport);
        }
    }

    async function finishAnalytics() {
        if (completed) {
            return;
        }

        completed = true;
        stopWatchers();
        currentAnalyticsStage = 'completed';
        showProcessing(
            'Analytics complete. Refreshing the report...',
            'completed'
        );

        try {
            await refreshInstitutionFragments();
            hideProcessing();
        } catch (error) {
            showProcessingError(
                error instanceof Error
                    ? error.message
                    : 'The updated analytics report could not be rendered.'
            );
        } finally {
            setRunButtonBusy(false);
        }
    }

    function handleState(state) {
        if (!state || completed) {
            return;
        }

        pollFailures = 0;
        applyAnalyticsMetricSnapshot(state.metrics);

        currentAnalyticsStage =
            state.stage || state.status || null;

        switch (state.status) {
            case 'queued':
                showProcessing('Waiting to start?');

                if (!queueWarningTimer) {
                    queueWarningTimer = window.setTimeout(() => {
                        if (
                            !completed
                            && currentAnalyticsStage === 'queued'
                        ) {
                            showProcessing(
                                'Still waiting to start. You can continue using this page.'
                            );
                        }
                    }, 8000);
                }
                break;

            case 'processing': {
                if (queueWarningTimer) {
                    window.clearTimeout(queueWarningTimer);
                    queueWarningTimer = null;
                }

                const stageLabels = {
                    loading_data:
                        'Reading institution and inventory data?',
                    python_scoring:
                        'Calculating ICT indicators?',
                    ai_prescriptive:
                        'Preparing recommendations?',
                    saving_results:
                        'Saving the report?',
                };

                showProcessing(
                    stageLabels[state.stage]
                    || 'Processing the latest data?'
                );
                break;
            }

            case 'completed':
                void finishAnalytics();
                break;

            case 'failed':
                completed = true;
                stopWatchers();
                setRunButtonBusy(false);
                showProcessingError(
                    state.error
                    || 'Analytics could not be completed. Please try again.'
                );
                break;

            case 'superseded':
                completed = true;
                stopWatchers();
                void refreshInstitutionFragments()
                    .then(hideProcessing)
                    .catch((error) => showProcessingError(error.message))
                    .finally(() => setRunButtonBusy(false));
                break;

            default:
                showProcessing('Waiting for analytics processing...');
                break;
        }
    }

    function startPolling(stateUrl) {
        if (!stateUrl || pollTimer || completed) {
            return;
        }

        let polling = false;
        const poll = async () => {
            if (polling || completed) return;
            polling = true;
            try {
                const response = await fetch(stateUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    cache: 'no-store',
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Analytics progress could not be checked.');
                }

                const payload = await response.json();
                handleState(payload.state);
            } catch {
                // Allow transient reconnects, but never leave the UI stuck indefinitely.
                if (!completed && ++pollFailures >= 3) {
                    handleState({ status: 'failed', error: 'Unable to check analytics progress. Refresh the page to reconnect.' });
                }
            } finally {
                polling = false;
            }
        };

        void poll();
        pollTimer = window.setInterval(poll, 2500);
    }

    function watchAnalytics(streamUrl, stateUrl) {
        completed = false;
        pollFailures = 0;
        stopWatchers();

        if (
            streamUrl
            && typeof EventSource !== 'undefined'
        ) {
            analyticsSource = new EventSource(streamUrl);

            analyticsSource.addEventListener(
                'analytics.status',
                (event) => {
                    try {
                        handleState(JSON.parse(event.data));
                    } catch (error) {
                        console.error(
                            'Invalid analytics stream payload.',
                            error
                        );
                    }
                }
            );

            analyticsSource.addEventListener('error', () => {
                if (!completed) {
                    startPolling(stateUrl);
                }
            });
        } else {
            startPolling(stateUrl);
        }
    }

    document.addEventListener('submit', async (event) => {
        const form = event.target;

        if (
            !(form instanceof HTMLFormElement)
            || !form.matches(
                '[data-infrastructure-analytics-form]'
            )
        ) {
            return;
        }

        event.preventDefault();

        const button = form.querySelector(
            'button[type="submit"]'
        );

        if (analyticsRunning || !button || button.disabled) return;

        completed = false;
        currentAnalyticsStage = 'queued';
        setRunButtonBusy(true);
        showProcessing(
            'Starting analytics?',
            'queued'
        );

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const payload = await response.json().catch(
                () => ({})
            );

            if (!response.ok) {
                throw new Error(
                    payload.message
                    || 'Analytics could not be started.'
                );
            }

            if (!payload.stream_url && !payload.state_url) {
                throw new Error('Analytics progress is unavailable. Refresh the page to reconnect.');
            }

            watchAnalytics(
                payload.stream_url,
                payload.state_url
            );
        } catch (error) {
            completed = true;
            stopWatchers();
            setRunButtonBusy(false);
            showProcessingError(
                error instanceof Error
                    ? error.message
                    : 'Analytics could not be started.'
            );
        }
    });

    const activeReport = document.getElementById(
        'institutionAnalyticsReport'
    );

    if (
        activeReport?.dataset.analyticsActive === '1'
        && activeReport.dataset.analyticsStateUrl
    ) {
        completed = false;
        currentAnalyticsStage =
            activeReport.dataset.analyticsStage || 'queued';
        setRunButtonBusy(true);
        showProcessing(
            'Reconnecting to the active run?',
            currentAnalyticsStage
        );

        watchAnalytics(
            activeReport.dataset.analyticsStreamUrl,
            activeReport.dataset.analyticsStateUrl
        );
    }
}

function installSidebarNavigation() {
    const sidebar = document.getElementById('talastasSidebar');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    const content = document.getElementById('app');
    if (!sidebar || !toggle || !backdrop || !content) {
        return;
    }

    const desktop = window.matchMedia('(min-width: 1024px)');
    let previousOverflow = null;

    function setOpen(open, restoreFocus = true) {
        const drawerOpen = open && !desktop.matches;
        const wasOpen = sidebar.hasAttribute('data-open');
        sidebar.toggleAttribute('data-open', drawerOpen);
        sidebar.inert = !desktop.matches && !drawerOpen;
        content.inert = drawerOpen;
        backdrop.hidden = !drawerOpen;
        toggle.setAttribute('aria-expanded', String(drawerOpen));
        if (drawerOpen) {
            sidebar.setAttribute('role', 'dialog');
            sidebar.setAttribute('aria-modal', 'true');
            if (previousOverflow === null) {
                previousOverflow = document.body.style.overflow;
            }
            document.body.style.overflow = 'hidden';
            sidebar.querySelector('[data-sidebar-close]')?.focus();
        } else {
            sidebar.removeAttribute('role');
            sidebar.removeAttribute('aria-modal');
            if (previousOverflow !== null) {
                document.body.style.overflow = previousOverflow;
                previousOverflow = null;
            }
            if (wasOpen && restoreFocus && !desktop.matches) {
                toggle.focus();
            }
        }
    }

    toggle.addEventListener('click', () => setOpen(true));
    backdrop.addEventListener('click', () => setOpen(false));
    sidebar.querySelector('[data-sidebar-close]')?.addEventListener('click', () => setOpen(false));
    sidebar.addEventListener('click', (event) => {
        if (event.target.closest('a[href]')) {
            setOpen(false);
        }
    });
    sidebar.addEventListener('keydown', (event) => {
        if (!sidebar.hasAttribute('data-open')) {
            return;
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            setOpen(false);
        } else if (event.key === 'Tab') {
            const focusable = Array.from(sidebar.querySelectorAll('a[href], button, summary'))
                .filter((element) => element.getClientRects().length > 0);
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        }
    });
    desktop.addEventListener('change', () => {
        const focusWasInside = sidebar.contains(document.activeElement);
        setOpen(false);
        if (focusWasInside) {
            (desktop.matches ? sidebar.querySelector('a[aria-current], nav a') : toggle)?.focus();
        }
    });
    window.addEventListener('pageshow', () => setOpen(false));
    setOpen(false, false);
}

document.addEventListener('DOMContentLoaded', () => {
    installSidebarNavigation();
    createMapLoadingOverlay();
    installInstitutionList();
    installInstitutionActions();
    installInstitutionRowNavigation();
    installAnalyticsLoadingState();
    installSubmitLoadingState();
    installNotificationRealtime();
    installNotificationPage();
    installMetricInfoTooltips();
    installInstitutionAnalyticsRealtime();
});
