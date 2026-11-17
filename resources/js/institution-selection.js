import { requestInstitutionDeletion, refreshAfterInstitutionDeletion } from './institution-deletion';

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
            row.toggleAttribute('data-selected', checkbox.checked);
        });

        const visible = inputs.filter(isVisible);
        const selectedCount = selected().length;
        selectAll.checked = visible.length > 0 && selectedCount === visible.length;
        selectAll.indeterminate = selectedCount > 0 && selectedCount < visible.length;
        selectAll.disabled = busy || visible.length === 0;
        actions.hidden = selectedCount === 0;
        count.textContent = `${selectedCount} institution${selectedCount === 1 ? '' : 's'} selected on this page`;
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
        const records = selected().map(checkbox => ({
            id: Number(checkbox.value), name: checkbox.dataset.institutionName || checkbox.getAttribute('aria-label').replace(/^Select /, ''),
        }));
        if (!records.length) return;

        busy = true;
        syncSelection();
        try {
            const payload = await requestInstitutionDeletion({
                url: container.dataset.batchDeleteUrl, records, trigger: deleteButton, batch: true,
                onSettled: () => { busy = false; syncSelection(); },
            });
            busy = false;
            if (payload) {
                const deleted = new Set(payload.deleted_ids);
                checkboxes().forEach(checkbox => {
                    if (deleted.has(Number(checkbox.value))) checkbox.checked = false;
                });
                syncSelection();
                await refreshAfterInstitutionDeletion(payload, records);
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
