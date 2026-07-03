<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IctInventoryController;
use App\Http\Controllers\IctMappingController;
use App\Http\Controllers\InstitutionController;
use App\Models\ApiKey;
use App\Models\Barangay;
use App\Models\InstitutionAnalytics;
use App\Models\Institution;
use App\Models\IctInventory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::prefix('v1')->group(function () {
    // Public endpoints (accessible without authentication)
    Route::get('/institutions', function () {
        return response()->json(
            Institution::with('barangay')->get()
        );
    });

    Route::get('/institutions/{institution}', function (Institution $institution) {
        return response()->json(
            $institution->load(['barangay', 'ictInventory', 'institutionAnalytics'])
        );
    });

    Route::get('/map/institutions', [IctMappingController::class, 'getInstitutionsGeoJSON']);
    Route::get('/barangays/geojson', [IctMappingController::class, 'getBarangaysGeoJSON']);
    Route::get('/map/barangays', [IctMappingController::class, 'getBarangaysGeoJSON']);
    Route::get('/barangays', function () {
        return response()->json(Barangay::all());
    });

    // API Key generation (for admin use)
    Route::post('/api-keys/generate', function () {
        $key = 'sk_live_' . Str::random(32);
        $apiKey = ApiKey::create([
            'name' => request('name', 'Flutter App'),
            'key' => $key,
            'expires_at' => request('expires_at') ? now()->parse(request('expires_at')) : null,
        ]);
        return response()->json(['key' => $key, 'name' => $apiKey->name]);
    });

    // Protected endpoints (require x-api-key header)
    Route::middleware('api.key')->group(function () {
        // Institution CRUD
        Route::post('/institutions', [InstitutionController::class, 'storeApi']);
        Route::put('/institutions/{institution}', [InstitutionController::class, 'updateApi']);
        Route::delete('/institutions/{institution}', [InstitutionController::class, 'destroyApi']);

        // ICT Inventory CRUD
        Route::get('/ict-inventory', function () {
            return response()->json(IctInventory::with('institution')->get());
        });
        Route::get('/ict-inventory/{ictInventory}', function (IctInventory $ictInventory) {
            return response()->json($ictInventory->load('institution'));
        });
        Route::post('/ict-inventory', [IctInventoryController::class, 'storeApi']);
        Route::put('/ict-inventory/{ictInventory}', [IctInventoryController::class, 'updateApi']);
        Route::delete('/ict-inventory/{ictInventory}', [IctInventoryController::class, 'destroyApi']);

        // Analytics endpoints
        Route::get('/analytics', [AnalyticsController::class, 'getAnalytics']);
        Route::post('/analytics/analyze/{institution}', [AnalyticsController::class, 'analyzeInstitution']);
        Route::post('/analytics/analyze-all', [AnalyticsController::class, 'analyzeAll']);

        // Geocode endpoint
        Route::post('/institutions/geocode', [InstitutionController::class, 'geocode']);

        // Export endpoint for reports
        Route::get('/analytics/export', function () {
            $analytics = InstitutionAnalytics::with('institution')->get();
            return response()->json(['export_url' => 'report would be generated here']);
        });

        // Barangay-level aggregation for decision support
        Route::get('/barangays/summary', function () {
            $summary = Institution::join('barangays', 'institutions.barangay_id', '=', 'barangays.id')
                ->select('barangays.name as barangay_name')
                ->groupBy('barangays.name')
                ->get();
            return response()->json($summary);
        });
    });

    // Keep Sanctum endpoints for web-based auth
    Route::post('/auth/login', [AuthController::class, 'apiLogin']);
    Route::middleware('auth:sanctum')->get('/auth/me', [AuthController::class, 'me']);
});
