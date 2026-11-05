<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AssignedIctInventoryController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IctInventoryController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\PortalIctInventoryController;
use App\Http\Controllers\PortalInstitutionController;
use App\Http\Controllers\PortalMappingController;
use App\Http\Controllers\UserActivityController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/security.php';
require __DIR__.'/legal.php';
require __DIR__.'/profile_notifications.php';

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1,auth-login:')
        ->name('login.submit');
});

Route::middleware(['auth:web', 'auth.session', 'account.secure', 'terms.accepted'])->group(function (): void {
    Route::post('/activity/heartbeat', [UserActivityController::class, 'heartbeat'])->middleware('throttle:120,1,activity-heartbeat:')->name('activity.heartbeat');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
});

Route::middleware(['auth:web', 'auth.session', 'account.secure', 'terms.accepted', 'rbac:editor'])->group(function (): void {
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

    Route::post(
        '/institutions/{institution}/audits',
        [AuditController::class, 'store']
    )
        ->whereNumber('institution')
        ->name('audits.store');

    Route::put('/audits/{audit}', [AuditController::class, 'update'])
        ->whereNumber('audit')
        ->name('audits.update');

    Route::delete('/audits/{audit}', [AuditController::class, 'destroy'])
        ->whereNumber('audit')
        ->name('audits.destroy');
});

Route::middleware(['auth:web', 'auth.session', 'account.secure', 'terms.accepted', 'rbac:admin'])->group(function (): void {
    Route::get('/admin/users/logs', [UserActivityController::class, 'index'])->name('admin.users.logs');

    Route::get('/admin/users', [UserManagementController::class, 'index'])
        ->name('admin.users.index');

    Route::get('/admin/users/create', [UserManagementController::class, 'create'])
        ->name('admin.users.create');

    Route::post('/admin/users', [UserManagementController::class, 'store'])
        ->name('admin.users.store');

    Route::put('/admin/users/{user}', [UserManagementController::class, 'update'])
        ->whereNumber('user')
        ->middleware('mfa.fresh')
        ->name('admin.users.update');

    Route::delete('/admin/users/{user}', [UserManagementController::class, 'destroy'])
        ->whereNumber('user')
        ->middleware('mfa.fresh')
        ->name('admin.users.destroy');

    Route::get('/institutions/create', [InstitutionController::class, 'create'])
        ->name('institutions.create');

    Route::post('/institutions', [InstitutionController::class, 'store'])
        ->name('institutions.store');

    Route::post(
        '/institutions/geocode',
        [InstitutionController::class, 'geocode']
    )->middleware('throttle:30,1')->name('institutions.geocode');

    Route::post(
        '/institutions/resolve-coordinates',
        [InstitutionController::class, 'resolveCoordinates']
    )->middleware('throttle:30,1')->name('institutions.resolve-coordinates');

    Route::post(
        '/institutions/osm-boundary/resolve',
        [InstitutionController::class, 'resolveOsmBoundary']
    )->middleware('throttle:30,1')->name('institutions.osm-boundary.resolve');

    Route::post(
        '/institutions/{institution}/osm-boundary',
        [InstitutionController::class, 'fetchOsmBoundary']
    )
        ->whereNumber('institution')
        ->middleware('throttle:30,1')->name('institutions.osm-boundary.fetch');

    Route::post(
        '/institutions/{institution}/analytics/run',
        [AnalyticsController::class, 'analyzeInstitution']
    )
        ->whereNumber('institution')
        ->middleware('throttle:10,1')->name('institutions.analytics.run');

    Route::get('/institutions/{institution}/edit', [InstitutionController::class, 'edit'])
        ->whereNumber('institution')
        ->name('institutions.edit');

    Route::put('/institutions/{institution}', [InstitutionController::class, 'update'])
        ->whereNumber('institution')
        ->name('institutions.update');

    Route::delete('/institutions/batch-delete', [InstitutionController::class, 'destroyBatch'])
        ->name('institutions.batch-destroy');

    Route::delete('/institutions/{institution}', [InstitutionController::class, 'destroy'])
        ->whereNumber('institution')
        ->name('institutions.destroy');

    Route::delete('/ict-inventory/{ict_inventory}', [IctInventoryController::class, 'destroy'])
        ->whereNumber('ict_inventory')
        ->name('ict-inventory.destroy');
});

Route::middleware(['auth:web', 'auth.session', 'account.secure', 'terms.accepted', 'rbac:viewer'])->group(function (): void {
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

    Route::get('/map/analytics/summary', [AnalyticsController::class, 'getAnalytics'])
        ->name('map.analytics.summary');

    Route::get('/analytics', [AnalyticsController::class, 'index'])
        ->name('analytics');

    Route::get('/analytics/report.pdf', [AnalyticsController::class, 'exportPdf'])
        ->middleware('rbac:planner')->name('analytics.report');
    Route::get('/analytics/export.csv', [AnalyticsController::class, 'export'])
        ->middleware('rbac:planner')->name('analytics.csv');

    Route::get('/institutions', [PortalInstitutionController::class, 'index'])
        ->name('institutions.index');

    Route::get('/institutions/{institution}', [PortalInstitutionController::class, 'show'])
        ->whereNumber('institution')
        ->name('institutions.show');

    Route::get(
        '/institutions/{institution}/analytics/state',
        [AnalyticsController::class, 'institutionAnalyticsState']
    )
        ->whereNumber('institution')
        ->name('institutions.analytics.state');

    Route::get(
        '/institutions/{institution}/analytics/stream',
        [AnalyticsController::class, 'streamInstitutionAnalytics']
    )
        ->whereNumber('institution')
        ->name('institutions.analytics.stream');

    Route::get('/ict-inventory', [PortalIctInventoryController::class, 'index'])
        ->name('ict-inventory.index');

    Route::get('/ict-inventory/{ict_inventory}', [PortalIctInventoryController::class, 'show'])
        ->whereNumber('ict_inventory')
        ->name('ict-inventory.show');

    Route::get('/audits', [AuditController::class, 'index'])
        ->name('audits.index');

    Route::get(
        '/institutions/{institution}/audits/create',
        [AuditController::class, 'create']
    )
        ->whereNumber('institution')
        ->middleware('rbac:editor')->name('audits.create');

    Route::get('/audits/{audit}/edit', [AuditController::class, 'edit'])
        ->whereNumber('audit')
        ->middleware('rbac:editor')->name('audits.edit');
});
