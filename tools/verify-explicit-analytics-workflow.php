<?php
// Isolated integration fixtures. Never boot the application or load .env.
require __DIR__.'/../vendor/autoload.php';
$c=new Illuminate\Container\Container;
Illuminate\Container\Container::setInstance($c);
Illuminate\Support\Facades\Facade::setFacadeApplication($c);
$capsule=new Illuminate\Database\Capsule\Manager($c);
$capsule->addConnection(['driver'=>'sqlite','database'=>':memory:','prefix'=>'']);
$capsule->setAsGlobal();$capsule->bootEloquent();$db=$capsule->getConnection();
if ($db->getDatabaseName() !== ':memory:') throw new RuntimeException('Unsafe fixture database');
$c->instance('db',$capsule->getDatabaseManager());
$c->instance('db.schema',$db->getSchemaBuilder());

$c->instance('log',new Psr\Log\NullLogger);
$c->instance('validator',new Illuminate\Validation\Factory(new Illuminate\Translation\Translator(new Illuminate\Translation\ArrayLoader,'en'),$c));
$db->statement('CREATE TABLE barangays (id INTEGER PRIMARY KEY, name TEXT)');
$db->statement('CREATE TABLE institutions (id INTEGER PRIMARY KEY, name TEXT, type TEXT, barangay_id INTEGER, has_internet_connection BOOLEAN, q10_number_of_connections INTEGER, created_at TEXT, updated_at TEXT)');
$db->statement('CREATE TABLE ict_components (id INTEGER PRIMARY KEY, key TEXT, name TEXT, component_type TEXT, counts_toward_infrastructure BOOLEAN, is_active BOOLEAN, is_standard BOOLEAN, is_computing_device BOOLEAN, created_at TEXT, updated_at TEXT)');
$db->statement('CREATE TABLE ict_inventory (asset_id INTEGER PRIMARY KEY, institution_id INTEGER, component_id INTEGER, is_computing_device BOOLEAN, category TEXT, component_type TEXT, asset_name TEXT, total_count INTEGER, active_count INTEGER, retired_count INTEGER, functional_count INTEGER, functional_count_recorded BOOLEAN, defective_count INTEGER, defective_active_count INTEGER, quantity INTEGER, status TEXT, condition TEXT, needs_maintenance BOOLEAN, created_at TEXT, updated_at TEXT)');
$db->statement('CREATE TABLE institution_internet_connections (id INTEGER PRIMARY KEY, institution_id INTEGER, connection_number INTEGER, isp TEXT, plan TEXT, allocation TEXT, validity TEXT, context TEXT, advertised_download_mbps NUMERIC, download_speed_mbps NUMERIC, upload_speed_mbps NUMERIC, latency_ms NUMERIC, throughput_mbps NUMERIC, created_at TEXT, updated_at TEXT)');
$db->statement('CREATE TABLE institution_analytics (id INTEGER PRIMARY KEY, institution_id INTEGER UNIQUE, gap_score NUMERIC, risk_level TEXT, infrastructure_deficit_score NUMERIC, connectivity_divide_score NUMERIC, technological_obsolescence_score NUMERIC, infrastructure_narrative TEXT, policy_recommendation TEXT, recommendations_detail TEXT, ai_priorities TEXT, analysis_date TEXT, processing_token TEXT, processing_status TEXT, processing_stage TEXT, processing_error TEXT, processing_started_at TEXT, processing_completed_at TEXT, created_at TEXT, updated_at TEXT)');
$db->table('barangays')->insert(['id'=>1,'name'=>'Fixture']);
$db->table('institutions')->insert(['id'=>1,'name'=>'Fixture','type'=>'Barangay Hall','barangay_id'=>1,'has_internet_connection'=>true,'q10_number_of_connections'=>1]);
$db->table('ict_components')->insert(['id'=>1,'key'=>'desktop','name'=>'Desktop','component_type'=>'hardware','counts_toward_infrastructure'=>true,'is_active'=>true,'is_standard'=>true,'is_computing_device'=>true]);
$db->table('ict_inventory')->insert(['asset_id'=>1,'institution_id'=>1,'component_id'=>1,'component_type'=>'hardware','asset_name'=>'Desktop','total_count'=>100,'active_count'=>80,'retired_count'=>20,'functional_count'=>60,'functional_count_recorded'=>true,'defective_count'=>15,'defective_active_count'=>10]);
$db->table('institution_internet_connections')->insert(['id'=>1,'institution_id'=>1,'connection_number'=>1,'plan'=>'100 Mbps','advertised_download_mbps'=>100,'download_speed_mbps'=>60]);
$db->table('institution_analytics')->insert(['id'=>1,'institution_id'=>1,'processing_token'=>'run-1','processing_status'=>'queued']);
class FixtureAnalyticsJob extends App\Jobs\ProcessInstitutionAnalytics {
 public ?array $pythonResult=null;
 protected function requestPythonAnalysis(array $payload,App\Models\Institution $institution): ?array {return $this->pythonResult;}
 public function payload(App\Models\Institution $institution): array {return $this->buildPythonPayload($institution);}
}
class FixtureAi extends App\Services\GeminiService {
 public $before=null;
 public bool $throw=false;
 public function analyzeInfrastructure(App\Models\Institution $institution,array $scores=[],array $planningEvidence=[]): array {
  if($this->before)($this->before)();
  if($this->throw)throw new RuntimeException('Fixture AI outage');
  // Malicious/extraneous numeric AI output must never be used.
  return ['gap_score'=>99,'infrastructure_narrative'=>'Fixture','policy_recommendation'=>'Fixture','recommendations_detail'=>[]];
 }
}
$checks=0;
function integrationCheck($ok,$label){global $checks;if(!$ok)throw new RuntimeException($label);$checks++;}
$calculator=new App\Services\IctGapBaselineCalculator;
$job=new FixtureAnalyticsJob(1,'run-1');$ai=new FixtureAi;$ai->throw=true;$job->handle($ai,$calculator);
$row=App\Models\InstitutionAnalytics::first();
integrationCheck($row->gap_score===24.17 && $row->processing_status==='completed','AI outage preserves deterministic publication');
integrationCheck(data_get($row->recommendations_detail,'ai_status')==='unavailable','AI status persisted');
integrationCheck(data_get($row->recommendations_detail,'score_source')==='laravel_fallback' && data_get($row->recommendations_detail,'python_service_used')===false,'Actual fallback provenance');
$ai=new FixtureAi;$job->handle($ai,$calculator);
integrationCheck(App\Models\InstitutionAnalytics::first()->gap_score===24.17,'AI cannot override scores');
$job->pythonResult=array_replace($calculator->calculate($job->payload(App\Models\Institution::first())),['institution_id'=>1]);
$job->handle($ai,$calculator);
$row=App\Models\InstitutionAnalytics::first();
integrationCheck($row->gap_score===24.17 && data_get($row->recommendations_detail,'score_source')==='python'
 && data_get($row->recommendations_detail,'python_service_used')===true,'Python result is used and provenance persisted');
