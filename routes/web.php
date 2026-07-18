<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IctInventoryController;
use App\Http\Controllers\IctMappingController;
use App\Http\Controllers\InstitutionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| These routes handle the browser-based login and logout process.
|
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
| LGU Administrator Routes
|--------------------------------------------------------------------------
|
| These routes require an authenticated browser session and an administrator
| role. They are used by the Laravel web interface, not by the Flutter API.
|
*/

Route::middleware(['auth', 'rbac:admin'])->group(function (): void {
    /*
    |--------------------------------------------------------------------------
    | Mapping
    |--------------------------------------------------------------------------
    */

    Route::get('/', [IctMappingController::class, 'index'])
        ->name('home');

    Route::get('/map', [IctMappingController::class, 'index'])
        ->name('map');

    /*
    | Return institution GeoJSON using the authenticated browser session.
    | The web mapping page should use this route instead of the mobile API.
    */
    Route::get(
        '/map/data/institutions',
        [IctMappingController::class, 'getInstitutionsGeoJSON']
    )->name('map.data.institutions');

    /*
    | Return barangay GeoJSON for the Laravel mapping page when required.
    */
    Route::get(
        '/map/data/barangays',
        [IctMappingController::class, 'getBarangaysGeoJSON']
    )->name('map.data.barangays');

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    */

    Route::get('/analytics', [AnalyticsController::class, 'index'])
        ->name('analytics');

    /*
    |--------------------------------------------------------------------------
    | Institutions
    |--------------------------------------------------------------------------
    */

    /*
    | This route must remain before the resource routes for clarity.
    | It searches for institution locations from the web modal.
    */
    Route::post(
        '/institutions/geocode',
        [InstitutionController::class, 'geocode']
    )->name('institutions.geocode');

    Route::resource('institutions', InstitutionController::class);

    /*
    |--------------------------------------------------------------------------
    | ICT Inventory
    |--------------------------------------------------------------------------
    */

    Route::resource('ict-inventory', IctInventoryController::class);
});