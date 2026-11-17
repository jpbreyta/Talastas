import { installInstitutionSelection } from './institution-selection';
import { installInstitutionDeletion, institutionDeletionOpen } from './institution-deletion';

export function listUrl(form, currentUrl) {
    const url = new URL(currentUrl);
    for (const key of ['search', 'barangay', 'sector', 'sort', 'page']) url.searchParams.delete(key);
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
    installInstitutionDeletion();
    const form = document.getElementById('institutionListFilters');
    const search = document.getElementById('institutionSearch');
    const clearSearch = document.querySelector('[data-institution-clear-search]');
    let appliedUrl = new URL(window.location.href);
    const applyButton = form.querySelector('[data-institution-apply]');
    const overlay = page.querySelector('[data-institution-loading]');
    function setLoading(loading) {
        applyButton.disabled = loading;
        applyButton.setAttribute('aria-busy', String(loading));
        applyButton.querySelector('[data-apply-spinner]').hidden = !loading;
        applyButton.querySelector('[data-apply-icon]').hidden = loading;
        applyButton.querySelector('[data-apply-label]').textContent = loading ? 'Applying…' : 'Apply';
        overlay.hidden = !loading;
    }
    const status = page.querySelector('[data-institution-list-status]');
    const searchStatus = page.querySelector('[data-institution-search-status]');
    const statusLabel = status.querySelector('[data-institution-status-label]');
    const statusIcon = status.querySelector('[data-institution-status-icon]');
    function setStatus(state, total = results()?.dataset.total) {
        searchStatus.hidden = !search.value.trim() && state === 'ready';
        if (clearSearch) clearSearch.hidden = !search.value;
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
        form.elements.sector.value = url.searchParams.get('sector') || '';
        form.elements.sort.value = url.searchParams.get('sort') || 'barangay_asc';
    };
    const cancel = () => { controller?.abort(); sequence++; };
    function syncPending() {
        const pending = ['barangay', 'sector', 'sort'].some(key => {
            const fallback = key === 'sort' ? 'barangay_asc' : '';
            return form.elements[key].value !== (appliedUrl.searchParams.get(key) || fallback);
        });
        applyButton.classList.toggle('bg-red-50', pending);
        applyButton.title = pending ? 'Apply pending filter changes' : 'Apply filters';
    }

    function searchUrl() {
        const url = new URL(appliedUrl);
        url.searchParams.delete('page');
        url.searchParams.delete('search');
        if (search.value.trim()) url.searchParams.set('search', search.value.trim());
        return url;
    }

    async function refresh(url, history = 'push') {
        // Do not replace a table whose batch deletion is still processing.
        if (institutionDeletionOpen() || results().querySelector('[data-institution-batch-delete][aria-busy="true"]')) return false;
        clearTimeout(timer);
        cancel();
        const requestId = sequence;
        controller = new AbortController();
        const old = results();
        const active = old.contains(document.activeElement) ? document.activeElement : null;
        const focusHref = active?.closest('a')?.getAttribute('href');
        old.querySelector('[data-institution-clear-selection]')?.click();
        setLoading(true);
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
            cleanup?.();
            old.replaceWith(replacement);
            cleanup = installInstitutionSelection();
            // The server clamps pages emptied by deletion to the last valid page.
            if (replacement.dataset.page && url.searchParams.has('page')) {
                url.searchParams.set('page', replacement.dataset.page);
            }
            appliedUrl = new URL(url);
            if (url.href !== window.location.href) {
                window.history[history === 'push' ? 'pushState' : 'replaceState'](null, '', url.href);
            }
            if (history === 'none') syncControls(url);
            syncPending();
            setStatus('ready', replacement.dataset.total);
            if (active && (document.activeElement === document.body || old.contains(document.activeElement))) {
                const link = focusHref && [...replacement.querySelectorAll('a')].find(link => link.getAttribute('href') === focusHref);
                (link || replacement.querySelector('[aria-label="Institutions table"]') || search).focus();
            }
            return true;
        } catch (error) {
            if (requestId !== sequence || error.name === 'AbortError') return;
            setStatus('error');
            window.TalastasToast?.notify('error', error.message || 'Check your connection and try again.');
            return false;
        } finally {
            if (requestId === sequence) {
                setLoading(false);
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
        setLoading(false);
        // Prevent deletion from stale results, including during the debounce interval.
        results().querySelector('[data-institution-clear-selection]')?.click();
        results().setAttribute('inert', '');
        results().setAttribute('aria-busy', 'true');
        results().classList.add('opacity-60', 'pointer-events-none');
        setStatus('loading');
        timer = setTimeout(() => refresh(searchUrl(), 'replace'), 300);
    });
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (applyButton.disabled) return;
        void refresh(listUrl(form, window.location.href));
    });
    form.addEventListener('change', (event) => {
        if (event.target.matches('select')) syncPending();
    });
    clearSearch?.addEventListener('click', () => {
        search.value = '';
        search.dispatchEvent(new Event('input', { bubbles: true }));
        search.focus();
    });
    page.addEventListener('click', (event) => {
        const link = event.target.closest('[data-institution-results] nav a, [data-institution-reset]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const url = new URL(link.href);
        if (url.origin !== window.location.origin) return;
        event.preventDefault();
        if (link.matches('[data-institution-reset]')) syncControls(url);
        else search.value = url.searchParams.get('search') || '';
        void refresh(url);
    });
    window.addEventListener('popstate', () => {
        const url = new URL(window.location.href);
        syncControls(url);
        void refresh(url, 'none');
    });
    // Expose the same refresh for a later deletion step without changing deletes.
    window.TalastasInstitutions = { refresh: () => refresh(new URL(appliedUrl), 'replace') };
    syncPending();
}
