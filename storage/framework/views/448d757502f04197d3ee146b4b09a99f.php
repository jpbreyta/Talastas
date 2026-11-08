<?php
    $connectionAssessments = collect(data_get($institution->institutionAnalytics?->recommendations_detail, 'connectivity_assessment.connections', []))->keyBy('connection_number');
    $detailFields = ['isp' => 'ISP', 'plan' => 'Plan', 'allocation' => 'Allocation', 'validity' => 'Validity', 'context' => 'Connection Context'];
    $performanceFields = ['advertised_download_mbps' => 'Advertised download (Mbps)', 'download_speed_mbps' => 'Download Speed (Mbps)', 'upload_speed_mbps' => 'Upload Speed (Mbps)', 'latency_ms' => 'Latency (ms)', 'throughput_mbps' => 'Throughput (Mbps)'];
?>
<section class="rounded-xl border border-gray-200 bg-white p-5" data-connectivity-connections>
    <h3 class="text-sm font-semibold text-gray-900">Internet Connection Details</h3>
    <p class="mt-2 text-xs text-gray-500">Reported connections: <?php echo e($institution->q10_number_of_connections ?? 'Not recorded'); ?></p>
    <?php $__empty_1 = true; $__currentLoopData = $institution->internetConnections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $connection): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php ($connectionNumber = $connection->connection_number); ?>
        <article class="mt-4 border-t border-gray-100 pt-4" data-connection-summary="<?php echo e($connectionNumber); ?>">
            <h4 class="text-sm font-semibold text-gray-900">Connection <?php echo e($connectionNumber); ?></h4>
            <dl class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <?php $__currentLoopData = $detailFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><dt class="text-xs text-gray-500"><?php echo e($label); ?></dt><dd class="mt-1 break-words"><?php echo e($connection->{$field} ?? 'Not recorded'); ?></dd></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </dl>
            <h5 class="mt-4 text-xs font-semibold text-gray-700">Network Performance</h5>
            <dl class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <?php $__currentLoopData = $performanceFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><dt class="text-xs text-gray-500"><?php echo e($label); ?></dt><dd class="mt-1 tabular-nums"><?php echo e($connection->{$field} === null ? 'Not recorded' : number_format($connection->{$field}, 2)); ?></dd></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </dl>
            <?php echo $__env->make('ict-inventory._connection-result', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </article>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <p class="mt-3 text-sm text-gray-500">Not recorded</p>
    <?php endif; ?>
</section>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/ict-inventory/_q10-summary.blade.php ENDPATH**/ ?>