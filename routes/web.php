<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IctInventoryController;
use App\Http\Controllers\IctMappingController;
use App\Http\Controllers\InstitutionController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/profile_notifications.php';

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.submit');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
});

/*
|--------------------------------------------------------------------------
| Editor capabilities
|--------------------------------------------------------------------------
| Register /create before the read-only /{record} routes below so Laravel
| never interprets the literal word "create" as a model identifier.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'rbac:editor'])->group(function (): void {
    Route::get('/ict-inventory/create', [IctInventoryController::class, 'create'])
        ->name('ict-inventory.create');

    Route::post('/ict-inventory', [IctInventoryController::class, 'store'])
        ->name('ict-inventory.store');

    Route::get('/ict-inventory/{ict_inventory}/edit', [IctInventoryController::class, 'edit'])
        ->whereNumber('ict_inventory')
        ->name('ict-inventory.edit');

    Route::put('/ict-inventory/{ict_inventory}', [IctInventoryController::class, 'update'])
        ->whereNumber('ict_inventory')
        ->name('ict-inventory.update');

    Route::patch('/ict-inventory/{ict_inventory}', [IctInventoryController::class, 'update'])
        ->whereNumber('ict_inventory');
});

/*
|--------------------------------------------------------------------------
| System Administrator capabilities
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'rbac:admin'])->group(function (): void {
    Route::get('/institutions/create', [InstitutionController::class, 'create'])
        ->name('institutions.create');

    Route::post('/institutions', [InstitutionController::class, 'store'])
        ->name('institutions.store');

    Route::post(
        '/institutions/geocode',
        [InstitutionController::class, 'geocode']
    )->name('institutions.geocode');

    Route::post(
        '/institutions/{institution}/analytics/run',
        [AnalyticsController::class, 'analyzeInstitution']
    )
        ->whereNumber('institution')
        ->name('institutions.analytics.run');

    Route::get('/institutions/{institution}/edit', [InstitutionController::class, 'edit'])
        ->whereNumber('institution')
        ->name('institutions.edit');

    Route::put('/institutions/{institution}', [InstitutionController::class, 'update'])
        ->whereNumber('institution')
        ->name('institutions.update');

    Route::patch('/institutions/{institution}', [InstitutionController::class, 'update'])
        ->whereNumber('institution');

    Route::delete('/institutions/{institution}', [InstitutionController::class, 'destroy'])
        ->whereNumber('institution')
        ->name('institutions.destroy');

    Route::delete('/ict-inventory/{ict_inventory}', [IctInventoryController::class, 'destroy'])
        ->whereNumber('ict_inventory')
        ->name('ict-inventory.destroy');
});

/*
|--------------------------------------------------------------------------
| Shared read-only portal access
|--------------------------------------------------------------------------
| All authenticated roles may use the same Talastas shell and inspect maps,
| institution records, inventory records, analytics, notifications, and their
| own profile. Mutation permissions are enforced in the groups above.
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function (): void {
    Route::get('/', [IctMappingController::class, 'index'])
        ->name('home');

    Route::get('/map', [IctMappingController::class, 'index'])
        ->name('map');

    Route::get(
        '/map/data/institutions',
        [IctMappingController::class, 'getInstitutionsGeoJSON']
    )->name('map.data.institutions');

    Route::get(
        '/map/data/barangays',
        [IctMappingController::class, 'getBarangaysGeoJSON']
    )->name('map.data.barangays');

    Route::get('/analytics', [AnalyticsController::class, 'index'])
        ->name('analytics');

    Route::get('/institutions', [InstitutionController::class, 'index'])
        ->name('institutions.index');

    Route::get('/institutions/{institution}', [InstitutionController::class, 'show'])
        ->whereNumber('institution')
        ->name('institutions.show');

    Route::get('/ict-inventory', [IctInventoryController::class, 'index'])
        ->name('ict-inventory.index');

    Route::get('/ict-inventory/{ict_inventory}', [IctInventoryController::class, 'show'])
        ->whereNumber('ict_inventory')
        ->name('ict-inventory.show');
});
