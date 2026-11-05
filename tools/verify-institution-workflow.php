<?php
// Isolated regression checks. Never connects to or changes the application database.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections' => ['sqlite' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
]], 'session.driver' => 'array', 'cache.default' => 'array', 'services.rdbma.api_key' => null]);
Illuminate\Support\Facades\DB::purge();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Institution;
use App\Services\InstitutionMapClassification;
if (DB::connection()->getDatabaseName() !== ':memory:') throw new RuntimeException('Unsafe database');
$checks = 0;
function check(bool $ok, string $message): void { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
function rejects(callable $action, string $field): void {
    try { $action(); } catch (ValidationException $e) { check(isset($e->errors()[$field]), 'Wrong validation field: '.$field); return; }
    throw new RuntimeException('Expected rejection: '.$field);
}
Schema::create('barangays', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('geom'); });
Schema::create('institutions', function (Blueprint $t) {
    $t->id(); $t->string('name'); $t->string('type'); $t->integer('barangay_id');
    $t->decimal('latitude', 10, 7); $t->decimal('longitude', 10, 7);
    $t->json('location_geojson')->nullable(); $t->json('location_bounding_box')->nullable();
    $t->integer('nominatim_place_id')->nullable(); $t->string('location_source')->nullable(); $t->string('osm_type')->nullable(); $t->integer('osm_id')->nullable();
    $t->boolean('is_active')->default(true); $t->timestamps();
});
DB::table('barangays')->insert([['id'=>1,'name'=>'Test','geom'=>'test'], ['id'=>2,'name'=>'Other','geom'=>'other']]);
// Test doubles for spatial functions exercise controller rejection paths; they do not verify PostGIS geometry.
$pdo = DB::connection()->getPdo();
$pdo->sqliteCreateFunction('ST_Point', fn($lng, $lat) => json_encode([$lng,$lat]), 2);
$pdo->sqliteCreateFunction('ST_SetSRID', fn($point, $srid) => $point, 2);
$pdo->sqliteCreateFunction('ST_Covers', function($geom, $point) { [$lng,$lat] = json_decode($point,true); return $geom === 'test' && $lat >= 13.8 && $lat <= 14.1 && $lng >= 121 && $lng <= 121.3; }, 2);
$controller = new App\Http\Controllers\InstitutionController;
$base = ['name'=>'Public School Test', 'type'=>'School', 'barangay_id'=>1, 'latitude'=>'13.9411234', 'longitude'=>'121.1631234', 'location_source'=>'manual'];
function createInstitution(array $values) {
    global $controller;
    $request = Request::create('/institutions', 'POST', $values);
    $request->headers->set('Accept', 'application/json');
    return Institution::withoutEvents(fn() => $controller->store($request));
}
$response = createInstitution($base);
check($response->getStatusCode() === 201, 'Valid creation failed');
check($response->getData()->success, 'Missing JSON success');
$saved = Institution::first();
check(abs($saved->latitude - 13.9411234) < 1e-10 && abs($saved->longitude - 121.1631234) < 1e-10, 'Coordinate round trip failed');
rejects(fn()=>createInstitution(array_replace($base, ['name'=>'  PUBLIC SCHOOL TEST  '])), 'name');
check(Institution::count() === 1, 'Duplicate was inserted');
foreach ([['latitude'=>null], ['longitude'=>null], ['latitude'=>91], ['longitude'=>181], ['latitude'=>'13oops'], ['latitude'=>13.5], ['barangay_id'=>2], ['type'=>'Private School']] as $invalid) {
    $field = array_key_first($invalid);
    if ($field === 'barangay_id' || ($invalid['latitude'] ?? null) === 13.5) $field = 'location';
    rejects(fn()=>createInstitution(array_replace($base, ['name'=>'Another'], $invalid)), $field);
}
createInstitution(array_replace($base, ['name'=>'Hall', 'type'=>'Barangay Hall', 'osm_type'=>'node', 'osm_id'=>42]));
rejects(fn()=>createInstitution(array_replace($base, ['name'=>'Renamed hall', 'osm_type'=>'node', 'osm_id'=>42])), 'name');
check(Institution::count() === 2, 'Mapped duplicate was inserted');
function updateInstitution(array $values, Institution $institution) {
    global $controller;
    return Institution::withoutEvents(fn() => $controller->update(Request::create('/institutions/'.$institution->id, 'PUT', $values), $institution));
}
rejects(fn()=>updateInstitution(array_replace($base, ['latitude'=>null]), $saved), 'latitude');
rejects(fn()=>updateInstitution(array_replace($base, ['longitude'=>120]), $saved), 'location');
check($saved->fresh()->longitude === 121.1631234, 'Rejected edit changed stored point');
$withoutCoordinates = $base;
unset($withoutCoordinates['latitude'], $withoutCoordinates['longitude']);
updateInstitution($withoutCoordinates, $saved);
check($saved->fresh()->latitude === 13.9411234, 'Omitted coordinates must preserve existing point');
$feature = ['category'=>'amenity', 'feature_type'=>'school'];
check(InstitutionMapClassification::detect($feature)['type'] === null, 'School alone must be ambiguous');
foreach (['public','government'] as $ownership) {
    check(InstitutionMapClassification::detect($feature + ['extratags'=>['operator:type'=>$ownership]])['type'] === 'School', 'Public school not detected');
}
check(InstitutionMapClassification::detect($feature + ['extratags'=>['operator:type'=>'private','ownership'=>'public']])['excluded'], 'Private evidence must win');
check(InstitutionMapClassification::detect(['category'=>'amenity','feature_type'=>'university','extratags'=>['operator:type'=>'public']])['type'] === null, 'University misclassified');
check(InstitutionMapClassification::detect(['category'=>'office','feature_type'=>'government'])['type'] === null, 'Government office misclassified');
check(InstitutionMapClassification::detect(['category'=>'amenity','feature_type'=>'townhall','extratags'=>['government'=>'barangay']])['type'] === 'Barangay Hall', 'Barangay hall not detected');
check(InstitutionMapClassification::detect(['name'=>'Barangay Hall Public School'])['type'] === null, 'Name-only classification');
check(InstitutionMapClassification::detect([])['type'] === null, 'Missing metadata fallback');
echo "PASS: $checks institution workflow checks (isolated SQLite; spatial functions mocked).\n";
