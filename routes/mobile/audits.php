<?php

use App\Http\Controllers\AuditController;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | Audit Routes
 |--------------------------------------------------------------------------
 |
 | Authenticated users may view audit records. Creating, updating, and
 | deleting audits requires editor access and institution assignment.
 |
 */

Route::get('/audits', [AuditController::class, 'index'])
    ->name('audits.index');

Route::middleware('rbac:editor')->group(function (): void {
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