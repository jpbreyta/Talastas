export function installInstitutionSelection() {
    const container = document.querySelector('[data-institution-selection]');
    if (!container) {
        return;
    }

    const selectAll = container.querySelector('[data-institution-select-all]');
    const actions = container.querySelector('[data-institution-batch-actions]');
    const count = container.querySelector('[data-institution-selected-count]');
    const deleteButton = container.querySelector('[data-institution-batch-delete]');
    const deleteLabel = container.querySelector('[data-batch-delete-label]');
    const clearButton = container.querySelector('[data-institution-clear-selection]');
    let busy = false;

    const checkboxes = () => Array.from(container.querySelectorAll('[data-institution-select]'));
    const isVisible = (checkbox) => {
        const row = checkbox.closest('.inst-row');
        return row && !row.hidden && !row.classList.contains('hidden') && row.style.display !== 'none';
    };
    const selected = () => checkboxes().filter((checkbox) => checkbox.checked && isVisible(checkbox));

    function syncSelection() {
        const inputs = checkboxes();
        inputs.forEach((checkbox) => {
            // Filtered-out records must never remain silently selected.
            if (!isVisible(checkbox)) {
                checkbox.checked = false;
            }
            checkbox.disabled = busy;
            const row = checkbox.closest('.inst-row');
            if (row.classList.contains('bg-red-50/60') !== checkbox.checked) {
                row.classList.toggle('bg-red-50/60', checkbox.checked);
            }
        });

        const visible = inputs.filter(isVisible);
        const selectedCount = selected().length;
        selectAll.checked = visible.length > 0 && selectedCount === visible.length;
        selectAll.indeterminate = selectedCount > 0 && selectedCount < visible.length;
        selectAll.disabled = busy || visible.length === 0;
        actions.hidden = selectedCount === 0;
        count.textContent = `${selectedCount} selected`;
        deleteButton.disabled = busy || selectedCount === 0;
        clearButton.disabled = busy;
        deleteButton.setAttribute('aria-busy', String(busy));
        deleteLabel.textContent = busy ? 'Deleting...' : 'Delete selected';
    }

    container.addEventListener('click', (event) => {
        if (event.target.closest(
            '[data-institution-selection-control], [data-institution-batch-actions]'
        )) {
            event.stopPropagation();
        }
    });

    container.addEventListener('change', (event) => {
        if (event.target === selectAll) {
            const checked = selectAll.checked;
            checkboxes().forEach((checkbox) => {
                checkbox.checked = checked && isVisible(checkbox);
            });
            syncSelection();
        } else if (event.target.matches('[data-institution-select]')) {
            syncSelection();
        }
    });

    clearButton.addEventListener('click', () => {
        checkboxes().forEach((checkbox) => { checkbox.checked = false; });
        syncSelection();
    });

    deleteButton.addEventListener('click', async () => {
        if (busy) {
            return;
        }
        syncSelection();
        const ids = selected().map((checkbox) => Number(checkbox.value));
        if (!ids.length || !window.confirm(
            `Delete ${ids.length} selected institution${ids.length === 1 ? '' : 's'} and their related inventory and analytics? This cannot be undone.`
        )) {
            return;
        }

        busy = true;
        syncSelection();
        try {
            const response = await fetch(container.dataset.batchDeleteUrl, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ institution_ids: ids }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.success !== true || !Array.isArray(payload.deleted_ids)) {
                const validationMessage = Object.values(payload.errors || {}).flat()[0];
                throw new Error(response.status >= 500
                    ? 'Institutions could not be deleted. No changes were saved.'
                    : validationMessage || payload.message || (response.status === 419
                        ? 'Your session expired. Refresh the page and try again.'
                        : 'The selected institutions could not be deleted.'));
            }

            // Reuse the existing Institution toast across a full refresh so
            // pagination and server totals reflect the completed deletion.
            payload.deleted_ids.forEach((id) => {
                checkboxes().find((checkbox) => Number(checkbox.value) === Number(id))
                    ?.closest('.inst-row').remove();
            });
            try {
                sessionStorage.setItem('institutionToast', JSON.stringify({
                    type: 'deleted', message: payload.message,
                }));
                const destination = new URL(window.location.href);
                const page = Number(destination.searchParams.get('page') || 1);
                if (checkboxes().length === 0 && page > 1) {
                    destination.searchParams.set('page', String(page - 1));
                    window.location.assign(destination.href);
                } else {
                    window.location.reload();
                }
            } catch {
                window.InstitutionModal?.notify('deleted', payload.message);
            }
        } catch (error) {
            window.InstitutionModal?.notify('error', error instanceof Error
                ? error.message : 'The selected institutions could not be deleted.');
        } finally {
            busy = false;
            syncSelection();
        }
    });

    // Single deletion removes rows. Observe row changes to keep selection
    // accurate, and disconnect when the async list replaces this container.
    const observer = new MutationObserver(syncSelection);
    observer.observe(container.querySelector('tbody'), {
        subtree: true, childList: true, attributes: true,
        attributeFilter: ['class', 'style', 'hidden'],
    });
    syncSelection();
    return () => observer.disconnect();
}
