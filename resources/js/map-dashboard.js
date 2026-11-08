export const numericMetric = value => value !== null && value !== undefined && value !== '' && Number.isFinite(Number(value)) ? Number(value) : null;
export const formatMetric = value => numericMetric(value) === null ? 'No calculated data' : `${Number(value).toFixed(1)}%`;

export function initializeMapDashboard() {
    const dashboard = document.querySelector('[data-dashboard-endpoint]');
    if (!dashboard) return;
    const create = (tag, text, className = '') => {
        const element = document.createElement(tag);
        element.textContent = text;
        element.className = className;
        return element;
    };
    const status = document.getElementById('mapDashboardStatus');
    const planning = document.getElementById('mapPlanningInsights');
    async function load() {
        status.textContent = 'Loading recorded assessment data…';
        try {
            const response = await fetch(dashboard.dataset.dashboardEndpoint, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('Dashboard unavailable');
            const payload = await response.json();
            if (!payload.success || !payload.summary) throw new Error('Invalid dashboard response');
            const summary = payload.summary;
            dashboard.querySelectorAll('[data-dashboard-metric]').forEach(element => {
                const key = element.dataset.dashboardMetric;
                element.textContent = key === 'institution_count' ? Number(summary[key] ?? 0).toLocaleString() : formatMetric(summary[key]);
                element.classList.toggle('has-value', numericMetric(summary[key]) !== null);
            });
            status.textContent = "";
            status.hidden = true;
            const badge = document.getElementById('mapAnalysisBadge');
            badge.textContent = numericMetric(summary.average_gap_score) === null ? 'No completed analysis yet' : '';
            badge.hidden = numericMetric(summary.average_gap_score) !== null;
            planning.replaceChildren();
            const actions = (payload.prescription?.actions || []).filter(action => action.source === 'AI recommendation');
            if (actions.length) {
                const list = create('ol', '', 'map-ai-priorities');
                actions.forEach((action, index) => {
                    const item = create('li');
                    const heading = create('div', '', 'map-ai-action-heading');
                    heading.append(create('span', String(index + 1), 'map-ai-action-number'), create('p', action.action));
                    const source = create('small', '', 'map-ai-action-source');
                    source.append(create('span', action.institution), create('span', action.source, 'map-ai-source-badge'));
                    item.append(heading, source);
                    list.append(item);
                });
                planning.append(list);
            } else {
                planning.append(create('p', 'No stored AI-generated recommendations are available for these results.'));
            }
        } catch {
            status.hidden = false;
            dashboard.querySelectorAll('[data-dashboard-metric]').forEach(element => { element.textContent = 'Unavailable'; });
            status.replaceChildren(create('span', 'Assessment data could not be loaded. '));
            const retry = create('button', 'Retry', 'dashboard-retry');
            retry.type = 'button'; retry.addEventListener('click', load); status.append(retry);
            planning.textContent = 'AI recommendations could not be loaded. Retry loading the results.';
        }
    }
    load();
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeMapDashboard);
    else initializeMapDashboard();
}
