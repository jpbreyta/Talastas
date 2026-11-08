<fieldset <?php if($isNewConnection ?? false): ?> data-new-connection <?php endif; ?> data-q10-connection="<?php echo e($connectionNumber); ?>" class="mt-5 border-t border-gray-100 pt-4">
    <legend class="text-sm font-semibold text-gray-800" data-connection-label>Connection <?php echo e($connectionNumber); ?></legend>
    <?php if($removable ?? false): ?>
        <div class="mt-2 flex justify-end">
            <button type="button" data-remove-connection aria-label="Remove connection <?php echo e($connectionNumber); ?>"
                class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold">
                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>Remove
            </button>
        </div>
    <?php endif; ?>
    <?php $__currentLoopData = ['Connection Details' => ['isp', 'plan', 'advertised_download_mbps', 'allocation', 'validity', 'context'], 'Network Performance' => ['advertised_download_mbps', 'download_speed_mbps', 'upload_speed_mbps', 'latency_ms', 'throughput_mbps']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupLabel => $groupFields): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <h4 class="mt-4 text-xs font-semibold text-gray-700"><?php echo e($groupLabel); ?></h4>
        <div class="inventory-connection-grid mt-3">
        <?php $__currentLoopData = $groupFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php ($label = $q10Fields[$field]); ?>
            <?php ($measurement = in_array($field, ['download_speed_mbps', 'upload_speed_mbps', 'latency_ms', 'throughput_mbps'], true)); ?>
            <label class="block text-xs font-semibold text-gray-600"><?php echo e($label); ?>

                <input name="q10[connections][<?php echo e($connectionNumber); ?>][<?php echo e($field); ?>]"
                    data-connection-field="<?php echo e($field); ?>"
                    type="<?php echo e($measurement ? 'number' : 'text'); ?>"
                    <?php if($measurement): ?> min="<?php echo e($field === 'advertised_download_mbps' ? '0.000001' : '0'); ?>" max="1000000" step="any" data-connection-measurement <?php else: ?> maxlength="500" <?php endif; ?>
                    value="<?php echo e($connectionValues[$field] ?? ''); ?>" placeholder="Not recorded"
                    class="form-input mt-1.5 w-full">
            </label>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php echo $__env->make('ict-inventory._connection-result', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</fieldset>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/ict-inventory/_internet-connection.blade.php ENDPATH**/ ?>