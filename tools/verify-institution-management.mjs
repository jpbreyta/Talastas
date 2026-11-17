import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

let checks = 0;
const check = (value, message) => { assert.ok(value, message); checks++; };
const source = file => fs.readFileSync(`resources/js/${file}.js`, 'utf8').replace(/^import .*;\r?\n/gm, '').replace(/export /g, '');
let doc;
class Element {
    constructor() {
        this.events = new Map(); this.attrs = new Map(); this.queries = new Map(); this.children = [];
        this.dataset = {}; this.style = {}; this.hidden = false; this.disabled = false; this.checked = false;
        this.isConnected = true; this.value = ''; this.textContent = '';
        const classes = new Set();
        this.classList = { contains: key => classes.has(key), add: (...keys) => keys.forEach(key => classes.add(key)),
            remove: (...keys) => keys.forEach(key => classes.delete(key)),
            toggle: (key, value = !classes.has(key)) => value ? classes.add(key) : classes.delete(key) };
    }
    addEventListener(key, fn) { if (!this.events.has(key)) this.events.set(key, new Set()); this.events.get(key).add(fn); }
    removeEventListener(key, fn) { this.events.get(key)?.delete(fn); }
    emit(key, event = {}) { return Promise.all([...this.events.get(key) || []].map(fn => fn({ target: this, ...event }))); }
    dispatchEvent(event) { return this.emit(event.type, event); }
    setAttribute(key, value) { this.attrs.set(key, value); }
    getAttribute(key) { return this.attrs.get(key) ?? null; }
    hasAttribute(key) { return this.attrs.has(key); }
    removeAttribute(key) { this.attrs.delete(key); }
    toggleAttribute(key, enabled) { enabled ? this.setAttribute(key, '') : this.removeAttribute(key); }
    querySelector(key) { return this.queries.get(key) || null; }
    querySelectorAll(key) { return this.queries.get(key) || []; }
    contains(node) { return node === this || this.children.some(child => child.contains(node)); }
    append(child) { this.children.push(child); child.parentElement = this; return child; }
    focus() { doc.activeElement = this; }
    remove() { this.removed = true; this.isConnected = false; }
    matches(selector) { return this.selector === selector; }
    closest(selector) { return this.closestMap?.[selector] || null; }
}
doc = new Element();
doc.body = new Element();
const shell = doc.body.append(new Element());
const trigger = shell.append(new Element());
const alreadyInert = shell.append(new Element()); alreadyInert.setAttribute('inert', '');
const modal = shell.append(new Element()); modal.classList.add('hidden');
const buttons = {};
for (const id of ['confirmDeleteInstitution', 'cancelDeleteInstitution', 'closeDeleteInstitutionModal',
    'deleteInstitutionModalTitle', 'deleteInstitutionDescription', 'deleteInstitutionModalOverlay']) {
    buttons[id] = modal.append(new Element()); modal.queries.set(`#${id}`, buttons[id]);
}
const confirm = buttons.confirmDeleteInstitution, cancel = buttons.cancelDeleteInstitution, close = buttons.closeDeleteInstitutionModal;
const label = new Element(); confirm.queries.set('span', label);
modal.querySelectorAll = () => [close, cancel, confirm].filter(button => !button.disabled);
doc.getElementById = id => id === 'deleteInstitutionModal' ? modal : id === 'institutionSearch' ? trigger : doc.queries.get(id);
doc.queries.set('meta[name="csrf-token"]', { content: 'fixture-token' });
const notifications = [];
let requests = [], response = async () => ({ ok: true, status: 200, json: async () => ({ success: true, deleted_ids: [1, 2] }) });
const window = { InstitutionModal: { notify: (...args) => notifications.push(args) },
    location: { reload() { window.reloaded = true; } }, TalastasInstitutions: { refresh: async () => true } };
