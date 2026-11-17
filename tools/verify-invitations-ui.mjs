import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';

let checks = 0;
const check = (condition, message) => { assert.ok(condition, message); checks++; };
let document;
class Element {
    constructor(selectors = []) {
        this.selectors = selectors; this.children = []; this.queries = {}; this.attrs = {};
        this.listeners = {}; this.dataset = {}; this.style = {}; this.hidden = false;
        this.disabled = false; this.checked = false; this.value = ''; this.open = false;
    }
    addEventListener(type, handler) { (this.listeners[type] ||= []).push(handler); }
    emit(type, event = {}) { this.listeners[type]?.forEach(handler => handler(event)); }
    setAttribute(name, value) { this.attrs[name] = value; }
    getAttribute(name) { return this.attrs[name]; }
    removeAttribute(name) { delete this.attrs[name]; }
    appendChild(child) {
        if (child.parentElement) child.parentElement.children = child.parentElement.children.filter(item => item !== child);
        child.parentElement = this; this.children.push(child); return child;
    }
    contains(target) { return this === target || this.children.some(child => child.contains(target)); }
    closest(selector) { for (let node = this; node; node = node.parentElement) if (node.selectors.includes(selector)) return node; return null; }
    querySelector(selector) { return this.queries[selector] || null; }
    querySelectorAll(selector) { return this.queries[selector] || []; }
    focus() { document.activeElement = this; }
    getBoundingClientRect() { return { left: 300, right: 370, top: 580, bottom: 624 }; }
}
document = new Element(); document.body = new Element();
const window = new Element(); window.innerWidth = 375; window.innerHeight = 667;
const page = document.body.appendChild(new Element());
document.queries['[data-invitations-page]'] = page;
const card = page.appendChild(new Element());
const toggle = card.appendChild(new Element()); toggle.setAttribute('aria-controls', 'fixture-invite-menu');
const menu = card.appendChild(new Element(['[role="menu"]'])); menu.id = 'fixture-invite-menu'; menu.hidden = true; menu.offsetWidth = 176; menu.offsetHeight = 100;
const actions = ['resend', 'revoke'].map(action => {
    const item = menu.appendChild(new Element(['[data-invite-action]']));
    item.dataset = { inviteAction: action, inviteId: 'fixture', recipientName: '<Recipient>', recipientEmail: 'recipient@example.invalid', actionUrl: `/admin/invitations/fixture/${action}` };
    return item;
});
menu.queries['[role="menuitem"]'] = actions; menu.querySelector = () => actions[0];
page.queries['[data-invite-menu-toggle]'] = [toggle]; page.queries['[data-invite-action]'] = actions;
document.querySelector = selector => selector === '[data-invitations-page]' ? page : toggle;
const dialog = page.appendChild(new Element());
const form = dialog.appendChild(new Element()); dialog.queries['[data-invitation-action-form]'] = form;
const password = form.appendChild(new Element()), confirmed = form.appendChild(new Element());
const cancel = form.appendChild(new Element()), submit = form.appendChild(new Element());
form.queries['[data-cancel-invitation-action]'] = cancel; form.queries['[data-invitation-submit]'] = submit;
for (const selector of ['[data-action-id]', '[data-action-kind]', '[data-action-title]', '[data-action-recipient]', '[data-action-email]', '[data-action-description]', '[data-action-confirmation-text]']) form.queries[selector] = new Element();
form.queries['input:not([type="hidden"]), button'] = [confirmed, password, cancel, submit];
form.reset = () => { password.value = ''; confirmed.checked = false; };
dialog.showModal = () => { dialog.open = true; }; dialog.close = () => { dialog.open = false; dialog.emit('close'); };
const create = new Element(), createSubmit = new Element(), createPassword = new Element();
page.queries['[data-invitation-create-form]'] = create;
create.queries['button[type="submit"]'] = createSubmit; create.queries['input[name="current_password"]'] = createPassword;
document.getElementById = id => ({ fixture: null, 'fixture-invite-menu': menu, invitationActionDialog: dialog, invitationActionPassword: password, invitationActionConfirmed: confirmed })[id];
const script = fs.readFileSync('resources/views/admin/users/_invitation-scripts.blade.php', 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1];
document.queries['[data-invitation-action-form]'] = [form];
const context = vm.createContext({ document, window });
const presentation = fs.readFileSync('resources/views/admin/users/_presentation-scripts.blade.php', 'utf8')
    .match(/<script>([\s\S]*?)<\/script>/)[1].replace("@json(route('admin.users.index'))", "'/admin/users'");
