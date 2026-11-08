<?php
// Pure calculations/encoding checks. No Laravel bootstrap, database or external services.
require __DIR__.'/../vendor/autoload.php';
$calculator = new App\Services\IctGapBaselineCalculator;
if (isset($argv[1])) {
    $payload = json_decode($argv[1], true, 512, JSON_THROW_ON_ERROR);
    // The service contract sends connections prepared by the PHP plan parser.
    if (isset($payload['raw_connections'])) {
        $payload['internet_connections'] = array_map(fn ($row) => (new App\Services\InternetConnectivityAssessment)->prepareConnection($row), $payload['raw_connections']);
        unset($payload['raw_connections']);
    }
    echo json_encode(['payload' => $payload, 'result' => $calculator->calculate($payload)], JSON_THROW_ON_ERROR);
    exit;
}
$container = new Illuminate\Container\Container;
Illuminate\Container\Container::setInstance($container);
Illuminate\Support\Facades\Facade::setFacadeApplication($container);
$container->instance('validator', new Illuminate\Validation\Factory(new Illuminate\Translation\Translator(new Illuminate\Translation\ArrayLoader, 'en'), $container));
$checks=0;
function checkExplicit($ok,$label) { global $checks; if (!$ok) throw new RuntimeException($label); $checks++; }
$base = ['total_applicable_inventory_count'=>100,'active_applicable_inventory_count'=>80,
    'retired_applicable_inventory_count'=>20,'defective_active_applicable_inventory_count'=>10,
    'defective_applicable_inventory_count'=>15,'functional_applicable_inventory_count'=>60,
    'has_internet_connection'=>true,'reported_internet_connection_count'=>1,
    'internet_connections'=>[['connection_number'=>1,'plan_classification'=>'identifiable','advertised_download_mbps'=>100,'download_speed_mbps'=>60,'upload_speed_mbps'=>60]]];
