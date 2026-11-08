<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([]));

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

foreach (array_filter(([]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<!-- Dashboard skeleton: metric cards + map workspace -->
<div class="space-y-6">
    <!-- Metric cards row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php for($i = 0; $i < 3; $i++): ?>
            <div class="talastas-skeleton-card p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="talastas-skeleton-avatar h-8 w-8"></div>
                    <div class="talastas-skeleton-text h-4 w-1/3"></div>
                </div>
                <div class="talastas-skeleton-placeholder h-4 w-3/4 mb-2"></div>
                <div class="talastas-skeleton-placeholder h-3 w-full"></div>
                <div class="talastas-skeleton-placeholder h-3 w-1/2 mt-2"></div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Map workspace placeholder -->
    <div class="talastas-skeleton-card h-[400px] p-4">
        <div class="talastas-skeleton-placeholder h-4 w-1/3 mb-3"></div>
        <div class="talastas-skeleton-placeholder h-[300px] w-full rounded-lg"></div>
    </div>

    <!-- Insights sidebar placeholder -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:col-span-3">
        <?php for($i = 0; $i < 2; $i++): ?>
            <div class="talastas-skeleton-card p-4 h-48">
                <div class="talastas-skeleton-placeholder h-4 w-1/2 mb-3"></div>
                <div class="talastas-skeleton-text h-3 w-full mb-2"></div>
                <div class="talastas-skeleton-text h-3 w-3/4"></div>
                <div class="talastas-skeleton-text h-3 w-1/2 mt-3"></div>
            </div>
        <?php endfor; ?>
    </div>
</div>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/components/skeletons/dashboard.blade.php ENDPATH**/ ?>