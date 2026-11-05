<?php

use App\Http\Controllers\PortalMappingController;
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
    PortalMappingController::class,
    'getInstitutionsGeoJSON',
])->name('map.institutions');

Route::get('/barangays/geojson', [
    PortalMappingController::class,
    'getBarangaysGeoJSON',
])->name('barangays.geojson');
