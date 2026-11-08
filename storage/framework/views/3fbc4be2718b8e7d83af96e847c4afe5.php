<?php
    $restoreEdit = (string) old('_editing_user') === (string) $user->id;
    $editingRole = $restoreEdit ? old('role', $user->role) : $user->role;
    $editingAssignments = $restoreEdit ? (array) old('institution_ids', []) : $user->institutions->pluck('id')->all();
?>
<form method="POST" action="<?php echo e(route('admin.users.update', $user)); ?>" class="space-y-5 p-6" data-role-form <?php if($restoreEdit && $errors->any()): ?> data-reopen-user-editor <?php endif; ?>>
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>
    <input type="hidden" name="_editing_user" value="<?php echo e($user->id); ?>">
    <?php if($restoreEdit && $errors->any()): ?>
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"><ul class="list-inside list-disc space-y-1"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div>
    <?php endif; ?>
    <p class="text-xs text-gray-500"><?php echo e($user->invitation_accepted_at ? 'Invitation accepted '.$user->invitation_accepted_at->format('M j, Y') : 'No invitation acceptance recorded'); ?></p>
    <label class="block text-sm font-medium" for="edit-name-<?php echo e($user->id); ?>">Full name
        <input id="edit-name-<?php echo e($user->id); ?>" name="name" value="<?php echo e($restoreEdit ? old('name', $user->name) : $user->name); ?>" required maxlength="255" autocomplete="name" class="users-control mt-2" autofocus>
    </label>
    <label class="block text-sm font-medium" for="edit-email-<?php echo e($user->id); ?>">Email
        <input id="edit-email-<?php echo e($user->id); ?>" name="email" type="email" value="<?php echo e($restoreEdit ? old('email', $user->email) : $user->email); ?>" required maxlength="255" autocomplete="email" class="users-control mt-2">
    </label>
    <label class="block text-sm font-medium" for="edit-role-<?php echo e($user->id); ?>">Role
        <select id="edit-role-<?php echo e($user->id); ?>" name="role" required data-user-role class="users-control mt-2" aria-controls="edit-assignments-<?php echo e($user->id); ?>">
            <option value="admin" <?php if($editingRole === 'admin'): echo 'selected'; endif; ?>>System Administrator</option>
            <option value="editor" <?php if($editingRole === 'editor'): echo 'selected'; endif; ?>>ICT Coordinator / Editor</option>
            <option value="viewer" <?php if($editingRole === 'viewer'): echo 'selected'; endif; ?>>LGU City Planner / Viewer</option>
        </select>
    </label>
    <fieldset id="edit-assignments-<?php echo e($user->id); ?>" data-editor-assignments <?php if($editingRole !== 'editor'): ?> hidden disabled <?php endif; ?>>
        <legend class="text-sm font-medium">Barangay and institution assignments</legend>
        <p class="mt-2 text-xs font-medium text-gray-700">An Editor must have at least one active school or barangay hall assigned.</p>
        <p class="mt-2 text-xs text-gray-500">Select a barangay to add all its current active public schools and barangay halls, or select individual institutions below. Saved assignments appear as checked institutions. Newly added institutions must be assigned separately.</p>
        <div class="mt-2 max-h-48 space-y-3 overflow-y-auto rounded-lg border border-gray-200 p-4" aria-label="Barangay assignments">
            <?php $__currentLoopData = $institutions->pluck('barangay')->filter()->unique('id')->sortBy('name'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $barangay): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <label class="flex items-start gap-2 text-sm text-gray-700"><input type="checkbox" name="barangay_ids[]" value="<?php echo e($barangay->id); ?>" <?php if(in_array($barangay->id, ($restoreEdit ? (array) old('barangay_ids', []) : []))): echo 'checked'; endif; ?> class="users-checkbox"><span><?php echo e($barangay->name); ?></span></label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <p class="mt-3 text-sm font-medium">Individual institutions</p>
        <div class="mt-2 max-h-60 space-y-3 overflow-y-auto rounded-lg border border-gray-200 p-4">
            <?php $__empty_1 = true; $__currentLoopData = $institutions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $institution): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <label class="flex items-start gap-2 text-sm text-gray-700"><input type="checkbox" name="institution_ids[]" value="<?php echo e($institution->id); ?>" <?php if(in_array($institution->id, $editingAssignments)): echo 'checked'; endif; ?> class="users-checkbox"><span><?php echo e($institution->name); ?> <?php if($institution->barangay): ?> <span class="text-gray-500">(<?php echo e($institution->barangay->name); ?>)</span> <?php endif; ?></span></label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-sm text-gray-500">No institutions available.</p>
            <?php endif; ?>
        </div>
    </fieldset>
    <div class="space-y-4 border-t border-gray-200 pt-5">
        <label class="block text-sm font-medium" for="edit-password-<?php echo e($user->id); ?>">Your current password
            <input id="edit-password-<?php echo e($user->id); ?>" name="current_password" type="password" required autocomplete="current-password" class="users-control mt-2">
        </label>
        <div class="flex justify-end gap-3"><button type="button" data-close-user-editor class="users-button users-button-secondary">Cancel</button><button type="submit" class="users-button">Save</button></div>
    </div>
</form>
<?php if(!auth()->user()->is($user)): ?>
    <details class="users-accordion border-t border-gray-200 p-6"><summary class="font-semibold">Security actions</summary><?php echo $__env->make('admin.users._security-actions', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></details>
<?php endif; ?>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/admin/users/_edit-form.blade.php ENDPATH**/ ?>