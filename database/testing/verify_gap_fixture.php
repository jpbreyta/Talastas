<?php
require __DIR__.'/../../vendor/autoload.php';
$env = Dotenv\Dotenv::parse(file_get_contents(__DIR__.'/../../.env'));
try {
    $db = new PDO('pgsql:host='.$env['DB_HOST'].';port='.$env['DB_PORT'].';dbname='.$env['DB_DATABASE'], $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    foreach (['barangays','ict_components','institutions','ict_inventory','institution_internet_connections','institution_analytics'] as $table) {
        $db->exec('CREATE TEMP TABLE '.$table.' (LIKE public.'.$table.' INCLUDING DEFAULTS INCLUDING CONSTRAINTS INCLUDING INDEXES)');
        if (in_array($table, ['barangays','ict_components'])) {
            $db->exec('INSERT INTO pg_temp.'.$table.' SELECT * FROM public.'.$table);
        } else {
            $column = $table === 'ict_inventory' ? 'asset_id' : 'id';
            $db->exec('CREATE TEMP SEQUENCE fixture_'.$table.'_id');
            $db->exec("ALTER TABLE pg_temp.$table ALTER COLUMN $column SET DEFAULT nextval('pg_temp.fixture_".$table."_id')");
            $db->exec("ALTER SEQUENCE pg_temp.fixture_".$table."_id OWNED BY pg_temp.$table.$column");
        }
    }
    // Simulate imported records with IDs ahead of their sequence values.
    $barangayId = (int) $db->query('SELECT min(id) FROM pg_temp.barangays')->fetchColumn();
    $db->exec("INSERT INTO pg_temp.institutions (id,name,type,barangay_id,latitude,longitude) VALUES (5000,'Existing record sequence regression','School',$barangayId,13.94,121.16)");
    $db->exec("INSERT INTO pg_temp.ict_inventory (asset_id,institution_id,category,condition,status) VALUES (112,5000,'regression_fixture','good','active')");
    $sql = file_get_contents(__DIR__.'/gap_analysis_10_barangays.sql');
    // All unqualified fixture writes resolve to connection-local temporary tables.
    $db->exec($sql);
    $first = $db->query("SELECT count(*) AS institutions, count(DISTINCT barangay_id) AS barangays FROM pg_temp.institutions WHERE id<>5000")->fetch(PDO::FETCH_ASSOC);
    echo json_encode($first), PHP_EOL;
    echo 'Inventory rows: '.$db->query('SELECT count(*) FROM pg_temp.ict_inventory WHERE institution_id<>5000')->fetchColumn(), PHP_EOL;
    if ((int)$db->query('SELECT count(*) FROM pg_temp.ict_inventory WHERE asset_id=112 AND institution_id=5000')->fetchColumn() !== 1) throw new RuntimeException('Existing row changed.');
    echo 'Stale sequences repaired; existing inventory ID 112 preserved.', PHP_EOL;
    $calculator = new App\Services\IctGapBaselineCalculator;
    $assessment = new App\Services\InternetConnectivityAssessment;
    foreach ($db->query('SELECT * FROM pg_temp.institutions WHERE id<>5000')->fetchAll(PDO::FETCH_ASSOC) as $institution) {
        $id = (int) $institution['id'];
        $inventory = $db->query("SELECT v.* FROM pg_temp.ict_inventory v JOIN pg_temp.ict_components c ON c.id=v.component_id WHERE institution_id=$id AND (v.component_type='software' OR (v.component_type IN ('hardware','network') AND c.counts_toward_infrastructure))")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($inventory as $item) {
            if (App\Services\InventoryCounts::errors($item) || $item['functional_count']+$item['defective_count']!=$item['active_count']) {
                throw new RuntimeException('Inventory count consistency failed.');
            }
        }
        $connections = array_map(fn ($row) => $assessment->prepareConnection($row), $db->query("SELECT * FROM pg_temp.institution_internet_connections WHERE institution_id=$id")->fetchAll(PDO::FETCH_ASSOC));
        $payload = [
            'total_applicable_inventory_count' => array_sum(array_column($inventory,'total_count')),
            'active_applicable_inventory_count' => array_sum(array_column($inventory,'active_count')),
            'retired_applicable_inventory_count' => array_sum(array_column($inventory,'retired_count')),
            'defective_applicable_inventory_count' => array_sum(array_column($inventory,'defective_count')),
            'reported_internet_connection_count' => (int) $institution['q10_number_of_connections'],
            'internet_connections' => $connections,
        ];
        $expected = $calculator->calculate($payload);
        $stored = $db->query("SELECT * FROM pg_temp.institution_analytics WHERE institution_id=$id")->fetch(PDO::FETCH_ASSOC);
        foreach ($expected as $key => $value) {
            if ($value === null || (is_numeric($value) ? abs((float)$stored[$key]-$value)>0.001 : $stored[$key]!==$value)) {
                throw new RuntimeException("Calculator mismatch: $id / $key");
            }
        }
    }
    echo 'All 20 analyses match the PHP calculator; inventory counts are consistent.', PHP_EOL;
    $db->exec($sql);
    echo 'Rerun institution count: '.$db->query('SELECT count(*) FROM pg_temp.institutions WHERE id<>5000')->fetchColumn(), PHP_EOL;
    echo 'AI outputs present: '.$db->query('SELECT count(*) FROM pg_temp.institution_analytics WHERE policy_recommendation IS NOT NULL OR infrastructure_narrative IS NOT NULL OR ai_priorities IS NOT NULL')->fetchColumn(), PHP_EOL;

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    fwrite(STDERR, get_class($e).': '.$e->getMessage().PHP_EOL);
    exit(1);
}
