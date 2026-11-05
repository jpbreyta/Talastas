<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RBAC
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        $allowed = match ($role) {
            'admin' => ['admin'],
            'editor' => ['admin', 'editor'],
            'viewer' => ['admin', 'editor', 'viewer'],
            'planner' => ['admin', 'viewer'],
            default => [],
        };
        abort_unless(in_array($user->role, $allowed, true), 403);

        return $next($request);
    }
}
