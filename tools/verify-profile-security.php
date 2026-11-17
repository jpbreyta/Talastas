<?php

// Test existing profile behavior only. No new endpoints, live accounts, or emails.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error): void { fwrite(STDERR, $error->getMessage().PHP_EOL); exit(1); });
config(['database.default' => 'sqlite', 'database.connections' => ['sqlite' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
]], 'cache.default' => 'array', 'session.driver' => 'array', 'mail.default' => 'array', 'account-security.mail_enabled' => false]);
Illuminate\Support\Facades\DB::purge();

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

if (DB::connection()->getDatabaseName() !== ':memory:') throw new RuntimeException('Unsafe fixture database');
Illuminate\Support\Facades\Queue::fake();
Illuminate\Support\Facades\Mail::fake();
Illuminate\Support\Facades\Http::preventStrayRequests();
$checks = 0;
function profileCheck(bool $ok, string $message): void { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
Schema::create('terms_acceptances', fn (Blueprint $table) => $table->id());
Schema::create('institutions', function (Blueprint $table) { $table->id(); $table->string('name'); });
foreach (['0001_01_01_000000_create_users_table.php', '2026_07_09_005957_create_personal_access_tokens_table.php',
    '2026_09_15_000001_create_institution_user_table.php', '2026_10_23_000001_add_account_security.php',
    '2026_10_23_000002_add_mfa_trusted_devices.php', '2026_11_01_000001_add_email_verification_codes.php',
] as $migration) (require __DIR__.'/../database/migrations/'.$migration)->up();
DB::table('institutions')->insert(['id' => 1, 'name' => 'Fixture institution']);
$users = collect(['admin', 'editor', 'viewer'])->map(fn ($role) => User::create([
    'name' => "Fixture $role", 'email' => "$role@example.invalid", 'role' => $role, 'password' => 'Original fixture passphrase',
]));
$users[1]->institutions()->sync([1]);
$notifications = Mockery::mock(App\Services\NotificationService::class);
$notifications->shouldReceive('accountSecurityChanged')->times(3)->andReturnNull();
$controller = new App\Http\Controllers\AccountProfileController;
function profileRequest(User $user, array $input = []): Request {
    global $app;
    Auth::setUser($user);
    $request = Request::create('/profile/password', 'PUT', $input);
    $request->setUserResolver(fn () => $user); $request->setLaravelSession(app('session')->driver());
    $request->session()->start(); $request->session()->regenerateToken();
    $app->instance('request', $request); return $request;
}
$route = app('router')->getRoutes()->getByName('profile.password.update');
foreach (['auth:web', 'auth.session', 'account.secure', 'terms.accepted', 'mfa.fresh', 'throttle:5,1,profile-password:'] as $middleware) {
    profileCheck(in_array($middleware, $route->gatherMiddleware()), 'Password route retains middleware: '.$middleware);
}
profileCheck($route->uri() === 'profile/password' && $route->parameterNames() === [], 'Profile password route cannot target another user by URL');
foreach ($users as $index => $user) {
    $original = $user->fresh();
    $other = $users[($index + 1) % 3]->fresh();
    $otherHash = $other->password;
    $input = ['current_password' => 'Original fixture passphrase', 'password' => 'New fixture passphrase '.$index,
        'password_confirmation' => 'New fixture passphrase '.$index,
        'user_id' => $other->id, 'id' => $other->id, 'role' => 'admin', 'is_active' => false,
        'institution_ids' => [999], 'name' => 'Injected name', 'email' => 'injected@example.invalid'];
    foreach ([['current_password' => 'wrong-password'], ['password_confirmation' => 'different'], ['password' => 'short', 'password_confirmation' => 'short']] as $invalid) {
        try { $controller->updatePassword(profileRequest($user, array_replace($input, $invalid)), $notifications); throw new RuntimeException('Invalid password change accepted'); }
        catch (Illuminate\Validation\ValidationException $error) {
            profileCheck($user->fresh()->password === $original->password, 'Invalid password requests cannot modify the account');
        }
    }
    $user->createToken('fixture-token');
    $request = profileRequest($user, $input);
    $sessionId = $request->session()->getId(); $csrf = $request->session()->token();
    $response = $controller->updatePassword($request, $notifications);
    $fresh = $user->fresh();
    profileCheck(Hash::check($input['password'], $fresh->password) && $fresh->password !== $input['password'], 'Existing password change stores a valid hash');
    profileCheck($other->fresh()->password === $otherHash, 'Submitted user identifiers cannot change another account password');
    profileCheck($fresh->role === $original->role && $fresh->is_active === $original->is_active && $fresh->name === $original->name && $fresh->email === $original->email, 'Privileged/basic fields in the password request are not mass assigned');
    profileCheck($fresh->institutions()->pluck('institutions.id')->all() === ($user->isEditor() ? [1] : []), 'Profile password change cannot modify assignments');
    profileCheck($fresh->security_version === $original->security_version + 1 && $fresh->tokens()->count() === 0, 'Security version increases and existing API tokens are revoked');
    profileCheck(!Auth::check() && $request->session()->getId() !== $sessionId && $request->session()->token() !== $csrf, 'Password change logs out and invalidates session/CSRF state');
    profileCheck(DB::table('security_events')->where('event', 'password.changed')->where('actor_id', $user->id)->where('target_id', $user->id)->exists(), 'Existing audit records identify the authenticated account');
    profileCheck($response->isRedirect(route('login')), 'Existing password flow requires sign-in again');
}
Mockery::close();
echo "PASS: $checks existing profile/password security checks (isolated SQLite; no live accounts or messages).\n";