const context = vm.createContext({ document: doc, window, console,
    fetch: async (...args) => { requests.push(args); return response(); },
    MutationObserver: class { constructor(fn) { context.observeSelection = fn; } observe() {} disconnect() { context.disconnected = true; } },
});
vm.runInContext(source('institution-deletion'), context);
const records = [{ id: 1, name: 'School One' }, { id: 2, name: 'Hall Two' }];
const open = (extra = {}) => context.requestInstitutionDeletion({ url: '/institutions/batch-delete', records, trigger, batch: true, ...extra });
let pending = open();
check(requests.length === 0 && doc.activeElement === cancel, 'Opening confirmation sends no request and focuses Cancel');
check(buttons.deleteInstitutionModalTitle.textContent === 'Delete Selected Institutions?' && label.textContent === 'Delete 2 Institutions', 'Bulk title and count are dynamic');
check(trigger.hasAttribute('inert') && alreadyInert.hasAttribute('inert'), 'Background is inert while confirming');
await cancel.emit('click');
check(await pending === null && requests.length === 0, 'Cancel sends no deletion request');
check(doc.activeElement === trigger && !trigger.hasAttribute('inert') && alreadyInert.hasAttribute('inert'), 'Cancel restores focus and previous inert states');
pending = open({ records: [records[0]], batch: false, url: '/institutions/1' });
check(buttons.deleteInstitutionModalTitle.textContent === 'Delete Institution' && buttons.deleteInstitutionDescription.textContent.includes('School One'), 'Single deletion uses the same named confirmation');
await doc.emit('keydown', { key: 'Escape', preventDefault() {}, stopImmediatePropagation() {} });
check(await pending === null, 'Escape cancels confirmation');
pending = open();
close.focus();
await doc.emit('keydown', { key: 'Tab', shiftKey: true, preventDefault() {} });
check(doc.activeElement === confirm, 'Shift Tab wraps to the last dialog button');
await doc.emit('keydown', { key: 'Tab', shiftKey: false, preventDefault() {} });
check(doc.activeElement === close, 'Tab wraps to the first dialog button');
let release;
response = () => new Promise(resolve => { release = resolve; });
const sending = confirm.emit('click');
await confirm.emit('click');
await cancel.emit('click');
check(requests.length === 1 && confirm.disabled && cancel.disabled && context.institutionDeletionOpen(), 'Pending deletion disables controls and rejects duplicate submits/cancel');
check(JSON.parse(requests[0][1].body).institution_ids.join(',') === '1,2'
    && requests[0][1].headers['X-CSRF-TOKEN'] === 'fixture-token', 'Only explicitly selected IDs are sent with CSRF and existing endpoint');
release({ ok: true, status: 200, json: async () => ({ success: true, deleted_ids: [1, 2] }) });
await sending;
check((await pending).deleted_ids.length === 2 && !context.institutionDeletionOpen(), 'Confirmed success releases the dialog and returns backend IDs');
pending = open({ records: [records[0]], batch: false, url: '/institutions/1' });
response = async () => ({ ok: true, status: 200, json: async () => ({ success: true }) });
await confirm.emit('click');
check((await pending).deleted_ids[0] === 1 && requests.at(-1)[0] === '/institutions/1' && !requests.at(-1)[1].body,
    'Single deletion retains its existing endpoint and waits for explicit confirmed success');
pending = open();
response = async () => ({ ok: false, status: 403, json: async () => ({ message: 'Forbidden' }) });
const denied = assert.rejects(pending, /School One, Hall Two: You do not have permission/);
await confirm.emit('click'); await denied;
check(!context.institutionDeletionOpen() && !confirm.disabled, 'Authorization rejection restores controls and names failed records');
pending = open();
response = async () => ({ ok: true, status: 200, json: async () => ({ success: true, deleted_ids: [999] }) });
const invalid = assert.rejects(pending, /unexpected result/);
await confirm.emit('click'); await invalid;
check(true, 'Unexpected deletion IDs are rejected');
const rows = records.map(() => new Element()); rows.forEach((row, index) => doc.queries.set(`institution-row-${index + 1}`, row));
await context.refreshAfterInstitutionDeletion({ deleted_ids: [1] }, records);
check(rows[0].removed && !rows[1].removed && notifications.at(-1)[1].includes('Not deleted: Hall Two'), 'Partial responses remove only confirmed IDs and name failures');

const container = new Element(); container.dataset.batchDeleteUrl = '/institutions/batch-delete';
const controls = {};
for (const selector of ['[data-institution-select-all]', '[data-institution-batch-actions]', '[data-institution-selected-count]',
    '[data-institution-batch-delete]', '[data-batch-delete-label]', '[data-institution-clear-selection]', 'tbody']) {
    controls[selector] = new Element(); container.queries.set(selector, controls[selector]);
}
const inputs = records.map((record, index) => {
    const input = new Element(); input.value = String(record.id); input.dataset.institutionName = record.name;
    input.selector = '[data-institution-select]'; input.closestMap = { '.inst-row': rows[index] };
    rows[index].isConnected = true; return input;
});
container.queries.set('[data-institution-select]', inputs);
doc.queries.set('[data-institution-selection]', container);
vm.runInContext(source('institution-selection'), context);
const cleanup = context.installInstitutionSelection();
const all = controls['[data-institution-select-all]'];
inputs[0].checked = true; await container.emit('change', { target: inputs[0] });
check(all.indeterminate && !all.checked && rows[0].hasAttribute('data-selected'), 'Single selection sets indeterminate state and row highlight');
all.checked = true; await container.emit('change', { target: all });
check(inputs.every(input => input.checked) && !all.indeterminate && controls['[data-institution-selected-count]'].textContent.includes('2 institutions selected on this page'), 'Select All selects only current page and communicates scope');
rows[1].hidden = true; context.observeSelection();
check(!inputs[1].checked && all.checked, 'Hidden rows are excluded and deselected');
await controls['[data-institution-clear-selection]'].emit('click');
check(inputs.every(input => !input.checked) && controls['[data-institution-batch-actions]'].hidden, 'Clear Selection hides toolbar');
rows[1].hidden = false; inputs[0].checked = true; await container.emit('change', { target: inputs[0] });
const selectionDelete = controls['[data-institution-batch-delete]'].emit('click');
await cancel.emit('click'); await selectionDelete;
check(inputs[0].checked && !inputs[0].disabled, 'Cancelling bulk deletion retains selection and restores controls');
cleanup();
check(context.disconnected, 'Selection observer disconnects when a page is replaced');

