export const notificationOptions = Object.freeze({ timeOut: 4000, errorTimeOut: 6000, maxVisible: 3 });

const appearances = {
    success: ['Success', 'fa-circle-check'],
    deleted: ['Deleted', 'fa-trash'],
    error: ['Error', 'fa-circle-exclamation'],
    warning: ['Warning', 'fa-triangle-exclamation'],
    info: ['Information', 'fa-circle-info'],
};

export function notificationType(type, message) {
    if (type === 'delete') return 'deleted';
    // Existing controllers intentionally flash successful deletes as success.
    if (type === 'success' && /\b(?:deleted|removed) successfully\b/i.test(String(message))) return 'deleted';
    return Object.hasOwn(appearances, type) ? type : 'info';
}

export function installNotifications() {
    const active = new Map();
    function notify(requestedType, message) {
        const container = document.getElementById('talastasToastContainer');
        if (!container || !message) return;
        const type = notificationType(requestedType, message);
        const key = `${type}:${message}`;
        if (active.has(key)) return;
        const [title, icon] = appearances[type];
        const toast = document.createElement('div');
        toast.className = `talastas-toast talastas-toast--${type}`;
        // Only static markup is interpolated. Messages are always plain text.
        toast.innerHTML = `<span class="talastas-toast__icon" aria-hidden="true"><i class="fa-solid ${icon}"></i></span>
            <div class="talastas-toast__content"><strong>${title}</strong><p></p></div>
            <button type="button" class="talastas-toast__close" aria-label="Close notification"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>`;
        toast.querySelector('p').textContent = String(message);
        const duration = type === 'error' ? notificationOptions.errorTimeOut : notificationOptions.timeOut;
        let timer;
        const pause = () => clearTimeout(timer);
        const remove = () => {
            pause();
            toast.remove();
            active.delete(key);
        };
        const resume = () => {
            pause();
            if (!toast.matches(':hover') && !toast.contains(document.activeElement)) timer = setTimeout(remove, duration);
        };
        toast.querySelector('button').addEventListener('click', remove);
        toast.addEventListener('mouseenter', pause);
        toast.addEventListener('mouseleave', resume);
        toast.addEventListener('focusin', pause);
        toast.addEventListener('focusout', () => setTimeout(resume, 0));
        if (active.size >= notificationOptions.maxVisible) {
            const oldest = [...active.values()].find((item) => !item.toast.contains(document.activeElement));
            oldest?.remove();
        }
        active.set(key, { toast, remove });
        container.querySelector(`[data-toast-region="${type === 'error' ? 'assertive' : 'polite'}"]`).appendChild(toast);
        resume();
    }
    window.TalastasToast = { notify };

    const ready = () => {
        const container = document.getElementById('talastasToastContainer');
        if (!container) return;
        let messages = [];
        try { messages = JSON.parse(container.dataset.notifications || '[]'); } catch { /* Ignore malformed flash data. */ }
        try {
            const stored = sessionStorage.getItem('institutionToast');
            if (stored) {
                sessionStorage.removeItem('institutionToast');
                messages.push(JSON.parse(stored));
            }
        } catch { /* Storage may be unavailable; flash messages still work. */ }
        messages.forEach((item) => notify(item.type || 'success', item.message));
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready, { once: true });
    else ready();
}
