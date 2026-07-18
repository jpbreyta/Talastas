<?php

use App\Http\Controllers\IctMappingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Mapping Routes
|--------------------------------------------------------------------------
|
| These protected routes preserve the existing mobile endpoint paths for
| institution points and barangay GeoJSON polygons.
|
*/

Route::get('/map/institutions', [
    IctMappingController::class,
    'getInstitutionsGeoJSON',
])->name('map.institutions');

Route::get('/barangays/geojson', [
    IctMappingController::class,
    'getBarangaysGeoJSON',
])->name('barangays.geojson');
