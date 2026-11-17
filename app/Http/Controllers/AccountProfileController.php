<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\ValidPasswordInput;
use App\Services\AccountAccess;
use App\Services\NotificationService;
use App\Services\PasswordPolicy;
use App\Services\ProfileEmailChange;
use App\Services\SecurityAudit;
use App\Services\SecurityMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountProfileController extends Controller
{
    public function show(Request $request, ProfileEmailChange $emailChange): View
    {
        return view('profile.account', [
            'user' => $request->user(),
            'emailChangeSupported' => $emailChange->supported(),
            'pendingEmailChange' => $emailChange->supported() ? $emailChange->pending($request) : null,
        ]);
    }

    private function profileRules(array $rules): array
    {
        return $rules + array_fill_keys(['id', 'user_id', 'role', 'permissions', 'institution_ids', 'barangay_ids',
            'is_active', 'security_version', 'email_verified_at', 'email_mfa_confirmed_at', 'password', 'password_confirmation',
            'remember_token', 'mfa_secret', 'mfa_recovery_codes', 'api_key'], ['prohibited']);
    }

    private function currentPasswordRules(): array
    {
        return ['required', 'string', 'max:4096', new ValidPasswordInput, 'current_password'];
    }

    public function updateName(Request $request): RedirectResponse
    {
        if (is_string($request->input('name'))) {
            $request->merge(['name' => trim($request->input('name'))]);
        }
        $validated = $request->validateWithBag('profileName', $this->profileRules([
            'name' => ['required', 'string', 'max:255'], 'current_password' => $this->currentPasswordRules(), 'email' => ['prohibited'],
        ]));
        DB::transaction(function () use ($request, $validated) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($user->is_active && $user->security_version === $request->user()->security_version, 409, 'Your account changed. Sign in again.');
            if (! Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'The password is incorrect.'])->errorBag('profileName');
            }
            $user->name = $validated['name'];
            $user->save();
            SecurityAudit::record('profile.name_updated', $user->id);
        });

        return redirect()->route('profile.account')->with('profile_status', 'Personal information updated.');
    }

    public function requestEmailChange(Request $request, ProfileEmailChange $emailChange): RedirectResponse
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }
        $validated = $request->validateWithBag('profileEmail', $this->profileRules([
            'email' => ['required', 'string', 'email', 'max:255'], 'current_password' => $this->currentPasswordRules(), 'name' => ['prohibited'],
        ]));
        try {
            $emailChange->request($request, $validated['email'], $validated['current_password']);
        } catch (ValidationException $error) {
            throw $error->errorBag('profileEmail');
        }

        return redirect()->route('profile.account')->with('profile_status', 'Verification sent to your proposed email. Your current email remains active until you confirm.');
    }

    public function confirmEmailChange(Request $request, ProfileEmailChange $emailChange): RedirectResponse
    {
        $validated = $request->validateWithBag('profileEmailConfirm', $this->profileRules([
            'code' => ['required', 'string', 'regex:/\A[0-9]{6}\z/D'], 'current_password' => $this->currentPasswordRules(),
            'name' => ['prohibited'], 'email' => ['prohibited'],
        ]));
        try {
            $noticeQueued = $emailChange->confirm($request, $validated['code'], $validated['current_password']);
        } catch (ValidationException $error) {
            throw $error->errorBag('profileEmailConfirm');
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Email changed. Sign in using your new email address.'
            .($noticeQueued ? '' : ' The previous-address notification could not be queued; contact your System Administrator.'));
    }

    public function updatePassword(
        Request $request,
        NotificationService $notificationService,
    ): RedirectResponse {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'max:4096', new ValidPasswordInput, 'current_password'],
            'password' => PasswordPolicy::rules(),
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $validated) {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            $locked->password = $validated['password'];
            AccountAccess::invalidate($locked);
            SecurityAudit::record('password.changed', $locked->id);
            if (SecurityMail::available()) {
                SecurityMail::queue('password_changed', (string) $locked->id, $locked->security_version);
            } else {
                SecurityAudit::record('notification.unavailable', $locked->id, 'failed');
            }
        });
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $notificationService->accountSecurityChanged(
            $user,
            'Your password was changed successfully. Existing API/mobile access tokens were revoked.',
        );

        return redirect()->route('login')->with('status', 'Password changed. Sign in again.');
    }
}
