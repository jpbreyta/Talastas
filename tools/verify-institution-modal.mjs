import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source = fs.readFileSync('resources/views/institutions/modals.blade.php', 'utf8');
let checks = 0;
function check(value, message) { assert.ok(value, message); checks++; }
function element(value = '') {
    return { value, disabled: false, innerHTML: '', attrs: {}, children: [],
        addEventListener() {}, classList: { toggle() {} }, setAttribute(k,v) { this.attrs[k]=v; }, removeAttribute(k) { delete this.attrs[k]; },
        replaceChildren() { this.children=[]; }, appendChild(child) { this.children.push(child); },
        focus() { this.focused=true; }, scrollIntoView() {} };
}
const errors = element();
const name = element('Existing School');
const selected = element('Existing School');
const form = { action:'/institutions', addEventListener(type, handler) { this[type]=handler; }, setAttribute(){}, removeAttribute(){} };
let redirects = [], notifications = [], calls=0;
let respond = async () => ({ok:false,status:422,json:async()=>({errors:{name:['This institution already exists in the system.']}})});
const context = vm.createContext({ document:{getElementById:()=>errors,createElement:()=>element()},
    form, formErrors:errors, modalName:name, selectedInstitutionName:selected,
    manualLatitude:element(), manualLongitude:element(), latitudeInput:element('13.941123456'), longitudeInput:element('121.163123456'),
    barangayId:element('1'), createButton:element(), gps:{canSubmit:()=>true},
    renderSubmissionErrors:undefined, notify:(...args)=>notifications.push(args),
    resetCreateButton(){}, setLocationStatus(){}, showLocationValidation(){},
    FormData:class {}, fetch:async()=>{ calls++; return respond(); }, sessionStorage:{setItem(){}},
    window:{location:{assign:url=>redirects.push(url)}}, console });
const start = source.indexOf('            const formErrors =');
const end = source.indexOf('            function openModal', start);
vm.runInContext(source.slice(start,end), context);
let prevented = false;
await form.submit({preventDefault(){prevented=true;}});
check(prevented, 'Native navigation must be prevented');
check(errors.children[0].textContent.includes('Institution name: This institution already exists'), 'Duplicate must be displayed in modal');
check(errors.focused, 'Error summary should receive focus');
check(name.value === 'Existing School' && selected.value === 'Existing School', 'Failed submission must preserve names');
check(context.latitudeInput.value === '13.941123456', 'Failed submission must preserve coordinate precision');
check(redirects.length === 0, 'Failed submission must not navigate');
check(!context.createButton.disabled, 'Failed submission must permit retry');
check(notifications.length === 1, 'Existing notification system should also report the error');
form.input({target:name});
check(errors.children.length === 0, 'Editing name must clear its duplicate message');
let release;
respond = () => new Promise(resolve=>{release=resolve;});
const pending = form.submit({preventDefault(){}});
await Promise.resolve();
const before = calls;
await form.submit({preventDefault(){}});
check(calls === before, 'Concurrent submission must be ignored');
release({ok:true,status:201,json:async()=>({success:true,redirect:'/institutions'})});
await pending;
check(redirects[0] === '/institutions', 'Successful save must navigate');
respond = async()=>{throw new Error('Network failure');};
context.console = {error(){}};
await form.submit({preventDefault(){}});
check(errors.children[0].textContent.includes('Check your connection'), 'Network failures need an actionable inline error');
for (const file of ['modals','edit']) {
    const blade = fs.readFileSync(`resources/views/institutions/${file}.blade.php`, 'utf8');
    // Parse the actual inline JavaScript with server-side placeholders substituted.
    for (const script of blade.matchAll(/<script[^>]*>([\s\S]*?)<\/script>/g)) {
        const js = script[1].replace(/\{\{[\s\S]*?\}\}/g, '/test')
            .replace(/@json\(\$errors->messages\(\)\)/g, '{}')
            .replace(/^\s*@(?:if|else|endif|can|endcan).*$/gm, '');
        new vm.Script(js);
    }
    check(!/\.value\s*=\s*[^;]*\.toFixed\(/.test(blade), `${file}: saved coordinates must not be display-rounded`);
    check(!/<input type="number"[^>]*(?:name="(?:latitude|longitude)"|id="manual(?:Latitude|Longitude)")/.test(blade), `${file}: coordinate inputs must not have wheel spinners`);
}
console.log(`PASS: ${checks} modal regression and coordinate markup checks (mock DOM, not browser tests).`);
