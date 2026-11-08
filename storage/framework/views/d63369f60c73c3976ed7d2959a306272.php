<?php
    $editable = $editable ?? false;
    $connectivityInventory = app(\App\Services\ConnectivityInventory::class);
    $sourceItems = $editable ? ($inventoryItems ?? []) : ($connectivityInstitution?->ictInventory->where('component_type', 'connectivity') ?? collect());
    $connectivityRows = $connectivityInventory->rows($sourceItems);
    $connectivityGroups = $connectivityRows->groupBy('subsection', true);
    $countFields = \App\Services\ConnectivityInventory::FIELDS;
?>
<section class="mb-5 overflow-hidden rounded-xl border border-gray-200 bg-white" data-connectivity-infrastructure aria-label="Internet Connectivity Inventory">
    <h3 class="sr-only">Internet Connectivity Inventory</h3>
    <div class="overflow-x-auto">
        <table class="inventory-quantity-table w-full min-w-[760px] text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50">
                    <th scope="col" class="min-w-[220px] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Component</th>
                    <?php $__currentLoopData = ['Total', 'Active', 'Retired', 'Functional', 'Defective', 'Defective within Active']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <th scope="col" class="w-20 px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600"><?php echo e($label); ?></th>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php if($editable): ?>
                        <th scope="col" class="w-16 px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white" <?php if($editable): ?> data-inventory-rows="connectivity" <?php endif; ?>>
                <?php $__currentLoopData = $connectivityGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subsection => $groupRows): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php ($subtotals = $connectivityInventory->subtotals($groupRows)); ?>
                    <tr class="bg-gray-50 font-semibold text-gray-700" data-subtotal-group="<?php echo e($subsection); ?>">
                        <th scope="row" class="px-3 py-3 text-left"><?php echo e($subsection); ?> (subtotal)</th>
                        <?php $__currentLoopData = $countFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <td class="px-2 py-3 text-center tabular-nums" data-subtotal-field="<?php echo e($field); ?>"><?php echo e($subtotals[$field] === null ? '—' : number_format($subtotals[$field])); ?></td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php if($editable): ?><td></td><?php endif; ?>
                    </tr>
                    <?php $__currentLoopData = $groupRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr <?php if($editable): ?> data-inventory-row data-category="connectivity" data-subsection="<?php echo e($subsection); ?>" <?php endif; ?> <?php if(!empty($item['asset_id'])): ?> data-connectivity-asset="<?php echo e($item['asset_id']); ?>" <?php endif; ?>>
                            <td class="px-3 py-3 align-top">
                                <p class="font-semibold text-gray-900"><?php echo e($item['component_label']); ?></p>
                                <?php if($editable): ?>
                                    <?php $__currentLoopData = ['asset_id', 'component_id', 'asset_name', 'subsection']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <input type="hidden" name="sections[connectivity][items][<?php echo e($index); ?>][<?php echo e($field); ?>]" data-field="<?php echo e($field); ?>" value="<?php echo e($item[$field] ?? ''); ?>">
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php ($availability = \App\Services\InventoryCounts::status($item)); ?>
                                    <p data-availability-status <?php if($availability === 'Availability Recorded'): ?> hidden <?php endif; ?> class="mt-1 text-xs text-amber-700"><?php echo e($availability === 'Availability Recorded' ? '' : $availability); ?></p>
                                <?php endif; ?>
                                <p class="mt-1 text-xs text-gray-500"><?php echo e($subsection); ?></p>
                            </td>
                            <?php if($editable): ?>
                                <td colspan="6" class="py-3 align-top">
                                    <?php ($quantityErrorId = 'inventory-quantity-error-connectivity-'.$index); ?>
                                    <div class="grid grid-cols-6">
                                        <?php $__currentLoopData = $countFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="min-w-0 px-2">
                                                <?php echo $__env->make('ict-inventory._count-input', [
                                                    'field' => $field,
                                                    'inputName' => "sections[connectivity][items][{$index}][{$field}]",
                                                    'inputValue' => $item[$field] ?? '', 'countsOptional' => true,
                                                    'errorKey' => "sections.connectivity.items.{$index}.{$field}",
                                                    'quantityErrorId' => $quantityErrorId,
                                                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                    <div class="px-2" data-row-feedback role="status" aria-live="polite">
                                        <?php echo $__env->make('ict-inventory._quantity-errors', [
                                            'errorPrefix' => "sections.connectivity.items.{$index}.",
                                            'countValues' => $item,
                                            'quantityErrorId' => $quantityErrorId,
                                        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    </div>
                                </td>
                                <td class="px-2 py-3 text-center align-top">
                                    <button type="button" data-remove-row
                                        <?php if(!empty($item['asset_id']) && !auth()->user()?->isAdmin()): ?> hidden <?php endif; ?>
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600"
                                        title="Remove item" aria-label="Remove <?php echo e($item['component_label']); ?>">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </td>
                            <?php else: ?>
                                <?php $__currentLoopData = $countFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <td class="px-2 py-3 text-center tabular-nums"><?php echo e(($item[$field] ?? null) === null ? 'Not recorded' : number_format($item[$field])); ?></td>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</section>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/ict-inventory/_connectivity-infrastructure.blade.php ENDPATH**/ ?>