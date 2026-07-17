<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IctInventoryController;
use App\Http\Controllers\IctMappingController;
use App\Http\Controllers\InstitutionController;
use App\Models\Barangay;
use App\Models\IctInventory;
use App\Models\Institution;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Service Health
    |--------------------------------------------------------------------------
    */

    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'version' => '1.0',
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/auth/login',
        [AuthController::class, 'apiLogin']
    );

    /*
    |--------------------------------------------------------------------------
    | Public Read-Only Routes
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/institutions',
        [InstitutionController::class, 'indexApi']
    );

    Route::get(
        '/institutions/{institution}',
        [InstitutionController::class, 'showApi']
    )->whereNumber('institution');

    Route::get('/barangays', function () {
        return response()->json([
            'success' => true,
            'data' => Barangay::query()
                ->orderBy('name')
                ->get(),
        ]);
    });

    Route::get(
        '/map/institutions',
        [IctMappingController::class, 'getInstitutionsGeoJSON']
    );

    Route::get(
        '/barangays/geojson',
        [IctMappingController::class, 'getBarangaysGeoJSON']
    );

    /*
    |--------------------------------------------------------------------------
    | Protected Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {
        Route::get(
            '/auth/me',
            [AuthController::class, 'me']
        );

        Route::post(
            '/auth/logout',
            [AuthController::class, 'apiLogout']
        );

        /*
        |--------------------------------------------------------------------------
        | Institution Management
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/institutions/geocode',
            [InstitutionController::class, 'geocode']
        );

        Route::post(
            '/institutions',
            [InstitutionController::class, 'storeApi']
        );

        Route::put(
            '/institutions/{institution}',
            [InstitutionController::class, 'updateApi']
        )->whereNumber('institution');

        Route::patch(
            '/institutions/{institution}',
            [InstitutionController::class, 'updateApi']
        )->whereNumber('institution');

        Route::delete(
            '/institutions/{institution}',
            [InstitutionController::class, 'destroyApi']
        )->whereNumber('institution');

        /*
        |--------------------------------------------------------------------------
        | ICT Inventory
        |--------------------------------------------------------------------------
        */

        Route::get('/ict-inventory', function () {
            return response()->json([
                'success' => true,
                'data' => IctInventory::query()
                    ->with('institution')
                    ->latest()
                    ->get(),
            ]);
        });

        Route::get(
            '/ict-inventory/{ictInventory}',
            function (IctInventory $ictInventory) {
                return response()->json([
                    'success' => true,
                    'data' => $ictInventory->load('institution'),
                ]);
            }
        )->whereNumber('ictInventory');

        Route::post(
            '/ict-inventory',
            [IctInventoryController::class, 'storeApi']
        );

        Route::put(
            '/ict-inventory/{ictInventory}',
            [IctInventoryController::class, 'updateApi']
        )->whereNumber('ictInventory');

        Route::patch(
            '/ict-inventory/{ictInventory}',
            [IctInventoryController::class, 'updateApi']
        )->whereNumber('ictInventory');

        Route::delete(
            '/ict-inventory/{ictInventory}',
            [IctInventoryController::class, 'destroyApi']
        )->whereNumber('ictInventory');

        /*
        |--------------------------------------------------------------------------
        | Analytics
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/analytics',
            [AnalyticsController::class, 'getAnalytics']
        );

        Route::get(
            '/analytics/export',
            [AnalyticsController::class, 'export']
        );

        Route::post(
            '/analytics/analyze/{institution}',
            [AnalyticsController::class, 'analyzeInstitution']
        )->whereNumber('institution');

        Route::post(
            '/analytics/analyze-all',
            [AnalyticsController::class, 'analyzeAll']
        );

        /*
        |--------------------------------------------------------------------------
        | Barangay Summary
        |--------------------------------------------------------------------------
        */

        Route::get('/barangays/summary', function () {
            $summary = Institution::query()
                ->join(
                    'barangays',
                    'institutions.barangay_id',
                    '=',
                    'barangays.id'
                )
                ->selectRaw(
                    'barangays.id,
                     barangays.name AS barangay_name,
                     COUNT(institutions.id) AS institution_count'
                )
                ->groupBy(
                    'barangays.id',
                    'barangays.name'
                )
                ->orderBy('barangays.name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        });
    });
});