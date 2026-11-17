<?php

// All schema/data changes below are confined to SQLite memory. No emails are sent.
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
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (DB::connection()->getDatabaseName() !== ':memory:') throw new RuntimeException('Unsafe fixture database');
Illuminate\Support\Facades\Queue::fake();
Illuminate\Support\Facades\Mail::fake();
Illuminate\Support\Facades\Http::preventStrayRequests();
$checks = 0;
function accountCheck(bool $value, string $message): void { global $checks; if (!$value) throw new RuntimeException($message); $checks++; }
Schema::create('terms_acceptances', fn (Blueprint $t) => $t->id());
Schema::create('barangays', function (Blueprint $t) { $t->id(); $t->string('name'); });
Schema::create('institutions', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('type'); $t->foreignId('barangay_id'); $t->boolean('is_active'); $t->timestamps(); });
foreach (['0001_01_01_000000_create_users_table.php', '2026_07_09_005957_create_personal_access_tokens_table.php',
    '2026_09_15_000001_create_institution_user_table.php', '2026_10_23_000001_add_account_security.php',
    '2026_10_23_000002_add_mfa_trusted_devices.php', '2026_11_01_000001_add_email_verification_codes.php',
    '2026_10_14_000001_create_user_activity_tables.php', '2026_05_08_080338_create_audit_records_table.php',
] as $migration) (require __DIR__.'/../database/migrations/'.$migration)->up();
DB::table('barangays')->insert(['id' => 1, 'name' => 'Fixture barangay']);
for ($id = 1; $id <= 5; $id++) DB::table('institutions')->insert(['id' => $id, 'name' => "Fixture institution $id", 'type' => 'School', 'barangay_id' => 1, 'is_active' => true]);
$admin = User::create(['name' => 'Admin fixture', 'email' => 'admin@example.invalid', 'password' => 'fixture-password', 'role' => 'admin']);
$backup = User::create(['name' => 'Backup fixture', 'email' => 'backup@example.invalid', 'password' => 'fixture-password', 'role' => 'admin']);
$editor = User::create(['name' => 'Editor fixture', 'email' => 'editor@example.invalid', 'password' => 'fixture-password', 'role' => 'editor']);
$inactive = User::create(['name' => 'Inactive fixture', 'email' => 'inactive@example.invalid', 'password' => 'fixture-password', 'role' => 'viewer']);
$inactive->forceFill(['is_active' => false])->save();
$editor->institutions()->sync([1, 2, 3, 4, 5]);
$controller = new App\Http\Controllers\UserManagementController;
function accountsRequest(array $params = [], string $method = 'GET', ?User $actor = null): Request {
    global $app, $admin;
    $actor ??= $admin; auth()->setUser($actor);
    $request = Request::create('/admin/users', $method, $params); $request->setUserResolver(fn () => $actor);
    $request->setLaravelSession(app('session')->driver()); $app->instance('request', $request); return $request;
}
$data = $controller->index(accountsRequest())->getData();
accountCheck($data['users']->total() === 4 && $data['users']->perPage() === 20, 'Account pagination remains twenty rows');
accountCheck($controller->index(accountsRequest(['search' => 'editor@']))->getData()['users']->pluck('id')->all() === [$editor->id], 'Email search uses the existing endpoint');
accountCheck($controller->index(accountsRequest(['search' => 'Backup']))->getData()['users']->pluck('id')->all() === [$backup->id], 'Name search works');
$empty = $controller->index(accountsRequest(['search' => 'does-not-match']))->getData();
accountCheck($empty['users']->isEmpty(), 'No-match search returns an empty page');
$source = str_replace("@extends('layouts.app')", '', file_get_contents(__DIR__.'/../resources/views/admin/users/index.blade.php'))."\n@yield('content')";
accountsRequest();
$html = Blade::render($source, $data + ['errors' => new Illuminate\Support\ViewErrorBag], deleteCachedView: true);
libxml_use_internal_errors(true); $dom = new DOMDocument; $dom->loadHTML('<?xml encoding="UTF-8">'.$html); $xpath = new DOMXPath($dom);
$editorRow = $xpath->query('//table[contains(@class,"users-accounts-table")]//tr[td/p="Editor fixture"]')->item(0);
accountCheck($xpath->query('./td[4]/span', $editorRow)->length === 2, 'Only two assigned institutions are visible by default');
accountCheck($xpath->query('./td[4]/details[not(@open)]//li', $editorRow)->length === 3 && str_contains($editorRow->textContent, '+3 more institutions'), 'Remaining assignment count is accurate and disclosure starts collapsed');
accountCheck(str_contains($html, 'None assigned') && str_contains($html, 'Show fewer institutions'), 'Empty assignments and collapse affordance are present');
accountCheck($xpath->query('//tr[td/p="Inactive fixture"]/td[1]/span[contains(@class,"bg-gray-100") and text()="Inactive"]')->length === 1, 'Inactive badge uses actual backend state');
accountCheck($xpath->query('//tr[td/p="Editor fixture"]/td[1]/span[contains(@class,"bg-green-50") and text()="Active"]')->length === 1, 'Active badge uses actual backend state');
accountCheck($xpath->query('//tr[td/p="Admin fixture"]//*[@data-user-actions-toggle]')->length === 0, 'Current account cannot open unsupported self-edit/delete actions');
accountCheck($xpath->query('//*[@data-user-actions-toggle]')->length === 3, 'Other accounts retain action menus');
accountCheck($xpath->query('//form[@data-account-delete-form]')->length === 3 && str_contains($html, 'related quarterly audit records'), 'Deletion remains explicit and warns about existing database policy');
accountCheck(str_contains($html, 'Accounts') && str_contains($html, 'Invitations') && str_contains($html, 'Session Activity') && str_contains($html, 'Data Changes'), 'Four existing tabs remain available');
accountsRequest(['search' => 'does-not-match']);
accountCheck(str_contains(Blade::render($source, $empty + ['errors' => new Illuminate\Support\ViewErrorBag], deleteCachedView: true), 'No accounts match this search.'), 'Empty state explains search recovery');
accountsRequest();
$updated = $controller->update(accountsRequest(['name' => $editor->name, 'email' => $editor->email,
    'role' => 'editor', 'institution_ids' => [1, 2], 'current_password' => 'fixture-password'], 'PUT'), $editor);
