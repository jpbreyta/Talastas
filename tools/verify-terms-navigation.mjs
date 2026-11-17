import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';

let checks = 0;
const check = (ok, message) => { assert.ok(ok, message); checks++; };
class Element {
    constructor() { this.attrs = {}; this.listeners = {}; this.value = ''; }
    setAttribute(name, value) { this.attrs[name] = value; }
    removeAttribute(name) { delete this.attrs[name]; }
    addEventListener(name, handler, options) { (this.listeners[name] ||= []).push(handler); this.options = options; }
    emit(name) { this.listeners[name]?.forEach(handler => handler()); }
}
const ids = ['section-1', 'section-2', 'policy-references'];
const document = { activeElement: null, documentElement: { scrollHeight: 5000 } };
const headings = ids.map((id, index) => ({ id, top: index * 500 + 200,
    getBoundingClientRect() { return { top: this.top }; },
    focus(options) { document.activeElement = this; this.focusOptions = options; },
}));
const links = ids.map(id => Object.assign(new Element(), { hash: `#${id}` }));
const jump = new Element();
const panel = { querySelectorAll: () => links, querySelector: () => jump };
document.querySelectorAll = () => [panel];
document.getElementById = id => headings.find(heading => heading.id === id);
const window = Object.assign(new Element(), { innerHeight: 800, scrollY: 0, location: {} });
Object.defineProperty(window.location, 'hash', { set(id) {
    this.current = id;
    const target = headings.find(heading => heading.id === id);
    const delta = target.top - 32;
    headings.forEach(heading => { heading.top -= delta; });
} });
const frames = [];
const code = fs.readFileSync('resources/views/legal/layout.blade.php', 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1];
vm.runInContext(code, vm.createContext({ document, window, requestAnimationFrame: callback => frames.push(callback) }));
check(links[0].attrs['aria-current'] === 'location' && jump.value === 'section-1', 'Initial section is highlighted in both navigation controls');
headings[0].top = -300; headings[1].top = 40;
window.emit('scroll'); window.emit('scroll');
check(frames.length === 1, 'Scroll updates coalesce into one animation-frame callback');
frames.shift()();
check(links[1].attrs['aria-current'] === 'location' && !links[0].attrs['aria-current'] && jump.value === 'section-2', 'Scrolling updates one current section and the mobile selection');
check(Object.values(links).filter(link => link.attrs['aria-current']).length === 1, 'Only one section is marked current');
document.activeElement = jump; jump.value = 'policy-references'; window.emit('resize');
check(jump.value === 'policy-references', 'Reading-position updates do not overwrite a keyboard-focused mobile choice');
jump.emit('change');
check(window.location.current === 'policy-references' && document.activeElement === headings[2], 'Mobile jump uses the original anchor and focuses its heading');
check(headings[2].focusOptions.preventScroll === true && headings[2].top === 32, 'Heading focus does not trigger a second scroll or animation');
check(links[2].attrs['aria-current'] === 'location', 'Mobile jump updates desktop active-section semantics');
const before = window.location.current; jump.value = ''; jump.emit('change');
check(window.location.current === before, 'Empty mobile choice does not navigate');
headings[0].top = -500; headings[1].top = -100; headings[2].top = 700;
window.scrollY = 200; document.documentElement.scrollHeight = 1000; window.emit('resize');
check(links[2].attrs['aria-current'] === 'location', 'References become current at the page bottom even if they cannot reach the top threshold');
window.scrollY = 0; document.documentElement.scrollHeight = 5000; headings[0].top = 32; headings[1].top = 400; headings[2].top = 900;
window.emit('hashchange');
check(links[0].attrs['aria-current'] === 'location', 'Hash/history navigation refreshes the active section');
console.log(`PASS: ${checks} Terms section-navigation interaction checks (mock DOM).`);
