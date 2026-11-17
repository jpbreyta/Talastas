<x-talastas-loader id="globalLoader" variant="page" :active="false" />
<script>
(() => {
    const overlay = document.getElementById('globalLoader');
    const label = overlay.querySelector('[data-talastas-loader-status]');
    const tasks = new Map();
    let navigating = false;
    let revealTimer = null;
    let blocked = false;
    let background = [];
    let previousFocus = null;
    let previousOverflow = '';

    function blockPage(busy) {
        if (blocked === busy) return;
        blocked = busy;
        if (busy) {
            previousFocus = document.activeElement === document.body ? null : document.activeElement;
            previousOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            background = [...document.body.children].filter(element =>
                element !== overlay && !['SCRIPT', 'STYLE', 'LINK'].includes(element.tagName)
                && !element.hasAttribute('inert')
            );
            background.forEach(element => element.setAttribute('inert', ''));
        } else {
            background.forEach(element => element.removeAttribute('inert'));
            background = [];
            document.body.style.overflow = previousOverflow;
            if ((document.activeElement === overlay || document.activeElement === document.body)
                && previousFocus?.isConnected && !previousFocus.disabled && !previousFocus.closest('[inert]')) {
                previousFocus.focus({ preventScroll: true });
            }
            previousFocus = null;
        }
    }

    function render() {
        const busy = navigating || tasks.size > 0;
        label.textContent = [...tasks.values()].at(-1) || 'Loading content';
        blockPage(busy);
        if (!busy) {
            clearTimeout(revealTimer);
            revealTimer = null;
            overlay.hidden = true;
            overlay.setAttribute('aria-hidden', 'true');
        } else if (overlay.hidden && revealTimer === null) {
            // Delay appearance only, never completion: quick requests do not flash.
            revealTimer = setTimeout(() => {
                revealTimer = null;
                if (!navigating && tasks.size === 0) return;
                overlay.hidden = false;
                overlay.setAttribute('aria-hidden', 'false');
                if (document.activeElement === document.body) overlay.focus({ preventScroll: true });
            }, 150);
        }
    }
    // Preserve token-based lifecycle and idempotent release for overlapping requests.
    window.showGlobalLoader = (message = 'Loading content') => {
        const token = Symbol();
        tasks.set(token, message);
        render();
        return () => window.hideGlobalLoader(token);
    };
    window.hideGlobalLoader = token => {
        if (token) tasks.delete(token);
        else tasks.clear();
        render();
    };
    const currentWindow = target => !target || target.toLowerCase() === '_self';
    const showNavigation = () => { navigating = true; render(); };
    const hideNavigation = () => { navigating = false; render(); };

    // A microtask lets all handlers finish and honor preventDefault() for AJAX.
    document.addEventListener('click', event => {
        const link = event.target.closest?.('a[href]');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        if (link.hasAttribute('download') || link.hasAttribute('data-no-global-loader') || link.dataset.method) return;
        if (!currentWindow(link.getAttribute('target') || document.querySelector('base[target]')?.getAttribute('target'))) return;
        const href = link.getAttribute('href')?.trim();
        if (!href || href.startsWith('#')) return;
        let url;
        try { url = new URL(link.href, location.href); } catch { return; }
        if (!['http:', 'https:'].includes(url.protocol)) return;
        if (url.origin === location.origin && url.pathname === location.pathname && url.search === location.search && url.hash) return;
        queueMicrotask(() => { if (!event.defaultPrevented) showNavigation(); });
    }, true);
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-global-loader')) return;
        const target = event.submitter?.getAttribute('formtarget') ?? form.getAttribute('target') ?? document.querySelector('base[target]')?.getAttribute('target');
        const method = event.submitter?.getAttribute('formmethod') || form.method;
        if (!currentWindow(target) || method.toLowerCase() === 'dialog') return;
        queueMicrotask(() => { if (!event.defaultPrevented) showNavigation(); });
    }, true);
    window.addEventListener('load', hideNavigation, { once: true });
    window.addEventListener('pageshow', event => {
        if (event.persisted) tasks.clear();
        hideNavigation();
    });
})();
</script>
