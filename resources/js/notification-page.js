function responseError(status, payload = {}) {
    const messages = {
        401: 'Please sign in again to manage notifications.',
        403: 'You are not authorized to change these notifications.',
        419: 'Your session expired. Refresh this page and try again.',
        422: Object.values(payload.errors || {}).flat().find((message) => typeof message === 'string')
            || 'The notification action could not be validated.',
    };
    return messages[status] || (status >= 500
        ? 'The server could not update notifications. Please try again.'
        : payload.message || 'Notifications could not be updated.');
}

export async function readNotificationResponse(response) {
    if (response.redirected) throw new Error('Please sign in again to manage notifications.');
    const isJson = /application\/(?:[\w.-]+\+)?json/i.test(response.headers.get('content-type') || '');
    const payload = isJson ? await response.json().catch(() => null) : null;
    if (!response.ok) throw new Error(responseError(response.status, payload || {}));
    if (!payload || payload.success !== true || !Array.isArray(payload.notifications?.notifications)
        || !Number.isFinite(Number(payload.notifications.unread_count))) {
        throw new Error('An unexpected response was received. Refresh this page and try again.');
    }
    return payload;
}

export function installNotificationPage() {
    const page = () => document.querySelector('[data-notifications-page]');
    if (!page()) return;
    let busy = false;

    function markRead(item) {
        item.dataset.unread = '0';
        const button = item.querySelector('button');
        button?.classList.remove('bg-red-50/30');
        button?.classList.add('bg-white');
        const dot = item.querySelector('[data-notification-dot]');
        dot?.classList.remove('bg-red-700');
        dot?.classList.add('bg-slate-300');
        item.querySelector('[data-notification-new]')?.remove();
    }

    function syncSnapshot(snapshot) {
        const root = page();
        if (!root) return;
        const count = root.querySelector('[data-notifications-page-unread]');
        if (count) count.textContent = String(snapshot.unread_count);
        root.querySelector('[data-notifications-page-count]')?.removeAttribute('hidden');
        const known = new Map(snapshot.notifications.map((item) => [String(item.id), item]));
        root.querySelectorAll('[data-notification-row]').forEach((item) => {
            if (Number(snapshot.unread_count) === 0 || known.get(item.dataset.notificationId)?.read) markRead(item);
        });
    }
    document.addEventListener('talastas:notifications-snapshot', (event) => syncSnapshot(event.detail));
    const initialCount = document.querySelector('[data-notification-unread-count]')?.textContent;
    if (initialCount !== undefined) syncSnapshot({ unread_count: Number(initialCount), notifications: [] });

    async function refreshPage() {
        const destination = new URL(window.location.href);
        // A deletion may make the current page invalid. Fetch the last valid
        // page if needed, without turning pagination clicks into AJAX navigation.
        for (let attempt = 0; attempt < 2; attempt++) {
            const response = await fetch(destination.href, {
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin', cache: 'no-store',
            });
            if (!response.ok || response.redirected) throw new Error('The notification list could not be refreshed.');
            const copy = new DOMParser().parseFromString(await response.text(), 'text/html');
            const replacement = copy.querySelector('[data-notifications-page]');
            if (!replacement) throw new Error('The notification list could not be refreshed.');
            const current = Number(replacement.dataset.currentPage);
            const last = Math.max(1, Number(replacement.dataset.lastPage));
            if (current > last) {
                destination.searchParams.set('page', String(last));
                continue;
            }
            page()?.replaceWith(replacement);
            if (destination.href !== window.location.href) window.history.replaceState(null, '', destination.href);
            return;
        }
        throw new Error('The notification list could not be refreshed.');
    }

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-notification-action]')
            || !(form.closest('[data-notifications-page]') || form.closest('[data-notifications-page-tools]')) || event.defaultPrevented) return;
        event.preventDefault();
        if (busy) return;
        busy = true;
        const action = form.dataset.notificationAction;
        const root = page();
        const tools = document.querySelector('[data-notifications-page-tools]');
        const restoreFocus = root.contains(document.activeElement) || tools?.contains(document.activeElement);
        const buttons = [...root.querySelectorAll('form[data-notification-action] button'),
            ...(tools?.querySelectorAll('form[data-notification-action] button') || [])]
            .map((button) => [button, button.disabled]);
        const trigger = event.submitter || form.querySelector('button');
        buttons.forEach(([button]) => { button.disabled = true; });
        trigger?.setAttribute('aria-busy', 'true');
        trigger?.classList.add('opacity-70', 'cursor-wait');
        const feedback = root.querySelector('[data-notifications-page-feedback]');
        if (feedback) feedback.textContent = 'Updating notifications…';
        document.dispatchEvent(new CustomEvent('talastas:notifications-page-start'));
        let snapshot, mutationSucceeded = false;
        try {
            const method = form.querySelector('input[name="_method"]')?.value || 'POST';
            const response = await fetch(form.action, {
                method, body: new FormData(form), credentials: 'same-origin',
                headers: {
                    Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            });
            const payload = await readNotificationResponse(response);
            mutationSucceeded = true;
            snapshot = payload.notifications;
            if (action === 'clear-read') {
                root.querySelectorAll('[data-notification-row][data-unread="0"]').forEach((item) => item.remove());
                root.querySelector('[data-notifications-empty]').hidden = root.querySelectorAll('[data-notification-row]').length > 0;
                root.querySelector('[data-notifications-pagination]').hidden = true;
                window.TalastasToast?.notify('deleted', 'Read notifications cleared.');
                await refreshPage();
            } else {
                if (action === 'read-all') root.querySelectorAll('[data-notification-row]').forEach(markRead);
                else markRead(form);
                window.TalastasToast?.notify('success', action === 'read-all' ? 'All notifications marked as read.' : 'Notification marked as read.');
                if (action === 'read' && payload.action_url) {
                    const destination = new URL(payload.action_url, window.location.origin);
                    if (destination.origin === window.location.origin) window.location.assign(destination.href);
                }
            }
        } catch (error) {
            const message = mutationSucceeded && action === 'clear-read'
                ? 'Read notifications were cleared, but the list could not be refreshed. Use Refresh notifications to reload the list.'
                : error instanceof Error ? error.message : 'Notifications could not be updated. Please try again.';
            if (mutationSucceeded && action === 'clear-read') page()?.querySelector('[data-notifications-refresh]')?.removeAttribute('hidden');
            window.TalastasToast?.notify('error', message);
        } finally {
            busy = false;
            buttons.forEach(([button, disabled]) => { button.disabled = disabled; });
            trigger?.removeAttribute('aria-busy');
            trigger?.classList.remove('opacity-70', 'cursor-wait');
            const currentFeedback = page()?.querySelector('[data-notifications-page-feedback]');
            if (currentFeedback) currentFeedback.textContent = '';
            if (snapshot) syncSnapshot(snapshot);
            document.dispatchEvent(new CustomEvent('talastas:notifications-page-finish', { detail: snapshot }));
            if (restoreFocus && action === 'clear-read') {
                (tools?.querySelector('[data-notification-action="clear-read"] button')
                    || page()?.querySelector('[data-notification-action="clear-read"] button'))?.focus();
            }
        }
    });
}
