<?php

// Real HTTP routes, controller and middleware against SQLite memory only.
// Never load .env, use the application database, or send notifications.
require __DIR__.'/../vendor/autoload.php';

use App\Models\User;
use App\Services\MfaDeviceTrust;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

foreach ([
    'APP_ENV' => 'testing',
    'APP_KEY' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
    'APP_CONFIG_CACHE' => sys_get_temp_dir().'/talastas-role-check-no-config.php',
    'APP_ROUTES_CACHE' => sys_get_temp_dir().'/talastas-role-check-no-routes.php',
    'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:',
    'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array',
    'BCRYPT_ROUNDS' => '4', 'ROLE_UPDATES_REQUIRE_OTP' => 'false',
] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}

$app = require __DIR__.'/../bootstrap/app.php';
$app->loadEnvironmentFrom('.env.role-check-never-load');
if (is_file($app->environmentFilePath()) || is_file($app->getCachedConfigPath()) || is_file($app->getCachedRoutesPath())) {
    throw new RuntimeException('Fixture environment/cache path must not exist.');
}
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $exception): void {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(1);
});
config([
    'database.default' => 'sqlite',
    'database.connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]],
    'session.driver' => 'array', 'cache.default' => 'array', 'legal.enforce_terms' => false,
    'hashing.bcrypt.rounds' => 4,
]);
if (DB::connection()->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('Unsafe fixture database.');
}
Queue::fake();
Mail::fake();
Http::preventStrayRequests();

Schema::create('terms_acceptances', fn (Blueprint $t) => $t->id());
Schema::create('barangays', function (Blueprint $t): void {
    $t->id(); $t->string('name');
});
Schema::create('institutions', function (Blueprint $t): void {
    $t->id(); $t->string('name'); $t->string('type');
    $t->foreignId('barangay_id'); $t->boolean('is_active'); $t->timestamps();
});
// Apply only the real account/pivot definitions to the isolated fixture database.
foreach ([
    '0001_01_01_000000_create_users_table.php',
    '2026_07_09_005957_create_personal_access_tokens_table.php',
    '2026_09_15_000001_create_institution_user_table.php',
    '2026_10_23_000001_add_account_security.php',
    '2026_10_23_000002_add_mfa_trusted_devices.php',
    '2026_11_01_000001_add_email_verification_codes.php',
] as $migration) {
    (require __DIR__.'/../database/migrations/'.$migration)->up();
}
DB::table('barangays')->insert(['id' => 1, 'name' => 'Fixture barangay']);
DB::table('institutions')->insert([
    ['id' => 1, 'name' => 'Fixture school', 'type' => 'School', 'barangay_id' => 1, 'is_active' => true],
    ['id' => 2, 'name' => 'Fixture hall', 'type' => 'Barangay Hall', 'barangay_id' => 1, 'is_active' => true],
    ['id' => 3, 'name' => 'Inactive school', 'type' => 'School', 'barangay_id' => 1, 'is_active' => false],
]);
$admin = User::create(['name' => 'Admin fixture', 'email' => 'admin@example.invalid', 'password' => 'fixture-password', 'role' => 'admin']);
$target = User::create(['name' => 'Target fixture', 'email' => 'target@example.invalid', 'password' => 'target-password', 'role' => 'viewer']);
$other = User::create(['name' => 'Other fixture', 'email' => 'other@example.invalid', 'password' => 'other-password', 'role' => 'viewer']);

// Represent an already signed-in administrator whose extra OTP has expired.
// Keep real RBAC, password validation, session version checks and invalidation.
$authorized = true;
$trust = Mockery::mock(MfaDeviceTrust::class)->makePartial();
$trust->shouldReceive('authorized')->andReturnUsing(function () use (&$authorized) { return $authorized; });
$trust->shouldReceive('fresh')->andReturn(false);
$trust->shouldReceive('touch')->andReturnNull();
$app->instance(MfaDeviceTrust::class, $trust);

class RoleUpdateHttpFixture
{
    use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
    use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
    use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

    public function __construct(public $app) {}
}
$http = new RoleUpdateHttpFixture($app);
$checks = 0;
function roleCheck(bool $condition, string $message): void
{
    global $checks;
    if (! $condition) throw new RuntimeException($message);
    $checks++;
}
function editRole(User $actor, User $target, array $changes = [], bool $json = true)
{
    global $http;
    $http->flushSession()->actingAs($actor, 'web')->withSession(['security_version' => $actor->security_version])->from('/admin/users');
    $input = array_replace([
        '_editing_user' => $target->id, 'name' => $target->name, 'email' => $target->email,
        'role' => 'viewer', 'current_password' => 'fixture-password',
    ], $changes);

    return $json ? $http->putJson('/admin/users/'.$target->id, $input) : $http->put('/admin/users/'.$target->id, $input);
}

