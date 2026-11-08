

<?php $__env->startSection('page-title', 'Institutions'); ?>
<?php $__env->startSection('page-icon', 'fa-building'); ?>

<?php $__env->startSection('dashboard-layout'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-tools'); ?>
<div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </div>

                        <input id="institutionSearch" name="search" form="institutionListFilters" value="<?php echo e(request('search')); ?>" aria-label="Search institutions" type="search" placeholder="Search institutions..."
                            class="w-36 sm:w-56 rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:ring-2 focus:ring-red-100">
                    </div>

                    <?php if(auth()->user()->canManageInstitutions()): ?>
                        <button type="button" id="openAddModal" data-open-institution-modal
                            class="page-header-primary inline-flex items-center gap-2 rounded-lg border border-red-100 bg-white px-4 py-2 text-sm font-medium text-red-700 shadow-sm transition hover:bg-red-50">
                            <i class="fa-solid fa-plus"></i>
                            Add Institution
                        </button>
                    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php ($canManageInstitutions = auth()->user()->canManageInstitutions()); ?>

    <div data-institution-list class="flex h-full min-h-0 flex-col bg-gray-100">
        

        
        <?php if($errors->any()): ?>
            <div class="mx-5 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <div>
                        <p class="font-semibold">Please correct the following:</p>
                        <ul class="mt-1 list-disc space-y-0.5 pl-5">
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        
        <div class="shrink-0 border-b border-gray-200 bg-gray-50/70 px-5 py-2">
            <form id="institutionListFilters" method="GET" action="<?php echo e(route('institutions.index')); ?>"
                class="talastas-filter-controls flex flex-wrap items-center gap-4">

                <div class="flex w-full flex-wrap items-center gap-4 sm:w-auto">
                    <div class="w-full sm:w-60">
                        <label for="barangayFilter"
                            class="sr-only">
                            Barangay
                        </label>
                        <select id="barangayFilter" name="barangay"
                            class="h-10 w-full appearance-auto rounded-md border border-gray-300 bg-white px-3 text-gray-700 transition hover:bg-gray-50 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                            <option value="">Filter by Barangay</option>
                            <?php $__currentLoopData = $barangays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $barangay): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($barangay->id); ?>"
                                    <?php if((string) request('barangay') === (string) $barangay->id): echo 'selected'; endif; ?>>
                                    <?php echo e($barangay->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="w-full sm:w-60">
                        <label for="institutionSort"
                            class="sr-only">
                            Sort By
                        </label>
                        <select id="institutionSort" name="sort"
                            class="h-10 w-full appearance-auto rounded-md border border-gray-300 bg-white px-3 text-gray-700 transition hover:bg-gray-50 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                            <option value="barangay_asc"
                                <?php if(request('sort', 'barangay_asc') === 'barangay_asc'): echo 'selected'; endif; ?>>
                                Sort By: Barangay A-Z
                            </option>
                            <option value="barangay_desc"
                                <?php if(request('sort') === 'barangay_desc'): echo 'selected'; endif; ?>>
                                Barangay Z-A
                            </option>
                            <option value="name_asc"
                                <?php if(request('sort') === 'name_asc'): echo 'selected'; endif; ?>>
                                Institution A-Z
                            </option>
                            <option value="name_desc"
                                <?php if(request('sort') === 'name_desc'): echo 'selected'; endif; ?>>
                                Institution Z-A
                            </option>
                            <option value="newest"
                                <?php if(request('sort') === 'newest'): echo 'selected'; endif; ?>>
                                Newest
                            </option>
                        </select>
                    </div>
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-4">
                    <button type="submit"
                        class="inline-flex h-10 items-center gap-2 rounded-md border border-red-700 bg-transparent px-4 text-sm font-medium text-red-700 transition hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                        <i class="fa-solid fa-filter text-xs" aria-hidden="true"></i>
                        Apply
                    </button>

                    <a data-institution-reset href="<?php echo e(route('institutions.index')); ?>"
                        class="inline-flex h-10 items-center gap-2 rounded-md border-0 bg-transparent px-4 text-sm font-medium text-gray-600 transition hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                        <i class="fa-solid fa-rotate-left text-xs" aria-hidden="true"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <div data-institution-search-status <?php if(!filled(request('search'))): ?> hidden <?php endif; ?>
            class="shrink-0 px-5 py-3">
            <div data-institution-list-status role="status" aria-live="polite" aria-atomic="true"
                class="inline-flex max-w-full items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm">
                <i data-institution-status-icon class="fa-solid fa-building-columns text-red-700" aria-hidden="true"></i>
                <span data-institution-status-label><?php echo e(number_format($institutions->total())); ?> <?php echo e($institutions->total() === 1 ? 'institution' : 'institutions'); ?></span>
            </div>
        </div>
        <?php echo $__env->make('institutions._table-results', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <?php if($canManageInstitutions): ?>
            <?php echo $__env->make('institutions.modals', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/institutions/index.blade.php ENDPATH**/ ?>