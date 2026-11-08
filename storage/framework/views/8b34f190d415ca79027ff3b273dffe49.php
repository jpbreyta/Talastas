<?php
    $logQuery = request()->routeIs('admin.users.logs') ? request()->except('tab') : [];
    $userTabs = [
        'accounts' => ['label' => 'Accounts', 'url' => route('admin.users.index')],
        'invitations' => ['label' => 'Invitations', 'url' => route('admin.invitations.index')],
        'sessions' => ['label' => 'Session Activity', 'url' => route('admin.users.logs', array_merge($logQuery, ['tab' => 'sessions']))],
        'changes' => ['label' => 'Data Changes', 'url' => route('admin.users.logs', array_merge($logQuery, ['tab' => 'changes']))],
    ];
?>
<nav class="users-tabs" aria-label="Accounts and logs">
    <?php $__currentLoopData = $userTabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $userTab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e($userTab['url']); ?>" <?php if($activeTab === $key): ?> aria-current="page" <?php endif; ?>><?php echo e($userTab['label']); ?></a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</nav>
<script>
document.addEventListener('DOMContentLoaded', () => {
    function revealSelectedTab() {
        const tabs = document.querySelector('.users-tabs');
        const selected = tabs?.querySelector('[aria-current="page"]');
        if (!selected || tabs.scrollWidth <= tabs.clientWidth) return;
        tabs.scrollLeft += selected.getBoundingClientRect().left - tabs.getBoundingClientRect().left - (tabs.clientWidth - selected.offsetWidth) / 2;
    }
    revealSelectedTab();
    document.fonts?.ready.then(revealSelectedTab);
    window.addEventListener('resize', revealSelectedTab);
});
</script>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/admin/users/_tabs.blade.php ENDPATH**/ ?>