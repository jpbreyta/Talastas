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

Route::get('/analytics/report.pdf', [AnalyticsController::class, 'exportPdf'])
    ->middleware('rbac:planner')->name('analytics.report');

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
        ->middleware('throttle:10,1')
        ->name('analytics.analyze');

    Route::post('/analytics/analyze-all', [
        AnalyticsController::class,
        'analyzeAll',
    ])
        ->middleware('throttle:5,1')
        ->name('analytics.analyze-all');

    Route::get('/analytics/python-health', [
        AnalyticsController::class,
        'pythonHealth',
    ])->name('analytics.python-health');
});
