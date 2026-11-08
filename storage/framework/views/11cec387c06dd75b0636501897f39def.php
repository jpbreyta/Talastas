<?php ($connectivityInstitution = $institution); ?>
<div class="p-4" data-unified-connectivity>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-sm font-semibold text-gray-900">Internet Connectivity</h2>
        <?php if(auth()->user()?->canManageInventory()): ?>
            <a href="<?php echo e(route('ict-inventory.create', ['institution_id' => $institution->id, 'tab' => 'connectivity'])); ?>" class="text-sm font-semibold text-red-700 hover:underline">Manage connections</a>
        <?php endif; ?>
    </div>
    <?php echo $__env->make('ict-inventory._connectivity-infrastructure', ['editable' => false], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('ict-inventory._q10-summary', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/ict-inventory/_connectivity-panel.blade.php ENDPATH**/ ?>