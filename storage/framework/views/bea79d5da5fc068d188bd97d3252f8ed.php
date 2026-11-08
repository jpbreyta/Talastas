<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['page' => 'default', 'message' => null]));

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

foreach (array_filter((['page' => 'default', 'message' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $reducedMotion = request()->cookie('talastas_reduced_motion', '0') === '1';
?>

<!-- Page skeleton overlay - shown during server-side page transitions -->
<div
    id="talastasPageSkeleton"
    <?php echo e($attributes->merge(['class' => 'fixed inset-0 z-[1900] flex items-center justify-center bg-white/95 backdrop-blur-sm opacity-0 transition-opacity duration-200 lg:left-[200px]', 'hidden' => true])); ?>

    role="status"
    aria-live="polite"
    aria-label="Loading page content"
>
    <div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <!-- Page header placeholder -->
        <div class="space-y-4 mb-8">
            <div class="h-8 bg-slate-200 rounded w-1/3 motion-reduce:animate-none <?php echo e($reducedMotion ? '' : 'animate-pulse'); ?>"></div>
            <div class="h-4 bg-slate-200 rounded w-1/5 motion-reduce:animate-none <?php echo e($reducedMotion ? '' : 'animate-pulse'); ?>"></div>
        </div>

        <?php if($page === 'dashboard'): ?>
            <?php echo $__env->make('components.skeletons.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($page === 'institutions'): ?>
            <?php echo $__env->make('components.skeletons.institutions', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($page === 'inventory'): ?>
            <?php echo $__env->make('components.skeletons.inventory', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($page === 'analytics'): ?>
            <?php echo $__env->make('components.skeletons.analytics', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($page === 'users'): ?>
            <?php echo $__env->make('components.skeletons.users', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php else: ?>
            <?php echo $__env->make('components.skeletons.default', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

        <?php if($message): ?>
            <p class="mt-4 text-xs text-gray-500 text-center"><?php echo e($message); ?></p>
        <?php endif; ?>
    </div>
</div>
<script>
    (() => {
        const key = 'talastas:navigation-pending';
        try {
            if (sessionStorage.getItem(key) !== '1') return;
            sessionStorage.removeItem(key);
        } catch {
            return;
        }
        const skeleton = document.getElementById('talastasPageSkeleton');
        if (!skeleton) return;
        skeleton.hidden = false;
        skeleton.classList.remove('opacity-0');
        skeleton.removeAttribute('aria-hidden');
    })();
</script>

<style>
    .talastas-skeleton-placeholder {
        background: #e2e8f0;
        border-radius: 0.5rem;
        animation: talastas-skeleton-pulse 1.5s ease-in-out infinite;
    }
    .talastas-skeleton-text {
        background: #cbd5e1;
        border-radius: 0.25rem;
        animation: talastas-skeleton-pulse 1.5s ease-in-out infinite;
    }
    .talastas-skeleton-avatar {
        background: #e2e8f0;
        border-radius: 9999px;
        animation: talastas-skeleton-pulse 1.5s ease-in-out infinite;
    }
    .talastas-skeleton-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        animation: talastas-skeleton-pulse 1.5s ease-in-out infinite;
        overflow: hidden;
    }
    .talastas-skeleton-table-row {
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        animation: talastas-skeleton-pulse 1.5s ease-in-out infinite;
    }
    @keyframes talastas-skeleton-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    @media (prefers-reduced-motion: reduce) {
        .talastas-skeleton-placeholder,
        .talastas-skeleton-text,
        .talastas-skeleton-avatar,
        .talastas-skeleton-card,
        .talastas-skeleton-table-row {
            animation: none;
            opacity: 1;
        }
    }
</style>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/components/page-skeleton.blade.php ENDPATH**/ ?>