// Reproduce the previous block, then exercise the same save with OTP paused.
config(['account-security.role_updates_require_otp' => true]);
$response = editRole($admin, $target, ['role' => 'editor', 'institution_ids' => [1]]);
roleCheck($response->status() === 403 && $response->json('code') === 'fresh_mfa_required', 'OTP enabled must reject a stale factor.');
roleCheck($target->fresh()->role === 'viewer', 'OTP rejection must not modify the account.');
config(['account-security.role_updates_require_otp' => false]);
$key = 'map.institutions.geojson.v4.baseline.editor:'.$target->id;
Cache::put($key, 'old scope');
$response = editRole($admin, $target, ['role' => 'editor', 'institution_ids' => [1]], false);
roleCheck($response->status() === 302 && str_ends_with($response->headers->get('Location'), '/admin/users'), 'Browser save should return to accounts without an OTP redirect.');
roleCheck($target->fresh()->role === 'editor' && $target->institutions()->pluck('institutions.id')->all() === [1], 'Editor role and institution must persist.');
roleCheck($target->fresh()->security_version === 2, 'Role changes must invalidate prior access.');
roleCheck(Cache::get($key) === null, 'Changed editor scope must clear cached map access.');
roleCheck(DB::table('security_events')->where('event', 'account.role_scope_updated')->where('target_id', $target->id)->exists(), 'Role changes must remain audited.');
$response = editRole($admin, $target, ['role' => 'editor', 'barangay_ids' => [1]]);
roleCheck($response->status() === 302 && $target->institutions()->orderBy('institutions.id')->pluck('institutions.id')->all() === [1, 2], 'Barangay assignment must expand to active schools/halls only.');
$response = editRole($admin, $target);
roleCheck($response->status() === 302 && $target->fresh()->role === 'viewer' && $target->institutions()->count() === 0, 'Viewer role must clear editor assignments.');
$response = editRole($admin, $target, ['role' => 'admin']);
roleCheck($response->status() === 302 && $target->fresh()->role === 'admin', 'Administrator role must be assignable.');
editRole($admin, $target);

foreach ([
    [['current_password' => 'wrong-password'], 'current_password'],
    [['current_password' => ''], 'current_password'],
    [['role' => 'owner'], 'role'],
    [['role' => 'editor'], 'institution_ids'],
    [['role' => 'editor', 'institution_ids' => [3]], 'institution_ids'],
    [['role' => 'editor', 'institution_ids' => [999]], 'institution_ids.0'],
] as [$input, $field]) {
    $response = editRole($admin, $target, $input);
    roleCheck($response->status() === 422 && isset($response->json('errors')[$field]), 'Expected validation rejection: '.$field);
    roleCheck($target->fresh()->role === 'viewer', 'Rejected request must not change the role.');
}
foreach (['viewer', 'editor'] as $role) {
    $other->update(['role' => $role]);
    $response = editRole($other, $target, ['role' => 'admin', 'current_password' => 'other-password']);
    roleCheck($response->status() === 403 && $target->fresh()->role === 'viewer', 'Non-admin role change must be forbidden: '.$role.' (HTTP '.$response->status().').');
}
$response = editRole($admin, $admin);
roleCheck($response->status() === 422 && $admin->fresh()->role === 'admin', 'Administrator self-edit safeguard must remain.');
$authorized = false;
$response = editRole($admin, $target, ['role' => 'admin']);
roleCheck($response->status() === 403 && $response->json('code') === 'email_otp_required', 'Sign-in MFA must still be required for an unverified administrator.');
$authorized = true;
$http->actingAs($admin, 'web')->withSession(['security_version' => $admin->security_version]);
$response = $http->deleteJson('/admin/users/'.$target->id);
roleCheck($response->status() === 403 && $response->json('code') === 'fresh_mfa_required', 'Delete must retain its fresh-OTP requirement.');
roleCheck(User::whereKey($target->id)->exists(), 'Rejected deletion must preserve the user.');
config(['account-security.role_updates_require_otp' => true]);
$response = editRole($admin, $target);
roleCheck($response->status() === 403 && $response->json('code') === 'fresh_mfa_required', 'The setting must restore role-edit OTP without route changes.');

Mockery::close();
echo "$checks user-role HTTP checks passed (SQLite memory; no external messages).\n";
