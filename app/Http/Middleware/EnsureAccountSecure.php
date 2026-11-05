<?php

namespace App\Http\Middleware;

use App\Services\MfaDeviceTrust;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAccountSecure
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! $user->is_active) {
            if ($request->hasSession()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            abort(403, 'This account is not available. Contact the System Admin.');
        }
        if ($request->hasSession() && (int) $request->session()->get('security_version', 0) !== (int) $user->security_version) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Please sign in again after your account security changed.');
        }
        if ($user->isAdmin() && ! $request->routeIs('security.mfa*', 'logout', 'api.v1.auth.logout')) {
            $verified = $request->hasSession() ? app(MfaDeviceTrust::class)->authorized($user, $request)
             : ($user->email_mfa_confirmed_at && ! $user->mfa_secret && in_array('email-otp', $user->currentAccessToken()?->abilities ?? [], true));
            if (! $verified) {
                if ($request->is('api/*') || $request->expectsJson()) {
                    return response()->json(['message' => 'Verify the code sent to your registered email before continuing.', 'code' => 'email_otp_required'], 403);
                }

                app(\App\Services\AuthenticationDestination::class)->remember($request);
                return redirect()->route('security.mfa');
            }
        }

        $response = $next($request);
        if ($user->isAdmin() && $request->hasSession() && $response->getStatusCode() < 400) {
            app(MfaDeviceTrust::class)->touch($user, $request);
        }

        return $response;
    }
}
