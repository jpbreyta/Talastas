<?php
// Mocked service tests: deliberately no application bootstrap or database connection.
require __DIR__.'/../vendor/autoload.php';
$c = new Illuminate\Container\Container;
Illuminate\Container\Container::setInstance($c);
Illuminate\Support\Facades\Facade::setFacadeApplication($c);
$c->instance('config', new Illuminate\Config\Repository(['services'=>['python_analytics'=>['url'=>'http://mock.test','timeout'=>1]]]));
$c->instance('log', new Psr\Log\NullLogger);
class ServiceProbe extends App\Jobs\ProcessInstitutionAnalytics {
    public function request(array $payload) { return $this->requestPythonAnalysis($payload, new App\Models\Institution(['id'=>1])); }
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
echo "3 mocked service-failure checks passed.\n";
