<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['message' => null, 'size' => 'sm', 'color' => 'red-600', 'class' => null]));

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

foreach (array_filter((['message' => null, 'size' => 'sm', 'color' => 'red-600', 'class' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div <?php echo e($attributes->merge(['class' => 'flex items-center gap-2 ' . ($class ?? '')])->class(['global-loading'])); ?>

    role="status"
    aria-live="polite"
>
    <span
        class="flex shrink-0 items-center justify-center <?php echo e(match($size) {
            'xs' => 'h-4 w-4',
            'sm' => 'h-5 w-5',
            'md' => 'h-6 w-6',
            'lg' => 'h-8 w-8',
            default => 'h-5 w-5',
        }); ?> text-<?php echo e($color); ?>"
        aria-hidden="true"
    >
        <i class="fa-solid fa-circle-notch fa-spin motion-reduce:animate-none"></i>
    </span>

    <?php if($message): ?>
        <span class="text-xs text-gray-600 leading-none"><?php echo e($message); ?></span>
    <?php endif; ?>
</div>

<style>
    .global-loading { display: inline-flex; }
</style>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/components/global-loading.blade.php ENDPATH**/ ?>