<section data-user-activity-live class="mb-4 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500" <?php if($activityTab !== 'sessions'): ?> hidden <?php endif; ?> aria-label="Session information">
    <p>Currently active: <?php echo e($online); ?> users</p>
    <details class="users-accordion max-w-xl text-xs">
        <summary>About session activity</summary>
        <p class="mt-2 leading-5">Active means seen within two minutes. Browser closure is not an exact logout. Expired sessions show last activity, not an invented close time. Logs begin when this feature is enabled; existing sessions show first observed access. Times are shown in Asia/Manila.</p>
    </details>
</section>
<section data-user-activity-live class="users-card" <?php if($activityTab !== 'sessions'): ?> hidden <?php endif; ?> aria-label="Session Activity">
    <div class="users-table-scroll" tabindex="0" role="region" aria-label="Session activity table">
        <table class="users-table min-w-[1000px]">
            <thead><tr>
                <?php $__currentLoopData = ['User', 'Channel', 'Status', 'Opened / Created', 'Last Activity', 'Signed Out']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <th scope="col"><?php echo e($column); ?></th>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $sessions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $session): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $seen = \Carbon\Carbon::parse($session->last_seen_at);
                        $state = $session->closed_at ? 'Signed out' : ($seen->lt(now()->subMinutes(config('session.lifetime', 120))) ? 'Expired' : ($seen->gte(now()->subMinutes(2)) ? 'Active' : 'Idle'));
                    ?>
                    <tr>
                        <td class="min-w-[180px]"><?php echo e($session->user_name); ?></td>
                        <td><?php echo e(strtoupper($session->channel)); ?></td>
                        <td class="whitespace-nowrap"><?php echo e($state); ?></td>
                        <td class="users-time"><?php echo e(\Carbon\Carbon::parse($session->opened_at)->timezone('Asia/Manila')->format('d M Y h:i:s A')); ?></td>
                        <td class="users-time"><?php echo e($seen->timezone('Asia/Manila')->format('d M Y h:i:s A')); ?></td>
                        <td class="users-time"><?php echo e($session->closed_at ? \Carbon\Carbon::parse($session->closed_at)->timezone('Asia/Manila')->format('d M Y h:i:s A') : 'Not recorded'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="text-center text-gray-500">No session activity recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="users-pagination"><?php echo e($sessions->links()); ?></div>
</section>
<section data-user-activity-live class="users-card" <?php if($activityTab !== 'changes'): ?> hidden <?php endif; ?> aria-label="Data Changes">
    <div class="users-table-scroll" tabindex="0" role="region" aria-label="Data changes table">
        <table class="users-table min-w-[1000px]">
            <thead><tr>
                <?php $__currentLoopData = ['User', 'Action', 'Record', 'Fields Changed', 'Time']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <th scope="col"><?php echo e($column); ?></th>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="min-w-[180px]"><?php echo e($log->user_name); ?></td>
                        <td><?php echo e(ucfirst($log->action)); ?></td>
                        <td class="min-w-[140px]"><?php echo e($log->record_type); ?> #<?php echo e($log->record_id); ?></td>
                        <td class="users-wrap min-w-[280px]">
                            <?php echo e($log->changed_fields ?: 'Not applicable'); ?>

                            <?php if($log->location_source ?? null): ?>
                                <details class="users-accordion mt-2 text-xs text-gray-600">
                                    <summary>Location: <?php echo e($log->location_source); ?></summary>
                                    <div class="mt-2 space-y-1 leading-5">
                                        <?php if($log->actor_latitude !== null): ?>
                                            <p>Device: <?php echo e($log->actor_latitude); ?>, <?php echo e($log->actor_longitude); ?></p>
                                            <p>Reported accuracy: approximately <?php echo e($log->gps_accuracy); ?> meters. Pin verified: <?php echo e($log->gps_verified ? 'Yes' : 'No'); ?>.</p>
                                        <?php else: ?>
                                            <p>No device location captured.</p>
                                        <?php endif; ?>
                                    </div>
                                </details>
                            <?php endif; ?>
                        </td>
                        <td class="users-time"><?php echo e(\Carbon\Carbon::parse($log->occurred_at)->timezone('Asia/Manila')->format('d M Y h:i:s A')); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="5" class="text-center text-gray-500">No data changes recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="users-pagination"><?php echo e($logs->links()); ?></div>
</section>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/admin/users/_activity.blade.php ENDPATH**/ ?>