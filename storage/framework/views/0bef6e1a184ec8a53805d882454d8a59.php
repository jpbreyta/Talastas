<?php if(session('success')): ?>
    <p role="status" class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800"><?php echo e(session('success')); ?></p>
<?php endif; ?>
<?php if($errors->any()): ?>
    <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
        <ul class="list-inside list-disc space-y-1">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> <li><?php echo e($error); ?></li> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/admin/users/_feedback.blade.php ENDPATH**/ ?>