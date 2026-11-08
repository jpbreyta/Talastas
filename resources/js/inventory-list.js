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
        group.querySelector('[data-accordion-panel]')?.classList.toggle('hidden', !expanded);
        group.querySelector('[data-chevron]')?.classList.toggle('rotate-90', expanded);
    }

    // Delegation keeps inventory accordions working after fragment replacements.
    function toggleAccordion(event) {
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
