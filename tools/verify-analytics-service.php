<?php
// Mocked service tests: deliberately no application bootstrap or database connection.
require __DIR__.'/../vendor/autoload.php';
$c = new Illuminate\Container\Container;
Illuminate\Container\Container::setInstance($c);
Illuminate\Support\Facades\Facade::setFacadeApplication($c);
$c->instance('config', new Illuminate\Config\Repository(['services'=>['python_analytics'=>['url'=>'http://mock.test','timeout'=>1]]]));
$c->instance('log', new Psr\Log\NullLogger);
$c->instance('validator', new Illuminate\Validation\Factory(new Illuminate\Translation\Translator(new Illuminate\Translation\ArrayLoader,'en'),$c));
class ServiceProbe extends App\Jobs\ProcessInstitutionAnalytics {
    public function request(array $payload) { $institution=new App\Models\Institution; $institution->id=1; return $this->requestPythonAnalysis($payload, $institution); }
}
$job = new ServiceProbe(1);
foreach ([400, 422] as $code) {
    $http = new Illuminate\Http\Client\Factory;
    $http->fake(['*'=>Illuminate\Http\Client\Factory::response(['detail'=>'Invalid inputs'],$code)]);
    Illuminate\Support\Facades\Http::swap($http);

    try { $job->request([]); throw new RuntimeException('Validation failure accepted'); }
    catch (UnexpectedValueException $expected) {}
}
$http = new Illuminate\Http\Client\Factory;
$http->fake(['*'=>Illuminate\Http\Client\Factory::response([],503)]);
Illuminate\Support\Facades\Http::swap($http);

if ($job->request([]) !== null) throw new RuntimeException('Technical failure must permit deterministic fallback');
$payload=['total_applicable_inventory_count'=>100,'active_applicable_inventory_count'=>80,
    'retired_applicable_inventory_count'=>20,'defective_active_applicable_inventory_count'=>10,
    'defective_applicable_inventory_count'=>15,'functional_applicable_inventory_count'=>60,
    'has_internet_connection'=>false,'reported_internet_connection_count'=>0,'internet_connections'=>[]];
$valid=array_merge((new App\Services\IctGapBaselineCalculator)->calculate($payload),['institution_id'=>1,
    'connectivity_method'=>App\Services\InternetConnectivityAssessment::METHOD]);
foreach ([$valid, array_replace($valid,['institution_id'=>2]), array_replace($valid,['gap_score'=>101]), array_diff_key($valid,['inventory_inputs'=>true])] as $index=>$body) {
    $http=new Illuminate\Http\Client\Factory;
    $http->fake(['*'=>Illuminate\Http\Client\Factory::response($body,200)]);
    Illuminate\Support\Facades\Http::swap($http);
    if ($index===0) {
        if ($job->request($payload)!=$valid) throw new RuntimeException('Valid Python result was not accepted');
    } else {
        try {$job->request($payload);throw new RuntimeException('Invalid successful Python response accepted');}
        catch (UnexpectedValueException $expected) {}
    }
}
$http=new Illuminate\Http\Client\Factory;
$http->fake(['*'=>function(){throw new Illuminate\Http\Client\ConnectionException('fixture timeout');}]);
Illuminate\Support\Facades\Http::swap($http);
if ($job->request($payload)!==null) throw new RuntimeException('Connection failure must permit deterministic fallback');
echo "8 mocked Python integration checks passed.\n";
