// Portal the existing row actions outside the table's scrolling/clipping wrappers.
export function installInstitutionActions() {
    const page = document.querySelector('[data-institution-list]');
    if (!page || page.dataset.actionsInstalled) return;
    page.dataset.actionsInstalled = 'true';
    let active = null;

    function close(restoreFocus = false) {
        if (!active) return;
        const { button, menu, parent } = active;
        menu.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        parent.appendChild(menu);
        active = null;
        if (restoreFocus && button.isConnected) button.focus();
    }

    function position() {
        if (!active) return;
        const { button, menu } = active;
        const rect = button.getBoundingClientRect();
        const width = menu.offsetWidth;
        const height = menu.offsetHeight;
        menu.style.left = `${Math.max(8, Math.min(rect.right - width, window.innerWidth - width - 8))}px`;
        const below = rect.bottom + 4;
        menu.style.top = `${Math.max(8, Math.min(below + height <= window.innerHeight - 8 ? below : rect.top - height - 4, window.innerHeight - height - 8))}px`;
    }

    page.addEventListener('click', (event) => {
        const button = event.target.closest('[data-institution-actions-toggle]');
        if (!button) return;
        event.stopPropagation();
        if (active?.button === button) return close(true);
        close();
        const menu = button.parentElement.querySelector('[data-institution-actions-menu]');
        if (!menu) return;
        active = { button, menu, parent: menu.parentElement };
        document.body.appendChild(menu);
        menu.hidden = false;
        button.setAttribute('aria-expanded', 'true');
        position();
        menu.querySelector('[role="menuitem"]')?.focus();
    });

    document.addEventListener('click', (event) => {
        if (!active) return;
        if (active.menu.contains(event.target)) {
            if (event.target.closest('[role="menuitem"]')) close(true);
            // Preserve the existing document-level delete confirmation handler.
            return;
        }
        if (!active.button.contains(event.target)) close();
    });

    document.addEventListener('keydown', (event) => {
        if (!active) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            close(true);
        } else if (event.key === 'Tab') {
            close(true);
        } else if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
            event.preventDefault();
            const items = [...active.menu.querySelectorAll('[role="menuitem"]')];
            const current = items.indexOf(document.activeElement);
            const index = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1
                : (current + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
            items[index]?.focus();
        }
    });
    document.addEventListener('focusin', (event) => {
        if (active && !active.menu.contains(event.target) && event.target !== active.button) close();
    });
    window.addEventListener('resize', () => close());
    document.addEventListener('scroll', (event) => {
        if (active && !active.menu.contains(event.target)) close();
    }, true);
    // Async search/pagination can replace the originating row while a menu is open.
    new MutationObserver(() => {
        if (active && !active.button.isConnected) close();
    }).observe(page, { childList: true, subtree: true });
}
