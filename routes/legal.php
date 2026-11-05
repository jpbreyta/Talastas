<?php

use App\Http\Controllers\LegalController;
use Illuminate\Support\Facades\Route;

Route::get('/terms/{version?}', [LegalController::class, 'show'])
    ->defaults('kind', 'terms')->name('legal.terms');
Route::get('/privacy/{version?}', [LegalController::class, 'show'])
    ->defaults('kind', 'privacy')->name('legal.privacy');

Route::middleware(['auth:web', 'auth.session', 'account.secure'])->group(function (): void {
    Route::post('/terms-decline', [LegalController::class, 'decline'])->name('legal.decline');
    Route::get('/terms-acceptance', [LegalController::class, 'review'])->name('legal.review');
    Route::post('/terms-acceptance', [LegalController::class, 'accept'])
        ->middleware('throttle:10,1,legal-accept:')->name('legal.accept');
    Route::get('/terms-receipts/{receipt}', [LegalController::class, 'receipt'])
        ->whereNumber('receipt')->name('legal.receipt');
});
