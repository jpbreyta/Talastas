<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="description" content="TALASTAS is an ICT mapping and spatial analytics system for institutional ICT assessment and planning in Lipa City.">

    <title><?php echo e(config('app.name', 'TALASTAS')); ?></title>

    <link rel="icon" type="image/png" href="<?php echo e(asset('images/logo_ict_mapping.png')); ?>">

    <!-- Fonts -->
    <?php echo $__env->make('partials.fonts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Core assets -->
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

    
    <?php if(request()->routeIs('home') || request()->routeIs('map')): ?>
        <?php echo app('Illuminate\Foundation\Vite')('resources/js/gis.js'); ?>
    <?php elseif(
        request()->routeIs('institutions.index')
        || request()->routeIs('institutions.create')
        || request()->routeIs('institutions.edit')
        || request()->routeIs('institutions.show')
    ): ?>
        <?php echo app('Illuminate\Foundation\Vite')('resources/js/institution-location.js'); ?>
    <?php endif; ?>

    <?php if(auth()->guard()->check()): ?>
        
        <?php if(! auth()->user()->canManageInstitutions()): ?>
            <style>
                [data-open-institution-modal],
                [data-delete-institution],
                .delete-institution-button,
                [data-infrastructure-analytics-form],
                a[href$="/institutions/create"],
                a[href*="/institutions/"][href$="/edit"] {
                    display: none !important;
                }
            </style>
        <?php endif; ?>

        <?php if(! auth()->user()->canManageInventory()): ?>
            <style>
                a[href$="/ict-inventory/create"],
                a[href*="/ict-inventory/create?"],
                a[href*="/ict-inventory/"][href$="/edit"],
                a[href*="/ict-inventory/"][href*="/edit?"] {
                    display: none !important;
                }
            </style>
        <?php endif; ?>

        <?php if(! auth()->user()->canDeleteInventory()): ?>
            <style>
                form:has(input[name="_method"][value="DELETE"]) {
                    display: none !important;
                }
            </style>
        <?php endif; ?>
    <?php endif; ?>
</head>
<body class="talastas-app font-sans antialiased bg-gray-50 <?php if(auth()->guard()->check()): ?> role-<?php echo e(auth()->user()->role); ?> <?php endif; ?> <?php if (! empty(trim($__env->yieldContent('dashboard-layout')))): ?> overflow-hidden h-screen <?php endif; ?>">
<?php
    $routeName = request()->route()?->getName() ?? '';
    $uri = request()->path();
    $skeletonPage = 'default';
    if ($routeName === 'home' || $routeName === 'map' || $uri === '/' || $uri === '/map') {
        $skeletonPage = 'dashboard';
    } elseif (str_starts_with($routeName, 'institutions.') || str_starts_with($uri, 'institutions')) {
        $skeletonPage = 'institutions';
    } elseif (str_starts_with($routeName, 'ict-inventory.') || str_starts_with($uri, 'ict-inventory')) {
        $skeletonPage = 'inventory';
    } elseif ($routeName === 'analytics' || $uri === '/analytics') {
        $skeletonPage = 'analytics';
    } elseif (str_starts_with($routeName, 'admin.users.') || str_starts_with($uri, 'admin/users')) {
        $skeletonPage = 'users';
    }
?>
<?php echo $__env->make('components.page-skeleton', ['page' => $skeletonPage], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('layouts.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div id="app" class="talastas-shell min-w-0 flex flex-col <?php if (! empty(trim($__env->yieldContent('dashboard-layout')))): ?> h-screen overflow-hidden <?php else: ?> min-h-screen <?php endif; ?>">
        <?php echo $__env->make('layouts.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <main class="min-w-0 <?php if (! empty(trim($__env->yieldContent('dashboard-layout')))): ?> flex-1 min-h-0 overflow-hidden flex flex-col <?php else: ?> flex-1 <?php endif; ?>">
            <?php echo $__env->yieldContent('content'); ?>
        </main>

        <?php if (! empty(trim($__env->yieldContent('dashboard-layout')))): ?>
        <?php else: ?>
            <?php echo $__env->make('layouts.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>
    </div>
    <?php echo $__env->make('partials.notifications', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php if(auth()->guard()->check()): ?>
    <script>
    (() => {
        const beat = () => {
            if (document.visibilityState !== 'visible') return;
            fetch(<?php echo json_encode(route('activity.heartbeat'), 15, 512) ?>, {method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},credentials:'same-origin'}).catch(() => {});
        };
        setInterval(beat, 60000);
        document.addEventListener('visibilitychange',beat);
    })();
    </script>
    <?php endif; ?>
    <?php echo $__env->yieldContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/layouts/app.blade.php ENDPATH**/ ?>