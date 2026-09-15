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

    /*
    |--------------------------------------------------------------------------
    | Shared read-only portal access
    | admin, editor, and viewer may access these screens.
    |--------------------------------------------------------------------------
    */
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

    Route::resource('institutions', InstitutionController::class)
        ->only(['index', 'show']);

    Route::resource('ict-inventory', IctInventoryController::class)
        ->only(['index', 'show']);
});

/*
|--------------------------------------------------------------------------
| Editor capabilities
|--------------------------------------------------------------------------
| System Administrators inherit editor access through the RBAC middleware.
| Editors may create and update ICT inventory records, but may not delete
| records or administer institution master data.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'rbac:editor'])->group(function (): void {
    Route::get('/ict-inventory/create', [IctInventoryController::class, 'create'])
        ->name('ict-inventory.create');

    Route::post('/ict-inventory', [IctInventoryController::class, 'store'])
        ->name('ict-inventory.store');

    Route::get('/ict-inventory/{ict_inventory}/edit', [IctInventoryController::class, 'edit'])
        ->name('ict-inventory.edit');

    Route::put('/ict-inventory/{ict_inventory}', [IctInventoryController::class, 'update'])
        ->name('ict-inventory.update');

    Route::patch('/ict-inventory/{ict_inventory}', [IctInventoryController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| System Administrator capabilities
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'rbac:admin'])->group(function (): void {
    Route::post(
        '/institutions/{institution}/analytics/run',
        [AnalyticsController::class, 'analyzeInstitution']
    )
        ->whereNumber('institution')
        ->name('institutions.analytics.run');

    Route::post(
        '/institutions/geocode',
        [InstitutionController::class, 'geocode']
    )->name('institutions.geocode');

    Route::get('/institutions/create', [InstitutionController::class, 'create'])
        ->name('institutions.create');

    Route::post('/institutions', [InstitutionController::class, 'store'])
        ->name('institutions.store');

    Route::get('/institutions/{institution}/edit', [InstitutionController::class, 'edit'])
        ->name('institutions.edit');

    Route::put('/institutions/{institution}', [InstitutionController::class, 'update'])
        ->name('institutions.update');

    Route::patch('/institutions/{institution}', [InstitutionController::class, 'update']);

    Route::delete('/institutions/{institution}', [InstitutionController::class, 'destroy'])
        ->name('institutions.destroy');

    Route::delete('/ict-inventory/{ict_inventory}', [IctInventoryController::class, 'destroy'])
        ->name('ict-inventory.destroy');
});
