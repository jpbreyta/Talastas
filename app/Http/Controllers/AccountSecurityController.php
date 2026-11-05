<?php

namespace App\Http\Controllers;

use App\Models\AccountRecovery;
use App\Models\MfaTrustedDevice;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\InvitationService;
use App\Services\LegalPolicies;
use App\Services\MfaDeviceTrust;
use App\Services\MfaService;
use App\Services\RecoveryService;
use App\Services\SecurityAudit;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AccountSecurityController extends Controller
{
    public function invitation(Request $request, string $id)
    {
        abort_unless(Str::isUuid($id), 404);
        $state = $request->session()->get('invitation_setup');
        $invite = $state && $state['id'] === $id ? UserInvitation::find($id) : null;
        if ($invite && (($state['expires'] ?? 0) <= time() || $invite->status !== 'pending' || $invite->expires_at->isPast()
            || $invite->generation !== ($state['generation'] ?? null) || ! hash_equals((string) $invite->token_hash, (string) ($state['token_hash'] ?? '')))) {
            $request->session()->forget('invitation_setup');
            $invite = null;
        }
        $terms = app(LegalPolicies::class)->document('terms');
        $privacy = app(LegalPolicies::class)->document('privacy');
        $policyState = app(LegalPolicies::class)->invitationPolicyState($terms, $privacy);

        return response()->view('security.invitation', compact('invite', 'terms', 'privacy', 'policyState', 'id'))->header('Referrer-Policy', 'no-referrer')->header('Cache-Control', 'no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function exchange(Request $request, string $id, InvitationService $service)
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:64']]);
        $request->session()->regenerate();
        $request->session()->put('invitation_setup', $service->exchange($id, $data['token']));

        return response()->json(['success' => true])->header('Cache-Control', 'no-store');
    }

    public function accept(Request $request, string $id, InvitationService $service)
    {
        $request->validate(['email' => ['required', 'email'], 'role' => ['prohibited'], 'institution_ids' => ['prohibited']]);
        $state = $request->session()->get('invitation_setup', []);
        abort_unless(($state['id'] ?? null) === $id, 422, 'Open the latest invitation link.');
        try {
            $service->accept($state, $request->all(), $request->user());
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages(['email' => 'An account or acceptance already exists. Sign in or request a new invitation.']);
        }
        $request->session()->forget('invitation_setup');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Invitation accepted. Sign in with your password. System Admins will receive a verification code by email.');
    }

    public function recovery(Request $request)
    {
        $state = $request->session()->get('password_recovery');
        if ($state) {
            $row = AccountRecovery::find($state['id'] ?? '');
            if (! $row || $row->consumed_at || $row->expires_at->isPast() || $row->generation !== ($state['generation'] ?? null)) {
                $request->session()->forget('password_recovery');
                $request->session()->flash('status', 'Your recovery session expired. Request a new email code to continue.');
            }
        }

        return response()->view('security.recovery')->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function requestCode(Request $request, RecoveryService $service)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $request->session()->forget('password_recovery');
        $service->request($data['email']);
        $request->session()->put('recovery_email', strtolower(trim($data['email'])));

        return back()->with('status', RecoveryService::MESSAGE)->with('resend_after', now()->addMinute()->timestamp);
    }

    public function verifyCode(Request $request, RecoveryService $service)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'code' => ['required', 'digits:6']]);
        $state = $service->verify($data['email'], $data['code']);
        if (! $state) {
            throw ValidationException::withMessages(['code' => 'The code is invalid, expired, or unavailable. Request a new code if needed.']);
        }
        $request->session()->regenerate();
        $request->session()->put('password_recovery', $state);

        return back()->with('status', 'Code verified. Set your new password within ten minutes.');
    }

    public function resetPassword(Request $request, RecoveryService $service)
    {
        try {
            $service->reset($request->session()->get('password_recovery', []), $request->all());
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() !== 422) {
                throw $exception;
            }
            $request->session()->forget('password_recovery');

            return redirect()->route('security.recovery')->withErrors(['code' => 'Recovery expired. Request a new code.']);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Password changed. Please sign in again.');
    }

    public function mfa(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $trust = app(MfaDeviceTrust::class);
        $mfaSatisfied = $trust->authorized($request->user(), $request);
        $devices = $mfaSatisfied ? MfaTrustedDevice::where('user_id', $request->user()->id)->whereNotNull('trusted_at')->latest()->limit(20)->get() : collect();
        $inactivityDays = $trust->inactivityDays();
        $currentDeviceId = $request->session()->get('mfa_device_id');
        $reuseLoginPassword = app(\App\Services\PendingMfaLogin::class)->valid($request);
        $challenge = null;
        $deliveryError = null;
        try {
            $challenge = app(MfaService::class)->send($request->user());
        } catch (ValidationException $exception) {
            $deliveryError = collect($exception->errors())->flatten()->first();
        }
        $resendAfter = $challenge?->requested_at->copy()->addMinute()->timestamp ?? now()->addMinute()->timestamp;

        return response()->view('security.mfa', compact('devices', 'inactivityDays', 'currentDeviceId', 'mfaSatisfied', 'reuseLoginPassword', 'challenge', 'deliveryError', 'resendAfter'))->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function sendMfaCode(Request $request, MfaService $service)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $service->send($request->user(), true);

        return back()->with('status', 'A new six-digit code was sent to your registered email. It expires in five minutes.');
    }

    public function verifyMfa(Request $request, MfaService $service)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $reuseLoginPassword = app(\App\Services\PendingMfaLogin::class)->valid($request);
        $data = $request->validate(['code' => ['required', 'digits:6'], 'password' => $reuseLoginPassword ? ['exclude'] : ['required', 'current_password'], 'trust_device' => ['sometimes', 'boolean']]);
        $user = $request->user();
        if (! $service->verify($user, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'The email code is incorrect, expired, or already used. Request a new code if needed.']);
        }
        try {
            app(MfaDeviceTrust::class)->establish($user, $request, $request->boolean('trust_device'));
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() !== 409) {
                throw $exception;
            }
            // A consumed factor must never grant access after a concurrent security change.
            SecurityAudit::record('mfa.session_changed', $user->id, 'denied');
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            app(MfaDeviceTrust::class)->forgetCookie();

            return redirect()->route('login')->with('status', 'Your sign-in could not be completed. Sign in again and request a new email code.');
        }
        $request->session()->forget('pending_mfa_login');
        return redirect(app(\App\Services\AuthenticationDestination::class)->pull($request));
    }

    public function revokeDevice(Request $request, string $device, MfaService $mfa, MfaDeviceTrust $trust)
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless(Str::isUuid($device), 404);
        MfaTrustedDevice::where('user_id', $request->user()->id)->whereKey($device)->firstOrFail();
        $this->confirmDeviceRevocation($request, $mfa);
        $trust->revoke($request->user(), $device);
        if ($device === $request->session()->get('mfa_device_id')) {
            $request->session()->forget(['mfa_version', 'mfa_device_id', 'mfa_fresh_at']);
            $trust->forgetCookie();
        }

        return redirect()->route('security.mfa')->with('status', 'Browser access revoked. That browser must verify an email code again.');
    }

    public function revokeDevices(Request $request, MfaService $mfa, MfaDeviceTrust $trust)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $this->confirmDeviceRevocation($request, $mfa);
        $trust->revokeAll($request->user());
        $request->session()->forget(['mfa_version', 'mfa_device_id', 'mfa_fresh_at']);
        $trust->forgetCookie();

        return redirect()->route('security.mfa')->with('status', 'All browser access revoked. Verify your email code before continuing.');
    }

    public function revokeUserDevices(Request $request, User $user, MfaService $mfa, MfaDeviceTrust $trust)
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_if($request->user()->is($user), 422, 'Manage your own browsers from your email verification settings.');
        $this->confirmDeviceRevocation($request, $mfa);
        $trust->revokeAll($user);

        return back()->with('success', 'Browser access revoked. The account holder must verify an email code again.');
    }

    private function confirmDeviceRevocation(Request $request, MfaService $mfa): void
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'confirmed' => ['required', 'accepted'], 'code' => ['required', 'digits:6']]);
        if (! $mfa->verify($request->user(), $data['code'])) {
            throw ValidationException::withMessages(['code' => 'Enter a new six-digit email code to revoke browser access.']);
        }
    }
}
