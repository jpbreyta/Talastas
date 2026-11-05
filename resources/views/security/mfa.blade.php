@extends('security.layout')
@section('title', 'Check your email')
@section('subtitle', 'Enter the six-digit code sent to your registered email address.')
@section('container-class', 'max-w-lg')
@section('card-class', 'mfa-card')
@section('signout-class', 'mfa-signout')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/mfa.css') }}">
@endsection
@section('content')
<div class="mfa-layout">
<div class="mfa-verification">
@if($mfaSatisfied ?? false)
<p role="status" class="mb-5 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-900">Administrator access is verified for this session.</p>
<a href="{{ url('/') }}" class="mb-5 inline-flex text-sm font-semibold text-red-900 underline">Return to TALASTAS</a>
@endif
@if(session('mfa_challenge_reason'))<p role="status" class="mb-5 rounded-lg bg-blue-50 p-3 text-sm text-blue-900">{{ session('mfa_challenge_reason') }}</p>@endif
<p class="mb-4 text-sm text-gray-700">Your registered email: <strong class="break-all">{{ auth()->user()->email }}</strong></p>
@if($deliveryError)
<p role="alert" class="mb-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-950">{{ $deliveryError }}</p>
@elseif($challenge && !$challenge->consumed_at)
<p role="status" class="mb-4 text-sm text-gray-600">A code was sent to your email. Open Gmail or your email app and look for “Your TALASTAS verification code”. Check Spam if you cannot find it.</p>
@endif
<form method="POST" action="{{ route('security.mfa.verify') }}" class="mfa-verify-form">@csrf
@if(!$reuseLoginPassword)
<label class="block text-sm font-medium">Password<input placeholder="Current password" name="password" type="password" required autocomplete="current-password" class="mt-1 block w-full rounded-lg border border-gray-300 p-3">
</label>
@endif
<div class="mfa-code-entry">
    <div id="mfaCodeBoxes" class="mfa-code-boxes" role="group" aria-label="Six-digit email code" hidden>
        @for($digit = 0; $digit < 6; $digit++)
        <input type="text" class="mfa-digit" inputmode="numeric" pattern="[0-9]" maxlength="6" aria-label="Digit {{ $digit + 1 }} of 6" autocomplete="{{ $digit === 0 ? 'one-time-code' : 'off' }}">
        @endfor
    </div>
    <label id="mfaCodeFallback" class="block text-sm font-medium">Six-digit email code
        <input id="mfaCodeValue" name="code" placeholder="123456" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code" spellcheck="false" class="mt-1 block w-full rounded-lg border border-gray-300 p-3">
    </label>
</div>
<p class="text-xs text-gray-600">Codes expire in five minutes and allow five attempts. Each code can be used once.</p>
<label class="mfa-trust">
    <input type="checkbox" name="trust_device" value="1" class="mt-1 h-4 w-4 shrink-0 accent-red-900" aria-describedby="trustedBrowserHelp">
    <span>Trust this browser</span>
</label>
<details class="mfa-trust-details"><summary>About trusted browsers</summary><p id="trustedBrowserHelp">Use this only on your own secure device. You can sign in without another code while this browser stays active. Verification is required after {{ $inactivityDays }} consecutive days of inactivity, within 30 days at the latest, or after a security change. Your password is still required when signing in.</p></details>
<button type="submit" class="mfa-primary">Verify and continue</button>
</form>
<form method="POST" action="{{ route('security.mfa.send') }}" class="mt-4">@csrf
<button type="submit" data-resend-after="{{ $resendAfter }}" class="text-sm font-semibold text-red-900 underline disabled:opacity-50">Send a new email code</button>
</form>
</div>
</div>
@if($mfaSatisfied ?? false)
<section class="space-y-4 border-t border-gray-200 pt-5" aria-labelledby="trustedBrowsersHeading">
<div>
    <h2 id="trustedBrowsersHeading" class="font-semibold">Trusted browsers</h2>
    <p class="mt-1 text-sm text-gray-600">Remove access for a browser you no longer use or recognize. Enter the new email code directly in a removal form below. A code already used with “Verify and continue” cannot be used again.</p>
