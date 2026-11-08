<script>
document.addEventListener('DOMContentLoaded', () => {
    const usersLink = Array.from(document.querySelectorAll('#talastasSidebar a')).find(link => link.href === @json(route('admin.users.index')));
    usersLink?.setAttribute('aria-current', 'page');
    document.querySelectorAll('[data-role-form]').forEach(form => {
        const role = form.querySelector('[data-user-role]');
        const assignments = form.querySelector('[data-editor-assignments]');
        if (!role || !assignments) return;
        const update = () => {
            assignments.hidden = role.value !== 'editor';
            assignments.disabled = role.value !== 'editor';
        };
        const updateInstitutionFilter = () => {
            const selectedBarangays = new Set(Array.from(assignments.querySelectorAll('[data-barangay-filter]:checked'), input => input.value));
            if (selectedBarangays.size === 0) {
                assignments.querySelectorAll('[data-institution-row] input:checked').forEach(input => {
                    const barangayId = input.closest('[data-institution-row]')?.dataset.barangayId;
                    if (barangayId) selectedBarangays.add(barangayId);
                });
            }
            const rows = Array.from(assignments.querySelectorAll('[data-institution-row]'));
            let visibleCount = 0;
            rows.forEach(row => {
                row.hidden = selectedBarangays.size === 0 || !selectedBarangays.has(row.dataset.barangayId);
                if (!row.hidden) visibleCount++;
            });
            const selectBarangay = assignments.querySelector('[data-institutions-select-barangay]');
            const noMatch = assignments.querySelector('[data-institutions-no-match]');
            if (selectBarangay) selectBarangay.hidden = selectedBarangays.size > 0;
            if (noMatch) noMatch.hidden = selectedBarangays.size === 0 || visibleCount > 0;
        };
        role.addEventListener('change', update);
        assignments.querySelectorAll('[data-barangay-filter]').forEach(input => input.addEventListener('change', updateInstitutionFilter));
        update();
        updateInstitutionFilter();
    });
    document.querySelectorAll('[data-invitation-action-form]').forEach(form => {
        const radios = Array.from(form.querySelectorAll('[data-invitation-action]'));
        const password = form.querySelector('[data-invitation-password]');
        const submit = form.querySelector('[data-invitation-submit]');
        const label = form.querySelector('[data-invitation-submit-label]');
        const confirmations = Array.from(form.querySelectorAll('[data-invitation-confirmation]'));
        const resetButton = () => {
            submit.disabled = true;
            submit.setAttribute('aria-disabled', 'true');
            submit.className = 'users-button cursor-not-allowed opacity-50';
        };
        const update = (resetConfirmation = false) => {
            const action = radios.find(radio => radio.checked)?.value;
            confirmations.forEach(row => {
                const checkbox = row.querySelector('[data-action-confirmation]');
                const active = row.dataset.invitationConfirmation === action;
                row.hidden = !active;
                checkbox.disabled = !active;
                if (!active || resetConfirmation) checkbox.checked = false;
            });
            const activeConfirmation = confirmations.find(row => row.dataset.invitationConfirmation === action)?.querySelector('[data-action-confirmation]');
            const confirmed = Boolean(action && activeConfirmation?.checked);
            const resend = action === 'resend';
            if (action) {
                form.action = resend ? form.dataset.resendUrl : form.dataset.revokeUrl;
                label.textContent = resend ? 'Resend invitation' : 'Revoke invitation';
                submit.className = confirmed
                    ? (resend ? submit.dataset.resendClass : submit.dataset.revokeClass)
                    : `${resend ? submit.dataset.resendClass : submit.dataset.revokeClass} cursor-not-allowed opacity-50`;
            } else {
                label.textContent = 'Select an action';
                resetButton();
            }
            password.disabled = !confirmed;
            password.setAttribute('aria-disabled', String(!confirmed));
            password.classList.toggle('cursor-not-allowed', !confirmed);
            password.classList.toggle('bg-gray-100', !confirmed);
            password.classList.toggle('text-gray-400', !confirmed);
            password.classList.toggle('bg-white', confirmed);
            password.classList.toggle('text-gray-900', confirmed);
            submit.disabled = !confirmed;
            submit.setAttribute('aria-disabled', String(!confirmed));
        };
        radios.forEach(radio => radio.addEventListener('change', () => update(true)));
        confirmations.forEach(row => row.querySelector('[data-action-confirmation]')?.addEventListener('change', () => update()));
        form.addEventListener('submit', event => {
            const action = radios.find(radio => radio.checked)?.value;
            const confirmation = confirmations.find(row => row.dataset.invitationConfirmation === action)?.querySelector('[data-action-confirmation]');
            if (!action || !confirmation?.checked || password.disabled || !password.value) {
                event.preventDefault();
                update();
                if (!action) radios[0]?.focus();
                else if (!confirmation?.checked) confirmation?.focus();
                else password.focus();
            }
        });
        update();
    });
    let editorTrigger = null;
    let previousOverflow = '';
    function openEditor(dialog, trigger) {
        if (!dialog || dialog.open) return;
        editorTrigger = trigger;
        previousOverflow = document.documentElement.style.overflow;
        dialog.showModal();
        document.documentElement.style.overflow = 'hidden';
    }
    document.querySelectorAll('[data-open-user-editor]').forEach(button => {
        button.addEventListener('click', () => openEditor(document.getElementById(button.dataset.openUserEditor), button));
    });
    document.querySelectorAll('[data-user-editor]').forEach(dialog => {
        dialog.querySelectorAll('[data-close-user-editor]').forEach(button => button.addEventListener('click', () => dialog.close()));
        dialog.addEventListener('keydown', event => {
            if (event.key !== 'Tab') return;
            const controls = Array.from(dialog.querySelectorAll('a[href], button, input, select, textarea, summary, [tabindex]'))
                .filter(control => !control.matches(':disabled') && control.tabIndex >= 0 && control.checkVisibility());
            const first = controls[0];
            const last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        });
        dialog.addEventListener('click', event => {
            if (event.target !== dialog) return;
            const bounds = dialog.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
        });
        dialog.addEventListener('close', () => {
            document.documentElement.style.overflow = previousOverflow;
            editorTrigger?.focus();
        });
    });
    const invalidEditor = document.querySelector('[data-reopen-user-editor]')?.closest('[data-user-editor]');
    if (invalidEditor) openEditor(invalidEditor, document.querySelector(`[data-open-user-editor="${invalidEditor.id}"]`));
});
</script>
