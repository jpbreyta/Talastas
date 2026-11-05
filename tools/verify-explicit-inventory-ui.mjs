import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const context={window:{}};
vm.runInNewContext(fs.readFileSync(new URL('../public/js/inventory-counts.js', import.meta.url),'utf8'),context);
const counts=context.window.InventoryCounts;
const base={total_count:'100',active_count:'80',retired_count:'20',functional_count:'60',defective_count:'15',defective_active_count:'10'};
assert.equal(counts.errors(base,true).length,0);
assert.equal(counts.errors({...base,defective_active_count:'81'},true).some(e=>e.field==='defective_active_count'),true);
assert.equal(counts.errors({...base,defective_active_count:''},true).length,0);
assert.equal(counts.errors({...base,defective_active_count:'0'},true).length,0);
assert.equal(counts.status({...base,defective_active_count:''}),'Availability Partially Recorded');
assert.equal(counts.errors({...base,active_count:'90',retired_count:'50'},true).length,0);
for (const path of ['create','_form-fields']) {
  const source=fs.readFileSync(new URL(`../resources/views/ict-inventory/${path}.blade.php`,import.meta.url),'utf8');
  for(const block of source.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g)) {
    let script=block[1];
    // Replace Blade's balanced @json expressions with a safe literal for syntax checking.
    let start;
    while((start=script.indexOf('@json('))>=0){
      let i=start+6,depth=1,quote=null;
      for(;i<script.length&&depth;i++){
        const c=script[i];
        if(quote){if(c===quote&&script[i-1]!=='\\')quote=null;}
        else if(c==='"'||c==="'")quote=c;
        else if(c==='(')depth++;
        else if(c===')')depth--;
      }
      assert.equal(depth,0);
      script=script.slice(0,start)+'null'+script.slice(i);
    }
    script=script.replace(/\{\{[\s\S]*?\}\}/g, 'fixture');
    new vm.Script(script);
  }
}
console.log('6 client validation checks and inventory inline JavaScript syntax passed.');
