<?php

use App\Http\Controllers\LegalController;
use Illuminate\Support\Facades\Route;

Route::get('/legal/terms/{version?}', [LegalController::class, 'show'])
    ->defaults('kind', 'terms')->name('legal.terms');
Route::get('/legal/privacy/{version?}', [LegalController::class, 'show'])
    ->defaults('kind', 'privacy')->name('legal.privacy');
Route::middleware(['auth:sanctum', 'account.secure'])->group(function (): void {
    Route::post('/legal/decline', [LegalController::class, 'decline'])->name('legal.decline');
    Route::get('/legal/acceptance', [LegalController::class, 'review'])->name('legal.review');
    Route::post('/legal/acceptance', [LegalController::class, 'accept'])
        ->middleware('throttle:10,1,legal-accept:')->name('legal.accept');
    Route::get('/legal/receipts/{receipt}', [LegalController::class, 'receipt'])
        ->whereNumber('receipt')->name('legal.receipt');
});
