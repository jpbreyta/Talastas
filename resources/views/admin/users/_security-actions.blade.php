@if(!auth()->user()->is($user))
<div class="mt-5 space-y-5">
@if($user->isAdmin())
<form method="POST" action="{{ route('security.mfa.send') }}" class="space-y-2">@csrf
<p class="text-xs text-gray-600">For browser removal or an administrator password reset, request a new code at your own registered email and enter it below.</p>
<button type="submit" class="users-button users-button-secondary">Email me a verification code</button>
</form>
@endif
@if($user->isAdmin())
<details><summary class="cursor-pointer text-sm font-semibold text-red-900">Revoke trusted browsers</summary>
<form method="POST" action="{{ route('admin.users.trusted-devices.revoke', $user) }}" class="mt-3 space-y-3">@csrf
<p class="text-xs text-gray-600">Remove all saved browser MFA authorizations for this administrator. They must verify again before protected access.</p>
<label class="block text-sm">Your current password<input type="password" name="current_password" required autocomplete="current-password" class="users-control mt-2"></label>
<label class="block text-sm">Your new six-digit email code<input name="code" required autocomplete="one-time-code" class="users-control mt-2"></label>
<label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required class="users-checkbox"><span>I confirm revoking this administrator's trusted browsers.</span></label>
<button type="submit" class="users-button users-button-secondary">Revoke trusted browsers</button>
</form></details>
@endif
<details><summary class="cursor-pointer text-sm font-semibold text-red-900">Send password reset instructions</summary>
<form method="POST" action="{{ route('admin.users.reset',$user) }}" class="mt-3 space-y-3">@csrf
<p class="text-xs text-gray-600">The account holder creates their own password using a code sent to their registered email.</p>
<label class="block text-sm">Your current password<input type="password" name="current_password" required autocomplete="current-password" class="users-control mt-2"></label>
@if($user->isAdmin())<label class="block text-sm">Your new six-digit email code<input name="email_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code" class="users-control mt-2"></label>@endif
<label class="flex gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required>I confirm sending recovery instructions to this account.</label>
<button type="submit" class="users-button">Send reset instructions</button></form></details>
<details><summary class="cursor-pointer text-sm font-semibold text-red-900">{{ $user->is_active ? 'Deactivate account' : 'Reactivate account' }}</summary>
<form method="POST" action="{{ route('admin.users.status',$user) }}" class="mt-3 space-y-3">@csrf
<label class="block text-sm">Your current password<input type="password" name="current_password" required autocomplete="current-password" class="users-control mt-2"></label>
<label class="flex gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required>I confirm changing access and invalidating existing sessions.</label>
<button type="submit" class="users-button users-button-secondary">Confirm access change</button></form></details>
</div>@endif
