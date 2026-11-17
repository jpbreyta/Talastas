<script>
document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('[data-accounts-table]');
    if (!page) return;
    let active = null;
    function closeMenu(restoreFocus = false) {
        if (!active) return;
        const { button, menu, parent } = active;
        menu.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        parent.appendChild(menu);
        active = null;
        if (restoreFocus) button.focus();
    }
    page.addEventListener('click', event => {
        const button = event.target.closest('[data-user-actions-toggle]');
        if (!button) return;
        if (active?.button === button) { closeMenu(true); return; }
        closeMenu();
        const menu = document.getElementById(button.getAttribute('aria-controls'));
        if (!menu) return;
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
    document.addEventListener('click', event => {
        if (!active) return;
        if (active.menu.contains(event.target)) {
            if (event.target.closest('[role="menuitem"]')) closeMenu();
        } else if (!active.button.contains(event.target)) closeMenu();
    });
    document.addEventListener('keydown', event => {
        if (!active) return;
        if (event.key === 'Escape') { event.preventDefault(); closeMenu(true); }
        else if (event.key === 'Tab') closeMenu(true);
        else if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
            event.preventDefault();
            const items = [...active.menu.querySelectorAll('[role="menuitem"]')];
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

    const search = document.getElementById('userSearch');
    const clear = page.querySelector('[data-users-clear-search]');
    search.addEventListener('input', () => { clear.hidden = !search.value; });
    clear.addEventListener('click', () => { search.value = ''; search.form.requestSubmit(); });

    const dialog = document.getElementById('deleteUserAccountDialog');
    const confirm = dialog.querySelector('[data-submit-account-delete]');
    let pendingForm = null, trigger = null;
    const submitted = new Set();
    function openConfirmation(form) {
        if (dialog.open || submitted.has(form)) return;
        pendingForm = form;
        const menu = form.closest('[data-user-actions-menu]');
        trigger = menu ? document.querySelector(`[data-user-actions-toggle][aria-controls="${menu.id}"]`) : document.activeElement;
        dialog.querySelector('[data-delete-account-name]').textContent = form.dataset.userName;
        dialog.querySelector('[data-delete-account-email]').textContent = form.dataset.userEmail;
        closeMenu();
        dialog.showModal();
        dialog.querySelector('[data-cancel-account-delete]').focus();
    }
    page.querySelectorAll('[data-account-delete-form]').forEach(form => {
        form.querySelector('[data-confirm-user-delete]').addEventListener('click', () => openConfirmation(form));
        form.addEventListener('submit', event => {
            if (form.dataset.deleteConfirmed !== 'true') { event.preventDefault(); openConfirmation(form); }
            else if (submitted.has(form)) event.preventDefault();
            else { submitted.add(form); confirm.disabled = true; }
        });
    });
    dialog.querySelector('[data-cancel-account-delete]').addEventListener('click', () => dialog.close());
    confirm.addEventListener('click', () => {
        if (!pendingForm || confirm.disabled) return;
        const form = pendingForm;
        form.dataset.deleteConfirmed = 'true';
        dialog.close();
        form.requestSubmit();
    });
    dialog.addEventListener('close', () => { pendingForm = null; trigger?.focus(); });
    window.addEventListener('pageshow', () => {
        submitted.clear(); confirm.disabled = false;
        page.querySelectorAll('[data-account-delete-form]').forEach(form => { delete form.dataset.deleteConfirmed; });
    });
});
</script>
