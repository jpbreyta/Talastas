<?php $__env->startSection('page-title', 'ICT Inventory'); ?>
<?php $__env->startSection('page-icon', 'fa-boxes-stacked'); ?>

<?php $__env->startSection('dashboard-layout'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-tools'); ?>
<div class="relative">
                        <button type="submit" form="inventoryListFilters" aria-label="Search inventory" class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </button>

                        <input id="inventoryInstitutionSearch" name="search" form="inventoryListFilters" value="<?php echo e(request('search')); ?>" maxlength="255" aria-label="Search inventory"
                            type="search"
                            placeholder="Search institutions or components..."
                            autocomplete="off"
                            class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:ring-2 focus:ring-red-100 lg:w-64">
                    </div>

                    <a href="<?php echo e(route('ict-inventory.create')); ?>"
                        class="page-header-primary inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-red-100 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-50">
                        <i class="fa-solid fa-plus"></i>
                        Add Inventory
                    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div data-inventory-list class="flex h-full min-h-0 flex-col bg-gray-100">

        <form id="inventoryListFilters" method="GET" action="<?php echo e(route('ict-inventory.index')); ?>" class="flex shrink-0 flex-wrap items-center gap-4 border-b border-gray-200 bg-gray-50/70 px-5 py-2">
            <label for="inventoryInstitutionFilter" class="sr-only">Inventory status</label>
            <select id="inventoryInstitutionFilter" name="status" aria-label="Filter inventory"
                class="h-10 w-full appearance-auto rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-700 transition hover:bg-gray-50 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100 sm:w-60">
                <option value="all" <?php if(request('status', 'all') === 'all'): echo 'selected'; endif; ?>>Filter by Inventory Status</option>
                <option value="active" <?php if(request('status', 'all') === 'active'): echo 'selected'; endif; ?>>With Active Units</option>
                <option value="attention" <?php if(request('status', 'all') === 'attention'): echo 'selected'; endif; ?>>With Defective Units</option>
                <option value="empty" <?php if(request('status', 'all') === 'empty'): echo 'selected'; endif; ?>>No Inventory</option>
            </select>
            <label for="inventorySort" class="sr-only">Sort inventory</label>
            <select id="inventorySort" name="sort" class="h-10 w-full appearance-auto rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-700 transition hover:bg-gray-50 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100 sm:w-60">
                <option value="name" <?php if(request('sort', 'name') === 'name'): echo 'selected'; endif; ?>>Institution name</option>
                <option value="latest" <?php if(request('sort') === 'latest'): echo 'selected'; endif; ?>>Latest inventory update</option>
                <option value="most_complete" <?php if(request('sort') === 'most_complete'): echo 'selected'; endif; ?>>Most complete inventory</option>
            </select>
            <p class="w-full text-xs text-gray-500">Completeness ranks valid recorded inventory counts. A complete assessment also requires connectivity inputs and confirmed defective-within-Active counts.</p>
            <p id="inventorySearchStatus" role="status" aria-live="polite" hidden class="text-sm text-gray-600"></p>
        </form>

        <?php echo $__env->make('ict-inventory._table-results', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/ict-inventory/index.blade.php ENDPATH**/ ?>