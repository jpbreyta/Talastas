import { installInstitutionSelection } from './institution-selection';

export function listUrl(form, currentUrl) {
    const url = new URL(currentUrl);
    for (const key of ['search', 'barangay', 'sort', 'page']) url.searchParams.delete(key);
    for (const [key, value] of new FormData(form)) {
        if (String(value).trim()) url.searchParams.set(key, String(value).trim());
    }
    return url;
}

export async function readInstitutionFragment(response) {
    const errors = {
        401: 'Please sign in again.', 403: 'You cannot access these institutions.',
        419: 'Your session expired. Refresh this page.',
        422: 'The search or filters could not be validated.',
    };
    if (response.redirected) throw new Error('Please sign in again.');
    if (!response.ok) throw new Error(errors[response.status] || 'Institutions could not be loaded. Please try again.');
    if (!(response.headers.get('content-type') || '').includes('text/html')) throw new Error('An unexpected response was received.');
    const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
    const root = parsed.querySelector('[data-institution-results]');
    if (!root || parsed.querySelector('[data-institution-list]')) throw new Error('An unexpected response was received.');
    return root;
}

export function installInstitutionList() {
    const page = document.querySelector('[data-institution-list]');
    if (!page || page.dataset.asyncInstalled) return;
    page.dataset.asyncInstalled = 'true';
    const form = document.getElementById('institutionListFilters');
    const search = document.getElementById('institutionSearch');
    const status = page.querySelector('[data-institution-list-status]');
    const searchStatus = page.querySelector('[data-institution-search-status]');
    const statusLabel = status.querySelector('[data-institution-status-label]');
    const statusIcon = status.querySelector('[data-institution-status-icon]');
    function setStatus(state, total = results()?.dataset.total) {
        searchStatus.hidden = !search.value.trim();
        const failed = state === 'error';
        status.className = 'inline-flex max-w-full items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium shadow-sm '
            + (failed ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-gray-200 bg-white text-gray-600');
        statusIcon.className = state === 'loading' ? 'fa-solid fa-spinner fa-spin text-red-700'
            : failed ? 'fa-solid fa-circle-exclamation text-amber-600' : 'fa-solid fa-building-columns text-red-700';
        const count = Number(total || 0);
        statusLabel.textContent = state === 'loading' ? 'Updating results...'
            : failed ? 'Unable to update. Select Apply to retry.'
            : `${count.toLocaleString()} ${count === 1 ? 'institution' : 'institutions'}`;
    }
    let controller, sequence = 0, timer;
    let cleanup = installInstitutionSelection();
    const results = () => page.querySelector('[data-institution-results]');
    const syncControls = (url) => {
        search.value = url.searchParams.get('search') || '';
        form.elements.barangay.value = url.searchParams.get('barangay') || '';
        form.elements.sort.value = url.searchParams.get('sort') || 'barangay_asc';
    };
    const cancel = () => { controller?.abort(); sequence++; };

    async function refresh(url, history = 'push') {
        // Do not replace a table whose batch deletion is still processing.
        if (results().querySelector('[data-institution-batch-delete][aria-busy="true"]')
            || document.getElementById('confirmDeleteInstitution')?.disabled) return;
        clearTimeout(timer);
        cancel();
        const requestId = sequence;
        controller = new AbortController();
        const old = results();
        old.setAttribute('aria-busy', 'true');
        old.classList.add('opacity-60', 'pointer-events-none');
        old.setAttribute('inert', '');
        setStatus('loading');
        try {
            const response = await fetch(url.href, {
                signal: controller.signal, credentials: 'same-origin',
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest', 'X-Talastas-Fragment': 'institutions' },
            });
            const replacement = await readInstitutionFragment(response);
            if (requestId !== sequence) return;
            // Focus stays on the search/filter controls. Preserve a pagination
            // link's focus only when its equivalent exists in the new results.
            const active = old.contains(document.activeElement) ? document.activeElement : null;
            const focusHref = active?.closest('a')?.getAttribute('href');
            cleanup?.();
            old.replaceWith(replacement);
            cleanup = installInstitutionSelection();
            if (history !== 'none' && url.href !== window.location.href) {
                window.history[history === 'replace' ? 'replaceState' : 'pushState'](null, '', url.href);
            }
            if (history === 'none') syncControls(url);
            setStatus('ready', replacement.dataset.total);
            if (focusHref) [...replacement.querySelectorAll('a')].find((link) => link.getAttribute('href') === focusHref)?.focus();
        } catch (error) {
            if (requestId !== sequence || error.name === 'AbortError') return;
            setStatus('error');
            window.TalastasToast?.notify('error', error.message || 'Check your connection and try again.');
        } finally {
            if (requestId === sequence) {
                results().removeAttribute('aria-busy');
                results().removeAttribute('inert');
                results().classList.remove('opacity-60', 'pointer-events-none');
            }
        }
    }

    search.addEventListener('input', () => {
        clearTimeout(timer);
        // Invalidate immediately, including during the debounce interval.
        cancel();
        results().removeAttribute('inert');
        results().removeAttribute('aria-busy');
        results().classList.remove('opacity-60', 'pointer-events-none');
        setStatus('ready');
        timer = setTimeout(() => refresh(listUrl(form, window.location.href), 'replace'), 300);
    });
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        void refresh(listUrl(form, window.location.href));
    });
    form.addEventListener('change', (event) => {
        if (event.target.matches('select')) void refresh(listUrl(form, window.location.href));
    });
    page.addEventListener('click', (event) => {
        const link = event.target.closest('[data-institution-results] nav a, [data-institution-reset]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const url = new URL(link.href);
        if (url.origin !== window.location.origin) return;
        event.preventDefault();
        if (link.matches('[data-institution-reset]')) syncControls(url);
        void refresh(url);
    });
    window.addEventListener('popstate', () => {
        const url = new URL(window.location.href);
        syncControls(url);
        void refresh(url, 'none');
    });
    // Expose the same refresh for a later deletion step without changing deletes.
    window.TalastasInstitutions = { refresh: () => refresh(new URL(window.location.href), 'none') };
}
