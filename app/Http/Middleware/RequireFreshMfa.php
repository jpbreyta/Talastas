<?php

namespace App\Http\Middleware;

use App\Services\MfaDeviceTrust;
use Closure;
use Illuminate\Http\Request;

class RequireFreshMfa
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()?->isAdmin() || app(MfaDeviceTrust::class)->fresh($request)) {
            return $next($request);
        }
        $message = 'This action was not submitted. Verify your email code, return to the page, and submit it again. One verification authorizes protected actions for 24 hours; trusted-browser expiry is separate.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'code' => 'fresh_mfa_required', 'verification_url' => route('security.mfa')], 403);
        }

        $request->session()->forget('pending_mfa_login');
        app(\App\Services\AuthenticationDestination::class)->remember($request);
        return redirect()->route('security.mfa')->with('status', $message);
    }
}