vm.runInContext(presentation, context);
vm.runInContext(script, context);
document.emit('DOMContentLoaded'); window.emit('pageshow');
function submitEvent(target) {
    let prevented = false;
    target.emit('submit', { preventDefault() { prevented = true; } });
    return !prevented;
}
toggle.emit('click');
check(!menu.hidden && menu.parentElement === document.body && toggle.attrs['aria-expanded'] === 'true', 'Menu uses existing portal pattern outside clipping card');
check(!dialog.open && !form.action, 'Opening menu performs no action and opens no confirmation');
check(parseFloat(menu.style.left) >= 8 && parseFloat(menu.style.top) + menu.offsetHeight <= 659, 'Menu remains inside mobile viewport');
check(document.activeElement === actions[0], 'Opening menu focuses first action');
document.emit('keydown', { key: 'ArrowDown', preventDefault() {} });
check(document.activeElement === actions[1], 'Arrow navigation reaches revoke');
document.emit('keydown', { key: 'Home', preventDefault() {} }); check(document.activeElement === actions[0], 'Home returns to first action');
document.emit('keydown', { key: 'End', preventDefault() {} }); check(document.activeElement === actions[1], 'End reaches last action');
document.emit('keydown', { key: 'Escape', preventDefault() {} });
check(menu.hidden && document.activeElement === toggle && menu.parentElement === card, 'Escape closes menu and restores focus');
toggle.emit('click'); document.emit('click', { target: new Element() }); check(menu.hidden, 'Outside click closes menu');
toggle.emit('click'); document.emit('click', { target: actions[0] });
check(dialog.open && menu.hidden && form.action.endsWith('/resend') && document.activeElement === cancel, 'Resend opens dialog using original endpoint and focuses Cancel');
check(form.queries['[data-action-recipient]'].textContent === '<Recipient>' && form.queries['[data-action-email]'].textContent === 'recipient@example.invalid', 'Recipient inserted as text without interpreting HTML');
check(form.queries['[data-action-kind]'].value === 'resend' && submit.textContent === 'Resend Invitation', 'Dialog identifies the selected action');
check(!submitEvent(form) && !submit.disabled, 'No submission without explicit confirmation and password');
confirmed.checked = true; check(!submitEvent(form) && document.activeElement === password, 'Password is still required after confirmation');
password.value = 'temporary-secret'; cancel.emit('click');
check(!dialog.open && password.value === '' && !confirmed.checked && document.activeElement === toggle, 'Cancel sends nothing, clears sensitive data, and restores focus');
toggle.emit('click'); document.emit('click', { target: actions[1] });
check(dialog.open && form.action.endsWith('/revoke') && submit.className.includes('users-button-danger'), 'Revoke opens destructive confirmation with correct endpoint');
submit.focus(); let trapped = false;
dialog.emit('keydown', { key: 'Tab', shiftKey: false, preventDefault() { trapped = true; } });
check(trapped && document.activeElement === confirmed, 'Dialog focus cycles forward');
trapped = false; confirmed.focus();
dialog.emit('keydown', { key: 'Tab', shiftKey: true, preventDefault() { trapped = true; } });
check(trapped && document.activeElement === submit, 'Dialog focus cycles backward');
confirmed.checked = true; password.value = 'fixture-password';
check(submitEvent(form) && submit.disabled && cancel.disabled && form.attrs['aria-busy'] === 'true', 'Explicit confirmation allows exactly one submit and indicates processing');
check(password.value === 'fixture-password' && !password.disabled, 'Password remains enabled for native form serialization');
check(!submitEvent(form), 'Second action submission is blocked');
let blocked = false; dialog.emit('cancel', { preventDefault() { blocked = true; } }); check(blocked, 'Escape cannot interrupt a submitted operation');
window.emit('pageshow');
check(!dialog.open && password.value === '' && !submit.disabled && !cancel.disabled, 'History restoration resets busy state and clears password');
check(submitEvent(create) && createSubmit.disabled && !submitEvent(create), 'New invitation form blocks duplicate submissions');
window.emit('pageshow'); check(!createSubmit.disabled && !create.attrs['aria-busy'], 'History restoration restores create form controls');
dialog.dataset = { reopenId: 'fixture', reopenAction: 'revoke' }; window.emit('pageshow');
check(dialog.open && form.action.endsWith('/revoke') && password.value === '' && !confirmed.checked, 'Validation redirect reopens matching action without password or confirmation reuse');
console.log(`PASS: ${checks} invitation menu/dialog/duplicate-submit interaction checks (mock DOM).`);
