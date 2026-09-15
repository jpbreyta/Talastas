<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
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
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers(),
            ],
        ]);

        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        // Revoke mobile/API tokens after a password change where Sanctum is used.
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        $notificationService->accountSecurityChanged(
            $user,
            'Your password was changed successfully. Existing API/mobile access tokens were revoked.',
        );

        return back()->with('status', 'password-updated');
    }
}
