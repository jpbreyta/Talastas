<?php
    $toastMessages = [];
    foreach (['success', 'deleted', 'error', 'warning', 'info'] as $toastType) {
        if (session()->has($toastType)) {
            $toastMessages[] = ['type' => $toastType, 'message' => session($toastType)];
        }
    }
    if ($errors->any() && !session()->has('error')) {
        $toastMessages[] = ['type' => 'error', 'message' => $errors->first()];
    }
?>
<div id="talastasToastContainer" data-notifications="<?php echo e(json_encode($toastMessages)); ?>">
    <div data-toast-region="polite" aria-live="polite" aria-relevant="additions" aria-atomic="false"></div>
    <div data-toast-region="assertive" aria-live="assertive" aria-relevant="additions" aria-atomic="false"></div>
</div>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/partials/notifications.blade.php ENDPATH**/ ?>