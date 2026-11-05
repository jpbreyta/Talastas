<?php

use App\Http\Middleware\EnsureAccountSecure;
use App\Http\Middleware\EnsureTermsAccepted;
use App\Http\Middleware\RBAC;
use App\Http\Middleware\RequireFreshMfa;
use App\Http\Middleware\TrackUserActivity;
use App\Services\SecurityAudit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->throttleApi('60,1,api:');
        $middleware->appendToGroup('web', TrackUserActivity::class);
        $middleware->appendToGroup('api', TrackUserActivity::class);
        // Register custom middleware aliases (rbac in addition to defaults)
        $middleware->alias([
            'rbac' => RBAC::class,
            'account.secure' => EnsureAccountSecure::class,
            'terms.accepted' => EnsureTermsAccepted::class,
            'mfa.fresh' => RequireFreshMfa::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if (! $request->routeIs('security.*', 'login.submit', 'api.v1.auth.login', 'admin.invitations.*', 'admin.users.*', 'legal.accept', 'api.v1.legal.accept', 'profile.password.update')) {
                return null;
            }
            SecurityAudit::record('security.rate_limited', $request->user()?->id, 'denied');
            $headers = $exception->getHeaders();
            $retryAfter = max(1, (int) ($headers['Retry-After'] ?? 60));
            $message = 'Too many attempts. Please wait '.$retryAfter.' seconds before trying again.';
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => $message, 'retry_after' => $retryAfter], 429, $headers)
                    ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
            }

            $returnUrl = route('login');
            $returnLabel = 'Return to sign in';
            if ($request->routeIs('security.invitation*')) {
                $returnUrl = route('security.invitation', ['id' => $request->route('id')]);
                $returnLabel = 'Return to your invitation';
            } elseif ($request->routeIs('security.recovery*')) {
                $returnUrl = route('security.recovery');
                $returnLabel = 'Return to password recovery';
            } elseif ($request->routeIs('security.mfa*')) {
                $returnUrl = route('security.mfa');
                $returnLabel = 'Return to email verification';
            } elseif ($request->routeIs('admin.*')) {
                $returnUrl = route('admin.invitations.index');
                $returnLabel = 'Return to account management';
            } elseif ($request->routeIs('legal.accept')) {
                $returnUrl = route('legal.review');
                $returnLabel = 'Return to Terms acknowledgement';
            } elseif ($request->routeIs('profile.password.update')) {
                $returnUrl = route('profile.account');
                $returnLabel = 'Return to your profile';
            }

            return response()->view('errors.429', compact('retryAfter', 'returnUrl', 'returnLabel'), 429, $headers)
                ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
        });
        $exceptions->dontFlash(['password', 'password_confirmation', 'current_password', 'token', 'code', 'email_code', 'authenticator_code', 'grant']);
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $exception): bool => $request->is('api/*') || $request->expectsJson()
            || in_array('text/event-stream', $request->getAcceptableContentTypes(), true)
        );
    })->create();
