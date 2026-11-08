<?php
    $remainingInventoryErrors = collect($errors->messages())->reject(function ($messages, $path) {
        return preg_match('/^(?:sections\.(hardware|software|network|connectivity)\.items\.\d+\.)?(total_count|active_count|retired_count|functional_count|defective_count|defective_active_count)$/', $path);
    });
?>
<?php if($remainingInventoryErrors->isNotEmpty()): ?>
    <details class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <summary class="cursor-pointer font-semibold"><?php echo e($remainingInventoryErrors->count()); ?> field(s) require correction</summary>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <?php $__currentLoopData = $remainingInventoryErrors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $path => $messages): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $parts = explode('.', $path);
                    $label = ucwords(str_replace('_', ' ', end($parts)));
                    if (($parts[0] ?? '') === 'sections' && isset($parts[3])) {
                        $name = old("sections.{$parts[1]}.items.{$parts[3]}.asset_name", 'Item '.((int) $parts[3] + 1));
                        $label = ucfirst($parts[1]).' / '.$name.' / '.$label;
                    }
                ?>
                <?php $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><strong><?php echo e($label); ?>:</strong> <?php echo e($message); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </details>
<?php endif; ?>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/ict-inventory/_validation-errors.blade.php ENDPATH**/ ?>