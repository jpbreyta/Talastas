let activeDeletion = null;

export function institutionDeletionOpen() {
    return activeDeletion !== null;
}

// Single and bulk deletion share the existing confirmation dialog and endpoints.
export function requestInstitutionDeletion({ url, records, trigger, batch = false, onSettled = () => {} }) {
    const modal = document.getElementById('deleteInstitutionModal');
    if (!modal || activeDeletion || !records.length) return Promise.resolve(null);
    const confirm = modal.querySelector('#confirmDeleteInstitution');
    const cancel = modal.querySelector('#cancelDeleteInstitution');
    const close = modal.querySelector('#closeDeleteInstitutionModal');
    const title = modal.querySelector('#deleteInstitutionModalTitle');
    const description = modal.querySelector('#deleteInstitutionDescription');
    const count = records.length;
    const label = batch ? `Delete ${count} Institution${count === 1 ? '' : 's'}` : 'Delete Institution';
    title.textContent = batch ? 'Delete Selected Institutions?' : 'Delete Institution';
    description.textContent = batch
        ? `You are about to delete ${count} institution records. This action may affect related information in the system.`
        : `Are you sure you want to delete ${records[0].name}?`;
    confirm.querySelector('span').textContent = label;
    let busy = false;
    const originalOverflow = document.body.style.overflow;
    // Inert the dialog's siblings at each ancestor, without altering existing inert states.
    const background = [];
    for (let node = modal; node.parentElement; node = node.parentElement) {
        for (const sibling of node.parentElement.children) {
            if (sibling !== node && !sibling.hasAttribute('inert')) {
                sibling.setAttribute('inert', '');
                background.push(sibling);
            }
        }
        if (node.parentElement === document.body) break;
    }
    modal.classList.remove('hidden');
    modal.removeAttribute('aria-hidden');
    modal.removeAttribute('inert');
    document.body.style.overflow = 'hidden';

    return new Promise((resolve, reject) => {
        activeDeletion = { records };
        const setBusy = value => {
            busy = value;
            [confirm, cancel, close].forEach(button => { button.disabled = value; });
            confirm.setAttribute('aria-busy', String(value));
            confirm.querySelector('span').textContent = value ? 'Deleting...' : label;
        };
        const finish = (payload, error) => {
            confirm.removeEventListener('click', submit);
            cancel.removeEventListener('click', dismiss);
            close.removeEventListener('click', dismiss);
            modal.querySelector('#deleteInstitutionModalOverlay').removeEventListener('click', dismiss);
            document.removeEventListener('keydown', keyboard, true);
            document.removeEventListener('focusin', containFocus);
            setBusy(false);
            activeDeletion = null;
            onSettled();
            background.forEach(element => element.removeAttribute('inert'));
            document.body.style.overflow = originalOverflow;
            (trigger?.isConnected ? trigger : document.getElementById('institutionSearch'))?.focus();
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            modal.setAttribute('inert', '');
            if (error) reject(error);
            else resolve(payload);
        };
        const dismiss = () => { if (!busy) finish(null); };
        const containFocus = event => {
            if (!modal.contains(event.target)) (busy ? modal : cancel).focus();
        };
        const keyboard = event => {
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopImmediatePropagation();
                dismiss();
            } else if (event.key === 'Tab') {
                const buttons = [...modal.querySelectorAll('button:not(:disabled)')];
                const first = buttons[0], last = buttons.at(-1);
                if (!first) { event.preventDefault(); modal.focus(); }
                else if (event.shiftKey && (document.activeElement === first || document.activeElement === modal)) {
                    event.preventDefault(); last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault(); first.focus();
                }
            }
        };
        async function submit() {
            if (busy) return;
            setBusy(true);
            try {
                const response = await fetch(url, {
                    method: 'DELETE', credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json', 'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    ...(batch ? { body: JSON.stringify({ institution_ids: records.map(record => record.id) }) } : {}),
                });
                const payload = await response.json().catch(() => ({}));
                if (response.redirected || !response.ok || payload.success !== true) {
                    const message = Object.values(payload.errors || {}).flat()[0] || payload.message;
                    throw new Error(response.redirected || response.status === 401 ? 'Please sign in again.'
                        : response.status === 419 ? 'Your session expired. Refresh the page and try again.'
                        : response.status === 403 ? 'You do not have permission to delete these institutions.'
                        : response.status >= 500 ? 'Deletion could not be confirmed. Refresh the table before trying again.'
                        : message || 'The institutions could not be deleted.');
                }
                const deleted = batch ? payload.deleted_ids : [records[0].id];
                const requested = new Set(records.map(record => record.id));
                if (!Array.isArray(deleted) || !deleted.length
                    || deleted.some(id => !requested.has(Number(id)))
                    || new Set(deleted.map(Number)).size !== deleted.length) {
                    throw new Error('Deletion returned an unexpected result. Refresh the table before trying again.');
                }
                finish({ ...payload, deleted_ids: deleted.map(Number) });
            } catch (error) {
                finish(null, new Error(`${records.map(record => record.name).join(', ')}: ${error.message || 'Deletion could not be confirmed. Refresh before trying again.'}`));
            }
        }
        confirm.addEventListener('click', submit);
        cancel.addEventListener('click', dismiss);
        close.addEventListener('click', dismiss);
        modal.querySelector('#deleteInstitutionModalOverlay').addEventListener('click', dismiss);
        document.addEventListener('keydown', keyboard, true);
        document.addEventListener('focusin', containFocus);
        cancel.focus();
    });
}

export async function refreshAfterInstitutionDeletion(payload, records) {
    const deleted = new Set(payload.deleted_ids);
    records.filter(record => deleted.has(record.id)).forEach(record => {
        document.getElementById(`institution-row-${record.id}`)?.remove();
    });
    const failed = records.filter(record => !deleted.has(record.id));
    const message = `${deleted.size} institution${deleted.size === 1 ? '' : 's'} deleted successfully.`;
    window.InstitutionModal?.notify(failed.length ? 'error' : 'deleted', failed.length
        ? `${message} Not deleted: ${failed.map(record => record.name).join(', ')}.` : message);
    const refreshed = await window.TalastasInstitutions?.refresh();
    if (!refreshed) window.location.reload();
}

export function installInstitutionDeletion() {
    const page = document.querySelector('[data-institution-list]');
    if (!page || page.dataset.deletionInstalled) return;
    page.dataset.deletionInstalled = 'true';
    document.addEventListener('click', async event => {
        const button = event.target.closest('.delete-institution-button');
        if (!button || activeDeletion) return;
        const records = [{ id: Number(button.dataset.institutionId), name: button.dataset.institutionName || 'this institution' }];
        const menuId = button.closest('[data-institution-actions-menu]')?.id;
        const trigger = menuId ? document.querySelector(`[aria-controls="${menuId}"]`) : button;
        try {
            const payload = await requestInstitutionDeletion({ url: button.dataset.deleteUrl, records, trigger });
            if (payload) await refreshAfterInstitutionDeletion(payload, records);
        } catch (error) {
            window.InstitutionModal?.notify('error', error.message);
        }
    });
}
