<?php ($invalidCount = isset($errorKey) && $errors->has($errorKey)); ?>
<input type="text" inputmode="numeric" pattern="[0-9]+"
    <?php if(isset($inputName)): ?> name="<?php echo e($inputName); ?>" <?php endif; ?>
    <?php if(isset($inputId)): ?> id="<?php echo e($inputId); ?>" data-count-field <?php endif; ?>
    value="<?php echo e($inputValue); ?>"
    maxlength="7" <?php if($field !== 'functional_count' && !($countsOptional ?? false)): ?> required <?php endif; ?>
    data-field="<?php echo e($field); ?>" data-count="<?php echo e($field); ?>"
    aria-label="<?php echo e($field === 'defective_active_count' ? 'Defective within Active' : ucwords(str_replace('_', ' ', $field))); ?>"
    <?php if($invalidCount): ?> aria-invalid="true" <?php if(isset($quantityErrorId)): ?> aria-describedby="<?php echo e($quantityErrorId); ?>" <?php endif; ?> <?php endif; ?>
    class="form-input w-full text-center">
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/ict-inventory/_count-input.blade.php ENDPATH**/ ?>