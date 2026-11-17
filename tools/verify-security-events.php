<?php

// Reuse only the isolated SQLite account fixture; no live accounts or audit writes.
require __DIR__.'/verify-accounts-page.php';

use App\Http\Controllers\InvitationManagementController;
use App\Http\Controllers\UserActivityController;
use App\Models\User;
use App\Services\MfaDeviceTrust;
use App\Services\SecurityAudit;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpKernel\Exception\HttpException;

if (DB::connection()->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('Unsafe audit fixture');
}
$app->instance('env', 'testing');
config(['legal.enforce_terms' => false]);
view()->share('errors', new ViewErrorBag);
(require __DIR__.'/../database/migrations/2026_09_15_000000_create_notifications_table.php')->up();
$auditChecks = 0;
function auditCheck(bool $ok, string $message): void
{
    global $auditChecks;
    if (! $ok) {
        throw new RuntimeException($message);
    }
    $auditChecks++;
}
function auditRequest(array $query = [], ?User $actor = null)
{
    return accountsRequest(['tab' => 'changes', 'view' => 'security'] + $query, 'GET', $actor);
}
$controller = new UserActivityController;
$target = User::create(['name' => 'Audit target fixture', 'email' => 'private-audit-email@example.invalid', 'password' => 'secret-fixture-password', 'role' => 'viewer']);
$viewer = User::create(['name' => 'Audit viewer fixture', 'email' => 'viewer-audit@example.invalid', 'password' => 'fixture-password', 'role' => 'viewer']);
$editorActor = User::create(['name' => 'Audit editor fixture', 'email' => 'editor-audit@example.invalid', 'password' => 'fixture-password', 'role' => 'editor']);
$target->forceFill(['remember_token' => 'secret-remember-fixture', 'mfa_secret' => 'secret-mfa-fixture'])->save();
for ($i = 0; $i < 55; $i++) {
    DB::table('security_events')->insert(['actor_id' => $admin->id, 'target_id' => $target->id,
        'event' => 'fixture_audit.event_'.$i, 'outcome' => $i % 2 ? 'denied' : 'success',
        'invitation_id' => 'non-secret-invitation-reference', 'occurred_at' => '2026-11-14 20:30:00',
        'request_fingerprint' => 'private-request-fingerprint']);
}
DB::table('security_events')->insert(['actor_id' => 999999, 'target_id' => null, 'event' => 'fixture_deleted.account',
    'outcome' => 'failed', 'occurred_at' => '2026-11-14 20:30:00', 'request_fingerprint' => 'private-request-fingerprint']);
DB::table('security_events')->insert(['actor_id' => null, 'target_id' => null, 'event' => 'fixture_system.event',
    'outcome' => 'transport_accepted', 'occurred_at' => '2026-11-14 20:30:00', 'request_fingerprint' => 'private-request-fingerprint']);
