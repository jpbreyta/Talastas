<?php ($hasMapControls = request()->routeIs('home', 'map')); ?>
<header class="talastas-header bg-red-900 sticky top-0 z-[5000] isolate shrink-0">
    <div class="<?php echo e($hasMapControls ? 'map-header-row' : 'talastas-feature-header px-4 sm:px-6 lg:px-8'); ?>">
        <div class="flex min-w-0 items-center gap-2 <?php echo e($hasMapControls ? 'map-header-brand' : 'flex-1'); ?>">
            <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white lg:hidden"
                data-sidebar-toggle aria-controls="talastasSidebar" aria-expanded="false" aria-label="Open navigation">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>
            <a href="<?php echo e(route('home')); ?>" class="talastas-header-branding flex items-center gap-1.5 text-white lg:hidden">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white p-0.5">
                    <img src="<?php echo e(asset('images/logo_ict_mapping.png')); ?>" alt="" width="28" height="28" class="h-full w-full object-contain">
                </span>
                <span class="talastas-brand">TALASTAS</span>
            </a>
            <?php if(! $hasMapControls && $__env->hasSection('page-title')): ?>
                <?php if (isset($component)) { $__componentOriginald1688300077dd49fa72a5a241a00f35f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald1688300077dd49fa72a5a241a00f35f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.header-page-title','data' => ['class' => 'hidden sm:flex sm:ml-3 lg:ml-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('header-page-title'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'hidden sm:flex sm:ml-3 lg:ml-0']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald1688300077dd49fa72a5a241a00f35f)): ?>
<?php $attributes = $__attributesOriginald1688300077dd49fa72a5a241a00f35f; ?>
<?php unset($__attributesOriginald1688300077dd49fa72a5a241a00f35f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald1688300077dd49fa72a5a241a00f35f)): ?>
<?php $component = $__componentOriginald1688300077dd49fa72a5a241a00f35f; ?>
<?php unset($__componentOriginald1688300077dd49fa72a5a241a00f35f); ?>
<?php endif; ?>
                <?php if (! empty(trim($__env->yieldContent('page-status')))): ?><div class="header-analysis-status"><?php echo $__env->yieldContent('page-status'); ?></div><?php endif; ?>
            <?php endif; ?>
        </div>
        <?php if($hasMapControls): ?>
            <?php echo $__env->make('partials.map-header-controls', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($__env->hasSection('page-tools')): ?>
            <div class="talastas-header-tools"><?php echo $__env->yieldContent('page-tools'); ?></div>
        <?php endif; ?>
        <div class="shrink-0 <?php echo e($hasMapControls ? 'map-header-account' : ''); ?>">
            <?php echo $__env->make('partials.account-controls', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <?php if(! $hasMapControls && $__env->hasSection('page-title')): ?>
            <?php if (isset($component)) { $__componentOriginald1688300077dd49fa72a5a241a00f35f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald1688300077dd49fa72a5a241a00f35f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.header-page-title','data' => ['class' => 'talastas-mobile-page-title flex pb-2 sm:hidden']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('header-page-title'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'talastas-mobile-page-title flex pb-2 sm:hidden']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald1688300077dd49fa72a5a241a00f35f)): ?>
<?php $attributes = $__attributesOriginald1688300077dd49fa72a5a241a00f35f; ?>
<?php unset($__attributesOriginald1688300077dd49fa72a5a241a00f35f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald1688300077dd49fa72a5a241a00f35f)): ?>
<?php $component = $__componentOriginald1688300077dd49fa72a5a241a00f35f; ?>
<?php unset($__componentOriginald1688300077dd49fa72a5a241a00f35f); ?>
<?php endif; ?>
        <?php endif; ?>
    </div>
</header>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/layouts/header.blade.php ENDPATH**/ ?>