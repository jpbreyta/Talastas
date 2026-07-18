<?php

use App\Http\Controllers\Api\InstitutionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Institution Routes
|--------------------------------------------------------------------------
|
| Authenticated users may read institution records.
| Creating, updating, and deleting institutions require administrator access.
|
*/

/*
|--------------------------------------------------------------------------
| Read Institution Records
|--------------------------------------------------------------------------
*/

Route::get('/institutions', [InstitutionController::class, 'index'])
    ->name('institutions.index');

Route::get('/institutions/{institution}', [InstitutionController::class, 'show'])
    ->whereNumber('institution')
    ->name('institutions.show');

/*
|--------------------------------------------------------------------------
| Manage Institution Records
|--------------------------------------------------------------------------
*/

Route::middleware('rbac:admin')->group(function (): void {
    Route::post('/institutions', [InstitutionController::class, 'store'])
        ->name('institutions.store');

    Route::put('/institutions/{institution}', [InstitutionController::class, 'update'])
        ->whereNumber('institution')
        ->name('institutions.update');

    Route::patch('/institutions/{institution}', [InstitutionController::class, 'update'])
        ->whereNumber('institution')
        ->name('institutions.patch');

    Route::delete('/institutions/{institution}', [InstitutionController::class, 'destroy'])
        ->whereNumber('institution')
        ->name('institutions.destroy');
});