<label for="<?php echo e($fieldId); ?>" class="mb-1.5 block text-xs font-semibold text-gray-600">
    Sector Type <span class="text-red-500">*</span>
</label>
<select name="type" id="<?php echo e($fieldId); ?>" required
    class="form-input w-full <?php $__errorArgs = ['type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-400 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
    <?php $__errorArgs = ['type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" aria-describedby="<?php echo e($fieldId); ?>-error" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
    <option value="" <?php if(old('type', $sectorValue) === ''): echo 'selected'; endif; ?> disabled>Select institution type</option>
    <?php $__currentLoopData = ['School', 'Barangay Hall']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sector): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($sector); ?>" <?php if(old('type', $sectorValue) === $sector): echo 'selected'; endif; ?>><?php echo e($sector === 'School' ? 'Public School' : $sector); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>
<?php $__errorArgs = ['type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
    <p id="<?php echo e($fieldId); ?>-error" class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

<?php if($fieldId === 'modalType'): ?>
    <p id="institutionTypeHelp" class="mt-2 text-xs text-gray-500" aria-live="polite">Select the institution type manually, or select a map result to check its type. Only public schools and barangay halls are allowed.</p>
<?php endif; ?>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/institutions/_sector-field.blade.php ENDPATH**/ ?>