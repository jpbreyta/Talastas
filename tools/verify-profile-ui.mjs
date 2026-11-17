import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';

let checks = 0;
const check = (condition, message) => { assert.ok(condition, message); checks++; };
class Element {
    constructor() { this.attrs = {}; this.listeners = {}; this.hidden = false; this.value = ''; this.type = 'password'; this.name = ''; this.queries = {}; }
    setAttribute(name, value) { this.attrs[name] = value; }
    getAttribute(name) { return this.attrs[name]; }
    addEventListener(name, listener) { (this.listeners[name] ||= []).push(listener); }
    emit(name, event = {}) { this.listeners[name]?.forEach(listener => listener(event)); }
    querySelector(selector) { return this.queries[selector] || null; }
    querySelectorAll(selector) { return this.queries[selector] || []; }
    focus() { document.activeElement = this; }
    getBoundingClientRect() { return { left: this.left || 0 }; }
}
const document = new Element(), window = new Element(), settings = new Element(), strip = new Element();
strip.scrollWidth = 580; strip.clientWidth = 300; strip.scrollLeft = 0;
const ids = ['personal', 'email', 'security'];
const fields = ids.map(() => Object.assign(new Element(), { name: 'current_password' }));
const code = Object.assign(new Element(), { name: 'code', type: 'text' });
const drafts = ['Name draft', 'new@example.invalid', ''];
const buttons = ids.map(() => {
    const button = new Element();
    for (const selector of ['.eye-open', '.eye-closed']) button.queries[selector] = { classList: new Set() };
    for (const icon of Object.values(button.queries)) icon.classList.remove = icon.classList.delete;
    return button;
});
const panels = ids.map((id, index) => {
    const panel = new Element(); panel.id = `profile-panel-${id}`; panel.hidden = index !== 0;
    const sensitive = index === 1 ? [fields[index], code] : [fields[index]];
    panel.queries['input[type="password"], input[name="code"]'] = sensitive;
    panel.contains = input => sensitive.includes(input);
    fields[index].parentElement = { querySelector: () => buttons[index] };
    panel.draft = drafts[index];
    return panel;
});
const tabs = ids.map((id, index) => {
    const tab = new Element(); tab.parentElement = strip; tab.offsetWidth = 150; tab.left = index * 150;
    tab.setAttribute('aria-controls', `profile-panel-${id}`); tab.setAttribute('aria-selected', String(index === 0));
    return tab;
});
settings.queries['[role="tab"]'] = tabs; settings.queries['[role="tabpanel"]'] = panels;
document.queries['[data-profile-settings]'] = settings;
document.getElementById = id => ({ current_password: fields[2] })[id];
const script = fs.readFileSync('resources/views/profile/account.blade.php', 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1];
const context = vm.createContext({ document, window });
vm.runInContext(script, context);
const active = index => !panels[index].hidden && panels.filter(panel => !panel.hidden).length === 1 && tabs[index].getAttribute('aria-selected') === 'true' && tabs[index].tabIndex === 0;
check(active(0), 'Personal tab opens alone');
fields[0].value = 'secret';
tabs[1].emit('click');
check(active(1) && fields[0].value === '', 'Click changes panel and clears previous current password');
check(panels[0].draft === 'Name draft' && panels[1].draft === 'new@example.invalid', 'Non-sensitive drafts are unchanged');
fields[1].value = 'email secret'; code.value = '123456';
let prevented = false;
tabs[1].emit('keydown', { key: 'ArrowRight', preventDefault() { prevented = true; } });
check(prevented && active(2) && document.activeElement === tabs[2], 'Right arrow selects and focuses next tab');
check(fields[1].value === '' && code.value === '', 'Leaving email clears password and verification code');
fields[2].value = 'revealed secret';
vm.runInContext('togglePasswordVisibility("current_password", settingsButton)', vm.createContext({ document, settingsButton: buttons[2], togglePasswordVisibility: context.togglePasswordVisibility }));
check(fields[2].type === 'text' && buttons[2].attrs['aria-pressed'] === 'true', 'Password visibility remains functional and accessible');
tabs[2].emit('keydown', { key: 'ArrowRight', preventDefault() {} });
check(active(0), 'Right arrow wraps from last to first');
check(fields[2].value === '' && fields[2].type === 'password' && buttons[2].attrs['aria-pressed'] === 'false', 'Revealed password cleared and masked on tab change');
tabs[0].emit('keydown', { key: 'ArrowLeft', preventDefault() {} });
check(active(2) && document.activeElement === tabs[2], 'Left arrow wraps from first to last');
tabs[2].emit('keydown', { key: 'Home', preventDefault() {} }); check(active(0), 'Home selects first tab');
tabs[0].emit('keydown', { key: 'End', preventDefault() {} }); check(active(2), 'End selects last tab');
tabs[2].emit('keydown', { key: 'Tab', preventDefault() { throw new Error('Must not trap Tab'); } });
check(active(2), 'Tab key retains normal browser focus navigation');
fields.forEach(field => { field.value = 'restored secret'; }); code.value = '123456';
window.emit('pageshow');
check(fields.every(field => field.value === '') && code.value === '', 'Browser history restoration clears all sensitive fields');
check(strip.scrollLeft !== 0, 'Selected mobile tab is revealed within horizontally scrolling strip');
console.log(`PASS: ${checks} Profile keyboard/tab/password interaction checks (mock DOM).`);
