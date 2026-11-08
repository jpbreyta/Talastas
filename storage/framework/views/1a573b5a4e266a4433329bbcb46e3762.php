<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label', 'icon', 'value' => null, 'metricKey' => null, 'description' => null, 'formula' => null, 'count' => false]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['label', 'icon', 'value' => null, 'metricKey' => null, 'description' => null, 'formula' => null, 'count' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<section class="dashboard-card dashboard-metric">
    <span class="dashboard-metric-icon" aria-hidden="true"><i class="fa-solid <?php echo e($icon); ?>"></i></span>
    <div class="min-w-0">
        <div class="flex items-center gap-1.5">
            <h2><?php echo e($label); ?></h2>
            <?php if($formula): ?><?php if (isset($component)) { $__componentOriginaleaf1255a69d338004fbcd79e97a19a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaleaf1255a69d338004fbcd79e97a19a9d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.metric-info','data' => ['title' => $label,'formula' => $formula,'description' => $description]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('metric-info'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($label),'formula' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($formula),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($description)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaleaf1255a69d338004fbcd79e97a19a9d)): ?>
<?php $attributes = $__attributesOriginaleaf1255a69d338004fbcd79e97a19a9d; ?>
<?php unset($__attributesOriginaleaf1255a69d338004fbcd79e97a19a9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaleaf1255a69d338004fbcd79e97a19a9d)): ?>
<?php $component = $__componentOriginaleaf1255a69d338004fbcd79e97a19a9d; ?>
<?php unset($__componentOriginaleaf1255a69d338004fbcd79e97a19a9d); ?>
<?php endif; ?><?php endif; ?>
        </div>
        <p class="dashboard-metric-value <?php echo e(is_numeric($value) ? 'has-value' : ''); ?>" <?php if($metricKey): ?> data-dashboard-metric="<?php echo e($metricKey); ?>" <?php endif; ?>>
            <?php echo e(is_numeric($value) ? ($count ? number_format($value) : number_format($value, 1).'%') : ($metricKey ? 'Loading…' : 'No calculated data')); ?>

        </p>
        <?php if($count): ?><p class="dashboard-muted">Active Lipa City schools &amp; offices</p><?php endif; ?>
    </div>
</section>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/components/dashboard-metric.blade.php ENDPATH**/ ?>