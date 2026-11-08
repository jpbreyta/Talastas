
<?php $__env->startSection('page-title', 'Accounts and Logs'); ?>
<?php $__env->startSection('page-icon', 'fa-users-gear'); ?>
<?php $__env->startSection('content'); ?>
<?php echo $__env->make('admin.users._styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<div class="users-module mx-auto w-full max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
    <?php echo $__env->make('admin.users._tabs', ['activeTab' => 'invitations'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('admin.users._feedback', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php if(!\App\Services\SecurityMail::available()): ?>
        <p role="alert" class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">System email is not configured on the server. Sender and queue setup must be completed before sending invitations, recovery instructions or administrator verification codes. Signing in as an administrator does not configure email delivery.</p>
    <?php endif; ?>
    <div class="grid items-start gap-6 lg:grid-cols-5 lg:gap-8">
        <section class="users-card p-5 sm:p-6 lg:col-span-2" aria-labelledby="inviteHeading">
            <h2 id="inviteHeading" class="mb-6 text-lg font-semibold">Invite a user</h2>
            <form method="POST" action="<?php echo e(route('admin.invitations.store')); ?>" class="space-y-5" data-role-form>
                <?php echo csrf_field(); ?>
                <label class="block text-sm font-medium" for="inviteName">Full name
                    <input id="inviteName" name="name" value="<?php echo e(old('name')); ?>" required maxlength="255" autocomplete="name" class="users-control mt-2">
                </label>
                <label class="block text-sm font-medium" for="inviteEmail">Email
                    <input id="inviteEmail" name="email" value="<?php echo e(old('email')); ?>" required type="email" maxlength="255" autocomplete="email" class="users-control mt-2">
                </label>
                <label class="block text-sm font-medium" for="inviteRole">Role
                    <select id="inviteRole" name="role" class="users-control mt-2" data-user-role aria-controls="inviteAssignments">
                        <option value="viewer" <?php if(old('role', 'viewer') === 'viewer'): echo 'selected'; endif; ?>>LGU City Planner</option>
                        <option value="editor" <?php if(old('role') === 'editor'): echo 'selected'; endif; ?>>Barangay Focal Person / ICT Coordinator</option>
                        <option value="admin" <?php if(old('role') === 'admin'): echo 'selected'; endif; ?>>System Administrator (MFA required)</option>
                    </select>
                </label>
                <fieldset id="inviteAssignments" data-editor-assignments <?php if(old('role', 'viewer') !== 'editor'): ?> hidden disabled <?php endif; ?>>
                    <legend class="text-sm font-medium">Barangay and institution assignments</legend>
                    <p class="mt-2 text-xs text-gray-500">Select a barangay to add all its current active public schools and barangay halls, or select individual institutions below. Saved assignments appear as checked institutions. Newly added institutions must be assigned separately.</p>
                    <div class="mt-2 max-h-48 space-y-3 overflow-y-auto rounded-lg border border-gray-200 p-4" aria-label="Barangay assignments">
                        <?php $__currentLoopData = $institutions->pluck('barangay')->filter()->unique('id')->sortBy('name'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $barangay): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="flex items-start gap-2 text-sm text-gray-700"><input type="checkbox" name="barangay_ids[]" value="<?php echo e($barangay->id); ?>" <?php if(in_array($barangay->id, (array) old('barangay_ids', []))): echo 'checked'; endif; ?> class="users-checkbox"><span><?php echo e($barangay->name); ?></span></label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <p class="mt-3 text-sm font-medium">Individual institutions</p>
                    <div class="mt-2 max-h-60 space-y-3 overflow-y-auto rounded-lg border border-gray-200 p-4">
                        <?php $__empty_1 = true; $__currentLoopData = $institutions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $institution): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <label class="flex items-start gap-2 text-sm text-gray-700"><input type="checkbox" name="institution_ids[]" value="<?php echo e($institution->id); ?>" <?php if(in_array($institution->id, (array) old('institution_ids', []))): echo 'checked'; endif; ?> class="users-checkbox"><span><?php echo e($institution->name); ?> <?php if($institution->barangay): ?> <span class="text-gray-500">(<?php echo e($institution->barangay->name); ?>)</span> <?php endif; ?></span></label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-sm text-gray-500">No institutions available.</p>
                        <?php endif; ?>
                    </div>
                </fieldset>
                <div class="space-y-4 border-t border-gray-200 pt-5">
                    <label class="block text-sm font-medium" for="invitePassword">Your current password
                        <input id="invitePassword" name="current_password" type="password" required autocomplete="current-password" class="users-control mt-2">
                    </label>
                    <label class="flex items-start gap-2 text-sm text-gray-600"><input type="checkbox" name="existing_account_confirmed" value="1" <?php if(old('existing_account_confirmed')): echo 'checked'; endif; ?> class="users-checkbox"><span>If this account already exists, I authorize updating its access after authenticated acceptance.</span></label>
                    <label class="flex items-start gap-2 text-sm text-gray-600"><input type="checkbox" name="confirmed" value="1" required <?php if(old('confirmed')): echo 'checked'; endif; ?> class="users-checkbox"><span>I confirm the recipient, role and institution assignments and authorize this invitation email.</span></label>
                </div>
                <button type="submit" class="users-button" <?php if(!\App\Services\SecurityMail::available()): echo 'disabled'; endif; ?>>Send Invitation</button>
            </form>
            <a href="<?php echo e(route('security.mfa')); ?>" class="mt-5 inline-block text-xs text-gray-500 underline hover:text-red-900">Email verification and trusted browsers</a>
        </section>
        <section class="min-w-0 space-y-5 lg:col-span-3" aria-labelledby="historyHeading">
            <h2 id="historyHeading" class="text-lg font-semibold">Invitation history</h2>
            <?php $__empty_1 = true; $__currentLoopData = $invitations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invite): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <article class="users-card space-y-4 p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="font-semibold"><?php echo e($invite->name); ?></h3>
                            <p class="mt-1 break-words text-sm text-gray-600"><?php echo e($invite->email); ?> | <?php echo e($invite->role); ?></p>
                        </div>
                        <span class="shrink-0 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold"><?php echo e($invite->status === 'pending' && $invite->expires_at->isPast() ? 'expired' : $invite->status); ?></span>
                    </div>
                    <p class="text-sm text-gray-600">Expires <?php echo e($invite->expires_at->format('M j, Y H:i')); ?> UTC.<?php if($invite->accepted_at): ?><br>Accepted <?php echo e($invite->accepted_at->format('M j, Y H:i')); ?> UTC | Account #<?php echo e($invite->accepted_user_id); ?><?php endif; ?></p>
                    <?php if($invite->institutions->isNotEmpty()): ?>
                        <div class="space-y-1 text-sm text-gray-600"><?php $__currentLoopData = $invite->institutions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $institution): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($institution->name); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
                    <?php endif; ?>
                    <details class="users-accordion text-sm text-gray-600" <?php if(in_array($invite->delivery_status, ['failed', 'unavailable'])): ?> open <?php endif; ?>>
                        <summary>Email: <?php echo e(str_replace('_', ' ', $invite->delivery_status)); ?></summary>
                        <p class="mt-2 text-xs leading-5">Transport acceptance does not confirm inbox delivery.</p>
                    </details>
                    <?php if(in_array($invite->status, ['pending', 'expired'])): ?>
                        <details class="users-accordion border-t border-gray-100 pt-4">
                            <summary class="text-sm font-medium text-red-900">Resend or revoke</summary>
                            <form method="POST" action="<?php echo e(route('admin.invitations.resend', $invite->id)); ?>" class="mt-4 space-y-4">
                                <?php echo csrf_field(); ?>
                                <label class="block text-sm font-medium">Your current password<input name="current_password" type="password" required autocomplete="current-password" class="users-control mt-2"></label>
                                <label class="flex items-start gap-2 text-sm text-gray-600"><input name="confirmed" type="checkbox" value="1" required class="users-checkbox"><span>I confirm this action; existing links and setup sessions will stop working.</span></label>
                                <div class="flex flex-wrap gap-3"><button type="submit" class="users-button">Resend invitation</button><button type="submit" formaction="<?php echo e(route('admin.invitations.revoke', $invite->id)); ?>" class="users-button users-button-secondary">Revoke invitation</button></div>
                            </form>
                        </details>
                    <?php endif; ?>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="users-card p-6 text-sm text-gray-500">No invitations yet.</p>
            <?php endif; ?>
            <?php echo e($invitations->links()); ?>

            <details class="users-accordion users-card p-5 sm:p-6">
                <summary class="font-semibold">Recent security events</summary>
                <div class="mt-4 max-h-80 space-y-3 overflow-y-auto">
                    <?php $__empty_1 = true; $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <p class="break-words text-xs leading-5 text-gray-600"><?php echo e($event->occurred_at); ?> UTC | <?php echo e($event->event); ?> | <?php echo e($event->outcome); ?> | Actor #<?php echo e($event->actor_id ?? 'system/guest'); ?> | Target #<?php echo e($event->target_id ?? '-'); ?></p>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-sm text-gray-500">No security events recorded.</p>
                    <?php endif; ?>
                </div>
            </details>
        </section>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
    <?php echo $__env->make('admin.users._presentation-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/admin/users/invitations.blade.php ENDPATH**/ ?>