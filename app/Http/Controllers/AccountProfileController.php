<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\ValidPasswordInput;
use App\Services\AccountAccess;
use App\Services\NotificationService;
use App\Services\PasswordPolicy;
use App\Services\SecurityAudit;
use App\Services\SecurityMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.account', [
            'user' => $request->user(),
        ]);
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
