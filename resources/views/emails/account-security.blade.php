<!doctype html>
<html lang="en">
<body style="font-family:Arial,sans-serif;color:#1f2937;background:#f3f4f6;padding:24px">
<div style="max-width:560px;margin:auto;background:white;padding:28px;border-top:4px solid #74151b">
<p style="color:#74151b;font-weight:bold">TALASTAS ICT Mapping</p>
<h1 style="font-size:22px">{{ $title }}</h1>
@if($kind==='verification')
<p>Enter this six-digit code in TALASTAS to verify your sign-in or security action:</p>
<p style="font-size:30px;font-weight:bold;letter-spacing:6px">{{ $details['code'] }}</p>
<p>This code expires in five minutes and can be used once. Never share it. If you did not request this code, do not enter it and contact your System Admin.</p>
@elseif($kind==='invitation')
<p>Hello {{ $details['name'] }}, you have been invited to TALASTAS as {{ $details['role'] }} using this email address. Confirm this address, choose your password, and review the legal policies to activate access.</p>
<p>
<a href="{{ $details['url'] }}">Review and accept your invitation</a>
</p>
<p>Expires: {{ $details['expires'] }}. Opening the link alone does not activate your account.</p>
@elseif(in_array($kind,['recovery','admin_reset']))
@if($kind==='admin_reset')<p>A System Admin requested a password reset for your account. The administrator does not know or choose your password. Use the email code below to verify the reset.</p>@endif
<p>Your six-digit recovery code is:</p>
<p style="font-size:30px;font-weight:bold;letter-spacing:6px">{{ $details['code'] }}</p>
<p>Expires: {{ $details['expires'] }}. Codes last five minutes, permit five attempts and work once. A replacement invalidates earlier codes. Never share this code.</p>
<p>
<a href="{{ $details['url'] }}">Continue password recovery</a>
</p>
@elseif($kind==='password_changed')<p>Your password was changed. Existing sessions and API access tokens were invalidated. If you did not do this, contact your System Admin immediately.</p>
@elseif($kind==='deactivated')<p>A System Admin deactivated your account. Existing sessions and API access tokens were invalidated. Contact your System Admin if this was unexpected.</p>
@elseif($kind==='role_changed')<p>A System Admin updated your account role or institution assignments. Sign in again to review your access. Contact your System Admin if this was unexpected.</p>
@elseif($kind==='test')<p>This is a controlled email delivery test. It contains no activation link, password, or recovery code.</p>
@else<p>Your account is available. Sign in with the password you established. System Admins will receive a verification code at their registered email before accessing protected features.</p>@endif
<p style="font-size:12px;color:#6b7280">This is an automated notification. This sender mailbox is not monitored. If this message was unexpected, contact your institution's System Admin through your established support channel.</p>
</div>
</body>
</html>