accountCheck($updated->isRedirect() && $editor->fresh()->institutions()->count() === 2, 'Existing password-confirmed edit and assignment save remain functional');
try {
    $controller->update(accountsRequest(['name' => $editor->name, 'email' => $editor->email,
        'role' => 'editor', 'institution_ids' => [1], 'current_password' => 'wrong-password'], 'PUT'), $editor);
    throw new RuntimeException('Wrong password accepted');
} catch (Illuminate\Validation\ValidationException $error) {
    accountCheck(isset($error->errors()['current_password']) && $editor->fresh()->institutions()->count() === 2, 'Invalid password cannot change an account');
}
$twoAssignmentData = $controller->index(accountsRequest())->getData();
$twoHtml = Blade::render($source, $twoAssignmentData + ['errors' => new Illuminate\Support\ViewErrorBag], deleteCachedView: true);
$twoDom = new DOMDocument; $twoDom->loadHTML('<?xml encoding="UTF-8">'.$twoHtml); $twoXpath = new DOMXPath($twoDom);
accountCheck($twoXpath->query('//tr[td/p="Editor fixture"]/td[4]/details')->length === 0, 'Two assignments remain visible without an unnecessary disclosure');
$self = $controller->destroy(accountsRequest([], 'DELETE'), $admin);
accountCheck(User::whereKey($admin->id)->exists() && $self->isRedirect(), 'Backend self-delete protection is retained');
try {
    $controller->update(accountsRequest(['name' => $admin->name, 'email' => $admin->email,
        'role' => 'admin', 'current_password' => 'fixture-password'], 'PUT'), $admin);
    throw new RuntimeException('Administrator self-edit unexpectedly accepted');
} catch (Symfony\Component\HttpKernel\Exception\HttpException $error) {
    accountCheck($error->getStatusCode() === 422 && $error->getMessage() === 'Use your profile to manage your own account.',
        'Admin account endpoint explicitly prohibits self-editing even with valid credentials');
}
$backup->forceFill(['is_active' => false])->save();
try { $controller->destroy(accountsRequest([], 'DELETE', $editor), $admin); throw new RuntimeException('Last admin deletion accepted'); }
catch (Illuminate\Validation\ValidationException $error) { accountCheck(User::whereKey($admin->id)->exists(), 'Last active administrator remains protected even for direct controller fixture'); }
foreach (['editor', 'viewer'] as $role) {
    $actor = new User(['role' => $role]); auth()->setUser($actor);
    try { (new App\Http\Middleware\RBAC)->handle(accountsRequest([], 'GET', $actor), fn () => response('unexpected'), 'admin'); throw new RuntimeException('Non-admin accepted'); }
    catch (Symfony\Component\HttpKernel\Exception\HttpException $error) { accountCheck($error->getStatusCode() === 403, 'Non-admin route access is denied'); }
}
foreach (['admin.users.index', 'admin.users.update', 'admin.users.destroy'] as $name) accountCheck(in_array('rbac:admin', app('router')->getRoutes()->getByName($name)->gatherMiddleware()), 'Administrator-only route remains: '.$name);
foreach (['admin.users.update', 'admin.users.destroy'] as $name) accountCheck(in_array('mfa.fresh', app('router')->getRoutes()->getByName($name)->gatherMiddleware()), 'Fresh MFA middleware remains: '.$name);
$trust = Mockery::mock(App\Services\MfaDeviceTrust::class)->makePartial(); $trust->shouldReceive('fresh')->andReturn(false); $app->instance(App\Services\MfaDeviceTrust::class, $trust);
$request = accountsRequest(); $request->headers->set('Accept', 'application/json');
$response = (new App\Http\Middleware\RequireFreshMfa)->handle($request, fn () => response('unexpected'));
accountCheck($response->getStatusCode() === 403 && $response->getData(true)['code'] === 'fresh_mfa_required', 'Stale MFA rejects protected actions under current policy');
DB::table('user_activity_logs')->insert(['user_id' => $editor->id, 'user_name' => 'Editor fixture', 'action' => 'updated', 'record_type' => 'Institution', 'record_id' => '1', 'changed_fields' => 'name', 'occurred_at' => now()]);
DB::table('user_activity_sessions')->insert(['user_id' => $editor->id, 'user_name' => 'Editor fixture', 'session_key' => 'sensitive-fixture-session-key', 'channel' => 'web', 'opened_at' => now(), 'last_seen_at' => now()]);
$controller->destroy(accountsRequest([], 'DELETE'), $editor);
accountCheck(!User::whereKey($editor->id)->exists() && DB::table('institution_user')->where('user_id', $editor->id)->count() === 0, 'Authorized controller deletion preserves the existing permanent-deletion and assignment policy');
accountCheck(DB::table('user_activity_logs')->where('user_name', 'Editor fixture')->exists() && DB::table('user_activity_sessions')->where('user_name', 'Editor fixture')->exists(), 'Historical activity/name snapshots survive account deletion');
$activity = (new App\Http\Controllers\UserActivityController)->activityData('Editor fixture');
accountCheck($activity['logs']->total() === 1 && $activity['sessions']->total() === 1, 'Existing activity search and pagination remain functional');
$activityHtml = view('admin.users._activity', $activity + ['activityTab' => 'sessions'])->render();
accountCheck(str_contains($activityHtml, 'Editor fixture') && !str_contains($activityHtml, 'sensitive-fixture-session-key'), 'Activity rendering retains attribution without exposing the session key');
$invites = collect();
foreach ([false, true] as $expired) {
    $invite = new App\Models\UserInvitation(['id' => (string) Illuminate\Support\Str::uuid(), 'name' => $expired ? 'Expired fixture' : 'Pending fixture',
        'email' => 'invite@example.invalid', 'role' => 'viewer', 'status' => 'pending', 'delivery_status' => 'sent',
        'expires_at' => $expired ? now()->subDay() : now()->addDay()]);
    $invite->setRelation('institutions', collect()); $invites->push($invite);
}
$invitationSource = str_replace("@extends('layouts.app')", '', file_get_contents(__DIR__.'/../resources/views/admin/users/invitations.blade.php'))."\n@yield('content')";
$invitationHtml = Blade::render($invitationSource, ['institutions' => $twoAssignmentData['institutions'],
    'invitations' => new Illuminate\Pagination\LengthAwarePaginator($invites, 2, 20),
    'events' => new Illuminate\Pagination\LengthAwarePaginator(collect(), 0, 20), 'errors' => new Illuminate\Support\ViewErrorBag], deleteCachedView: true);
$inviteDom = new DOMDocument; $inviteDom->loadHTML('<?xml encoding="UTF-8">'.$invitationHtml); $inviteXpath = new DOMXPath($inviteDom);
accountCheck($inviteXpath->query('//span[contains(@class,"bg-amber-50") and text()="pending"]')->length === 1
    && $inviteXpath->query('//span[contains(@class,"bg-gray-100") and text()="expired"]')->length === 1, 'Pending and expired invitations have distinct styling without changing expiration logic');
accountCheck($inviteXpath->query('//dt[normalize-space(.)="Expires"]/following-sibling::dd/time')->length === 2
    && str_contains($invitationHtml, 'data-invitation-action-form'), 'Existing expiration information and secured invitation actions are preserved');
Mockery::close();
echo "PASS: $checks accounts rendering/search/security checks (isolated SQLite; no live accounts or messages).\n";
