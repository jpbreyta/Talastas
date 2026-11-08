<footer class="bg-red-900 border-t border-red-800 flex-shrink-0">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex w-full flex-col gap-3 md:flex-row md:justify-between md:items-center">
            <div class="text-sm text-red-200">
                © <?php echo e(date('Y')); ?> Lipa City Local Government Unit. All rights reserved.
            </div>
            <div class="text-red-100 md:ml-auto">
                <?php echo $__env->make('partials.legal-links', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </div>
</footer>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/layouts/footer.blade.php ENDPATH**/ ?>