<?php

use App\Http\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Analytics Routes
|--------------------------------------------------------------------------
|
| Authenticated users may view analytics. Exporting or starting an analysis
| changes or exposes official records and is therefore restricted to admins.
|
*/

Route::get('/analytics', [
    AnalyticsController::class,
    'getAnalytics',
])->name('analytics.index');

Route::middleware('rbac:admin')->group(function (): void {
    Route::get('/analytics/export', [
        AnalyticsController::class,
        'export',
    ])->name('analytics.export');

    Route::post('/analytics/analyze/{institution}', [
        AnalyticsController::class,
        'analyzeInstitution',
    ])
        ->whereNumber('institution')
        ->name('analytics.analyze');

    Route::post('/analytics/analyze-all', [
        AnalyticsController::class,
        'analyzeAll',
    ])->name('analytics.analyze-all');
});
