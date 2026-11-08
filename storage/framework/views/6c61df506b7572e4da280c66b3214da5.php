<?php if(auth()->guard()->check()): ?>
    <div class="flex items-center gap-2">
        <?php if (isset($component)) { $__componentOriginal0676521d0d1386b8a24fdc18016b8d4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0676521d0d1386b8a24fdc18016b8d4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.notification-dropdown','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('notification-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0676521d0d1386b8a24fdc18016b8d4a)): ?>
<?php $attributes = $__attributesOriginal0676521d0d1386b8a24fdc18016b8d4a; ?>
<?php unset($__attributesOriginal0676521d0d1386b8a24fdc18016b8d4a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0676521d0d1386b8a24fdc18016b8d4a)): ?>
<?php $component = $__componentOriginal0676521d0d1386b8a24fdc18016b8d4a; ?>
<?php unset($__componentOriginal0676521d0d1386b8a24fdc18016b8d4a); ?>
<?php endif; ?>
    </div>
<?php endif; ?>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/partials/account-controls.blade.php ENDPATH**/ ?>