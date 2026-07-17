<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IctInventoryController;
use App\Http\Controllers\IctMappingController;
use App\Http\Controllers\InstitutionController;
use App\Models\Barangay;
use App\Models\IctInventory;
use App\Models\Institution;
use App\Models\InstitutionAnalytics;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'version' => '1.0',
        ]);
    });

    Route::post(
        '/auth/login',
        [AuthController::class, 'apiLogin']
    );

    /*
     * Public read-only routes.
     */
    Route::get(
        '/institutions',
        [InstitutionController::class, 'indexApi']
    );

    Route::get('/barangays', function () {
        return response()->json([
            'success' => true,
            'data' => Barangay::orderBy('name')->get(),
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
     * Protected routes require a Sanctum Bearer token.
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
         * Put geocode before the dynamic institution route.
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
        );

        Route::delete(
            '/institutions/{institution}',
            [InstitutionController::class, 'destroyApi']
        );

        Route::get('/ict-inventory', function () {
            return response()->json(
                IctInventory::with('institution')->get()
            );
        });

        Route::get(
            '/ict-inventory/{ictInventory}',
            function (IctInventory $ictInventory) {
                return response()->json(
                    $ictInventory->load('institution')
                );
            }
        );

        Route::post(
            '/ict-inventory',
            [IctInventoryController::class, 'storeApi']
        );

        Route::put(
            '/ict-inventory/{ictInventory}',
            [IctInventoryController::class, 'updateApi']
        );

        Route::delete(
            '/ict-inventory/{ictInventory}',
            [IctInventoryController::class, 'destroyApi']
        );

        Route::get(
            '/analytics',
            [AnalyticsController::class, 'getAnalytics']
        );

        Route::post(
            '/analytics/analyze/{institution}',
            [AnalyticsController::class, 'analyzeInstitution']
        );

        Route::post(
            '/analytics/analyze-all',
            [AnalyticsController::class, 'analyzeAll']
        );

        Route::get('/analytics/export', function () {
            InstitutionAnalytics::with('institution')->get();

            return response()->json([
                'export_url' => 'report would be generated here',
            ]);
        });

        Route::get('/barangays/summary', function () {
            $summary = Institution::join(
                'barangays',
                'institutions.barangay_id',
                '=',
                'barangays.id'
            )
                ->select('barangays.name as barangay_name')
                ->groupBy('barangays.name')
                ->get();

            return response()->json($summary);
        });
    });

    /*
     * Keep the dynamic GET route after the fixed routes.
     */
    Route::get(
        '/institutions/{institution}',
        [InstitutionController::class, 'showApi']
    );
});