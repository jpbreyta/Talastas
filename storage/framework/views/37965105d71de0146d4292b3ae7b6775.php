<?php ($connectionAssessment = ($connectionAssessments ?? collect())->get($connectionNumber)); ?>
<div class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600" data-connection-result>
    <?php if($connectionAssessment): ?>
        <div class="flex flex-wrap gap-x-5 gap-y-1">
            <span>Advertised download: <strong><?php echo e(isset($connectionAssessment['advertised_download_mbps']) ? number_format($connectionAssessment['advertised_download_mbps'], 2).' Mbps' : 'Unidentified'); ?></strong></span>
            <span>Download shortfall: <strong><?php echo e(isset($connectionAssessment['shortfall_percent']) ? number_format($connectionAssessment['shortfall_percent'], 2).'%' : 'Unavailable'); ?></strong></span>
        </div>
        <?php $__currentLoopData = $connectionAssessment['reason_codes'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reason): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <p class="mt-1"><?php echo e(\App\Services\InternetConnectivityAssessment::reasonLabel($reason)); ?></p>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php else: ?>
        Download shortfall: Not calculated
    <?php endif; ?>
</div>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/ict-inventory/_connection-result.blade.php ENDPATH**/ ?>