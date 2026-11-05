@extends('security.layout')
@section('title','Accept your invitation')
@section('content')
<p id="invitationMessage" role="status" aria-live="polite" class="text-sm text-gray-600" @if($invite) hidden @endif>Open the latest invitation email to begin. Opening this page does not activate an account.</p>
<button id="invitationRetry" type="button" hidden disabled class="rounded-lg border border-red-900 px-4 py-2 text-sm font-semibold text-red-900 disabled:cursor-not-allowed disabled:opacity-50">Try again</button>
@if($invite && !$policyState['can_accept'])
<div role="status" class="space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
    <p class="font-semibold">Account setup is temporarily unavailable</p>
    <p>{{ $policyState['message'] }}</p>
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('legal.terms') }}" class="font-medium underline">Read the current Terms</a>
        <a href="{{ route('legal.privacy') }}" class="font-medium underline">Read the current Privacy Notice</a>
    </div>
</div>
@elseif($invite)
<p class="text-sm text-gray-600">You were invited as <strong>{{ ['admin' => 'System Administrator', 'editor' => 'Barangay Focal Person / ICT Coordinator', 'viewer' => 'LGU City Planner'][$invite->role] ?? $invite->role }}</strong>. Confirm the address that received the invitation. This invitation expires {{ $invite->expires_at->timezone('Asia/Manila')->format('M j, Y H:i T') }}. Expired link? Ask the System Admin who invited you to resend it. Always use the latest email link.</p>
<ol class="list-decimal space-y-2 pl-5 text-sm text-gray-600"><li>Confirm your invited email and choose a password for a new account.</li><li>Review the Terms and the separate Privacy Notice, then activate your account.</li><li>Sign in. Administrators will receive a six-digit verification code at their registered email.</li></ol>
<p class="text-xs text-gray-600">Complete this setup within ten minutes of opening the email link. If the setup session expires, reopen the latest invitation email. Current Terms accepted here are recognized when you sign in.</p>
@if(($policyState['mode'] ?? null) === 'developer-managed')
<div role="note" class="space-y-2 rounded-xl border border-green-200 bg-green-50 p-4 text-sm leading-relaxed text-gray-900">
    <p class="font-semibold text-green-900">Published for application use</p>
    <p>The project developers have enabled the current Terms and Privacy Notice for startup account setup. Institutional approval and the outstanding governance and privacy disclosures remain pending. Review both documents before acknowledging them below.</p>
    <p class="text-xs text-gray-700">Application use from {{ $policyState['release']['effective_at'] }} &middot; Published by {{ $policyState['release']['published_by'] }}.</p>
</div>
@endif
@if(\App\Models\User::whereRaw('LOWER(TRIM(email)) = ?',[$invite->email])->exists() && !auth()->check())
<p class="rounded-lg bg-amber-50 p-3 text-sm">This email already has an account. <a class="font-semibold underline" href="{{ route('login') }}">Sign in with that account</a>, then reopen the invitation link. No password will be changed by this invitation.</p>
@endif
<form method="POST" action="{{ route('security.invitation.accept',$id) }}" class="space-y-4">@csrf
<label class="block text-sm font-medium">Confirm your invited email<input name="email" type="email" required autocomplete="email" class="mt-1 block w-full rounded-lg border border-gray-300 p-3">
</label>
@if(!\App\Models\User::whereRaw('LOWER(TRIM(email)) = ?',[$invite->email])->exists()) @include('security._password') @endif
<input type="hidden" name="version" value="{{ $terms['content']['version'] }}">
<input type="hidden" name="content_hash" value="{{ $terms['hash'] }}">
<input type="hidden" name="privacy_hash" value="{{ $privacy['hash'] }}">
<label class="flex items-start gap-2 text-sm">
<input type="checkbox" name="accepted" value="1" required class="mt-1 accent-red-900">
<span>I have reviewed and accept the <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener noreferrer" class="underline">Terms and Conditions</a>.</span>
</label>
<label class="flex items-start gap-2 text-sm">
<input type="checkbox" name="privacy_accepted" value="1" required class="mt-1 accent-red-900">
<span>I have reviewed and acknowledge the <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener noreferrer" class="underline">Privacy Notice</a>.</span>
</label>
<button type="submit" class="rounded-lg bg-red-900 px-4 py-3 font-semibold text-white">Accept invitation and activate account</button>
</form>
@endif
@endsection
@section('scripts')
<script>
(() => {
    const token = new URLSearchParams(location.hash.slice(1)).get('token');
    history.replaceState(null, '', location.pathname);
    if (!token) return;
    const message = document.getElementById('invitationMessage');
    const retry = document.getElementById('invitationRetry');
    let inFlight = false;
    let retryTimer;
    const showMessage = text => {
        message.hidden = false;
        message.textContent = text;
    };
    const allowRetry = text => {
        showMessage(text);
        retry.hidden = false;
        retry.disabled = false;
        retry.textContent = 'Try again';
    };
    const waitToRetry = seconds => {
        const waitUntil = Date.now() + seconds * 1000;
        retry.hidden = false;
        retry.disabled = true;
        const update = () => {
            const remaining = Math.max(0, Math.ceil((waitUntil - Date.now()) / 1000));
            if (remaining === 0) {
                clearInterval(retryTimer);
                allowRetry('You can check your invitation again now. Select Try again to continue.');
                return;
            }
            showMessage('Too many attempts. Please wait ' + remaining + ' seconds before checking this invitation again. Your invitation has not been accepted.');
            retry.textContent = 'Try again in ' + remaining + 's';
        };
        retryTimer = setInterval(update, 1000);
        update();
    };
    const exchange = async () => {
        if (inFlight || (!retry.hidden && retry.disabled)) return;
        clearInterval(retryTimer);
        inFlight = true;
        retry.hidden = true;
        retry.disabled = true;
        showMessage('Checking your invitation...');
        try {
            const response = await fetch(@json(route('security.invitation.exchange', $id)), {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ token }),
            });
            if (response.status === 429) {
                const payload = await response.json().catch(() => ({}));
                const header = response.headers.get('Retry-After');
                const headerSeconds = header && /^\d+$/.test(header) ? Number(header) : (Date.parse(header || '') - Date.now()) / 1000;
                const values = [Number(payload.retry_after), headerSeconds].filter(value => Number.isFinite(value) && value > 0);
                waitToRetry(values.length ? Math.ceil(Math.max(...values)) : 60);
            } else if (response.ok) {
                location.replace(location.pathname);
            } else if ([404, 410, 422].includes(response.status)) {
                showMessage('This invitation could not be verified. Open the latest invitation email or ask the System Admin for a replacement.');
            } else if (response.status === 419) {
                showMessage('Your session expired. Open the latest invitation email again to start a new session.');
            } else {
                allowRetry('Invitation checking is temporarily unavailable. Keep this page open and select Try again in a moment.');
            }
        } catch {
            allowRetry('Unable to connect. Check your connection, then select Try again. Keep this page open to retry your invitation.');
        } finally {
            inFlight = false;
        }
    };
    retry.addEventListener('click', exchange);
    window.addEventListener('pagehide', () => clearInterval(retryTimer), { once: true });
    exchange();
})();
</script>
@endsection
