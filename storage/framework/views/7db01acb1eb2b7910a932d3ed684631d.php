<?php $__env->startSection('page-title', 'Accounts and Logs'); ?>
<?php $__env->startSection('page-icon', 'fa-users-gear'); ?>
<?php $__env->startSection('content'); ?>
<?php echo $__env->make('admin.users._styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<div class="users-module mx-auto w-full max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
    <?php echo $__env->make('admin.users._tabs', ['activeTab' => 'accounts'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('admin.users._feedback', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <section class="users-card" aria-labelledby="accountsHeading">
        <h2 id="accountsHeading" class="border-b border-gray-200 px-5 py-4 text-lg font-semibold">Accounts</h2>
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="<?php echo e(route('admin.users.index')); ?>" class="w-full sm:max-w-sm">
                <input type="hidden" name="log_search" value="<?php echo e(request('log_search')); ?>">
                <label for="userSearch" class="sr-only">Search users by name or email</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                    <input id="userSearch" name="search" type="search" value="<?php echo e(request('search')); ?>" placeholder="Search users..." class="users-control" style="padding-left: 2.5rem">
                </div>
            </form>
            <a href="<?php echo e(route('admin.invitations.index')); ?>" class="users-button shrink-0"><i class="fa-solid fa-plus" aria-hidden="true"></i>Invite User</a>
        </div>
        <div class="users-table-scroll" tabindex="0" role="region" aria-label="Accounts table">
            <table class="users-table min-w-[960px]">
                <thead><tr>
                    <?php $__currentLoopData = ['Name', 'Email', 'Role', 'Assigned Institutions', 'Actions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <th scope="col"><?php echo e($column); ?></th>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="min-w-[180px]"><p class="font-medium"><?php echo e($user->name); ?></p><p class="mt-1 text-xs <?php echo e($user->is_active ? 'text-gray-500' : 'text-red-700'); ?>"><?php echo e($user->is_active ? 'Active' : 'Inactive'); ?></p></td>
                            <td class="users-wrap min-w-[200px] text-gray-700"><?php echo e($user->email); ?></td>
                            <td class="min-w-[180px] text-gray-700"><?php echo e($user->roleLabel()); ?></td>
                            <td class="min-w-[200px] text-gray-700">
                                <?php $__empty_2 = true; $__currentLoopData = $user->institutions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $institution): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                    <span class="block"><?php echo e($institution->name); ?></span>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                    <span class="text-gray-400">None assigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="w-32">
                                <div class="flex flex-col items-start gap-1">
                                    <button type="button" data-open-user-editor="user-editor-<?php echo e($user->id); ?>" aria-haspopup="dialog" aria-controls="user-editor-<?php echo e($user->id); ?>" class="inline-flex items-center gap-2 rounded py-2 font-medium text-gray-600 hover:text-red-900">
                                        <i class="fa-solid fa-pen text-xs" aria-hidden="true"></i>Edit<span class="sr-only"> <?php echo e($user->name); ?></span>
                                    </button>
                                    <?php if(!auth()->user()->is($user)): ?>
                                        <form method="POST" action="<?php echo e(route('admin.users.destroy', $user)); ?>" onsubmit="return confirm('Delete this user account?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="inline-flex items-center gap-2 rounded py-2 font-medium text-red-700 hover:text-red-800"><i class="fa-solid fa-trash text-xs" aria-hidden="true"></i>Delete<span class="sr-only"> <?php echo e($user->name); ?></span></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="text-center text-gray-500">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="users-pagination"><?php echo e($users->links()); ?></div>
    </section>
    <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <dialog id="user-editor-<?php echo e($user->id); ?>" class="users-drawer" aria-labelledby="user-editor-title-<?php echo e($user->id); ?>" data-user-editor>
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-gray-200 bg-white px-6 py-5">
                <div><h2 id="user-editor-title-<?php echo e($user->id); ?>" class="text-lg font-semibold">Edit account</h2><p class="mt-1 text-sm text-gray-500"><?php echo e($user->name); ?></p></div>
                <button type="button" data-close-user-editor class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100" aria-label="Close account editor"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </div>
            <?php echo $__env->make('admin.users._edit-form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </dialog>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
    <?php echo $__env->make('admin.users._presentation-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/admin/users/index.blade.php ENDPATH**/ ?>