{{-- Reusable modals for the institution details page --}}
<div id="institutionToastContainer"
    class="pointer-events-none fixed right-4 top-4 z-[5000] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-3"
    aria-live="polite">
</div>

<div id="institutionNotesModal"
    class="fixed inset-0 z-[3200] hidden overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="institutionNotesModalTitle"
    inert>
    <div class="flex min-h-screen items-center justify-center px-4 py-8">
        <div data-close-notes-modal
            class="fixed inset-0 bg-gray-900/60 backdrop-blur-[1px]"></div>

        <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-5">
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <i class="fa-solid fa-note-sticky"></i>
                    </span>

                    <div>
                        <h2 id="institutionNotesModalTitle"
                            class="text-lg font-semibold text-gray-900">
                            Institution Notes
                        </h2>
                        <p class="mt-1 text-sm leading-5 text-gray-500">
                            Add observations, reminders, or follow-up information for this institution.
                        </p>
                    </div>
                </div>

                <button type="button" data-close-notes-modal
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                    aria-label="Close notes modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="institutionNotesForm"
                method="POST"
                action="{{ route('institutions.update', $institution) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="notes_only" value="1">

                <div class="px-6 py-5">
                    <label for="institutionNotesInput"
                        class="mb-2 block text-sm font-semibold text-gray-700">
                        Notes
                    </label>

                    <textarea id="institutionNotesInput"
                        name="notes"
                        rows="8"
                        maxlength="5000"
                        class="block w-full resize-y rounded-xl border border-gray-300 px-4 py-3 text-sm leading-6 text-gray-800 placeholder-gray-400 transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100"
                        placeholder="Enter institution notes...">{{ $institution->notes }}</textarea>

                    <div class="mt-2 flex items-center justify-between gap-3">
                        <p id="institutionNotesError"
                            class="hidden text-xs font-medium text-red-600">
                        </p>

                        <p class="ml-auto text-xs text-gray-400">
                            <span id="institutionNotesCount">{{ mb_strlen((string) $institution->notes) }}</span>/5000
                        </p>
                    </div>
                </div>

                <div
                    class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-end">
                    <button type="button" data-close-notes-modal
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                        <i class="fa-solid fa-xmark"></i>
                        Cancel
                    </button>

                    <button type="submit" id="saveInstitutionNotesButton"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Save Notes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="deleteInstitutionModal"
    class="fixed inset-0 z-[3300] hidden overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="deleteInstitutionModalTitle"
    inert>
    <div class="flex min-h-screen items-center justify-center px-4 py-8">
        <div data-close-delete-modal
            class="fixed inset-0 bg-gray-900/60 backdrop-blur-[1px]"></div>

        <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-5">
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600">
                        <i class="fa-solid fa-trash-can"></i>
                    </span>

                    <div>
                        <h2 id="deleteInstitutionModalTitle"
                            class="text-lg font-semibold text-gray-900">
                            Delete Institution
                        </h2>
                        <p class="mt-1 text-sm leading-5 text-gray-500">
                            This action permanently removes the institution and its related records.
                        </p>
                    </div>
                </div>

                <button type="button" data-close-delete-modal
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                    aria-label="Close delete modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="px-6 py-5">
                <p class="text-sm leading-6 text-gray-600">
                    Delete
                    <span id="deleteInstitutionName"
                        class="font-semibold text-gray-900">
                        this institution
                    </span>?
                </p>

                <div class="mt-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5 text-red-600"></i>
                    <p class="text-sm leading-5 text-red-700">
                        This action cannot be undone.
                    </p>
                </div>
            </div>

            <div
                class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" data-close-delete-modal
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                    Cancel
                </button>

                <button type="button" id="confirmDeleteInstitutionButton"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <i class="fa-solid fa-trash-can"></i>
                    <span>Delete Institution</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        'use strict';

        const notesModal = document.getElementById('institutionNotesModal');
        const notesForm = document.getElementById('institutionNotesForm');
        const notesInput = document.getElementById('institutionNotesInput');
        const notesCount = document.getElementById('institutionNotesCount');
        const notesError = document.getElementById('institutionNotesError');
        const notesText = document.getElementById('institutionNotesText');
        const notesEmpty = document.getElementById('institutionNotesEmpty');
        const notesActionLabel = document.getElementById('notesActionLabel');
        const saveNotesButton = document.getElementById('saveInstitutionNotesButton');

        const deleteModal = document.getElementById('deleteInstitutionModal');
        const deleteName = document.getElementById('deleteInstitutionName');
        const confirmDeleteButton = document.getElementById('confirmDeleteInstitutionButton');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || '';

        let notesLastFocusedElement = null;
        let deleteLastFocusedElement = null;
        let deleteUrl = null;

        configureToastr();

        const flashSuccess = @json(session('success'));
        const flashError = @json(session('error'));

        if (flashSuccess) {
            notify('success', flashSuccess);
        }

        if (flashError) {
            notify('error', flashError);
        }

        document.querySelectorAll('[data-open-notes-modal]').forEach(button => {
            button.addEventListener('click', event => {
                notesLastFocusedElement = event.currentTarget;
                openModal(notesModal);
                clearNotesError();

                setTimeout(() => {
                    notesInput?.focus();
                    notesInput?.setSelectionRange(
                        notesInput.value.length,
                        notesInput.value.length
                    );
                }, 50);
            });
        });

        document.querySelectorAll('[data-close-notes-modal]').forEach(button => {
            button.addEventListener('click', () => {
                closeModal(notesModal, notesLastFocusedElement);
            });
        });

        notesInput?.addEventListener('input', () => {
            notesCount.textContent = notesInput.value.length;
            clearNotesError();
        });

        notesForm?.addEventListener('submit', async event => {
            event.preventDefault();

            if (saveNotesButton.disabled) {
                return;
            }

            setNotesLoading(true);
            clearNotesError();

            try {
                const response = await fetch(notesForm.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(notesForm)
                });

                const data = await readJsonResponse(response);

                if (!response.ok) {
                    const validationMessage = firstValidationMessage(data?.errors);

                    throw new Error(
                        validationMessage
                        || data?.message
                        || 'Unable to save the institution notes.'
                    );
                }

                const savedNotes = String(data?.notes ?? '').trim();

                updateNotesDisplay(savedNotes);
                closeModal(notesModal, notesLastFocusedElement);
                notify(
                    'success',
                    data?.message || 'Institution notes updated successfully.'
                );
            } catch (error) {
                showNotesError(
                    error.message || 'Unable to save the institution notes.'
                );
            } finally {
                setNotesLoading(false);
            }
        });

        document.querySelectorAll('[data-delete-institution]').forEach(button => {
            button.addEventListener('click', event => {
                const trigger = event.currentTarget;

                deleteLastFocusedElement = trigger;
                deleteUrl = trigger.dataset.deleteUrl || null;
                deleteName.textContent =
                    trigger.dataset.institutionName || 'this institution';

                openModal(deleteModal);
            });
        });

        document.querySelectorAll('[data-close-delete-modal]').forEach(button => {
            button.addEventListener('click', () => {
                closeModal(deleteModal, deleteLastFocusedElement);
            });
        });

        confirmDeleteButton?.addEventListener('click', async () => {
            if (!deleteUrl || confirmDeleteButton.disabled) {
                return;
            }

            setDeleteLoading(true);

            try {
                const response = await fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await readJsonResponse(response);

                if (!response.ok) {
                    throw new Error(
                        data?.message || 'Unable to delete the institution.'
                    );
                }

                closeModal(deleteModal, null);
                notify(
                    'success',
                    data?.message || 'Institution deleted successfully.'
                );

                setTimeout(() => {
                    window.location.href = @json(route('institutions.index'));
                }, 700);
            } catch (error) {
                closeModal(deleteModal, deleteLastFocusedElement);
                notify(
                    'error',
                    error.message || 'Unable to delete the institution.'
                );
            } finally {
                setDeleteLoading(false);
            }
        });

        document.addEventListener('keydown', event => {
            if (event.key !== 'Escape') {
                return;
            }

            if (!notesModal.classList.contains('hidden')) {
                closeModal(notesModal, notesLastFocusedElement);
                return;
            }

            if (!deleteModal.classList.contains('hidden')) {
                closeModal(deleteModal, deleteLastFocusedElement);
            }
        });

        function openModal(modal) {
            if (!modal) {
                return;
            }

            modal.inert = false;
            modal.removeAttribute('inert');
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modal, focusTarget) {
            if (!modal) {
                return;
            }

            if (
                document.activeElement
                && modal.contains(document.activeElement)
            ) {
                document.activeElement.blur();
            }

            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            modal.setAttribute('inert', '');
            modal.inert = true;
            document.body.style.overflow = '';

            if (focusTarget instanceof HTMLElement) {
                setTimeout(() => focusTarget.focus(), 0);
            }
        }

        function setNotesLoading(isLoading) {
            saveNotesButton.disabled = isLoading;
            saveNotesButton.innerHTML = isLoading
                ? '<i class="fa-solid fa-spinner fa-spin"></i><span>Saving Notes</span>'
                : '<i class="fa-solid fa-floppy-disk"></i><span>Save Notes</span>';
        }

        function setDeleteLoading(isLoading) {
            confirmDeleteButton.disabled = isLoading;
            confirmDeleteButton.innerHTML = isLoading
                ? '<i class="fa-solid fa-spinner fa-spin"></i><span>Deleting</span>'
                : '<i class="fa-solid fa-trash-can"></i><span>Delete Institution</span>';
        }

        function updateNotesDisplay(notes) {
            notesInput.value = notes;
            notesCount.textContent = notes.length;

            if (notes) {
                notesText.textContent = notes;
                notesText.classList.remove('hidden');
                notesEmpty.classList.add('hidden');
                notesActionLabel.textContent = 'Edit Notes';

                const actionIcon = document.querySelector(
                    '[data-open-notes-modal] i'
                );

                if (actionIcon) {
                    actionIcon.className = 'fa-solid fa-pen';
                }

                return;
            }

            notesText.textContent = '';
            notesText.classList.add('hidden');
            notesEmpty.classList.remove('hidden');
            notesActionLabel.textContent = 'Add Notes';

            const actionIcon = document.querySelector(
                '[data-open-notes-modal] i'
            );

            if (actionIcon) {
                actionIcon.className = 'fa-solid fa-plus';
            }
        }

        function showNotesError(message) {
            notesError.textContent = message;
            notesError.classList.remove('hidden');
        }

        function clearNotesError() {
            notesError.textContent = '';
            notesError.classList.add('hidden');
        }

        async function readJsonResponse(response) {
            const contentType = response.headers.get('content-type') || '';

            if (!contentType.includes('application/json')) {
                return null;
            }

            return response.json();
        }

        function firstValidationMessage(errors) {
            if (!errors || typeof errors !== 'object') {
                return null;
            }

            for (const value of Object.values(errors)) {
                if (Array.isArray(value) && value.length > 0) {
                    return String(value[0]);
                }

                if (value) {
                    return String(value);
                }
            }

            return null;
        }

        function configureToastr() {
            if (!window.toastr) {
                return;
            }

            window.toastr.options = {
                ...window.toastr.options,
                closeButton: true,
                progressBar: true,
                newestOnTop: true,
                preventDuplicates: true,
                positionClass: 'toast-top-right',
                timeOut: 3500,
                extendedTimeOut: 1000,
                showDuration: 200,
                hideDuration: 200,
                showMethod: 'fadeIn',
                hideMethod: 'fadeOut'
            };
        }

        function notify(type, message) {
            if (
                window.toastr
                && typeof window.toastr[type] === 'function'
            ) {
                window.toastr[type](message);
                return;
            }

            showFallbackToast(type, message);
        }

        function showFallbackToast(type, message) {
            const container = document.getElementById(
                'institutionToastContainer'
            );

            if (!container) {
                return;
            }

            const isSuccess = type === 'success';
            const toast = document.createElement('div');

            toast.className = [
                'pointer-events-auto flex w-full max-w-sm translate-x-4 items-start gap-3',
                'rounded-xl border bg-white px-4 py-3 opacity-0 shadow-lg',
                'transition duration-200',
                isSuccess ? 'border-green-200' : 'border-red-200'
            ].join(' ');

            toast.innerHTML = `
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${
                    isSuccess
                        ? 'bg-green-50 text-green-600'
                        : 'bg-red-50 text-red-600'
                }">
                    <i class="fa-solid ${
                        isSuccess
                            ? 'fa-circle-check'
                            : 'fa-circle-exclamation'
                    }"></i>
                </span>

                <span class="min-w-0 flex-1 text-sm font-medium text-gray-700">
                    ${escapeHtml(message)}
                </span>

                <button type="button"
                    class="mt-0.5 text-gray-400 transition hover:text-gray-600"
                    aria-label="Close notification">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            `;

            const removeToast = () => {
                toast.classList.add('translate-x-4', 'opacity-0');

                setTimeout(() => toast.remove(), 200);
            };

            toast.querySelector('button')?.addEventListener(
                'click',
                removeToast
            );

            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.remove('translate-x-4', 'opacity-0');
            });

            setTimeout(removeToast, 3500);
        }

        function escapeHtml(value) {
            const element = document.createElement('div');
            element.textContent = String(value ?? '');
            return element.innerHTML;
        }
    });
</script>
