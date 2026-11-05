<?php
// In-memory fixtures only. No application bootstrap, .env, migrations or live database.
require __DIR__.'/../vendor/autoload.php';
$capsule = new Illuminate\Database\Capsule\Manager;
$capsule->addConnection(['driver'=>'sqlite','database'=>':memory:','prefix'=>'']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$db = $capsule->getConnection();
$db->statement('CREATE TABLE institutions (id INTEGER PRIMARY KEY, name TEXT)');
$db->statement('CREATE TABLE ict_inventory (asset_id INTEGER PRIMARY KEY, institution_id INTEGER, component_type TEXT, total_count INTEGER, active_count INTEGER, retired_count INTEGER, functional_count INTEGER, functional_count_recorded BOOLEAN, defective_count INTEGER, defective_active_count INTEGER, created_at TEXT, updated_at TEXT)');
foreach ([1=>'Complete',2=>'Partial',3=>'Empty',4=>'Invalid',5=>'Mixed'] as $id=>$name) $db->table('institutions')->insert(compact('id','name'));
$row=['component_type'=>'hardware','total_count'=>10,'active_count'=>8,'retired_count'=>2,'functional_count'=>7,'functional_count_recorded'=>true,'defective_count'=>1,'defective_active_count'=>1,'created_at'=>'2026-01-01','updated_at'=>'2026-01-01'];
foreach ([1=>[],2=>['active_count'=>null,'retired_count'=>null,'functional_count_recorded'=>false,'updated_at'=>'2026-03-01'],4=>['defective_count'=>20],5=>[]] as $id=>$changes) {
$db->table('ict_inventory')->insert(array_replace($row,['institution_id'=>$id],$changes));
}
$db->table('ict_inventory')->insert(array_replace($row,['institution_id'=>5,'active_count'=>null,'retired_count'=>null,'functional_count_recorded'=>false,'defective_count'=>null]));
$sorter=new App\Services\InventoryListOrdering;
$query=fn($sort)=>$sorter->apply(App\Models\Institution::query()->select('institutions.*'),$sort,['hardware','network','software','connectivity']);
function verify($ok,$message) { if (!$ok) throw new RuntimeException($message); }
verify($query('most_complete')->pluck('name')->all() === ['Complete','Invalid','Mixed','Partial','Empty'],'Global completeness ordering');
verify($query('latest')->pluck('name')->all() === ['Partial','Complete','Invalid','Mixed','Empty'],'Latest update and NULL ordering');
verify($query('name')->pluck('name')->all() === ['Complete','Empty','Invalid','Mixed','Partial'],'Name order preserved');
verify((float)$query('most_complete')->where('institutions.id',2)->first()->inventory_input_coverage === 50.0,'Unknown Functional flag not counted');
verify(abs((float)$query('most_complete')->where('institutions.id',5)->first()->inventory_input_coverage - (200.0 / 3)) < 0.000001,'Incomplete rows retained');
verify($query('most_complete')->offset(1)->limit(1)->first()->name === 'Invalid','Ordering before pagination');
echo "6 isolated inventory sorting checks passed.\n";
