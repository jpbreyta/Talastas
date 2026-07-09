<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IctInventoryController;
use App\Http\Controllers\IctMappingController;
use App\Http\Controllers\InstitutionController;
use App\Models\Barangay;
use App\Models\Institution;
use App\Models\InstitutionAnalytics;
use App\Models\IctInventory;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    */

    Route::post('/auth/login', [AuthController::class, 'apiLogin']);

    Route::get('/institutions', function () {
        return response()->json(
            Institution::with('barangay')->get()
        );
    });

    Route::get('/institutions/{institution}', function (Institution $institution) {
        return response()->json(
            $institution->load([
                'barangay',
                'ictInventory',
                'institutionAnalytics'
            ])
        );
    });

    Route::get('/barangays', function () {
        return response()->json(Barangay::all());
    });

    Route::get('/map/institutions', [IctMappingController::class, 'getInstitutionsGeoJSON']);
    Route::get('/barangays/geojson', [IctMappingController::class, 'getBarangaysGeoJSON']);
    Route::get('/map/barangays', [IctMappingController::class, 'getBarangaysGeoJSON']);

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (Sanctum)
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/auth/me', [AuthController::class, 'me']);

        // Institution CRUD
        Route::post('/institutions', [InstitutionController::class, 'storeApi']);
        Route::put('/institutions/{institution}', [InstitutionController::class, 'updateApi']);
        Route::delete('/institutions/{institution}', [InstitutionController::class, 'destroyApi']);

        // ICT Inventory
        Route::get('/ict-inventory', function () {
            return response()->json(
                IctInventory::with('institution')->get()
            );
        });

        Route::get('/ict-inventory/{ictInventory}', function (IctInventory $ictInventory) {
            return response()->json(
                $ictInventory->load('institution')
            );
        });

        Route::post('/ict-inventory', [IctInventoryController::class, 'storeApi']);
        Route::put('/ict-inventory/{ictInventory}', [IctInventoryController::class, 'updateApi']);
        Route::delete('/ict-inventory/{ictInventory}', [IctInventoryController::class, 'destroyApi']);

        // Analytics
        Route::get('/analytics', [AnalyticsController::class, 'getAnalytics']);
        Route::post('/analytics/analyze/{institution}', [AnalyticsController::class, 'analyzeInstitution']);
        Route::post('/analytics/analyze-all', [AnalyticsController::class, 'analyzeAll']);

        // Geocoding
        Route::post('/institutions/geocode', [InstitutionController::class, 'geocode']);

        // Export
        Route::get('/analytics/export', function () {
            $analytics = InstitutionAnalytics::with('institution')->get();

            return response()->json([
                'export_url' => 'report would be generated here'
            ]);
        });

        // Barangay Summary
        Route::get('/barangays/summary', function () {
            $summary = Institution::join('barangays', 'institutions.barangay_id', '=', 'barangays.id')
                ->select('barangays.name as barangay_name')
                ->groupBy('barangays.name')
                ->get();

            return response()->json($summary);
        });

    });


    Route::middleware('auth:sanctum')->post('/auth/logout', [AuthController::class, 'apiLogout']);
});