$snapshot = fn ($table) => DB::table($table)->orderBy('id')->get()->toJson();
$beforeSecurity = $snapshot('security_events');
$beforeChanges = $snapshot('user_activity_logs');
$beforeSessions = $snapshot('user_activity_sessions');
$view = $controller->index(auditRequest());
$data = $view->getData();
$events = $data['securityEvents'];
auditCheck(! isset($data['logs'], $data['sessions']), 'Security view does not load unrelated data-change/session records');
auditCheck($events->perPage() === 20 && $events->count() === 20 && $events->total() === DB::table('security_events')->count(), 'Server pagination is fixed at 20 and covers all security records');
$allowed = ['id', 'event', 'outcome', 'occurred_at', 'actor_id', 'target_id', 'invitation_id', 'actor_name', 'target_name'];
$keys = array_keys((array) $events->first());
sort($keys);
sort($allowed);
auditCheck($keys === $allowed, 'Query returns only explicitly allowlisted display fields');
$pageTwo = $controller->index(auditRequest(['security_page' => 2, 'per_page' => 999999]))->getData()['securityEvents'];
auditCheck($pageTwo->currentPage() === 2 && $pageTwo->perPage() === 20, 'Client cannot increase page size');
auditCheck(array_intersect($events->pluck('id')->all(), $pageTwo->pluck('id')->all()) === [], 'Stable timestamp/id ordering has no duplicate rows across pages');
$allIds = [];
for ($page = 1; $page <= $events->lastPage(); $page++) {
    $allIds = array_merge($allIds, $controller->index(auditRequest(['security_page' => $page]))->getData()['securityEvents']->pluck('id')->all());
}
auditCheck(count(array_unique($allIds)) === $events->total() && $events->total() > 50, 'All audit history is reachable beyond the previous 50-event panel limit');
auditCheck(str_contains($pageTwo->url(1), 'view=security') && str_contains($pageTwo->url(1), 'tab=changes'), 'Pagination preserves selected audit view');
$byEvent = $controller->index(auditRequest(['search' => 'fixture_audit.event_34']))->getData()['securityEvents'];
auditCheck($byEvent->total() === 1 && $byEvent->first()->event === 'fixture_audit.event_34', 'Event filtering returns actual records');
auditCheck($byEvent->first()->actor_name === $admin->name && $byEvent->first()->target_name === $target->name, 'Actor and related account names come from actual users');
auditCheck($controller->index(auditRequest(['search' => 'Audit target fixture']))->getData()['securityEvents']->total() === 55, 'Related-account name filtering works');
auditCheck($controller->index(auditRequest(['search' => $admin->name]))->getData()['securityEvents']->total() >= 55, 'Actor-name filtering works');
auditCheck($controller->index(auditRequest(['search' => 'denied']))->getData()['securityEvents']->every(fn ($event) => str_contains($event->outcome, 'denied') || str_contains($event->event, 'denied')), 'Outcome filtering works');
$html = $controller->index(auditRequest(['search' => 'fixture_audit.event_34']))->render();
auditCheck(str_contains($html, '15 Nov 2026 04:30:00 AM') && str_contains($html, 'Time (PHT)'), 'Stored UTC timestamp is converted to Philippine time across day boundary');
foreach (['private-request-fingerprint', 'private-audit-email@example.invalid', 'secret-remember-fixture', 'secret-mfa-fixture', 'secret-fixture-password', $target->password, $target->getRawOriginal('mfa_secret')] as $secret) {
    auditCheck(! str_contains($html, $secret), 'Security view never renders sensitive fixture details');
}
$dom = new DOMDocument;
@$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
$xpath = new DOMXPath($dom);
auditCheck($xpath->query('//nav[@aria-label="Audit record type"]/a')->length === 2, 'Compact switcher keeps two separate audit views');
auditCheck($xpath->query('//nav[@aria-label="Accounts and logs"]/a')->length === 4, 'Existing four-tab module navigation preserved');
auditCheck($xpath->query('//nav[@aria-label="Audit record type"]/a[@aria-current="page" and normalize-space(.)="Security Events"]')->length === 1, 'Selected security view is clearly identified');
auditCheck($xpath->query('//*[@data-user-activity-live]')->length === 1, 'Existing auto-refresh targets only the selected security table');
auditCheck($xpath->query('//form[@method="GET"]/input[@name="view" and @value="security"]')->length === 1, 'Search keeps the selected security view');
auditCheck($xpath->query('//form[@method="POST"]')->length === 0 || $xpath->query('//form[@method="POST" and not(contains(@action,"logout"))]')->length === 0, 'Audit view adds no modifying controls');
$deletedHtml = $controller->index(auditRequest(['search' => 'fixture_deleted']))->render();
auditCheck(str_contains($deletedHtml, 'Account #999999 (name unavailable)'), 'Deleted-account reference is preserved without inventing a historical name');
auditCheck(str_contains($controller->index(auditRequest(['search' => 'fixture_system']))->render(), 'System / guest'), 'Null actor remains distinguishable');
auditCheck(str_contains($controller->index(auditRequest(['search' => 'no-fixture-match']))->render(), 'No security events match this search.'), 'Filtered empty state is useful');
$default = $controller->index(accountsRequest(['tab' => 'changes']))->getData();
auditCheck(isset($default['logs'], $default['sessions']) && ! isset($default['securityEvents']), 'Data Changes remains default with original data sources');
$sessions = $controller->index(accountsRequest(['tab' => 'sessions']))->getData();
auditCheck($sessions['sessions']->perPage() === 15 && $sessions['logs']->perPage() === 20, 'Existing session/data-change pagination unchanged');
auditCheck(str_contains(view('admin.users._activity', $sessions + ['activityTab' => 'sessions'])->render(), 'Session Activity'), 'Session Activity still renders');
auditCheck($snapshot('security_events') === $beforeSecurity && $snapshot('user_activity_logs') === $beforeChanges && $snapshot('user_activity_sessions') === $beforeSessions, 'Controller/view reads do not change any audit/session records');
foreach ([$viewer, $editorActor] as $actor) {
    try {
        $controller->index(auditRequest([], $actor));
        throw new RuntimeException('Non-administrator direct read accepted');
    } catch (HttpException $error) {
        auditCheck($error->getStatusCode() === 403, 'Controller defensively rejects non-administrator security reads');
    }
}

