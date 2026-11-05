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
    /*
    |--------------------------------------------------------------------------
    | Institution Location Search
    |--------------------------------------------------------------------------
    |
    | This endpoint uses Laravel's Nominatim integration and returns the same
    | location choices used by the web institution form.
    |
    */

    Route::post('/institutions/geocode', [InstitutionController::class, 'geocode'])
        ->middleware('throttle:30,1')->name('institutions.geocode');

    Route::post('/institutions', [InstitutionController::class, 'store'])
        ->name('institutions.store');

    Route::put('/institutions/{institution}', [InstitutionController::class, 'update'])
        ->whereNumber('institution')
        ->name('institutions.update');

    Route::delete('/institutions/{institution}', [InstitutionController::class, 'destroy'])
        ->whereNumber('institution')
        ->name('institutions.destroy');
});