</div>
@if($mfaSatisfied ?? false)
@forelse($devices->filter(fn ($device) => $device->trusted_at !== null) as $device)
@php
    $deviceRevoked = $device->revoked_at !== null;
    $deviceExpired = $device->expires_at->lte(now()) || $device->last_activity_at->copy()->addDays($inactivityDays)->lte(now());
@endphp
<article class="space-y-3 rounded-xl border border-gray-200 p-4">
    <div class="flex flex-wrap items-center gap-2">
        <h3 class="text-sm font-semibold">Browser trusted {{ $device->trusted_at->format('M j, Y') }}</h3>
        @if($device->id === $currentDeviceId)<span class="rounded-full bg-blue-50 px-2 py-1 text-xs text-blue-900">This browser</span>@endif
        <span class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700">{{ $deviceRevoked ? 'Revoked' : ($deviceExpired ? 'Expired' : 'Active') }}</span>
    </div>
    <dl class="grid gap-2 text-xs text-gray-600 sm:grid-cols-2">
        <div><dt>Last used</dt><dd class="mt-1 text-gray-900">{{ $device->last_activity_at->format('M j, Y H:i T') }}</dd></div>
        <div><dt>Last email verification</dt><dd class="mt-1 text-gray-900">{{ $device->last_verified_at->format('M j, Y H:i T') }}</dd></div>
        <div><dt>Trust expires by</dt><dd class="mt-1 text-gray-900">{{ $device->expires_at->format('M j, Y H:i T') }}</dd></div>
    </dl>
    @if(!$deviceRevoked)
    <details>
        <summary class="cursor-pointer text-sm font-medium text-red-900">Revoke this browser</summary>
        <form method="POST" action="{{ route('security.mfa.devices.revoke', $device->id) }}" class="mt-3 space-y-3">@csrf
            <label class="block text-sm">Current password<input name="current_password" type="password" required autocomplete="current-password" class="mt-1 block w-full rounded-lg border border-gray-300 p-3"></label>
            <label class="block text-sm">New six-digit email code<input name="code" required autocomplete="one-time-code" class="mt-1 block w-full rounded-lg border border-gray-300 p-3"></label>
            <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required class="mt-1 accent-red-900"><span>I confirm removing this browser's saved MFA authorization.</span></label>
            <button type="submit" class="rounded-lg border border-red-900 px-4 py-2 text-sm font-semibold text-red-900">Revoke browser</button>
        </form>
    </details>
    @endif
</article>
@empty
<p class="rounded-lg bg-gray-50 p-3 text-sm text-gray-600">No trusted browsers. Complete verification and choose Trust this browser to add this device.</p>
@endforelse
<details>
    <summary class="cursor-pointer text-sm font-semibold text-red-900">Revoke all trusted browsers</summary>
    <form method="POST" action="{{ route('security.mfa.devices.revoke-all') }}" class="mt-3 space-y-3">@csrf
        <p class="text-sm text-gray-600">All saved browser authorizations will be removed, including this browser. Each browser must verify again before administrator access.</p>
        <label class="block text-sm">Current password<input name="current_password" type="password" required autocomplete="current-password" class="mt-1 block w-full rounded-lg border border-gray-300 p-3"></label>
        <label class="block text-sm">New six-digit email code<input name="code" required autocomplete="one-time-code" class="mt-1 block w-full rounded-lg border border-gray-300 p-3"></label>
        <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required class="mt-1 accent-red-900"><span>I confirm revoking all trusted browsers.</span></label>
        <button type="submit" class="rounded-lg border border-red-900 px-4 py-2 text-sm font-semibold text-red-900">Revoke all browsers</button>
    </form>
</details>
@else
<p class="rounded-lg bg-gray-50 p-3 text-sm text-gray-600">Verify above to view and manage your trusted browsers.</p>
@endif
</section>
@endif
@endsection

@section('scripts')
<script src="{{ asset('js/mfa-code.js') }}" defer></script>
<script>
(() => {
    const button = document.querySelector('[data-resend-after]');
    if (!button) return;
    const update = () => {
        const seconds = Math.max(0, Number(button.dataset.resendAfter) - Math.floor(Date.now() / 1000));
        button.disabled = seconds > 0;
        button.textContent = seconds ? `Send a new code in ${seconds}s` : 'Send a new email code';
    };
    update();
    const timer = setInterval(() => { update(); if (!button.disabled) clearInterval(timer); }, 1000);
})();
</script>
@endsection