// Exercise the actual list controller with stubbed fragments and a controlled debounce clock.
const page = new Element(), form = new Element(), search = new Element(), clearSearch = new Element();
search.value = 'School';
const apply = new Element();
for (const key of ['[data-apply-spinner]', '[data-apply-icon]', '[data-apply-label]']) apply.queries.set(key, new Element());
form.queries.set('[data-institution-apply]', apply);
form.elements = { barangay: { value: '1' }, sector: { value: '' }, sort: { value: 'barangay_asc' } };
const status = new Element(); status.queries.set('[data-institution-status-label]', new Element()); status.queries.set('[data-institution-status-icon]', new Element());
page.queries.set('[data-institution-list-status]', status);
page.queries.set('[data-institution-search-status]', new Element()); page.queries.set('[data-institution-loading]', new Element());
let current, total = '2', returnedPage = '1';
function fragment() {
    const root = new Element(); root.dataset = { total, page: returnedPage };
    root.replaceWith = next => { current = next; page.queries.set('[data-institution-results]', next); };
    return root;
}
current = fragment(); page.queries.set('[data-institution-results]', current);
doc.queries.set('[data-institution-list]', page); doc.queries.set('[data-institution-clear-search]', clearSearch);
doc.queries.set('institutionListFilters', form); doc.queries.set('institutionSearch', search);
doc.getElementById = id => doc.queries.get(id);
window.location = { href: 'https://fixture.test/institutions?barangay=1&search=School&page=2', origin: 'https://fixture.test' };
window.handlers = {};
window.addEventListener = (name, handler) => { window.handlers[name] = handler; };
window.history = { pushState: (_, __, url) => { window.location.href = url; }, replaceState: (_, __, url) => { window.location.href = url; } };
let listRequests = [], timer;
Object.assign(context, { URL, AbortController, Event,
    FormData: class { *[Symbol.iterator]() { yield ['search', search.value]; for (const [key, element] of Object.entries(form.elements)) yield [key, element.value]; } },
    DOMParser: class { parseFromString() { return { querySelector: key => key === '[data-institution-results]' ? fragment() : null }; } },
    setTimeout: fn => { timer = fn; return 1; }, clearTimeout: () => { timer = null; },
    installInstitutionSelection: () => () => {}, installInstitutionDeletion: () => {},
    fetch: async url => { listRequests.push(url); return { ok: true, redirected: false, headers: { get: () => 'text/html' }, text: async () => 'fixture' }; },
});
vm.runInContext(source('institution-list'), context);
context.installInstitutionList();
form.elements.barangay.value = '2';
await form.emit('change', { target: { matches: () => true } });
check(listRequests.length === 0 && apply.title.includes('pending'), 'Filter choices stay pending until Apply');
search.value = 'New'; await search.emit('input');
check(listRequests.length === 0 && typeof timer === 'function' && current.hasAttribute('inert'), 'Search is debounced and stale results cannot be selected or deleted');
await timer();
const searched = new URL(listRequests.at(-1));
check(searched.searchParams.get('barangay') === '1' && searched.searchParams.get('search') === 'New' && !searched.searchParams.has('page'), 'Search uses applied filters and resets pagination without applying pending filters');
await form.emit('submit', { preventDefault() {} });
for (let i = 0; i < 10; i++) await Promise.resolve();
check(new URL(listRequests.at(-1)).searchParams.get('barangay') === '2', 'Apply submits pending filters');
total = '0'; await clearSearch.emit('click'); // Trigger input explicitly in this mock DOM.
search.value = ''; await search.emit('input'); await timer();
check(!new URL(listRequests.at(-1)).searchParams.has('search') && clearSearch.hidden, 'Clearing search removes its query and hides the clear control');
check(current.dataset.total === '0', 'Empty search result count comes from the returned fragment');
returnedPage = '1'; window.location.href = 'https://fixture.test/institutions?page=9';
window.handlers.popstate();
for (let i = 0; i < 10; i++) await Promise.resolve();
check(current.dataset.page === '1' && new URL(window.location.href).searchParams.get('page') === '1', 'Server-corrected pagination stays synchronized with browser history');

console.log(`PASS: ${checks} institution management checks (mock DOM/fetch; no live records changed).`);
