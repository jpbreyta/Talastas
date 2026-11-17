<?php

// Isolated security regression fixture. Never uses live accounts, cache entries, or email.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->instance('env', 'testing');
set_exception_handler(function (Throwable $error): void { fwrite(STDERR, $error.PHP_EOL); exit(1); });

use App\Models\User;
use App\Services\ProfileEmailChange;
use Illuminate\Support\Facades\{Auth, Cache, DB, Http, Queue, RateLimiter, Schema};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

$child = ($argv[1] ?? '') === '--confirm-child';
$fixture = $child ? json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR) : null;
$root = $fixture['root'] ?? sys_get_temp_dir().'/talastas-profile-'.bin2hex(random_bytes(8));
if (!$child) { mkdir($root); touch($root.'/fixture.sqlite'); }
if (!str_starts_with(str_replace('\\', '/', $root), str_replace('\\', '/', sys_get_temp_dir()).'/talastas-profile-')) throw new RuntimeException('Unsafe fixture');
if (!$child) register_shutdown_function(function () use ($root) {
    if (str_starts_with(str_replace('\\', '/', $root), str_replace('\\', '/', sys_get_temp_dir()).'/talastas-profile-')) {
        (new Illuminate\Filesystem\Filesystem)->deleteDirectory($root);
    }
});
config(['database.default' => 'sqlite', 'database.connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => $root.'/fixture.sqlite', 'prefix' => '', 'foreign_key_constraints' => true]],
    'cache.default' => 'profile_fixture', 'cache.stores.profile_fixture' => ['driver' => 'file', 'path' => $root, 'lock_path' => $root], 'session.driver' => 'array']);
