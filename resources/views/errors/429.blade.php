@extends('security.layout')
@section('title', 'Please wait before trying again')
@section('content')
@php($retryAfter = max(1, (int) ($retryAfter ?? 60)))
@php($returnUrl = $returnUrl ?? route('login'))
@php($returnLabel = $returnLabel ?? 'Return to sign in')
<div class="space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-relaxed text-amber-950">
    <p class="font-semibold">Too many attempts were made in a short time.</p>
    <p id="rateLimitMessage" role="status">Please wait <span id="rateLimitCountdown" aria-live="off">{{ $retryAfter }}</span> seconds before trying again.</p>
    <p>Your request was not completed. Return to the form after the wait, check your details and submit again. Nothing will be submitted automatically.</p>
</div>
<a id="rateLimitReturn" role="link" aria-disabled="true" tabindex="-1"
    data-return-url="{{ $returnUrl }}" data-retry-after="{{ $retryAfter }}"
    class="inline-flex cursor-not-allowed rounded-lg bg-red-900 px-4 py-3 text-sm font-semibold text-white opacity-50">{{ $returnLabel }}</a>
<p class="text-xs leading-relaxed text-gray-600">Keep this page open while the countdown finishes. If you were opening an invitation, return to the latest invitation email when prompted.</p>
<noscript><p class="text-sm text-gray-600">JavaScript is unavailable. Wait {{ $retryAfter }} seconds, then use the TALASTAS link above to return to sign in.</p></noscript>
@endsection
@section('scripts')
<script>
(() => {
    if (location.hash) history.replaceState(null, '', location.pathname + location.search);
    const link = document.getElementById('rateLimitReturn');
    const countdown = document.getElementById('rateLimitCountdown');
    const message = document.getElementById('rateLimitMessage');
    const waitUntil = Date.now() + Number(link.dataset.retryAfter) * 1000;
    let timer;
    const update = () => {
        const remaining = Math.max(0, Math.ceil((waitUntil - Date.now()) / 1000));
        countdown.textContent = remaining;
        if (remaining > 0) return;
        clearInterval(timer);
        link.href = link.dataset.returnUrl;
        link.removeAttribute('aria-disabled');
        link.removeAttribute('tabindex');
        link.classList.remove('cursor-not-allowed', 'opacity-50');
        message.textContent = 'You can return to the form and try again now.';
    };
    timer = setInterval(update, 1000);
    update();
    window.addEventListener('pagehide', () => clearInterval(timer), { once: true });
})();
</script>
@endsection
