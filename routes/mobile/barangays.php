<?php

use App\Models\Barangay;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Barangay Routes
|--------------------------------------------------------------------------
|
| The standard barangay endpoint returns only fields needed by forms and
| summaries. Large polygon geometry is returned only by the mapping endpoint.
|
*/

Route::get('/barangays', function () {
    $barangays = Barangay::query()
        ->select([
            'id',
            'name',
            'code',
            'district',
            'population',
            'total_institutions',
        ])
        ->orderBy('name')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $barangays,
    ]);
})->name('barangays.index');

Route::get('/barangays/summary', function () {
    $summary = Barangay::query()
        ->leftJoin(
            'institutions',
            'barangays.id',
            '=',
            'institutions.barangay_id'
        )
        ->selectRaw(
            'barangays.id,
             barangays.name AS barangay_name,
             COUNT(institutions.id) AS institution_count'
        )
        ->groupBy(
            'barangays.id',
            'barangays.name'
        )
        ->orderBy('barangays.name')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $summary,
    ]);
})->name('barangays.summary');