// Exercise the real GET route and authentication/RBAC middleware, including JSON requests.
$trust = Mockery::mock(MfaDeviceTrust::class)->makePartial();
$trust->shouldReceive('authorized')->andReturn(true);
$trust->shouldReceive('touch')->andReturnNull();
$app->instance(MfaDeviceTrust::class, $trust);
class SecurityEventsHttpFixture
{
    use InteractsWithAuthentication;
    use InteractsWithSession;
    use MakesHttpRequests;

    public function __construct(public $app) {}
}
$http = new SecurityEventsHttpFixture($app);
$url = '/admin/users/logs?tab=changes&view=security';
Auth::logout();
$http->flushSession();
auditCheck($http->getJson($url)->getStatusCode() === 401, 'Unauthenticated direct JSON request denied');
foreach ([$viewer, $editorActor] as $actor) {
    $http->flushSession()->actingAs($actor, 'web')->withSession(['security_version' => $actor->security_version]);
    $response = $http->getJson($url);
    auditCheck($response->getStatusCode() === 403 && ! str_contains($response->getContent(), 'fixture_audit'), 'Direct JSON request cannot bypass administrator authorization');
}
$http->flushSession()->actingAs($admin, 'web')->withSession(['security_version' => $admin->security_version]);
$response = $http->get($url);
auditCheck($response->getStatusCode() === 200 && str_contains($response->getContent(), 'Security Events'), 'Authorized administrator can view security table through real route');
$response = $http->get('/admin/users/logs?tab=changes');
$dom = new DOMDocument;
@$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
$xpath = new DOMXPath($dom);
auditCheck($response->getStatusCode() === 200 && $xpath->query('//nav[@aria-label="Audit record type"]/a[@aria-current="page" and normalize-space(.)="Data Changes"]')->length === 1, 'Real route defaults to Data Changes');
auditCheck($http->get('/admin/users/logs?tab=sessions')->getStatusCode() === 200, 'Existing Session Activity HTTP route works');
auditCheck($http->getJson($url.'&security_page=0')->getStatusCode() === 422, 'Invalid page number rejected');
auditCheck($http->getJson('/admin/users/logs?tab=changes&view=unknown')->getStatusCode() === 422, 'Unknown record view rejected');
foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
    auditCheck($http->json($method, $url, ['id' => 1, 'outcome' => 'overwritten'])->getStatusCode() === 405, 'Security retrieval route rejects '.$method);
}
auditCheck($snapshot('security_events') === $beforeSecurity && $snapshot('user_activity_logs') === $beforeChanges, 'HTTP reads and rejected writes leave security/data-change records unchanged');
accountsRequest();
SecurityAudit::record('fixture_audit.logging_continues', $admin->id);
auditCheck(DB::table('security_events')->where('event', 'fixture_audit.logging_continues')->where('actor_id', $admin->id)->exists(), 'Existing security logging still records new events');
$invitationData = (new InvitationManagementController)->index(accountsRequest())->getData();
auditCheck(! isset($invitationData['events']), 'Invitations no longer queries or receives security event records');
$invitationHtml = view('admin.users.invitations', $invitationData)->render();
auditCheck(! str_contains($invitationHtml, 'Recent security events') && ! str_contains($invitationHtml, 'securityEventsHeading')
    && str_contains($invitationHtml, 'Invitation history') && str_contains($invitationHtml, 'Send Invitation'), 'Only the redundant Invitations audit panel is removed');
Mockery::close();
echo "PASS: $auditChecks read-only security audit/query/rendering/HTTP authorization checks (isolated SQLite).\n";
