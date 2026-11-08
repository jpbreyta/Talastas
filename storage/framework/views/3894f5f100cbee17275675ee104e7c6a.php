<?php
    $quantityMessages = [];
    $exceededFields = [];
    foreach (['total_count', 'active_count', 'retired_count', 'functional_count', 'defective_count', 'defective_active_count'] as $quantityField) {
        $quantityKey = ($errorPrefix ?? '') . $quantityField;
        foreach ($errors->get($quantityKey) as $quantityMessage) {
            if (preg_match('/^([A-Z][a-z]+) units cannot exceed Total units\.$/', $quantityMessage, $quantityMatch)) {
                $exceededFields[] = $quantityMatch[1];
            } elseif ($quantityMessage === 'Recorded Active + Retired cannot exceed Total. Other units may be unclassified.') {
                $quantityMessages[] = 'Check quantities: Active and Retired together cannot exceed Total (' . ($countValues['total_count'] ?? '—') . ').';
            } else {
                $quantityMessages[] = $quantityMessage;
            }
        }
    }
    $exceededFields = array_values(array_unique($exceededFields));
    if ($exceededFields) {
        $lastQuantityLabel = array_pop($exceededFields);
        $quantityLabels = $exceededFields ? implode(', ', $exceededFields) . ' and ' . $lastQuantityLabel : $lastQuantityLabel;
        $quantityMessages[] = 'Check quantities: ' . $quantityLabels . ' ' . ($exceededFields ? 'counts' : 'count') . ' cannot exceed Total (' . ($countValues['total_count'] ?? '—') . ').';
    }
?>
<?php if($quantityMessages): ?>
    <div id="<?php echo e($quantityErrorId); ?>" class="inventory-quantity-errors mt-2 space-y-1 text-xs leading-5 text-red-800" role="status">
        <?php $__currentLoopData = array_unique($quantityMessages); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quantityMessage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <p><?php echo e($quantityMessage); ?></p>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/ict-inventory/_quantity-errors.blade.php ENDPATH**/ ?>