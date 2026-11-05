@extends('security.layout')
@section('title','Recover your password')
@section('content')
@if(!\App\Services\SecurityMail::available())
<p role="status" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-950">Recovery email is currently unavailable. Contact the System Admin for assistance and try again after email delivery is restored. Account verification is still required.</p>
@endif
@if(session()->has('password_recovery'))
<p class="text-sm text-gray-600">Choose and confirm your new password within ten minutes of code verification. After saving, sign in with your new password.</p>
<form method="POST" action="{{ route('security.recovery.reset') }}" class="space-y-4">@csrf @include('security._password')<button type="submit" class="rounded-lg bg-red-900 px-4 py-3 font-semibold text-white">Save new password</button>
</form>
@else
<p class="text-sm text-gray-600">Editors and city planners can request a code sent to their registered email. System Admins must ask another System Admin to initiate a password reset, then enter the code received by email.</p>
<form method="POST" action="{{ route('security.recovery.request') }}" class="space-y-3">@csrf
<label class="block text-sm font-medium">Registered email<input name="email" value="{{ old('email',session('recovery_email')) }}" type="email" required autocomplete="email" class="mt-1 block w-full rounded-lg border border-gray-300 p-3">
</label>
<button type="submit" data-resend-after="{{ session('resend_after',0) }}" class="rounded-lg bg-red-900 px-4 py-3 font-semibold text-white disabled:opacity-50">Send recovery code</button>
</form>
<hr class="border-gray-200">
<form method="POST" action="{{ route('security.recovery.verify') }}" class="space-y-3">@csrf
<label class="block text-sm font-medium">Registered email<input name="email" value="{{ old('email',session('recovery_email')) }}" type="email" required autocomplete="email" class="mt-1 block w-full rounded-lg border border-gray-300 p-3">
</label>
<label class="block text-sm font-medium">Six-digit email code<input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code" class="mt-1 block w-full rounded-lg border border-gray-300 p-3">
</label>
<p class="text-xs text-gray-600">Codes expire in five minutes and allow five attempts. Sending a replacement invalidates the old code.</p>
<button type="submit" class="rounded-lg bg-red-900 px-4 py-3 font-semibold text-white">Verify code</button>
</form>
@endif
<a href="{{ route('login') }}" class="inline-block text-sm font-semibold text-red-900 underline">Return to sign in</a>
@endsection
@section('scripts')<script>const resend=document.querySelector('[data-resend-after]');if(resend){const timer=setInterval(()=>{const left=Math.max(0,Number(resend.dataset.resendAfter)-Math.floor(Date.now()/1000));resend.disabled=left>0;resend.textContent=left?`Resend available in ${left}s`:'Send recovery code';if(!left)clearInterval(timer);},250);}</script>@endsection
