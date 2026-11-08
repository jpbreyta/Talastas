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

<!-- Institutions skeleton: search + filters + table/card placeholders -->
<div class="space-y-4">
    <!-- Search + filter toolbar -->
    <div class="flex flex-wrap gap-4 border-b border-gray-200 bg-gray-50/70 px-5 py-2">
        <div class="w-full sm:w-56">
            <div class="talastas-skeleton-card h-10 p-3">
                <div class="talastas-skeleton-text h-5 w-full"></div>
            </div>
        </div>
        <div class="w-full sm:w-60">
            <div class="talastas-skeleton-card h-10 p-3">
                <div class="talastas-skeleton-text h-5 w-full"></div>
            </div>
        </div>
        <div class="w-full sm:w-60">
            <div class="talastas-skeleton-card h-10 p-3">
                <div class="talastas-skeleton-text h-5 w-full"></div>
            </div>
        </div>
        <div class="ml-auto flex shrink-0 items-center gap-4">
            <div class="talastas-skeleton-card h-10 px-4">
                <div class="talastas-skeleton-text h-5 w-12"></div>
            </div>
        </div>
    </div>

    <!-- Institution list placeholders -->
    <div class="space-y-3">
        <?php for($i = 0; $i < 6; $i++): ?>
            <div class="talastas-skeleton-card p-4 flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="talastas-skeleton-avatar h-10 w-10 shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <div class="talastas-skeleton-text h-4 w-1/2"></div>
                    <div class="talastas-skeleton-text h-3 w-1/3 mt-1"></div>
                    <div class="talastas-skeleton-text h-3 w-1/4 mt-1"></div>
                </div>
                <div class="flex gap-2">
                    <div class="talastas-skeleton-placeholder h-8 w-8"></div>
                    <div class="talastas-skeleton-placeholder h-8 w-8"></div>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Pagination placeholder -->
    <div class="talastas-skeleton-placeholder h-10 w-1/4"></div>
</div>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/components/skeletons/institutions.blade.php ENDPATH**/ ?>