DB::purge();
app()->singleton('cache.rate_limiter', fn () => Cache::store());
RateLimiter::swap(new Illuminate\Cache\RateLimiter(Cache::store()));
Queue::fake();
Http::preventStrayRequests();
$messages = [];
Http::fake(function ($request) use (&$messages) { $messages[] = $request->data(); return Http::response(['id' => 'fixture'], 200); });
if ($child) {
    $user = User::findOrFail($fixture['user']);
    $session = new Illuminate\Session\Store('fixture', new Illuminate\Session\ArraySessionHandler(120), $fixture['session']);
    $session->start();
    $request = fixtureRequest($user, [], $session);
    touch($root.'/ready-'.$fixture['worker']);
    $deadline = microtime(true) + 10;
    while (!file_exists($root.'/start') && microtime(true) < $deadline) usleep(10000);
    try { (new ProfileEmailChange)->confirm($request, $fixture['code'], 'Original fixture passphrase'); echo 'confirmed'; }
    catch (ValidationException|Symfony\Component\HttpKernel\Exception\HttpException $error) { echo 'rejected'; }
    exit;
}
Schema::create('terms_acceptances', fn (Blueprint $table) => $table->id());
Schema::create('institutions', function (Blueprint $table) { $table->id(); $table->string('name'); });
Schema::create('user_activity_sessions', function (Blueprint $table) {
    $table->id(); $table->unsignedBigInteger('user_id'); $table->string('user_name'); $table->string('session_key')->unique();
    $table->string('channel'); $table->timestamp('opened_at'); $table->timestamp('last_seen_at'); $table->timestamp('closed_at')->nullable();
});
foreach (['0001_01_01_000000_create_users_table.php', '2026_07_09_005957_create_personal_access_tokens_table.php', '2026_09_15_000001_create_institution_user_table.php', '2026_10_23_000001_add_account_security.php', '2026_10_23_000002_add_mfa_trusted_devices.php', '2026_11_01_000001_add_email_verification_codes.php'] as $migration) (require __DIR__.'/../database/migrations/'.$migration)->up();
DB::table('institutions')->insert(['id' => 1, 'name' => 'Fixture']);
$checks = 0;
function checkProfile(bool $ok, string $message): void { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
function rejected(callable $work, string $message): void {
    try { $work(); } catch (ValidationException|Symfony\Component\HttpKernel\Exception\HttpException $error) { checkProfile(true, $message); return; }
    throw new RuntimeException($message);
}
function fixtureUser(string $role = 'editor'): User {
    return User::create(['name' => 'Fixture', 'email' => bin2hex(random_bytes(8)).'@example.invalid', 'role' => $role, 'password' => 'Original fixture passphrase']);
}
function fixtureRequest(User $user, array $input = [], $session = null): Request {
    global $app;
    Auth::setUser($user);
    $request = Request::create('/profile', 'POST', $input);
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession($session ?? new Illuminate\Session\Store('fixture', new Illuminate\Session\ArraySessionHandler(120)));
    if (!$request->session()->isStarted()) $request->session()->start();
    $app->instance('request', $request);
    return $request;
}
function issue(ProfileEmailChange $service, User $user, string $email): array {
    global $messages;
    $request = fixtureRequest($user);
    $service->request($request, $email, 'Original fixture passphrase');
    preg_match('/\b[0-9]{6}\b/', end($messages)['html'], $match);
    checkProfile(isset($match[0]), 'Verification mail contains a code');
    return [$request, $match[0]];
}
$service = new ProfileEmailChange;
$controller = new App\Http\Controllers\AccountProfileController;
checkProfile($service->supported(), 'Real isolated file cache supports locking');
foreach (['admin', 'editor', 'viewer'] as $role) {
    $user = fixtureUser($role);
    $user->institutions()->sync([1]);
    $controller->updateName(fixtureRequest($user, ['name' => ' Updated name ', 'current_password' => 'Original fixture passphrase']));
    checkProfile($user->fresh()->name === 'Updated name', 'All authenticated roles can update their own name');
    checkProfile($user->fresh()->role === $role && $user->institutions()->count() === 1, 'Roles and assignments unchanged');
    rejected(fn () => $controller->updateName(fixtureRequest($user, ['name' => 'Denied', 'current_password' => 'wrong'])), 'Incorrect password rejected');
    foreach (['role' => 'admin', 'user_id' => 999, 'institution_ids' => [999]] as $field => $value) {
        rejected(fn () => $controller->updateName(fixtureRequest($user, ['name' => 'Denied', 'current_password' => 'Original fixture passphrase', $field => $value])), 'Privileged or target fields rejected');
    }
    checkProfile(DB::table('security_events')->where('event', 'profile.name_updated')->where('actor_id', $user->id)->exists(), 'Name change audited');
}
$user = fixtureUser('admin');
$oldEmail = $user->email;
[$request, $code] = issue($service, $user, 'new@example.invalid');
checkProfile(end($messages)['to'] === ['new@example.invalid'] && $user->fresh()->email === $oldEmail, 'Only proposed address receives code; original remains active');
checkProfile(array_keys($service->pending($request)) === ['email', 'expires_at'], 'Pending state exposes no verification secret');
$saved = Cache::get('profile-email-change:'.$user->id);
checkProfile(!str_contains($saved, 'new@example.invalid') && !str_contains($saved, $code), 'Cached challenge encrypted');
rejected(fn () => $service->confirm(fixtureRequest($user), $code, 'Original fixture passphrase'), 'Code cannot transfer between sessions');
rejected(fn () => $service->confirm($request, $code, 'wrong'), 'Password rechecked under database lock');
rejected(fn () => $service->request($request, 'another@example.invalid', 'Original fixture passphrase'), 'Resend cooldown enforced');
$user->createToken('fixture');
DB::table('mfa_trusted_devices')->insert(['id' => (string) Illuminate\Support\Str::uuid(), 'user_id' => $user->id,
    'security_version' => $user->security_version, 'token_hash' => hash('sha256', 'fixture'), 'session_hash' => hash('sha256', 'session'),
    'browser_hash' => hash('sha256', 'browser'), 'network_hash' => hash('sha256', 'network'), 'last_verified_at' => now(),
    'last_activity_at' => now(), 'expires_at' => now()->addDay(), 'trusted_at' => now()]);
$version = $user->security_version;
$sessionId = $request->session()->getId();
$response = $controller->confirmEmailChange(fixtureRequest($user, ['code' => $code, 'current_password' => 'Original fixture passphrase'], $request->session()), $service);
$fresh = $user->fresh();
checkProfile($fresh->email === 'new@example.invalid' && $fresh->email_verified_at !== null, 'Confirmation updates and verifies email');
checkProfile($fresh->security_version === $version + 1 && $fresh->tokens()->count() === 0, 'All previous session versions and API tokens revoked');
checkProfile(DB::table('mfa_trusted_devices')->where('user_id', $user->id)->whereNull('revoked_at')->count() === 0
    && DB::table('mfa_trusted_devices')->where('user_id', $user->id)->whereNotNull('token_hash')->count() === 0, 'Trusted browser tokens revoked');
checkProfile($fresh->email_mfa_confirmed_at === null && $fresh->mfa_confirmed_at === null && $fresh->mfa_verified_at === null, 'Administrator must verify MFA again');
checkProfile(!Auth::check() && $request->session()->getId() !== $sessionId && $response->isRedirect(route('login')), 'Confirmation logs out and rotates session');
Queue::assertPushed(App\Jobs\SendProfileEmailChangedNotice::class, fn ($job) => $job->previousEmail === $oldEmail);
checkProfile(true, 'Previous-address notification queued');
(new App\Jobs\SendProfileEmailChangedNotice($oldEmail, $user->id))->handle();
checkProfile(end($messages)['to'] === [$oldEmail], 'Change notice targets previous address');
$replaySession = new Illuminate\Session\Store('fixture', new Illuminate\Session\ArraySessionHandler(120), $sessionId);
$replaySession->start();
$request = fixtureRequest($user, [], $replaySession);
rejected(fn () => $service->confirm($request, $code, 'Original fixture passphrase'), 'Consumed code cannot be reused');
Cache::put('profile-email-change:'.$user->id, $saved, 300);
rejected(fn () => $service->confirm($request, $code, 'Original fixture passphrase'), 'Database version fence rejects replay of restored challenge');
checkProfile(DB::table('security_events')->where('event', 'profile.email_changed')->where('target_id', $user->id)->count() === 1, 'Only one successful confirmation and audit event');
$oldSession = fixtureRequest($fresh);
$oldSession->session()->put('security_version', $version);
$response = (new App\Http\Middleware\EnsureAccountSecure)->handle($oldSession, fn () => response('unexpected'));
checkProfile($response->isRedirect(route('login')), 'Actual security middleware rejects previous authenticated session version');

$duplicate = fixtureUser();
rejected(fn () => $service->request(fixtureRequest(fixtureUser()), $duplicate->email, 'Original fixture passphrase'), 'Duplicate email rejected at request');
$user = fixtureUser();
[$request, $code] = issue($service, $user, 'race@example.invalid');
$duplicate->email = 'race@example.invalid'; $duplicate->save();
rejected(fn () => $service->confirm($request, $code, 'Original fixture passphrase'), 'Uniqueness rechecked at confirmation');
$user = fixtureUser();
[$request, $code] = issue($service, $user, 'expiry@example.invalid');
Illuminate\Support\Carbon::setTestNow(now()->addMinutes(6));
rejected(fn () => $service->confirm($request, $code, 'Original fixture passphrase'), 'Expired code rejected');
Illuminate\Support\Carbon::setTestNow();
$user = fixtureUser();
[$request, $code] = issue($service, $user, 'attempts@example.invalid');
$wrong = $code === '000000' ? '999999' : '000000';
for ($i = 0; $i < 5; $i++) rejected(fn () => $service->confirm($request, $wrong, 'Original fixture passphrase'), 'Wrong code rejected');
rejected(fn () => $service->confirm($request, $code, 'Original fixture passphrase'), 'Five wrong attempts consume challenge');
foreach (['profile.name.update', 'profile.email.request', 'profile.email.confirm'] as $name) {
    $route = app('router')->getRoutes()->getByName($name);
    foreach (['auth:web', 'auth.session', 'account.secure', 'mfa.fresh', 'web'] as $middleware) checkProfile(in_array($middleware, $route->gatherMiddleware()), 'Profile route retains '.$middleware);
    checkProfile($route->parameterNames() === [], 'No client-selected user URL parameter');
}
$user = fixtureUser();
[$request, $code] = issue($service, $user, 'concurrent@example.invalid');
$workers = [];
for ($i = 0; $i < 2; $i++) {
    $process = proc_open([PHP_BINARY, __FILE__, '--confirm-child'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, base_path());
    if (!is_resource($process)) throw new RuntimeException('Cannot launch concurrent fixture');
    fwrite($pipes[0], json_encode(['root' => $root, 'user' => $user->id, 'session' => $request->session()->getId(), 'code' => $code, 'worker' => $i]));
    fclose($pipes[0]);
    $workers[] = [$process, $pipes];
}
$deadline = microtime(true) + 10;
while ((!file_exists($root.'/ready-0') || !file_exists($root.'/ready-1')) && microtime(true) < $deadline) usleep(10000);
checkProfile(file_exists($root.'/ready-0') && file_exists($root.'/ready-1'), 'Both confirmation processes ready with same account version');
touch($root.'/start');
$outcomes = [];
foreach ($workers as [$process, $pipes]) {
    $outcomes[] = stream_get_contents($pipes[1]); $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    checkProfile(proc_close($process) === 0, 'Concurrent worker exits cleanly: '.$errors);
}
sort($outcomes);
checkProfile($outcomes === ['confirmed', 'rejected'], 'Exactly one simultaneous confirmation succeeds');
checkProfile(DB::table('security_events')->where('event', 'profile.email_changed')->where('target_id', $user->id)->count() === 1, 'Concurrent confirmation produces one change and audit event');
$user = fixtureUser();
$oldEmail = $user->email;
Http::swap(new Illuminate\Http\Client\Factory);
Http::preventStrayRequests();
Http::fake(fn () => Http::response(['error' => 'fixture transport rejection'], 503));
rejected(fn () => $service->request(fixtureRequest($user), 'failed@example.invalid', 'Original fixture passphrase'), 'Transport failure reported safely');
checkProfile($user->fresh()->email === $oldEmail && !Cache::has('profile-email-change:'.$user->id), 'Failed delivery leaves original email and no usable challenge');
$mfa = Mockery::mock(App\Services\MfaDeviceTrust::class)->makePartial();
$mfa->shouldReceive('fresh')->andReturn(false);
$mfa->shouldReceive('authorized')->andReturn(true);
$mfa->shouldReceive('touch')->andReturnNull();
app()->instance(App\Services\MfaDeviceTrust::class, $mfa);
$request = fixtureRequest(fixtureUser('admin')); $request->headers->set('Accept', 'application/json');
$response = (new App\Http\Middleware\RequireFreshMfa)->handle($request, fn () => response('unexpected'));
checkProfile($response->getStatusCode() === 403, 'Actual middleware rejects administrator without fresh MFA');
class ProfileHttpFixture
{
    use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
    use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
    use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
    public function __construct(public $app) {}
}
config(['legal.enforce_terms' => false]);
$http = new ProfileHttpFixture($app);
Auth::logout();
checkProfile($http->putJson('/profile/name', ['name' => 'Denied'])->getStatusCode() === 401, 'Unauthenticated HTTP profile update rejected');
$admin = fixtureUser('admin');
$http->flushSession()->actingAs($admin, 'web')->withSession(['security_version' => $admin->security_version]);
checkProfile($http->putJson('/profile/name', ['name' => 'Denied', 'current_password' => 'Original fixture passphrase'])->getStatusCode() === 403, 'HTTP profile route requires administrator fresh MFA');
$editor = fixtureUser();
$http->flushSession()->actingAs($editor, 'web')->withSession(['security_version' => $editor->security_version]);
$response = $http->putJson('/profile/name', ['name' => 'HTTP updated', 'current_password' => 'Original fixture passphrase']);
checkProfile($response->getStatusCode() === 302 && $editor->fresh()->name === 'HTTP updated', 'Authenticated HTTP name update succeeds: '.$response->getStatusCode().' '.$response->getContent());
checkProfile($http->putJson('/profile/name', ['name' => 'Denied', 'current_password' => 'Original fixture passphrase', 'user_id' => $admin->id])->getStatusCode() === 422 && $admin->fresh()->name === 'Fixture', 'HTTP cannot target another account');
checkProfile($http->putJson('/profile/name', ['name' => 'Denied', 'current_password' => 'Original fixture passphrase', 'role' => 'admin'])->getStatusCode() === 422 && $editor->fresh()->role === 'editor', 'HTTP cannot promote self');
// Compile the actual changed templates, including all branches, without a live database.
foreach (['profile/account', 'emails/account-security'] as $template) {
    $compiled = app('blade.compiler')->compileString(file_get_contents(resource_path('views/'.$template.'.blade.php')));
    $file = $root.'/'.str_replace('/', '-', $template).'.php';
    file_put_contents($file, $compiled);
    $process = proc_open([PHP_BINARY, '-l', $file], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    checkProfile(proc_close($process) === 0, 'Changed Blade template compiles: '.$output);
}
config(['cache.default' => 'array']);
checkProfile(!$service->supported(), 'Nonpersistent array cache rejected');
Mockery::close();
echo "PASS: $checks profile-management checks (isolated SQLite/file cache; mocked transport).\n";
