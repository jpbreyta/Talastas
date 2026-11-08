<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title',
    'formula',
    'description',
]));

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

foreach (array_filter(([
    'title',
    'formula',
    'description',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<button type="button"
    data-metric-info-trigger
    data-info-title="<?php echo e($title); ?>"
    data-info-description="<?php echo e($description); ?>"
    data-info-formula="<?php echo e($formula); ?>"
    class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white text-[10px] text-slate-500 transition hover:border-slate-400 hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-100"
    aria-label="Information about <?php echo e($title); ?>"
    aria-expanded="false">
    <i class="fa-solid fa-info" aria-hidden="true"></i>
</button>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/components/metric-info.blade.php ENDPATH**/ ?>