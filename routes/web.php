<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IctMappingController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;

Route::get('/', [IctMappingController::class, 'index'])->middleware(['auth', 'rbac:admin'])->name('home');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// LGU Admin protected routes - requires admin role (rbac:admin)
Route::middleware(['auth', 'rbac:admin'])->group(function () {
    Route::get('/map', [IctMappingController::class, 'index'])->name('map');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
});
