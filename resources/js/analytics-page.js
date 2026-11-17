export function installAnalyticsPage() {
    const page = document.querySelector('.analytics-dashboard');
    if (!page || page.dataset.refinementsInstalled) return;
    page.dataset.refinementsInstalled = 'true';
    page.addEventListener('click', event => {
        const button = event.target.closest('[data-analysis-row-toggle]');
        if (!button) return;
        const details = document.getElementById(button.getAttribute('aria-controls'));
        if (!details) return;
        const expanded = button.getAttribute('aria-expanded') !== 'true';
        button.setAttribute('aria-expanded', String(expanded));
        details.hidden = !expanded;
        button.querySelector('i')?.classList.toggle('fa-chevron-down', expanded);
        button.querySelector('i')?.classList.toggle('fa-chevron-right', !expanded);
    });
    const form = page.querySelector('[data-analytics-selection]');
    if (!form) return;
    const from = form.elements.date_from, through = form.elements.date_to;
    const error = form.querySelector('[data-analytics-date-error]');
    const validateDates = () => {
        const invalid = Boolean(from.value && through.value && from.value > through.value);
        through.setCustomValidity(invalid ? 'Through date must be on or after From date.' : '');
        error.hidden = !invalid;
        through.setAttribute('aria-invalid', String(invalid));
        return !invalid;
    };
    from.addEventListener('input', validateDates);
    through.addEventListener('input', validateDates);
    form.addEventListener('submit', event => {
        if (!validateDates()) { event.preventDefault(); through.reportValidity(); through.focus(); }
    });
    validateDates();
}
