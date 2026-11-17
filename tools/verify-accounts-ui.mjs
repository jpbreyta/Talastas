import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

let checks = 0, document, requests = 0;
const check = (value, message) => { assert.ok(value, message); checks++; };
class Element {
    constructor(selectors = []) { this.selectors = selectors; this.events = {}; this.attrs = {}; this.dataset = {}; this.children = []; this.queries = {}; this.style = {}; this.isConnected = true; }
    addEventListener(name, fn) { (this.events[name] ||= []).push(fn); }
    emit(name, extra = {}) { const event = { target: this, ...extra }; for (const fn of this.events[name] || []) fn(event); return event; }
    setAttribute(key, value) { this.attrs[key] = value; }
    getAttribute(key) { return this.attrs[key]; }
    appendChild(child) { if (child.parentElement) child.parentElement.children = child.parentElement.children.filter(node => node !== child); this.children.push(child); child.parentElement = this; return child; }
    contains(element) { return this === element || this.children.some(child => child.contains(element)); }
    closest(selector) { for (let node = this; node; node = node.parentElement) if (node.selectors.includes(selector)) return node; return null; }
    querySelector(selector) { return this.queries[selector] || null; }
    querySelectorAll(selector) { return this.queries[selector] || []; }
    focus() { document.activeElement = this; }
    getBoundingClientRect() { return { right: 365, bottom: 630, top: 594 }; }
}
document = new Element(); document.body = new Element();
const page = document.body.appendChild(new Element()), row = page.appendChild(new Element());
const toggle = row.appendChild(new Element(['[data-user-actions-toggle]'])); toggle.attrs['aria-controls'] = 'fixture-menu';
const menu = row.appendChild(new Element(['[data-user-actions-menu]'])); menu.id = 'fixture-menu'; menu.hidden = true; menu.offsetWidth = 176; menu.offsetHeight = 88;
const edit = menu.appendChild(new Element(['[role="menuitem"]']));
const form = menu.appendChild(new Element()); form.dataset = { userName: '<Fixture Account>', userEmail: 'fixture@example.invalid' };
const remove = form.appendChild(new Element(['[role="menuitem"]']));
menu.queries['[role="menuitem"]'] = [edit, remove];
menu.querySelector = () => edit;
form.queries['[data-confirm-user-delete]'] = remove;
form.requestSubmit = () => { let prevented = false; form.emit('submit', { preventDefault() { prevented = true; } }); if (!prevented) requests++; };
const dialog = document.body.appendChild(new Element()); dialog.open = false;
const cancel = dialog.appendChild(new Element()), confirm = dialog.appendChild(new Element());
const name = new Element(), email = new Element();
Object.assign(dialog.queries, { '[data-cancel-account-delete]': cancel, '[data-submit-account-delete]': confirm, '[data-delete-account-name]': name, '[data-delete-account-email]': email });
dialog.showModal = () => { dialog.open = true; }; dialog.close = () => { dialog.open = false; dialog.emit('close'); };
const search = new Element(), clear = new Element(); search.value = '';
let searches = 0; search.form = { requestSubmit() { searches++; } };
page.queries['[data-users-clear-search]'] = clear; page.queries['[data-account-delete-form]'] = [form];
document.querySelector = selector => selector === '[data-accounts-table]' ? page : toggle;
document.getElementById = id => ({ 'fixture-menu': menu, userSearch: search, deleteUserAccountDialog: dialog })[id];
const window = new Element(); window.innerWidth = 375; window.innerHeight = 667;
const context = vm.createContext({ document, window });
const code = fs.readFileSync('resources/views/admin/users/_account-scripts.blade.php', 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1];
vm.runInContext(code, context); document.emit('DOMContentLoaded');
page.emit('click', { target: toggle });
check(!menu.hidden && menu.parentElement === document.body && toggle.attrs['aria-expanded'] === 'true', 'Menu is portalled outside clipping wrappers and exposes expanded state');
check(document.activeElement === edit && parseFloat(menu.style.left) >= 8 && parseFloat(menu.style.top) + menu.offsetHeight <= 659, 'Menu focuses its first action and remains inside a mobile viewport');
document.emit('keydown', { key: 'ArrowDown', preventDefault() {} });
check(document.activeElement === remove, 'Arrow Down moves to Delete Account');
document.emit('keydown', { key: 'Home', preventDefault() {} });
check(document.activeElement === edit, 'Home returns to the first menu item');
document.emit('keydown', { key: 'Escape', preventDefault() {} });
check(menu.hidden && menu.parentElement === row && document.activeElement === toggle, 'Escape closes the menu and restores focus');
page.emit('click', { target: toggle }); document.emit('click', { target: page });
check(menu.hidden, 'Outside clicks close the menu');
page.emit('click', { target: toggle }); window.emit('resize');
check(menu.hidden, 'Resize closes a stale-positioned menu');
page.emit('click', { target: toggle }); document.emit('scroll', { target: page });
check(menu.hidden, 'Table/page scrolling closes the menu');
search.value = 'Fixture'; search.emit('input');
check(!clear.hidden && searches === 0, 'Typing reveals clear-search without new requests');
clear.emit('click');
check(search.value === '' && searches === 1, 'Clear search uses the existing GET form');
page.emit('click', { target: toggle }); remove.emit('click');
check(dialog.open && !requests && document.activeElement === cancel, 'Delete opens confirmation, focuses Cancel and sends no request');
check(name.textContent === '<Fixture Account>' && email.textContent === 'fixture@example.invalid', 'Confirmation identifies the account via safe textContent');
cancel.emit('click');
check(!dialog.open && !requests && document.activeElement === toggle, 'Cancel retains data and restores the visible menu trigger');
remove.emit('click'); confirm.emit('click');
check(!dialog.open && requests === 1 && confirm.disabled, 'Explicit confirmation submits the existing deletion form once');
form.requestSubmit();
check(requests === 1, 'Duplicate deletion requests are prevented');
window.emit('pageshow');
check(!confirm.disabled && !form.dataset.deleteConfirmed, 'Back-forward restoration clears confirmation state');
form.requestSubmit();
check(dialog.open && requests === 1, 'Unconfirmed form submissions cannot bypass the dialog');
cancel.emit('click');
new vm.Script(fs.readFileSync('resources/views/admin/users/_presentation-scripts.blade.php', 'utf8')
    .match(/<script>([\s\S]*?)<\/script>/)[1]
    .replace("@json(route('admin.users.index'))", '"https://fixture.test/admin/users"'));
check(true, 'Existing account editor and invitation presentation JavaScript still parses');
console.log(`PASS: ${checks} account menu/search/confirmation checks (mock DOM; no live requests).`);
