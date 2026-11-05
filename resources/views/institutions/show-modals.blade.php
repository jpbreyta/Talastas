{{-- Delete confirmation for the streamlined institution details page --}}
<div id="deleteInstitutionModal"
    class="fixed inset-0 z-[3300] hidden overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="deleteInstitutionModalTitle"
    inert>
    <div class="flex min-h-screen items-center justify-center px-4 py-8">
        <button type="button"
            data-close-delete-modal
            class="fixed inset-0 cursor-default bg-gray-900/60"
            aria-label="Close delete dialog"></button>

        <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="border-b border-gray-200 px-6 py-5">
                <h2 id="deleteInstitutionModalTitle"
                    class="text-lg font-semibold text-gray-900">
                    Delete Institution
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    This action permanently removes the institution and its related records.
                </p>
            </div>

            <div class="px-6 py-5">
                <p class="text-sm leading-6 text-gray-600">
                    Delete
                    <span id="deleteInstitutionName"
                        class="font-semibold text-gray-900">
                        this institution
                    </span>?
                </p>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button"
                    data-close-delete-modal
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700">
                    Cancel
                </button>
                <button type="button"
                    id="confirmDeleteInstitutionButton"
                    class="rounded-lg bg-red-600 px-5 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60">
                    Delete Institution
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal =
        document.getElementById('deleteInstitutionModal');
    const name =
        document.getElementById('deleteInstitutionName');
    const confirmButton =
        document.getElementById('confirmDeleteInstitutionButton');
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.content || '';

    let deleteUrl = null;

    function openModal(button) {
        deleteUrl = button.dataset.deleteUrl || null;

        if (name) {
            name.textContent =
                button.dataset.institutionName || 'this institution';
        }

        modal?.classList.remove('hidden');
        modal?.removeAttribute('inert');
        modal?.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        modal?.classList.add('hidden');
        modal?.setAttribute('inert', '');
        modal?.setAttribute('aria-hidden', 'true');
        deleteUrl = null;
    }

    document.querySelectorAll('[data-delete-institution]')
        .forEach((button) => {
            button.addEventListener('click', () => openModal(button));
        });

    document.querySelectorAll('[data-close-delete-modal]')
        .forEach((button) => {
            button.addEventListener('click', closeModal);
        });

    confirmButton?.addEventListener('click', async () => {
        if (!deleteUrl || confirmButton.disabled) {
            return;
        }

        confirmButton.disabled = true;
        confirmButton.textContent = 'Deleting...';

        try {
            const response = await fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Institution could not be deleted.');
            }

            window.location.assign(
                '{{ route('institutions.index') }}'
            );
        } catch (error) {
            alert(
                error instanceof Error
                    ? error.message
                    : 'Institution could not be deleted.'
            );

            confirmButton.disabled = false;
            confirmButton.textContent = 'Delete Institution';
        }
    });

    document.addEventListener('keydown', (event) => {
        if (
            event.key === 'Escape'
            && !modal?.classList.contains('hidden')
        ) {
            closeModal();
        }
    });
});
</script>
