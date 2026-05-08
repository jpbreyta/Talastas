<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IctMappingController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Models\Institution;
use App\Models\AiGapAnalytics;

Route::prefix('v1')->group(function () {
    // Public endpoints (accessible without authentication)
    Route::get('/institutions', function () {
        return response()->json(
            Institution::all()
        );
    });

    Route::get('/map/institutions', [IctMappingController::class, 'getInstitutionsGeoJSON']);
    Route::get('/barangays/geojson', [IctMappingController::class, 'getBarangaysGeoJSON']);
    Route::get('/map/barangays', [IctMappingController::class, 'getBarangaysGeoJSON']);

    // Authentication endpoints
    Route::post('/auth/login', [AuthController::class, 'apiLogin']);
    Route::middleware('auth')->get('/auth/me', [AuthController::class, 'me']);

    // Analytics endpoints (require authentication)
    Route::middleware('auth')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'getAnalytics']);
        Route::post('/analytics/analyze/{institution}', [AnalyticsController::class, 'analyzeInstitution']);
        Route::post('/analytics/analyze-all', [AnalyticsController::class, 'analyzeAll']);
        
        // Export endpoint for reports
        Route::get('/analytics/export', function () {
            $analytics = AiGapAnalytics::with('institution')->get();
            return response()->json(['export_url' => 'report would be generated here']);
        });
        
        // Barangay-level aggregation for decision support
        Route::get('/barangays/summary', function () {
            $summary = Institution::select('barangay_name')
                ->withAvg('aiGapAnalytics', 'gap_score')
                ->withCount('aiGapAnalytics')
                ->groupBy('barangay_name')
                ->get();
            
            return response()->json($summary);
        });
    });
});
