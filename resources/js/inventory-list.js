export function inventoryListUrl(form, currentUrl) {
    const url = new URL(currentUrl);
    for (const key of ['search', 'status', 'sort', 'page']) url.searchParams.delete(key);
    for (const [key, value] of new FormData(form)) {
        if (String(value).trim()) url.searchParams.set(key, String(value).trim());
    }
    return url;
}

export function readInventoryFragment(response) {
    if (response.redirected) throw new Error('Please sign in again.');
    if (!response.ok) {
        const errors = {
            401: 'Please sign in again.',
            403: 'You cannot access this inventory.',
            419: 'Your session expired. Refresh this page.',
            422: 'The search or filters could not be validated.',
        };
        throw new Error(errors[response.status] || 'Inventory could not be loaded. Please try again.');
    }
    if (!(response.headers.get('content-type') || '').includes('text/html')) {
        throw new Error('An unexpected response was received.');
    }

    return response.text().then((html) => {
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        const root = parsed.querySelector('[data-inventory-results]');
        if (!root || parsed.querySelector('[data-inventory-list]')) {
            throw new Error('An unexpected response was received.');
        }
        return root;
    });
}

export function installInventoryList() {
    const page = document.querySelector('[data-inventory-list]');
    if (!page || page.dataset.asyncInstalled) return;
    page.dataset.asyncInstalled = 'true';

    const form = document.getElementById('inventoryListFilters');
    const search = document.getElementById('inventoryInstitutionSearch');
    const statusFilter = document.getElementById('inventoryInstitutionFilter');
    const sort = document.getElementById('inventorySort');
    const status = document.getElementById('inventorySearchStatus');
    const results = () => page.querySelector('[data-inventory-results]');
    let controller;
    let sequence = 0;
    let timer;

    function setStatus(state, message = '') {
        status.hidden = state === 'ready';
        status.textContent = state === 'loading' ? 'Updating results…'
            : state === 'error' ? `${message} Select Apply to retry.` : '';
        status.classList.toggle('text-red-700', state === 'error');
        status.classList.toggle('text-gray-600', state !== 'error');
    }

    function setBusy(root, busy) {
        root.setAttribute('aria-busy', String(busy));
        root.toggleAttribute('inert', busy);
        root.classList.toggle('opacity-60', busy);
        root.classList.toggle('pointer-events-none', busy);
    }

    function cancel() {
        clearTimeout(timer);
        controller?.abort();
        sequence++;
    }

    function syncControls(url) {
        search.value = url.searchParams.get('search') || '';
        statusFilter.value = url.searchParams.get('status') || 'all';
        sort.value = url.searchParams.get('sort') || 'name';
    }

    async function refresh(url, history = 'push') {
        clearTimeout(timer);
        cancel();
        const requestId = sequence;
        controller = new AbortController();
        const old = results();
        setBusy(old, true);
        setStatus('loading');

        try {
            const response = await fetch(url.href, {
                signal: controller.signal,
                credentials: 'same-origin',
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Talastas-Fragment': 'inventory',
                },
            });
            const replacement = await readInventoryFragment(response);
            if (requestId !== sequence) return;

            const active = old.contains(document.activeElement) ? document.activeElement : null;
            const focusHref = active?.closest('a')?.getAttribute('href');
            old.replaceWith(replacement);
            if (history !== 'none' && url.href !== window.location.href) {
                window.history[history === 'replace' ? 'replaceState' : 'pushState'](null, '', url.href);
            }
            if (history === 'none') syncControls(url);
            setStatus('ready');
            if (focusHref) {
                [...replacement.querySelectorAll('a')]
                    .find((link) => link.getAttribute('href') === focusHref)?.focus();
            }
        } catch (error) {
            if (requestId !== sequence || error.name === 'AbortError') return;
            setStatus('error', error.message || 'Inventory could not be loaded.');
        } finally {
            if (requestId === sequence) setBusy(results(), false);
        }
    }

    function setExpanded(group, expanded) {
        group.querySelector('[data-accordion-trigger]')?.setAttribute('aria-expanded', String(expanded));
        const panel = group.querySelector('[data-accordion-panel]');
        panel?.classList.toggle('hidden', !expanded);
        group.querySelector('[data-chevron]')?.classList.toggle('rotate-90', expanded);
        if (expanded && panel && panel.dataset.loaded !== 'true' && panel.dataset.loading !== 'true') {
            void loadInstitutionDetails(panel);
        }
    }

    async function loadInstitutionDetails(panel) {
        const container = panel.querySelector('[data-inventory-details-container]');
        if (!container || panel.dataset.loading === 'true' || panel.dataset.loaded === 'true') return;
        panel.dataset.loading = 'true';
        const status = container.querySelector('[data-inventory-details-status]');
        if (status) status.textContent = 'Loading inventory details…';

        try {
            const response = await fetch(panel.dataset.inventoryDetailsUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (response.redirected) throw new Error('Please sign in again to view inventory details.');
            if (!response.ok) {
                const messages = {
                    401: 'Please sign in again to view inventory details.',
                    403: 'You cannot access this institution’s inventory.',
                    404: 'This institution or its inventory details are unavailable.',
                    419: 'Your session expired. Refresh this page and try again.',
                };
                throw new Error(messages[response.status] || 'Inventory details could not be loaded.');
            }
            if (!(response.headers.get('content-type') || '').includes('text/html')) {
                throw new Error('An unexpected response was received.');
            }
            const html = await response.text();
            const template = document.createElement('template');
            template.innerHTML = html;
            if (!template.content.querySelector('[data-inventory-institution-details]')) {
                throw new Error('An unexpected response was received.');
            }
            if (!panel.isConnected) return;
            container.replaceChildren(template.content);
            panel.dataset.loaded = 'true';
        } catch (error) {
            if (!panel.isConnected) return;
            container.innerHTML = '';
            const message = document.createElement('p');
            message.className = 'text-sm text-red-700';
            message.setAttribute('role', 'alert');
            message.textContent = error.message || 'Inventory details could not be loaded.';
            const retry = document.createElement('button');
            retry.type = 'button';
            retry.dataset.inventoryDetailsRetry = '';
            retry.className = 'mt-2 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50';
            retry.textContent = 'Try again';
            container.append(message, retry);
        } finally {
            delete panel.dataset.loading;
        }
    }

    // Delegation keeps inventory accordions working after fragment replacements.
    function toggleAccordion(event) {
        const retry = event.target.closest('[data-inventory-details-retry]');
        if (retry && event.type === 'click') {
            const panel = retry.closest('[data-accordion-panel]');
            if (panel) void loadInstitutionDetails(panel);
            return;
        }
        const trigger = event.target.closest('[data-accordion-trigger]');
        if (!trigger || event.target.closest('[data-stop-accordion]')) return;
        if (event.type === 'keydown') {
            if (event.target !== trigger || !['Enter', ' '].includes(event.key)) return;
            event.preventDefault();
        }
        setExpanded(trigger.closest('[data-institution-group]'), trigger.getAttribute('aria-expanded') !== 'true');
    }

    document.addEventListener('click', toggleAccordion);
    document.addEventListener('keydown', toggleAccordion);

    search.addEventListener('input', () => {
        cancel();
        const root = results();
        root.removeAttribute('aria-busy');
        root.removeAttribute('inert');
        root.classList.remove('opacity-60', 'pointer-events-none');
        setStatus('ready');
        timer = window.setTimeout(() => refresh(inventoryListUrl(form, window.location.href), 'replace'), 300);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        void refresh(inventoryListUrl(form, window.location.href));
    });
    form.addEventListener('change', (event) => {
        if (event.target.matches('select')) void refresh(inventoryListUrl(form, window.location.href));
    });

    page.addEventListener('click', (event) => {
        const link = event.target.closest('[data-inventory-results] nav a');
        if (!link || event.defaultPrevented || event.button !== 0
            || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const url = new URL(link.href);
        if (url.origin !== window.location.origin) return;
        event.preventDefault();
        void refresh(url);
    });

    window.addEventListener('popstate', () => {
        const url = new URL(window.location.href);
        syncControls(url);
        void refresh(url, 'none');
    });
}
