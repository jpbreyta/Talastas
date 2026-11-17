<script>
document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('[data-invitations-page]');
    if (!page) return;
    const dialog = document.getElementById('invitationActionDialog');
    const form = dialog.querySelector('[data-invitation-action-form]');
    const password = document.getElementById('invitationActionPassword');
    const confirmed = document.getElementById('invitationActionConfirmed');
    const submit = form.querySelector('[data-invitation-submit]');
    const cancel = form.querySelector('[data-cancel-invitation-action]');
    let active = null, trigger = null, submitted = false, selectedAction = null;
    function closeMenu(restoreFocus = false) {
        if (!active) return;
        const { button, menu, parent } = active;
        menu.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        parent.appendChild(menu);
        active = null;
        if (restoreFocus) button.focus();
    }
    page.querySelectorAll('[data-invite-menu-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            if (active?.button === button) { closeMenu(true); return; }
            closeMenu();
            const menu = document.getElementById(button.getAttribute('aria-controls'));
            active = { button, menu, parent: menu.parentElement };
            document.body.appendChild(menu);
            menu.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            const rect = button.getBoundingClientRect();
            const { offsetWidth: width, offsetHeight: height } = menu;
            menu.style.left = `${Math.max(8, Math.min(rect.right - width, window.innerWidth - width - 8))}px`;
            menu.style.top = `${Math.max(8, Math.min(rect.bottom + height + 4 <= window.innerHeight - 8 ? rect.bottom + 4 : rect.top - height - 4, window.innerHeight - height - 8))}px`;
            menu.querySelector('[role="menuitem"]')?.focus();
        });
    });
    function openConfirmation(item) {
        if (dialog.open || submitted || !['resend', 'revoke'].includes(item.dataset.inviteAction)) return;
        const menu = item.closest('[role="menu"]');
        trigger = document.querySelector(`[data-invite-menu-toggle][aria-controls="${menu.id}"]`);
        closeMenu();
        form.reset();
        password.value = ''; confirmed.checked = false;
        selectedAction = item.dataset;
        const errors = form.querySelector('[data-action-errors]');
        if (errors) errors.hidden = errors.dataset.errorInviteId !== selectedAction.inviteId;
        const resend = selectedAction.inviteAction === 'resend';
        form.action = selectedAction.actionUrl;
        form.querySelector('[data-action-id]').value = selectedAction.inviteId;
        form.querySelector('[data-action-kind]').value = selectedAction.inviteAction;
        form.querySelector('[data-action-title]').textContent = resend ? 'Resend Invitation?' : 'Revoke Invitation?';
        form.querySelector('[data-action-recipient]').textContent = selectedAction.recipientName;
        form.querySelector('[data-action-email]').textContent = selectedAction.recipientEmail;
        form.querySelector('[data-action-description]').textContent = resend
            ? 'A replacement invitation will be sent. Older links and setup sessions will stop working.'
            : 'This invitation and its link will be invalidated.';
        form.querySelector('[data-action-confirmation-text]').textContent = resend
            ? 'I confirm sending a replacement link; older links will stop working.'
            : 'I confirm revoking this invitation and invalidating its link.';
        submit.textContent = resend ? 'Resend Invitation' : 'Revoke Invitation';
        submit.className = resend ? 'users-button' : 'users-button users-button-danger';
        dialog.showModal();
        cancel.focus();
    }
    document.addEventListener('click', event => {
        const action = event.target.closest('[data-invite-action]');
        if (action) { openConfirmation(action); return; }
        if (active && !active.menu.contains(event.target) && !active.button.contains(event.target)) closeMenu();
    });
    document.addEventListener('keydown', event => {
        if (!active) return;
        if (event.key === 'Escape') { event.preventDefault(); closeMenu(true); }
        else if (event.key === 'Tab') closeMenu(true);
        else if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
            event.preventDefault();
            const items = Array.from(active.menu.querySelectorAll('[role="menuitem"]'));
            const current = items.indexOf(document.activeElement);
            const index = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1
                : (current + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
            items[index]?.focus();
        }
    });
    document.addEventListener('focusin', event => {
        if (active && !active.menu.contains(event.target) && event.target !== active.button) closeMenu();
    });
    window.addEventListener('resize', () => closeMenu());
    document.addEventListener('scroll', event => { if (active && !active.menu.contains(event.target)) closeMenu(); }, true);
    cancel.addEventListener('click', () => { if (!submitted) dialog.close(); });
    dialog.addEventListener('cancel', event => { if (submitted) event.preventDefault(); });
    dialog.addEventListener('keydown', event => {
        if (event.key !== 'Tab') return;
        const controls = Array.from(form.querySelectorAll('input:not([type="hidden"]), button')).filter(control => !control.disabled);
        const first = controls[0], last = controls[controls.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    });
    dialog.addEventListener('close', () => {
        form.reset(); password.value = ''; confirmed.checked = false;
        selectedAction = null;
        trigger?.focus();
    });
    form.addEventListener('submit', event => {
        if (submitted || !dialog.open || !selectedAction || !confirmed.checked || !password.value) {
            event.preventDefault();
            if (!confirmed.checked) confirmed.focus();
            else if (!password.value) password.focus();
            return;
        }
        submitted = true;
        submit.disabled = true; cancel.disabled = true;
        form.setAttribute('aria-busy', 'true');
    });
    const create = page.querySelector('[data-invitation-create-form]');
    const createSubmit = create.querySelector('button[type="submit"]');
    const initiallyDisabled = createSubmit.disabled;
    let creating = false;
    create.addEventListener('submit', event => {
        if (creating || initiallyDisabled) { event.preventDefault(); return; }
        creating = true; createSubmit.disabled = true; create.setAttribute('aria-busy', 'true');
    });
    window.addEventListener('pageshow', () => {
        submitted = false; creating = false;
        submit.disabled = false; cancel.disabled = false; createSubmit.disabled = initiallyDisabled;
        form.removeAttribute('aria-busy'); create.removeAttribute('aria-busy');
        password.value = ''; confirmed.checked = false;
        create.querySelector('input[name="current_password"]').value = '';
        if (dialog.open) dialog.close();
        const item = Array.from(page.querySelectorAll('[data-invite-action]')).find(item =>
            item.dataset.inviteId === dialog.dataset.reopenId && item.dataset.inviteAction === dialog.dataset.reopenAction);
        if (item) openConfirmation(item);
        delete dialog.dataset.reopenId; delete dialog.dataset.reopenAction;
    });
});
</script>
