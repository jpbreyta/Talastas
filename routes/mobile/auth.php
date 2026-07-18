<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Authentication
|--------------------------------------------------------------------------
|
| Login is public but rate-limited. Account information and logout require a
| valid Sanctum token issued by the login endpoint.
|
*/

Route::prefix('auth')
    ->name('auth.')
    ->group(function (): void {
        Route::post('/login', [AuthController::class, 'apiLogin'])
            ->middleware('throttle:5,1')
            ->name('login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', [AuthController::class, 'me'])
                ->name('me');

            Route::post('/logout', [AuthController::class, 'apiLogout'])
                ->name('logout');
        });
    });
