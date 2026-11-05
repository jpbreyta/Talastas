<?php

namespace App\Services;

use App\Models\AccountRecovery;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountAccess
{
    public static function invalidate(User $user): void
    {
        $user->security_version++;
        $user->setRememberToken(Str::random(60));
        $user->save();
        app(MfaDeviceTrust::class)->revokeAll($user);
        $user->tokens()->delete();
        // Security-version middleware also invalidates file/Redis sessions on their next request.
        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        AccountRecovery::where('user_id', $user->id)->update(['consumed_at' => now(), 'code_hash' => null, 'grant_hash' => null]);
        \App\Models\EmailVerificationCode::where('user_id', $user->id)->update(['consumed_at' => now(), 'code_hash' => null]);
    }

    public static function lockAdministrators(): void
    {
        User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
    }

    public static function protectLastAdmin(User $user): void
    {
        if ($user->isAdmin() && $user->is_active && User::where('role', 'admin')->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages(['user' => 'The last active System Admin cannot be disabled or demoted.']);
        }
    }
}
