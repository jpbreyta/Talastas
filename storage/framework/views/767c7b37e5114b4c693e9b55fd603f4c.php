<?php $__env->startSection('page-title', 'Accounts and Logs'); ?>
<?php $__env->startSection('page-icon', 'fa-users-gear'); ?>
<?php $__env->startSection('content'); ?>
<?php echo $__env->make('admin.users._styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php
    $activityTab = request('tab') === 'changes' ? 'changes' : 'sessions';
?>
<div class="users-module mx-auto w-full max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
    <?php echo $__env->make('admin.users._tabs', ['activeTab' => $activityTab], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('admin.users._feedback', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="<?php echo e(route('admin.users.logs')); ?>" class="flex min-w-0 flex-wrap items-center gap-2">
            <input type="hidden" name="tab" value="<?php echo e($activityTab); ?>">
            <label for="activitySearch" class="sr-only">Search user logs</label>
            <div class="relative w-full sm:w-64">
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                <input id="activitySearch" type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Search user name" class="users-control" style="padding-left: 2.5rem">
            </div>
            <button type="submit" class="users-button">Search</button>
            <a href="<?php echo e(route('admin.users.logs', ['tab' => $activityTab])); ?>" class="users-button users-button-secondary">Refresh</a>
        </form>
    </div>
    <?php echo $__env->make('admin.users._activity', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.users._activity-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/admin/users/logs.blade.php ENDPATH**/ ?>