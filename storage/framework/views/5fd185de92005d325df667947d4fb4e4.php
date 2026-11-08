<?php
    $navigation = [
        ['route' => 'home', 'active' => request()->routeIs('home', 'map'), 'label' => 'Dashboard', 'icon' => 'fa-map-location-dot'],
        ['route' => 'institutions.index', 'active' => request()->routeIs('institutions.*'), 'label' => auth()->user()->isEditor() ? 'Institutions' : 'Institutions', 'icon' => 'fa-building'],
        ['route' => 'ict-inventory.index', 'active' => request()->routeIs('ict-inventory.*'), 'label' => auth()->user()->isEditor() ? 'Inventory' : 'Inventory', 'icon' => 'fa-boxes-stacked'],
        ['route' => 'analytics', 'active' => request()->routeIs('analytics'), 'label' => 'Analysis', 'icon' => 'fa-chart-line'],
    ];
    if (auth()->user()->isAdmin()) {
        $navigation[] = ['route' => 'admin.users.index', 'active' => request()->routeIs('admin.users.*'), 'label' => 'Users', 'icon' => 'fa-users-gear'];
    }
?>

<div data-sidebar-backdrop class="talastas-sidebar-backdrop" hidden aria-hidden="true"></div>
<aside id="talastasSidebar" class="talastas-sidebar" aria-label="Main navigation">
    <div class="flex h-15 shrink-0 items-center justify-between gap-2 px-4">
        <a href="<?php echo e(route('home')); ?>" class="flex items-center gap-2.5 text-white">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white p-0.5">
                <img src="<?php echo e(asset('images/logo_ict_mapping.png')); ?>" alt="" width="32" height="32" class="h-full w-full object-contain">
            </span>
            <span class="talastas-brand">TALASTAS</span>
        </a>
        <button type="button" data-sidebar-close aria-label="Close navigation"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-100 hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-white lg:hidden">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
    <nav class="min-h-0 flex-1 overflow-y-auto px-4 pb-4 pt-6" aria-label="Primary navigation">
        <p class="mb-3 px-3 text-[10px] font-medium uppercase tracking-[0.12em] text-red-100/60">MAIN MENU</p>
        <div class="space-y-1">
        <?php $__currentLoopData = $navigation; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route($item['route'])); ?>" <?php if($item['active']): ?> aria-current="page" <?php endif; ?>
                class="talastas-sidebar-link">
                <i class="fa-solid <?php echo e($item['icon']); ?> w-5 shrink-0 text-center" aria-hidden="true"></i>
                <span><?php echo e($item['label']); ?></span>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php if(request()->routeIs('home', 'map')): ?>
            <fieldset class="sidebar-map-styles mt-5 border-t border-white/15 pt-4">
                <legend class="px-3 text-[10px] font-semibold uppercase tracking-wide text-red-100/70">Map background</legend>
                <?php $__currentLoopData = ['default' => 'Default streets', 'satellite' => 'Satellite', 'terrain' => 'Terrain', 'blank' => 'Blank (no labels)']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-xs text-white hover:bg-white/10">
                        <input type="radio" name="map-background" value="<?php echo e($value); ?>" data-map-background <?php if($value === 'default'): echo 'checked'; endif; ?> class="accent-white">
                        <span><?php echo e($label); ?></span>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <p id="mapBackgroundStatus" class="px-3 pt-2 text-[10px] leading-4 text-red-100" role="status"></p>
            </fieldset>
        <?php endif; ?>
    </nav>
    <?php if(auth()->guard()->check()): ?>
        <div class="mt-auto shrink-0 px-2 py-3">
            <?php if (isset($component)) { $__componentOriginalcedb185ae97611e17ef6f86a5f08cf12 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcedb185ae97611e17ef6f86a5f08cf12 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.profile-dropdown','data' => ['sidebar' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('profile-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['sidebar' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcedb185ae97611e17ef6f86a5f08cf12)): ?>
<?php $attributes = $__attributesOriginalcedb185ae97611e17ef6f86a5f08cf12; ?>
<?php unset($__attributesOriginalcedb185ae97611e17ef6f86a5f08cf12); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcedb185ae97611e17ef6f86a5f08cf12)): ?>
<?php $component = $__componentOriginalcedb185ae97611e17ef6f86a5f08cf12; ?>
<?php unset($__componentOriginalcedb185ae97611e17ef6f86a5f08cf12); ?>
<?php endif; ?>
        </div>
    <?php endif; ?>
</aside>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/layouts/sidebar.blade.php ENDPATH**/ ?>