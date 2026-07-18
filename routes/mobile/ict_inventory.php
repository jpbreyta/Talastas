<?php

use App\Http\Controllers\IctInventoryController;
use App\Models\IctInventory;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ICT Inventory Routes
|--------------------------------------------------------------------------
|
| Authenticated users may read inventory records. Only administrators may add,
| update, or delete official inventory data through the mobile application.
|
*/

Route::get('/ict-inventory', function () {
    $inventory = IctInventory::query()
        ->with('institution')
        ->latest()
        ->get();

    return response()->json([
        'success' => true,
        'data' => $inventory,
    ]);
})->name('ict-inventory.index');

Route::get('/ict-inventory/{ictInventory}', function (
    IctInventory $ictInventory
) {
    return response()->json([
        'success' => true,
        'data' => $ictInventory->load('institution'),
    ]);
})
    ->whereNumber('ictInventory')
    ->name('ict-inventory.show');

Route::middleware('rbac:admin')->group(function (): void {
    Route::post('/ict-inventory', [
        IctInventoryController::class,
        'storeApi',
    ])->name('ict-inventory.store');

    Route::put('/ict-inventory/{ictInventory}', [
        IctInventoryController::class,
        'updateApi',
    ])
        ->whereNumber('ictInventory')
        ->name('ict-inventory.update');

    Route::patch('/ict-inventory/{ictInventory}', [
        IctInventoryController::class,
        'updateApi',
    ])
        ->whereNumber('ictInventory')
        ->name('ict-inventory.patch');

    Route::delete('/ict-inventory/{ictInventory}', [
        IctInventoryController::class,
        'destroyApi',
    ])
        ->whereNumber('ictInventory')
        ->name('ict-inventory.destroy');
});
