<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\ValidPasswordInput;
use App\Services\LegalPolicies;
use App\Services\MfaDeviceTrust;
use App\Services\MfaService;
use App\Services\SecurityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:4096', new ValidPasswordInput],
        ]);

        $credentials = $this->activeCredentials($credentials);
        $request->validate(['remember' => ['sometimes', 'boolean']]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $request->session()->put('security_version', (int) $request->user()->security_version);
            $request->session()->forget(['mfa_version', 'mfa_device_id', 'mfa_fresh_at', 'mfa_enrollment', 'mfa_challenge_reason', 'pending_mfa_login']);
            if ($request->user()->isAdmin()) {
                if (! app(MfaDeviceTrust::class)->resume($request->user(), $request)) {
                    app(\App\Services\PendingMfaLogin::class)->begin($request);
                    return redirect()->route('security.mfa');
                }
            }

            if (app(LegalPolicies::class)->enforcementActive()
                && ! app(LegalPolicies::class)->accepted($request->user())) {
                return redirect()->route('legal.review');
            }

            return redirect(app(\App\Services\AuthenticationDestination::class)->pull($request));
        }

        app(MfaDeviceTrust::class)->failedPassword($request, $credentials['email']);
        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function apiLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:4096', new ValidPasswordInput],
        ]);

        $credentials = $this->activeCredentials($credentials);
        if (! Auth::attempt($credentials)) {
            app(MfaDeviceTrust::class)->failedPassword($request, $credentials['email']);

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        /** @var User $user */
        $user = $request->user();

        if ($user->isAdmin()) {
            $data = $request->validate(['email_code' => ['nullable', 'digits:6'], 'challenge_id' => ['required_with:email_code', 'nullable', 'uuid'], 'resend_code' => ['sometimes', 'boolean']]);
            if (empty($data['email_code'])) {
                $challenge = app(MfaService::class)->send($user, $request->boolean('resend_code'));
                Auth::logout();

                return response()->json(['message' => 'Enter the six-digit code sent to your registered email.', 'code' => 'email_otp_required',
                    'challenge_id' => $challenge->id, 'expires_at' => $challenge->expires_at->toIso8601String(),
                    'resend_after' => max(0, $challenge->requested_at->timestamp + 60 - now()->timestamp)], 403)->header('Cache-Control', 'no-store');
            }
            if (! app(MfaService::class)->verify($user, $data['email_code'], $data['challenge_id'])) {
                Auth::logout();

                return response()->json(['message' => 'The email code is incorrect, expired, or already used.', 'code' => 'email_otp_invalid'], 403)->header('Cache-Control', 'no-store');
            }
        }
        // Remove old tokens so only one active token exists per user
        $user->tokens()->delete();

        // Create a new Sanctum token
        $token = $user->createToken('flutter', $user->isAdmin() ? ['*', 'email-otp'] : ['*'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'terms' => app(LegalPolicies::class)->loginStatus($user),
                'token' => $token,
                'user' => $user,
            ],
        ]);
    }

    private function activeCredentials(array $credentials): array
    {
        $email = strtolower(trim($credentials['email']));
        $key = 'account-login:'.hash_hmac('sha256', $email, (string) config('app.key'));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            SecurityAudit::record('login.throttled', null, 'denied');
            throw ValidationException::withMessages(['email' => 'Too many sign-in attempts. Please wait a minute.']);
        }
        RateLimiter::hit($key, 60);
        $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
        $credentials['email'] = $user?->email ?? $email;
        $credentials['is_active'] = true;

        return $credentials;
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }

    public function apiLogout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
