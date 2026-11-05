<?php
// Pure regression checks: no Laravel bootstrap, database, migrations, HTTP or AI.
require __DIR__.'/../vendor/autoload.php';
$c = new App\Services\IctGapBaselineCalculator;
if (isset($argv[1])) {
    echo json_encode($c->calculate(json_decode($argv[1], true, 512, JSON_THROW_ON_ERROR)));
    exit;
}
$checks = 0;
function verify($ok, $label) { global $checks; if (!$ok) throw new RuntimeException($label); $checks++; }
$p = ['total_applicable_inventory_count'=>10, 'active_applicable_inventory_count'=>8,
'retired_applicable_inventory_count'=>2, 'functional_applicable_inventory_count'=>7,
'defective_applicable_inventory_count'=>1, 'has_internet_connection'=>true,
'bandwidth_allocation_mbps'=>100, 'download_speed_mbps'=>40];
$r = $c->calculate($p);
verify($r['gap_score'] === null && $r['infrastructure_deficit_score'] === null, 'Unconfirmed defective population');
verify($r['connectivity_divide_score'] === 60.0 && $r['technological_obsolescence_score'] === 20.0, 'Available components');
foreach ([null, 0] as $retired) {
$r=$c->calculate(array_replace($p,['retired_applicable_inventory_count'=>$retired]));
verify($r['technological_obsolescence_score'] === ($retired === null ? null : 0.0), 'Unknown versus zero');
}
$r=$c->calculate(array_replace($p,['active_applicable_inventory_count'=>0]));
verify($r['infrastructure_deficit_score'] === null, 'Functional positive Active zero');
$r=$c->calculate(array_replace($p,['defective_applicable_inventory_count'=>9]));
verify($r['infrastructure_deficit_score'] === null, 'Defective greater than Active');
foreach ([['download_speed_mbps'=>null],['download_speed_mbps'=>-1],['bandwidth_allocation_mbps'=>0]] as $change) {
verify($c->calculate(array_replace($p,$change))['connectivity_divide_score'] === null, 'Invalid or missing connectivity');
}
verify($c->calculate(array_replace($p,['has_internet_connection'=>false]))['connectivity_divide_score'] === 100.0, 'Explicit no Internet');
verify($c->calculate(array_replace($p,['total_applicable_inventory_count'=>0,'retired_applicable_inventory_count'=>0]))['technological_obsolescence_score'] === null, 'Zero denominator');
verify($c->calculate(array_replace($p,['retired_applicable_inventory_count'=>11]))['technological_obsolescence_score'] === null, 'Invalid subset');
verify($c->fromPythonResult(['gap_score'=>0],$p) === $c->calculate($p), 'External scores cannot override');
verify(App\Services\InventoryCounts::sum(collect([['x'=>0],['x'=>null]]),'x') === null, 'Incomplete sum');
verify(App\Services\InventoryCounts::sum(collect([['x'=>0]]),'x') === 0, 'Confirmed zero sum');
verify(App\Services\InventoryCounts::quantity('1.5') === null, 'Reject fractional count');
verify(App\Services\InventoryCounts::errors(['total_count'=>10,'active_count'=>8,'retired_count'=>8]) === [], 'No invented exhaustive state constraint');
echo "$checks isolated checks passed.\n";

$scope = new App\Services\InventoryCalculationScope;
foreach ([true, false] as $includeSoftware) {
    $item = new App\Models\IctInventory(['component_type'=>'software']);
    verify($scope->includes($item, $includeSoftware) === $includeSoftware, 'Software scope');
}
echo "Scope checks passed.\n";
