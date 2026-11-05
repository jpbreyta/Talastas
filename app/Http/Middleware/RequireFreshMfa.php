<?php

namespace App\Http\Middleware;

use App\Services\MfaDeviceTrust;
use Closure;
use Illuminate\Http\Request;

class RequireFreshMfa
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('admin.users.update') && ! config('account-security.role_updates_require_otp', false)) {
            return $next($request);
        }

        if (! $request->user()?->isAdmin() || app(MfaDeviceTrust::class)->fresh($request)) {
            return $next($request);
        }
        // This operation independently requires a new factor in its controller.
        if ($request->routeIs('admin.users.reset') && $request->route('user')?->isAdmin()) {
            return $next($request);
        }
        $message = 'Verify your email code again, then return and retry this security action. Browser trust does not authorize sensitive changes.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'code' => 'fresh_mfa_required', 'verification_url' => route('security.mfa')], 403);
        }

        $request->session()->forget('pending_mfa_login');
        app(\App\Services\AuthenticationDestination::class)->remember($request);
        return redirect()->route('security.mfa')->with('status', $message);
    }
}