$job->pythonResult=null;
$db->table('institution_analytics')->update(['processing_token'=>'newer','processing_status'=>'queued']);
$job->handle($ai,$calculator);
integrationCheck(App\Models\InstitutionAnalytics::first()->processing_status==='queued','Stale token cannot overwrite newer run');
$db->table('institution_analytics')->update(['processing_token'=>'run-1']);
$ai->before=fn()=>$db->table('ict_inventory')->where('asset_id',1)->update(['defective_active_count'=>11]);
try{$job->handle($ai,$calculator);throw new RuntimeException('Expected source rejection');}
catch(RuntimeException $e){integrationCheck(str_contains($e->getMessage(),'Source data changed'),'Changed inventory discarded before publication');}
integrationCheck(App\Models\InstitutionAnalytics::first()->gap_score===24.17,'No superseded values published');
$ai->before=null;$job->handle($ai,$calculator);
$inventory=App\Models\IctInventory::first();$inventory->defective_active_count=12;$inventory->save();
(new App\Observers\IctInventoryObserver)->updated($inventory);
integrationCheck(App\Models\InstitutionAnalytics::first()->gap_score===null,'Defective Active change invalidates analytics');
$manager=new App\Services\InternetConnectionChanges;
$institution=App\Models\Institution::first();
$db->transaction(fn()=>$manager->save($institution,['connections'=>[1=>['download_speed_mbps'=>55]]]));
integrationCheck((float)$db->table('institution_internet_connections')->value('advertised_download_mbps')===100.0,'Partial update preserves advertisement');
try{$db->transaction(function()use($db,$manager,$institution){$db->table('ict_inventory')->update(['active_count'=>79]);$manager->save($institution,['has_internet_connection'=>false]);});throw new RuntimeException('Expected conflict');}
catch(Illuminate\Validation\ValidationException $e){integrationCheck((int)$db->table('ict_inventory')->value('active_count')===80,'Connectivity rejection rolls back transaction');}
$db->transaction(fn()=>$manager->save($institution,['removed_connection_numbers'=>[1],'has_internet_connection'=>false,'number_of_connections'=>0]));
integrationCheck($db->table('institution_internet_connections')->count()===0,'Explicit removal persisted');
$institution->refresh();$payload=$job->payload($institution);integrationCheck($calculator->calculate($payload)['connectivity_divide_score']===100.0,'No service persisted and calculated');
// Intake API validation/building uses the same explicit field and retains it on partial updates.
Illuminate\Http\Request::macro('validate', function (array $rules) { return Illuminate\Support\Facades\Validator::make($this->all(),$rules)->validate(); });
$controller=new App\Http\Controllers\IctInventoryController;
$method=new ReflectionMethod($controller,'validateInventory');$method->setAccessible(true);
$request=Illuminate\Http\Request::create('/fixture','PATCH',['defective_active_count'=>5]);
$data=$method->invoke($controller,$request,true,App\Models\IctInventory::first());
integrationCheck($data['defective_active_count']===5,'API explicit intake');
$request=Illuminate\Http\Request::create('/fixture','PATCH',['defective_count'=>14]);
$data=$method->invoke($controller,$request,true,App\Models\IctInventory::first());
integrationCheck($data['defective_active_count']===12,'API omission retains recorded count');
$db->table('ict_inventory')->where('asset_id',1)->update(['functional_count'=>99,'functional_count_recorded'=>false]);
$sync=new ReflectionMethod($controller,'syncInventorySection');$sync->setAccessible(true);
$sync->invoke($controller,1,'hardware',[['asset_id'=>1,'component_id'=>1,'asset_name'=>'Desktop','active_count'=>79]]);
integrationCheck((int)$db->table('ict_inventory')->value('functional_count')===99 && !(bool)$db->table('ict_inventory')->value('functional_count_recorded'),'Unified partial update preserves historical Functional provenance');
$request=Illuminate\Http\Request::create('/fixture','PATCH',['defective_active_count'=>null]);
$data=$method->invoke($controller,$request,true,App\Models\IctInventory::first());
integrationCheck($data['defective_active_count']===null,'API explicit blank remains unknown');
echo "$checks isolated workflow checks passed.\n";
