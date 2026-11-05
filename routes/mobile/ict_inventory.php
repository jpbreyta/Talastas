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
        ->when(request()->user()->isEditor(), fn ($q) => $q->whereHas('institution.assignedUsers', fn ($assigned) => $assigned->whereKey(request()->user()->id)))
        ->whereIn(
            'component_type',
            IctInventoryController::COMPONENT_TYPES
        )
        ->with('institution:id,name,type,barangay_id')
        ->get([
            'asset_id',
            'institution_id',
            'component_id',
            'category',
            'component_type',
            'asset_name',
            'total_count',
            'active_count',
            'retired_count',
            'functional_count',
            'defective_count',
            'functional_count_recorded',
        ]);

    return response()->json([
        'success' => true,
        'data' => $inventory,
    ]);
})->name('ict-inventory.index');

Route::get('/ict-inventory/{ictInventory}', function (
    IctInventory $ictInventory
) {
    abort_unless(request()->user()->canAccessInstitution($ictInventory->institution_id), 403);
    abort_unless(
        in_array(
            $ictInventory->component_type,
            IctInventoryController::COMPONENT_TYPES,
            true
        ),
        404
    );

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

    Route::delete('/ict-inventory/{ictInventory}', [
        IctInventoryController::class,
        'destroyApi',
    ])
        ->whereNumber('ictInventory')
        ->name('ict-inventory.destroy');
});
