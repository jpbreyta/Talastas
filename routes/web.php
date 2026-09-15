<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AssignedIctInventoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IctInventoryController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\PortalIctInventoryController;
use App\Http\Controllers\PortalInstitutionController;
use App\Http\Controllers\PortalMappingController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/profile_notifications.php';

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

Route::middleware(['auth', 'rbac:editor'])->group(function (): void {
    Route::get('/ict-inventory/create', [AssignedIctInventoryController::class, 'create'])
        ->name('ict-inventory.create');

    Route::post('/ict-inventory', [AssignedIctInventoryController::class, 'store'])
        ->name('ict-inventory.store');

    Route::get('/ict-inventory/{ict_inventory}/edit', [AssignedIctInventoryController::class, 'edit'])
        ->whereNumber('ict_inventory')
        ->name('ict-inventory.edit');

    Route::put('/ict-inventory/{ict_inventory}', [AssignedIctInventoryController::class, 'update'])
        ->whereNumber('ict_inventory')
        ->name('ict-inventory.update');

    Route::patch('/ict-inventory/{ict_inventory}', [AssignedIctInventoryController::class, 'update'])
        ->whereNumber('ict_inventory');
});

Route::middleware(['auth', 'rbac:admin'])->group(function (): void {
    Route::get('/admin/users', [UserManagementController::class, 'index'])
        ->name('admin.users.index');

    Route::post('/admin/users', [UserManagementController::class, 'store'])
        ->name('admin.users.store');

    Route::put('/admin/users/{user}', [UserManagementController::class, 'update'])
        ->whereNumber('user')
        ->name('admin.users.update');

    Route::delete('/admin/users/{user}', [UserManagementController::class, 'destroy'])
        ->whereNumber('user')
        ->name('admin.users.destroy');

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

Route::middleware('auth')->group(function (): void {
    Route::get('/', [PortalMappingController::class, 'index'])
        ->name('home');

    Route::get('/map', [PortalMappingController::class, 'index'])
        ->name('map');

    Route::get(
        '/map/data/institutions',
        [PortalMappingController::class, 'getInstitutionsGeoJSON']
    )->name('map.data.institutions');

    Route::get(
        '/map/data/barangays',
        [PortalMappingController::class, 'getBarangaysGeoJSON']
    )->name('map.data.barangays');

    Route::get('/analytics', [AnalyticsController::class, 'index'])
        ->name('analytics');

    Route::get('/institutions', [PortalInstitutionController::class, 'index'])
        ->name('institutions.index');

    Route::get('/institutions/{institution}', [PortalInstitutionController::class, 'show'])
        ->whereNumber('institution')
        ->name('institutions.show');

    Route::get('/ict-inventory', [PortalIctInventoryController::class, 'index'])
        ->name('ict-inventory.index');

    Route::get('/ict-inventory/{ict_inventory}', [PortalIctInventoryController::class, 'show'])
        ->whereNumber('ict_inventory')
        ->name('ict-inventory.show');
});
