<?php ($canManageInstitutions = auth()->user()->canManageInstitutions()); ?>
        
        <div data-institution-results data-total="<?php echo e($institutions->total()); ?>" class="flex min-h-0 flex-1 flex-col">
            <?php if($institutions->count() > 0): ?>
                <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-1 pt-3">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                        <?php if($canManageInstitutions): ?>
                            data-institution-selection data-batch-delete-url="<?php echo e(route('institutions.batch-destroy')); ?>"
                        <?php endif; ?>>
                        <?php if($canManageInstitutions): ?>
                            <div data-institution-batch-actions hidden
                                class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-red-50/50 px-4 py-2">
                                <span data-institution-selected-count class="text-xs font-medium text-gray-700" role="status" aria-live="polite"></span>
                                <div class="flex items-center gap-2">
                                    <button type="button" data-institution-clear-selection
                                        class="rounded-lg px-3 py-2 text-xs font-medium text-gray-600 hover:bg-white focus-visible:outline-2 focus-visible:outline-red-700">Clear selection</button>
                                    <button type="button" data-institution-batch-delete
                                        class="inline-flex items-center gap-2 rounded-lg bg-red-700 px-3 py-2 text-xs font-semibold text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        <span data-batch-delete-label>Delete selected</span>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[800px] text-sm">
                                <thead class="sticky top-0 z-10">
                                    <tr class="border-b border-gray-200 bg-gray-50 text-left">
                                        <?php if($canManageInstitutions): ?>
                                            <th scope="col" class="w-12 px-2 py-2">
                                                <label data-institution-selection-control class="inline-flex h-8 w-8 cursor-pointer items-center justify-center">
                                                    <input type="checkbox" data-institution-select-all aria-label="Select all visible institutions on this page"
                                                        class="h-4 w-4 cursor-pointer accent-red-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                                                </label>
                                            </th>
                                        <?php endif; ?>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Institution
                                        </th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Sector
                                        </th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Barangay
                                        </th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Status
                                        </th>
                                        <th class="w-20 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100">
                                    <?php $__currentLoopData = $institutions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $institution): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr id="institution-row-<?php echo e($institution->id); ?>"
                                            data-institution-show-url="<?php echo e(route('institutions.show', $institution)); ?>"
                                            class="inst-row cursor-pointer transition hover:bg-red-50/40 focus-within:bg-red-50/40">
                                            <?php if($canManageInstitutions): ?>
                                                <td class="w-12 px-2 py-2">
                                                    <label data-institution-selection-control class="inline-flex h-8 w-8 cursor-pointer items-center justify-center">
                                                        <input type="checkbox" data-institution-select name="institution_ids[]" value="<?php echo e($institution->id); ?>"
                                                            aria-label="Select <?php echo e($institution->name); ?>"
                                                            class="h-4 w-4 cursor-pointer accent-red-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                                                    </label>
                                                </td>
                                            <?php endif; ?>
                                            <td class="px-4 py-3">
                                                <a href="<?php echo e(route('institutions.show', $institution)); ?>"
                                                    class="font-medium text-gray-900 hover:text-red-800 hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                                                    <?php echo e($institution->name); ?>

                                                </a>
                                            </td>

                                            <td class="px-4 py-3 text-gray-500">
                                                <?php echo e($institution->type); ?>

                                            </td>

                                            <td class="px-4 py-3 text-gray-500">
                                                <?php echo e($institution->barangay?->name ?? '—'); ?>

                                            </td>

                                            <td class="px-4 py-3">
                                                <?php if($institution->is_active): ?>
                                                    <span class="inline-flex items-center gap-1 rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                                                        <i class="fa-solid fa-circle-check text-[10px]"></i>
                                                        Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-500">
                                                        <i class="fa-solid fa-circle-minus text-[10px]"></i>
                                                        Inactive
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="w-20 px-4 py-3 text-right">
                                                <div class="flex justify-end">
                                                    <?php if($canManageInstitutions): ?>
                                                        <button type="button" data-institution-actions-toggle
                                                            aria-label="Open actions for <?php echo e($institution->name); ?>" aria-expanded="false" aria-haspopup="menu"
                                                            aria-controls="institution-actions-<?php echo e($institution->id); ?>"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                                                            <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
                                                        </button>
                                                        <div id="institution-actions-<?php echo e($institution->id); ?>" data-institution-actions-menu hidden
                                                            role="menu" aria-label="Actions for <?php echo e($institution->name); ?>"
                                                            class="fixed z-[2500] w-36 max-w-[calc(100vw-16px)] rounded-lg border border-gray-200 bg-white p-1 text-left shadow-md">
                                                        <a href="<?php echo e(route('institutions.edit', $institution)); ?>"
                                                            role="menuitem" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-red-700">
                                                            <i class="fa-solid fa-pen"></i>
                                                            Edit
                                                        </a>

                                                        <button type="button"
                                                            role="menuitem" class="delete-institution-button flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-red-700 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-red-700"
                                                            data-delete-url="<?php echo e(route('institutions.destroy', $institution)); ?>"
                                                            data-institution-id="<?php echo e($institution->id); ?>"
                                                            data-institution-name="<?php echo e($institution->name); ?>">
                                                            <i class="fa-solid fa-trash"></i>
                                                            Delete
                                                        </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-200 bg-white px-5 py-2">
                    <?php echo e($institutions->links()); ?>

                </div>
            <?php else: ?>
                <div class="flex flex-1 flex-col items-center justify-center px-5 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-500">
                        <i class="fa-solid fa-building text-xl"></i>
                    </div>

                    <h3 class="mt-4 text-sm font-semibold text-gray-900">
                        <?php echo e(request()->filled('search') || request()->filled('barangay') ? 'No matching institutions' : 'No institutions yet'); ?>

                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        <?php echo e(request()->filled('search') || request()->filled('barangay')
                            ? 'Try another search or reset your filters.'
                            : ($canManageInstitutions ? 'Get started by adding your first institution.' : 'No institution records are currently available.')); ?>

                    </p>

                    <?php if($canManageInstitutions): ?>
                        <button type="button" id="openAddModalEmpty" data-open-institution-modal
                            class="mt-5 inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                            <i class="fa-solid fa-plus"></i>
                            Add Institution
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/institutions/_table-results.blade.php ENDPATH**/ ?>