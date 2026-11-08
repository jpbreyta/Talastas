<h1 <?php echo e($attributes->class(['talastas-header-page-title min-w-0 text-white'])); ?>>
    <?php if (! empty(trim($__env->yieldContent('page-icon')))): ?>
        <i class="fa-solid <?php echo e(trim($__env->yieldContent('page-icon'))); ?>" aria-hidden="true"></i>
    <?php endif; ?>
    <span class="min-w-0 truncate"><?php echo $__env->yieldContent('page-title'); ?></span>
</h1>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/components/header-page-title.blade.php ENDPATH**/ ?>