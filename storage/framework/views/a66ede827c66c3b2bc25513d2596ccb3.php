<div data-gps-controls data-gps-resolve-url="<?php echo e(url('/institutions/resolve-coordinates')); ?>" data-gps-state="idle" class="location-method-card gps-card">
    <div class="gps-card-heading">
        <h4><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>Use Current Location (GPS)</h4>
        <span class="location-source-badge">Location source: <span data-location-source-label>Manual</span></span>
    </div>
    <button type="button" data-gps-button class="location-method-button gps-capture-button"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i><span>Use My Current Location</span></button>
    <div class="gps-help"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div>
        <p>Capture while at the institution. Only locations inside mapped Lipa City boundaries are accepted. Review the pin before saving.</p>
        <p>Optional: search or enter coordinates instead. On save, device coordinates and reported accuracy are attached to the admin activity log. Read the <a href="<?php echo e(route('legal.privacy')); ?>" class="font-semibold text-red-900 underline">Privacy Notice</a> before requesting location. Retention and the official privacy contact are pending confirmation.</p>
    </div></div>
    <?php $__currentLoopData = ['location_source', 'actor_latitude', 'actor_longitude', 'gps_accuracy', 'gps_verified']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $gpsField): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $__errorArgs = [$gpsField];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <div class="gps-status"><?php if (isset($component)) { $__componentOriginalfeca38ed371c800d9ec38febdd194e55 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfeca38ed371c800d9ec38febdd194e55 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.global-loading','data' => ['size' => 'sm','color' => 'blue-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('global-loading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','color' => 'blue-600']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfeca38ed371c800d9ec38febdd194e55)): ?>
<?php $attributes = $__attributesOriginalfeca38ed371c800d9ec38febdd194e55; ?>
<?php unset($__attributesOriginalfeca38ed371c800d9ec38febdd194e55); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfeca38ed371c800d9ec38febdd194e55)): ?>
<?php $component = $__componentOriginalfeca38ed371c800d9ec38febdd194e55; ?>
<?php unset($__componentOriginalfeca38ed371c800d9ec38febdd194e55); ?>
<?php endif; ?><p data-gps-message role="status" aria-live="polite"></p></div>
    <input type="hidden" name="location_source" value="<?php echo e(old('location_source', $institution->location_source ?? 'manual')); ?>">
    <input type="hidden" name="actor_latitude">
    <input type="hidden" name="actor_longitude">
    <input type="hidden" name="gps_accuracy">
    <input type="hidden" name="gps_verified" value="0">
    <label data-gps-review class="gps-review hidden"><input type="checkbox" data-gps-confirm> I checked the pin against the institution location and corrected it if needed.</label>
</div>

<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/institutions/_gps.blade.php ENDPATH**/ ?>