<?php

// Isolated fixtures only: never connect to the configured application database.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections' => ['sqlite' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
]], 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge();

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (DB::connection()->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('Unsafe database');
}
$checks = 0;
function checkManagement(bool $condition, string $message): void
{
    global $checks;
    if (! $condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}
Schema::create('barangays', function (Blueprint $table) {
    $table->id(); $table->string('name');
});
Schema::create('institutions', function (Blueprint $table) {
    $table->id(); $table->string('name'); $table->string('type'); $table->foreignId('barangay_id');
    $table->boolean('is_active')->default(true); $table->timestamps();
});
// Match the existing cascading inventory relationship and restricting invitation relationship.
Schema::create('ict_inventory', function (Blueprint $table) {
    $table->id(); $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
});
Schema::create('invitation_institution', function (Blueprint $table) {
    $table->id(); $table->foreignId('institution_id')->constrained('institutions')->restrictOnDelete();
});
DB::table('barangays')->insert(['id' => 1, 'name' => 'Fixture Barangay']);
for ($id = 1; $id <= 25; $id++) {
    DB::table('institutions')->insert(['id' => $id, 'name' => sprintf('Fixture %02d', $id),
        'type' => $id % 2 ? 'School' : 'Barangay Hall', 'barangay_id' => 1, 'is_active' => $id !== 3,
        'created_at' => '2026-01-01', 'updated_at' => '2026-01-01']);
}
$admin = new User(['role' => 'admin']);
$editor = new User(['role' => 'editor']);
$viewer = new User(['role' => 'viewer']);
$deletion = new App\Http\Controllers\InstitutionController;
$list = new App\Http\Controllers\PortalInstitutionController;
function managementRequest(array $params = [], ?User $user = null, string $method = 'GET'): Request
{
    global $app, $admin;
    $request = Request::create('/institutions', $method, $params);
    $request->setUserResolver(fn () => $user ?? $admin);
    $app->instance('request', $request);
    return $request;
}
foreach ([$editor, $viewer] as $user) {
    try {
        $deletion->destroyBatch(managementRequest(['institution_ids' => [1]], $user, 'DELETE'));
        throw new RuntimeException('Unauthorized deletion accepted');
    } catch (Symfony\Component\HttpKernel\Exception\HttpException $error) {
        checkManagement($error->getStatusCode() === 403, 'Batch endpoint must reject non-admin users');
    }
    auth()->setUser($user);
    try {
        (new App\Http\Middleware\RBAC)->handle(managementRequest([], $user), fn () => response('unexpected'), 'admin');
        throw new RuntimeException('Admin route middleware accepted non-admin');
    } catch (Symfony\Component\HttpKernel\Exception\HttpException $error) {
        checkManagement($error->getStatusCode() === 403, 'Single/batch admin route middleware must reject non-admin users');
    }
}
auth()->setUser($admin);
foreach (['institutions.destroy', 'institutions.batch-destroy'] as $name) {
    checkManagement(in_array('rbac:admin', app('router')->getRoutes()->getByName($name)->gatherMiddleware()), 'Deletion route must remain administrator-only');
}
checkManagement(Institution::count() === 25, 'Authorization failures must not change records');
$page = $list->index(managementRequest(['page' => 2]))->getData()['institutions'];
checkManagement($page->count() === 5 && $page->total() === 25 && $page->perPage() === 20, 'Twenty-row pagination and totals');
$page = $list->index(managementRequest(['page' => 9]))->getData()['institutions'];
checkManagement($page->currentPage() === 2 && $page->count() === 5, 'Out-of-range page must clamp to the last valid page');
$page = $list->index(managementRequest(['search' => 'not-present', 'page' => 9]))->getData()['institutions'];
checkManagement($page->total() === 0 && $page->currentPage() === 1, 'No-match search must report zero on page one');
$page = $list->index(managementRequest(['sector' => 'School', 'search' => 'Fixture', 'sort' => 'name_desc']))->getData()['institutions'];
checkManagement($page->total() === 13 && $page->first()->name === 'Fixture 25', 'Search, sector filter and sorting must compose');
foreach ([[1, 1], [999]] as $ids) {
    try {
        $deletion->destroyBatch(managementRequest(['institution_ids' => $ids], null, 'DELETE'));
        throw new RuntimeException('Invalid IDs accepted');
    } catch (Illuminate\Validation\ValidationException $error) {
        checkManagement(count($error->errors()) > 0 && Institution::count() === 25, 'Duplicate/stale IDs must fail validation without deletion');
    }
}
DB::table('ict_inventory')->insert(['institution_id' => 1]);
DB::table('invitation_institution')->insert(['institution_id' => 2]);
try {
    $deletion->destroyBatch(managementRequest(['institution_ids' => [1, 2]], null, 'DELETE'));
    throw new RuntimeException('Restricting relationship ignored');
} catch (Illuminate\Database\QueryException $error) {
    checkManagement(Institution::whereIn('id', [1, 2])->count() === 2 && DB::table('ict_inventory')->count() === 1,
        'A restricted record must roll back the entire batch, including existing cascades');
}
DB::table('invitation_institution')->delete();
$payload = $deletion->destroyBatch(managementRequest(['institution_ids' => [1, 2]], null, 'DELETE'))->getData(true);
checkManagement($payload['success'] && $payload['deleted_ids'] === [1, 2] && Institution::count() === 23, 'Successful batch confirms exact deleted IDs');
checkManagement(DB::table('ict_inventory')->count() === 0, 'Existing database cascade policy is preserved, with no new manual cascade');
$response = $deletion->destroy(Institution::findOrFail(3));
checkManagement($response->getData(true)['success'] && !Institution::find(3), 'Existing single deletion remains supported');
$page = $list->index(managementRequest(['page' => 9]))->getData()['institutions'];
checkManagement($page->total() === 22 && $page->currentPage() === 2 && $page->count() === 2, 'Deletion updates totals and nearest valid page');

foreach (['index', '_table-results', 'modals'] as $name) {
    $compiled = app('blade.compiler')->compileString(file_get_contents(__DIR__."/../resources/views/institutions/$name.blade.php"));
    $path = tempnam(sys_get_temp_dir(), 'talastas-institution-view-');
    try {
        file_put_contents($path, $compiled);
        $process = proc_open([PHP_BINARY, '-l', $path], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        checkManagement(proc_close($process) === 0, "$name Blade syntax: $output");
    } finally {
        unlink($path);
    }
}
echo "PASS: $checks institution authorization, deletion, pagination and Blade checks (isolated SQLite).\n";
