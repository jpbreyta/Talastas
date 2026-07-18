<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Service Health
|--------------------------------------------------------------------------
|
| This public endpoint allows the mobile application to confirm that the API
| is reachable without exposing institution or account information.
|
*/

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'status' => 'ok',
        'version' => '1.0',
    ]);
})->name('health');
