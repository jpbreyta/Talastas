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
            assignments.querySelectorAll('[data-institution-row]').forEach(row => {
                row.hidden = selectedBarangays.size > 0 && !selectedBarangays.has(row.dataset.barangayId);
            });
        };
        role.addEventListener('change', update);
        assignments.querySelectorAll('[data-barangay-filter]').forEach(input => input.addEventListener('change', updateInstitutionFilter));
        update();
        updateInstitutionFilter();
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
