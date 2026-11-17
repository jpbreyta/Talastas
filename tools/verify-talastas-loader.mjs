import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

let checks = 0;
const check = (condition, message) => { assert.ok(condition, message); checks++; };
let document;
class Element {
    constructor(tagName = 'DIV') { this.tagName = tagName; this.attrs = new Map(); this.children = []; this.style = {}; this.dataset = {}; this.isConnected = true; }
    hasAttribute(name) { return this.attrs.has(name); }
    getAttribute(name) { return this.attrs.get(name) ?? null; }
    setAttribute(name, value) {
        this.attrs.set(name, value);
        if (name === 'inert' && this.contains(document.activeElement)) document.activeElement = document.body;
    }
    removeAttribute(name) { this.attrs.delete(name); }
    contains(element) { return this === element || this.children.some(child => child.contains(element)); }
    closest() { for (let node = this; node; node = node.parentElement) if (node.hasAttribute('inert')) return node; return null; }
    append(child) { this.children.push(child); child.parentElement = this; return child; }
    focus() { document.activeElement = this; }
}
class Form extends Element { constructor() { super('FORM'); this.method = 'post'; } }
const body = new Element('BODY');
const page = body.append(new Element());
const trigger = page.append(new Element('BUTTON'));
const alreadyInert = body.append(new Element()); alreadyInert.attrs.set('inert', '');
const overlay = body.append(new Element()); overlay.hidden = true;
const status = new Element(); overlay.querySelector = () => status;
const script = body.append(new Element('SCRIPT'));
const handlers = {}, windowHandlers = {};
document = { body, activeElement: trigger, getElementById: () => overlay, querySelector: () => null,
    addEventListener: (type, handler) => { handlers[type] = handler; } };
const window = { addEventListener: (type, handler) => { windowHandlers[type] = handler; } };
const timers = new Map(), microtasks = [];
let nextTimer = 0;
const context = vm.createContext({ document, window, HTMLFormElement: Form, URL,
    location: new URL('https://fixture.test/institutions'),
    setTimeout: (fn, delay) => { const id = ++nextTimer; timers.set(id, { fn, delay }); return id; },
    clearTimeout: id => timers.delete(id), queueMicrotask: fn => microtasks.push(fn),
});
const source = fs.readFileSync('resources/views/partials/global-loader.blade.php', 'utf8');
vm.runInContext(source.match(/<script>([\s\S]*?)<\/script>/)[1], context);
const reveal = () => { for (const [id, { fn }] of [...timers]) { timers.delete(id); fn(); } };
const flush = () => { while (microtasks.length) microtasks.shift()(); };
check(overlay.hidden && !page.hasAttribute('inert'), 'Loader starts hidden and does not block idle pages');
let release = window.showGlobalLoader('Updating fixture');
check(overlay.hidden && page.hasAttribute('inert') && body.style.overflow === 'hidden', 'Blocking begins immediately while short operations avoid visual flashes');
check([...timers.values()][0].delay === 150, 'Appearance delay is short and has no minimum display duration');
release();
check(overlay.hidden && timers.size === 0 && !page.hasAttribute('inert'), 'Fast completion cancels pending appearance');
check(alreadyInert.hasAttribute('inert') && !script.hasAttribute('inert') && document.activeElement === trigger, 'Existing inert state and focus are preserved');
release = window.showGlobalLoader('First request'); reveal();
check(!overlay.hidden && overlay.getAttribute('aria-hidden') === 'false' && document.activeElement === overlay, 'Longer operation reveals one accessible overlay');
const secondRelease = window.showGlobalLoader('Second request');
check(status.textContent === 'Second request', 'Accessible status follows the most recent active operation');
release(); release();
check(!overlay.hidden && page.hasAttribute('inert'), 'Idempotent release cannot hide another active request');
secondRelease();
check(overlay.hidden && !page.hasAttribute('inert') && document.activeElement === trigger, 'Final completion immediately hides the overlay, unlocks and restores focus');
try {
    const finish = window.showGlobalLoader('Failing request'); reveal();
    try { throw new Error('fixture failure'); } finally { finish(); }
} catch {}
check(overlay.hidden && !page.hasAttribute('inert'), 'Failure cleanup hides the loader through the existing release API');
const click = (attrs = {}, overrides = {}) => {
    const link = new Element('A'); link.attrs = new Map(Object.entries({ href: '/next', ...attrs }));
    link.href = new URL(link.getAttribute('href'), context.location).href;
    const event = { target: { closest: () => link }, button: 0, defaultPrevented: false, ...overrides };
    handlers.click(event); return event;
};
click({ download: '' }); flush();
check(!page.hasAttribute('inert'), 'Downloads do not activate a navigation loader');
click({ 'data-no-global-loader': '' }); flush();
check(!page.hasAttribute('inert'), 'Export opt-out preserves download behavior without a stuck overlay');
click({ target: '_blank' }); flush();
click({ href: '#section' }); flush();
click({}, { ctrlKey: true }); flush();
check(!page.hasAttribute('inert'), 'New tabs, same-page anchors and modified clicks do not block the page');
const cancelled = click(); cancelled.defaultPrevented = true; flush();
check(!page.hasAttribute('inert'), 'AJAX preventDefault is honored after all click handlers complete');
click(); flush(); reveal();
check(!overlay.hidden, 'Genuine same-window navigation displays the loader');
windowHandlers.pageshow({ persisted: false });
check(overlay.hidden && !page.hasAttribute('inert'), 'Page arrival clears navigation loading');
const form = new Form();
const submission = { target: form, defaultPrevented: false };
handlers.submit(submission); submission.defaultPrevented = true; flush();
check(!page.hasAttribute('inert'), 'AJAX form submission keeps its local loading behavior');
handlers.submit({ target: form, defaultPrevented: false }); flush(); reveal();
check(!overlay.hidden, 'Normal form navigation uses branded loading');
windowHandlers.pageshow({ persisted: true });
check(overlay.hidden && !page.hasAttribute('inert'), 'Back-forward restoration clears navigation and outstanding loader state');
window.showGlobalLoader('Stale task'); reveal(); windowHandlers.pageshow({ persisted: true });
check(overlay.hidden && !page.hasAttribute('inert'), 'Back-forward restoration also clears stale explicit tasks');
console.log(`PASS: ${checks} branded-loader lifecycle checks (mock DOM/timers; no live requests).`);
