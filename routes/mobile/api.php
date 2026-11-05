<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API Version 1
|--------------------------------------------------------------------------
|
| Public routes are loaded first. All application data routes are protected
| by Laravel Sanctum and require a valid authenticated mobile user.
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        require __DIR__.'/health.php';
        require __DIR__.'/auth.php';
        require __DIR__.'/legal.php';

        Route::middleware(['auth:sanctum', 'account.secure', 'rbac:viewer', 'terms.accepted'])->group(function (): void {
            require __DIR__.'/institutions.php';
            require __DIR__.'/barangays.php';
            require __DIR__.'/mapping.php';
            require __DIR__.'/ict_inventory.php';
            require __DIR__.'/analytics.php';
            require __DIR__.'/audits.php';
        });
    });
