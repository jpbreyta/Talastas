<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title', 'subtitle' => null, 'backUrl' => null, 'backLabel' => 'Go back',
    'padded' => false, 'contentClass' => '', 'heading' => 'h1', 'titleId' => null,
    'toolbar' => false]));

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

foreach (array_filter((['title', 'subtitle' => null, 'backUrl' => null, 'backLabel' => 'Go back',
    'padded' => false, 'contentClass' => '', 'heading' => 'h1', 'titleId' => null,
    'toolbar' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $titleInGlobalHeader = $heading === 'h1' && $__env->hasSection('page-title');
    $hasContentContext = ! $titleInGlobalHeader || $backUrl || trim((string) $slot) !== '';
?>

<?php if($hasContentContext || isset($actions)): ?>
<header <?php echo e($attributes->class(['talastas-page-header shrink-0', 'px-5' => $padded,
    'talastas-page-header-context' => $titleInGlobalHeader,
    'talastas-page-toolbar' => $toolbar,
    'talastas-page-header-dialog' => $heading === 'h2'])); ?>>
    <div class="talastas-page-header-inner <?php echo e($contentClass); ?>">
        <?php if($hasContentContext): ?>
        <div class="flex min-w-0 flex-1 items-start gap-3">
            <?php if($backUrl): ?>
                <a href="<?php echo e($backUrl); ?>" aria-label="<?php echo e($backLabel); ?>" title="<?php echo e($backLabel); ?>"
                    class="talastas-page-header-back">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                </a>
            <?php endif; ?>
            <div class="min-w-0">
                <?php if($titleInGlobalHeader): ?>
                    <?php if($subtitle && $backUrl): ?>
                        <p class="text-sm font-semibold text-slate-800"><?php echo e($title); ?></p>
                        <p class="talastas-page-header-subtitle"><?php echo e($subtitle); ?></p>
                    <?php endif; ?>
                <?php elseif($heading === 'h2'): ?>
                    <h2 <?php if($titleId): ?> id="<?php echo e($titleId); ?>" <?php endif; ?> class="talastas-page-header-title"><?php echo e($title); ?></h2>
                <?php else: ?>
                    <h1 <?php if($titleId): ?> id="<?php echo e($titleId); ?>" <?php endif; ?> class="talastas-page-header-title"><?php echo e($title); ?></h1>
                <?php endif; ?>
                <?php if($subtitle && ! $titleInGlobalHeader): ?>
                    <p class="talastas-page-header-subtitle"><?php echo e($subtitle); ?></p>
                <?php endif; ?>
                <?php echo e($slot); ?>

            </div>
        </div>
        <?php endif; ?>
        <?php if(isset($actions)): ?>
            <div class="talastas-page-header-actions"><?php echo e($actions); ?></div>
        <?php endif; ?>
    </div>
</header>
<?php endif; ?>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/components/page-header.blade.php ENDPATH**/ ?>