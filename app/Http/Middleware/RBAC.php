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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($role === 'admin' && $user->role !== 'admin') {
            abort(403, 'Unauthorized: Admin access required');
        }

        if ($role === 'editor' && !in_array($user->role, ['admin', 'editor'])) {
            abort(403, 'Unauthorized: Editor access required');
        }

        return $next($request);
    }
}
