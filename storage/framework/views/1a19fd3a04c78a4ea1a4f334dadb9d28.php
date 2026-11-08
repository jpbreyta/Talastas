<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['sidebar' => false]));

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

foreach (array_filter((['sidebar' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $user = auth()->user();
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
?>

<details class="relative <?php echo e($sidebar ? 'talastas-sidebar-account' : ''); ?>">
    <summary class="list-none cursor-pointer flex items-center gap-2 rounded-md px-3 py-2 transition-colors text-sm font-medium text-red-200 hover:text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
        <span class="inline-flex shrink-0 <?php echo e($sidebar ? 'h-8 w-8 text-xs' : 'h-6 w-6 text-[10px]'); ?> items-center justify-center rounded-full bg-white/20 font-bold text-white">
            <?php echo e($initials ?: 'U'); ?>

        </span>
        <span class="<?php echo e($sidebar ? 'block flex-1' : 'hidden md:block'); ?> min-w-0 text-left">
            <span class="block <?php echo e($sidebar ? 'break-words text-xs text-white' : 'max-w-40 truncate'); ?> leading-tight"><?php echo e($user->name); ?></span>
            <span class="block <?php echo e($sidebar ? 'mt-0.5 text-[10px]' : 'max-w-52 truncate text-[11px]'); ?> leading-tight opacity-80"><?php echo e($user->roleLabel()); ?></span>
        </span>
        <i class="fa-solid fa-chevron-down h-3 w-3 shrink-0 text-xs" aria-hidden="true"></i>
    </summary>

    <div class="<?php echo e($sidebar ? 'talastas-sidebar-account-menu absolute bottom-full left-0 mb-2 overflow-y-auto' : 'absolute right-0 mt-2 w-72 overflow-hidden'); ?> z-[3000] rounded-xl border border-slate-200 bg-white shadow-xl">
        <div class="border-b border-slate-100 px-4 py-3">
            <p class="truncate text-sm font-semibold text-slate-900"><?php echo e($user->name); ?></p>
            <p class="truncate text-xs text-slate-500"><?php echo e($user->email); ?></p>
            <span class="mt-2 inline-flex rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">
                <?php echo e($user->roleLabel()); ?>

            </span>
        </div>

        <nav class="profile-menu-list" aria-label="Account menu">
            <a href="<?php echo e(route('profile.account')); ?>" class="profile-menu-item"><i class="fa-regular fa-user" aria-hidden="true"></i><span>My profile</span></a>
            <a href="<?php echo e(route('legal.review')); ?>" class="profile-menu-item"><i class="fa-solid fa-file-circle-check" aria-hidden="true"></i><span>My Terms acknowledgement</span></a>
            <?php echo $__env->make('partials.legal-links', ['menu' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </nav>
        <form method="POST" action="<?php echo e(route('logout')); ?>" class="border-t border-slate-100 py-1">
            <?php echo csrf_field(); ?>
            <button type="submit" class="profile-menu-item profile-menu-signout w-full text-left"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span>Sign out</span></button>
        </form>
    </div>
</details>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/components/profile-dropdown.blade.php ENDPATH**/ ?>