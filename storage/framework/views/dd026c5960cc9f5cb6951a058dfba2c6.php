<?php $__env->startSection('scripts'); ?>
<script>
(() => {
 let refreshing = false;
 setInterval(async () => {
  if (refreshing || document.visibilityState !== 'visible' || document.activeElement?.closest('[data-user-activity-live]')) return;
  refreshing = true;
  try {
   const response = await fetch(window.location.href, {headers:{'X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
   if (!response.ok || response.redirected) return;
   const next = new DOMParser().parseFromString(await response.text(), 'text/html');
   const sections = [...next.querySelectorAll('[data-user-activity-live]')];
   const current = [...document.querySelectorAll('[data-user-activity-live]')];
   if (sections.length === current.length) current.forEach((section,index) => section.replaceChildren(...sections[index].childNodes));
  } catch {} finally { refreshing = false; }
 },30000);
})();
</script>
<?php $__env->stopSection(); ?>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/admin/users/_activity-scripts.blade.php ENDPATH**/ ?>