$r=$calculator->calculate($base);
foreach (['infrastructure_deficit_score'=>12.5,'connectivity_divide_score'=>40.0,'technological_obsolescence_score'=>20.0,'gap_score'=>24.17] as $key=>$expected) checkExplicit($r[$key] === $expected,$key);
foreach ([0,null] as $value) {
 $r=$calculator->calculate(array_replace($base,['defective_active_applicable_inventory_count'=>$value]));
 checkExplicit($r['infrastructure_deficit_score'] === ($value === null ? null : 0.0),'Zero versus unknown');
}
foreach ([81,-1,1.5] as $value) checkExplicit($calculator->calculate(array_replace($base,['defective_active_applicable_inventory_count'=>$value]))['infrastructure_deficit_score'] === null,'Invalid explicit numerator');
checkExplicit($calculator->calculate(array_replace($base,['defective_applicable_inventory_count'=>9]))['infrastructure_deficit_score'] === null,'General Defective cap');
checkExplicit($calculator->calculate(array_replace($base,['total_applicable_inventory_count'=>5]))['infrastructure_deficit_score'] === null,'Total cap');
$r=$calculator->calculate(array_replace($base,['active_applicable_inventory_count'=>0,'defective_active_applicable_inventory_count'=>0]));
checkExplicit($r['unavailable_reasons']['infrastructure_deficit_score'] === 'Active count must be greater than zero.','Zero Active');
$r=$calculator->calculate(array_replace($base,['total_applicable_inventory_count'=>0,'retired_applicable_inventory_count'=>0]));
checkExplicit($r['technological_obsolescence_score'] === null && $r['connectivity_divide_score'] === 40.0,'Zero Total partial');
$r=$calculator->calculate(array_replace($base,['functional_applicable_inventory_count'=>-1]));
checkExplicit($r['gap_score'] === 24.17 && $r['invalid_inputs'] !== [],'Functional warning preserves all required indicators');
$two=$base;$two['reported_internet_connection_count']=2;$two['internet_connections'][]=['connection_number'=>2,'plan_classification'=>'identifiable','advertised_download_mbps'=>50,'download_speed_mbps'=>50,'upload_speed_mbps'=>50];
checkExplicit($calculator->calculate($two)['connectivity_divide_score'] === 20.0,'Equal connection weighting');
$two['internet_connections'][1]['download_speed_mbps']=null;
checkExplicit($calculator->calculate($two)['connectivity_divide_score'] === null,'Incomplete expected connection');
checkExplicit($calculator->calculate(array_replace($base,['has_internet_connection'=>false,'reported_internet_connection_count'=>0,'internet_connections'=>[]]))['connectivity_divide_score'] === 100.0,'Explicit no service');
checkExplicit($calculator->calculate(array_replace($base,['has_internet_connection'=>null,'reported_internet_connection_count'=>null,'internet_connections'=>[]]))['connectivity_divide_score'] === null,'Unknown service');
$conn = new App\Services\InternetConnectivityAssessment;
$raw=['connection_number'=>1,'plan'=>'PHP 2000','download_speed_mbps'=>0.54,'upload_speed_mbps'=>0.54];
$r=$calculator->calculate(array_replace($base,['internet_connections'=>[$conn->prepareConnection($raw)]]));
checkExplicit($r['unavailable_reasons']['connectivity_divide_score'] === 'Enter advertised download speed in Mbps for connection 1.','Mabini missing verified advertisement');
checkExplicit($conn->prepareConnection(array_replace($raw,['plan'=>'100 Mbps']))['advertised_download_mbps'] === 100.0,'Plan speed fallback');
checkExplicit($conn->prepareConnection(array_replace($raw,['advertised_download_mbps'=>50]))['advertised_download_mbps'] === 50.0,'Explicit speed priority');
checkExplicit($conn->prepareConnection(array_replace($raw,['plan'=>'100 Mbps','advertised_download_mbps'=>50]))['plan_reason'] === 'advertised_speed_conflict','Conflicting speeds');
$r=$calculator->calculate(array_replace($base,['missing_expected_inventory'=>['Explicitly applicable Desktop']]));
checkExplicit($r['infrastructure_deficit_score'] === null && $r['technological_obsolescence_score'] === null && $r['connectivity_divide_score'] === 40.0,'Established expected coverage');
$changes=new App\Services\InternetConnectionChanges;
$existing=[1=>$raw,2=>array_replace($raw,['connection_number'=>2])];
checkExplicit(count($changes->prepare($existing,['connections'=>[1=>['download_speed_mbps'=>1]]],true,2)['rows']) === 2,'Omission preserves existing connection');
checkExplicit(count($changes->prepare($existing,['removed_connection_numbers'=>[2],'number_of_connections'=>1],true,2)['rows']) === 1,'Explicit removal');
foreach ([['has_internet_connection'=>false],['number_of_connections'=>0],['removed_connection_numbers'=>[9]]] as $input) {
 try { $changes->prepare($existing,$input,true,2); throw new RuntimeException('Expected contradiction rejection'); }
 catch (Illuminate\Validation\ValidationException $e) {checkExplicit(true,'Encoding contradiction');}
}
$scope=new App\Services\InventoryCalculationScope;
foreach ([true,false] as $software) {
 $item=new App\Models\IctInventory(['component_type'=>'software']);$item->setRelation('component',new App\Models\IctComponent(['counts_toward_infrastructure'=>false]));
 checkExplicit($scope->includes($item,$software) === $software,'Existing software policy');
}
$item=new App\Models\IctInventory(['component_type'=>'network']);$item->setRelation('component',new App\Models\IctComponent(['counts_toward_infrastructure'=>false]));
checkExplicit(!$scope->includes($item),'Flagged scope exclusion');
$router=new App\Models\IctInventory(['component_type'=>'network']);$router->setRelation('component',new App\Models\IctComponent(['key'=>'router','counts_toward_infrastructure'=>false]));
checkExplicit($scope->includes($router),'Router remains included in declared Network scope');
echo "$checks explicit-input checks passed.\n";
