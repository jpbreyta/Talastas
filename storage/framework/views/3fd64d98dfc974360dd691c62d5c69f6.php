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

<!-- Analytics skeleton: metric cards + chart placeholders -->
<div class="space-y-6">
    <!-- Report selection form -->
    <div class="talastas-skeleton-card p-4">
        <div class="talastas-skeleton-placeholder h-4 w-1/3 mb-3"></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php for($i = 0; $i < 3; $i++): ?>
                <div class="talastas-skeleton-card p-3">
                    <div class="talastas-skeleton-text h-4 w-full mb-2"></div>
                    <div class="talastas-skeleton-text h-3 w-1/2 mb-2"></div>
                    <button class="talastas-skeleton-placeholder h-10 w-full rounded-lg"></button>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Metric cards grid -->
    <div class="dashboard-summary-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php for($i = 0; $i < 3; $i++): ?>
            <div class="talastas-skeleton-card p-4">
                <div class="flex items-center gap-3">
                    <div class="talastas-skeleton-avatar h-8 w-8"></div>
                    <div class="talastas-skeleton-text h-4 w-1/3"></div>
                </div>
                <div class="talastas-skeleton-placeholder h-4 w-2/3 mt-2"></div>
                <div class="talastas-skeleton-placeholder h-3 w-full mt-2"></div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Chart placeholders -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="talastas-skeleton-card h-[300px] p-4">
            <div class="talastas-skeleton-placeholder h-4 w-1/3 mb-3"></div>
            <div class="talastas-skeleton-placeholder h-[200px] w-full rounded-lg"></div>
        </div>
        <div class="talastas-skeleton-card h-[300px] p-4">
            <div class="talastas-skeleton-placeholder h-4 w-1/3 mb-3"></div>
            <div class="talastas-skeleton-placeholder h-[200px] w-full rounded-lg"></div>
        </div>
    </div>
</div>

<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/components/skeletons/analytics.blade.php ENDPATH**/ ?>