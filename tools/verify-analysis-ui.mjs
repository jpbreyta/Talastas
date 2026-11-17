import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

let checks = 0;
const check = (value, message) => { assert.ok(value, message); checks++; };
const element = (value = '') => ({ value, attrs: {}, events: {},
    addEventListener(type, handler) { this.events[type] = handler; },
    setAttribute(key, value) { this.attrs[key] = value; }, getAttribute(key) { return this.attrs[key]; },
    setCustomValidity(message) { this.validationMessage = message; },
    reportValidity() { this.reported = true; }, focus() { this.focused = true; },
});
const from = element('2026-11-01'), through = element('2026-11-30'), error = element();
const form = element(); form.elements = { date_from: from, date_to: through };
form.querySelector = () => error;
const page = element(); page.dataset = {}; page.querySelector = () => form;
const row = { hidden: true }, button = element();
button.attrs = { 'aria-controls': 'analysis-details-1', 'aria-expanded': 'false' };
const iconClasses = new Set(['fa-chevron-right']);
button.querySelector = () => ({ classList: { toggle: (name, enabled) => enabled ? iconClasses.add(name) : iconClasses.delete(name) } });
const context = vm.createContext({ document: { querySelector: () => page, getElementById: id => id === 'analysis-details-1' ? row : null } });
vm.runInContext(fs.readFileSync('resources/js/analytics-page.js', 'utf8').replace('export ', ''), context);
context.installAnalyticsPage();
check(error.hidden && !through.validationMessage, 'Valid initial dates have no error');
page.events.click({ target: { closest: () => button } });
check(!row.hidden && button.attrs['aria-expanded'] === 'true' && iconClasses.has('fa-chevron-down'), 'Disclosure opens only its controlled row and updates accessibility state');
page.events.click({ target: { closest: () => button } });
check(row.hidden && button.attrs['aria-expanded'] === 'false' && iconClasses.has('fa-chevron-right'), 'Disclosure closes and restores the chevron');
const firstHandler = page.events.click; context.installAnalyticsPage();
check(page.events.click === firstHandler, 'Repeated installation does not duplicate handlers');
from.value = '2026-12-01'; from.events.input();
check(!error.hidden && through.attrs['aria-invalid'] === 'true' && Boolean(through.validationMessage), 'Reversed dates show an accessible validation error');
let prevented = false;
form.events.submit({ preventDefault() { prevented = true; } });
check(prevented && through.reported && through.focused, 'Invalid submission is blocked and focuses the invalid date');
from.value = '2026-11-30'; from.events.input();
check(error.hidden && !through.validationMessage && through.attrs['aria-invalid'] === 'false', 'Inclusive same-day ranges are accepted and clear errors');
from.value = ''; from.events.input();
check(error.hidden && !through.validationMessage, 'Open-ended reporting dates remain supported');
let validPrevented = false;
form.events.submit({ preventDefault() { validPrevented = true; } });
check(!validPrevented, 'Valid filters retain normal GET submission');
console.log(`PASS: ${checks} analysis disclosure/date checks (mock DOM; no browser).`);
