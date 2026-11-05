<?php

namespace App\Http\Middleware;

use App\Services\LegalPolicies;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        // This alias is always placed after authentication. Logout/policy routes are separate.
        if (!config('legal.enforce_terms') || $request->routeIs('logout')) {
            return $next($request);
        }
        $policies = app(LegalPolicies::class);
        if ($policies->accepted($request->user())) {
            return $next($request);
        }
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'code' => 'terms_acceptance_required',
                'message' => 'Review and accept the current Terms before accessing protected features.',
                'terms' => $policies->status($request->user()),
            ], 403)->header('Cache-Control', 'private, no-store');
        }

        app(\App\Services\AuthenticationDestination::class)->remember($request);
        return redirect()->route('legal.review');
    }
}
