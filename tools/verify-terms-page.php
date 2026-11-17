<?php

use App\Models\User;
use App\Services\LegalPolicies;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

// Real policy files and controllers against isolated SQLite only; never live receipts.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$app->instance('env', 'testing');
config(['database.default' => 'sqlite', 'database.connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]],
    'session.driver' => 'array', 'cache.default' => 'array', 'hashing.bcrypt.rounds' => 4]);
DB::purge();
set_exception_handler(function (Throwable $error): void {
    fwrite(STDERR, $error.PHP_EOL);
    exit(1);
});

if (DB::connection()->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('Unsafe policy fixture');
}
Http::preventStrayRequests();
Queue::fake();
(require __DIR__.'/../database/migrations/0001_01_01_000000_create_users_table.php')->up();
(require __DIR__.'/../database/migrations/2026_10_19_000001_create_terms_acceptances_table.php')->up();
Schema::create('institutions', fn (Blueprint $table) => $table->id());
(require __DIR__.'/../database/migrations/2026_10_23_000001_add_account_security.php')->up();
(require __DIR__.'/../database/migrations/2026_10_14_000001_create_user_activity_tables.php')->up();
$policyChecks = 0;
function policyCheck(bool $ok, string $message): void
{
    global $policyChecks;
    if (! $ok) {
        throw new RuntimeException($message);
    }
    $policyChecks++;
}
class TermsHttpFixture
{
    use InteractsWithAuthentication;
    use InteractsWithSession;
    use MakesHttpRequests;

    public function __construct(public $app) {}
}
function policyDom(string $html): DOMXPath
{
    $dom = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    return new DOMXPath($dom);
}
$http = new TermsHttpFixture($app);
$policies = new LegalPolicies;
$terms = $policies->document('terms');
$privacy = $policies->document('privacy');
$configBefore = config('legal');
$response = $http->get('/terms');
$html = $response->getContent();
policyCheck($response->getStatusCode() === 200, 'Public Terms route renders without authentication');
$dom = policyDom($html);
policyCheck($dom->query('//body[contains(@class,"terms-refined")]')->length === 1 && $dom->query('//*[@id="talastasSidebar"]')->length === 0, 'Terms keeps public branding without administrative sidebar');
policyCheck($dom->query('//section[@aria-labelledby="document-status-heading"]')->length === 1, 'One compact document status area');
policyCheck(str_contains($html, 'Draft &mdash; Pending Institutional Approval') && str_contains($html, 'Published for application use'), 'Draft approval and application publication remain distinct');
policyCheck($dom->query('//section[@aria-labelledby="document-status-heading" and contains(@class,"bg-amber-50")]')->length === 1, 'Draft uses restrained amber, not approval-green styling');
policyCheck($dom->query('//section[@aria-labelledby="document-status-heading"]/details[not(@open)]/summary[@aria-controls="document-status-details"]')->length === 1, 'Status uses a native keyboard-accessible disclosure collapsed by default');
$banner = $dom->query('//section[@aria-labelledby="document-status-heading"]/details/summary')->item(0);
policyCheck(str_contains($banner->textContent, 'Pending Institutional Approval') && str_contains($banner->textContent, $terms['content']['version'])
    && str_contains($banner->textContent, 'Published for application use. Institutional approval is still pending.'), 'Collapsed banner preserves status, source version and application/approval distinction');
policyCheck($dom->query('//details/summary//span[contains(@class,"terms-details-collapsed") and normalize-space(.)="View details"]')->length === 1
    && $dom->query('//details/summary//span[contains(@class,"terms-details-expanded") and normalize-space(.)="Hide details"]')->length === 1, 'Both native disclosure labels are available for CSS state switching');
policyCheck($dom->query('//div[@id="document-status-details"]//dl/div')->length === 4
    && $dom->query('//div[@id="document-status-details"]/p[contains(.,"Application acknowledgement does not constitute institutional approval.")]')->length === 1
    && $dom->query('//summary//dl')->length === 0, 'Complete metadata and existing disclosure remain inside expandable details');
policyCheck(str_contains($html, 'Application acknowledgement does not constitute institutional approval.'), 'Complete existing institutional approval caveat retained');
policyCheck(str_contains($html, 'Oct 23, 2026 6:08 PM PHT') && ! str_contains($html, '2026-10-23T10:08:10Z'), 'Publication metadata converts actual UTC timestamp to readable PHT');
policyCheck(str_contains($html, $terms['content']['version']), 'Full source version remains visible');
foreach ($terms['content']['sections'] as $index => $section) {
    $number = $index + 1;
    $node = $dom->query('//section[@aria-labelledby="section-'.$number.'"]')->item(0);
    policyCheck($node !== null, 'Every original section anchor remains');
    policyCheck(trim($dom->query('./h2', $node)->item(0)->textContent) === $section['heading'], 'Original section heading preserved verbatim');
    $paragraphs = $dom->query('./p', $node);
    policyCheck($paragraphs->length === count($section['paragraphs']), 'Original section paragraph count unchanged');
    foreach ($section['paragraphs'] as $i => $paragraph) {
        policyCheck(trim($paragraphs->item($i)->textContent) === $paragraph, 'Legal paragraph rendered verbatim');
    }
    policyCheck($dom->query('//a[@data-toc-link and @href="#section-'.$number.'"]')->length === 1
        && $dom->query('//select[@data-policy-jump]/option[@value="section-'.$number.'"]')->length === 1, 'Desktop and mobile navigation cover each section');
}
foreach ($terms['content']['references'] as $reference) {
    policyCheck($dom->query('//section[@aria-labelledby="policy-references"]//a[@href="'.$reference['url'].'"]')->length === 1, 'Original reference destination preserved');
}
policyCheck($dom->query('//details[summary="Document integrity"]/p[contains(.,"'.$terms['hash'].'")]')->length === 1, 'Integrity hash remains accessible in disclosure');
policyCheck($dom->query('//a[@href="#policy-title" and contains(.,"Back to top")]')->length === 1
    && $dom->query('//*[@id="policy-title" and @tabindex="-1"]')->length === 1, 'Back-to-top link has focusable document heading');
policyCheck($dom->query('//section[@aria-labelledby="consent-heading"]//a[contains(@href,"terms-acceptance#acknowledgement")]')->length === 1, 'Acknowledgement button still navigates to explicit confirmation');
policyCheck(DB::table('terms_acceptances')->count() === 0, 'Reading Terms never records acceptance');
$privacyResponse = $http->get('/privacy');
policyCheck($privacyResponse->getStatusCode() === 200 && ! str_contains($privacyResponse->getContent(), 'terms-refined"'), 'Privacy Notice retains its own page styling');
$privacyDom = policyDom($privacyResponse->getContent());
policyCheck($privacyDom->query('//section[@aria-labelledby="document-status-heading" and contains(@class,"bg-amber-50")]/details[not(@open)]/summary[@aria-controls="document-status-details"]')->length === 1, 'Privacy Notice reuses the same collapsed amber status disclosure');
policyCheck(str_contains($privacyDom->query('//section[@aria-labelledby="document-status-heading"]/details/summary')->item(0)->textContent, $privacy['content']['version'])
    && $privacyDom->query('//div[@id="document-status-details"]/p[contains(.,"Application acknowledgement does not constitute institutional approval.")]')->length === 1, 'Privacy source version and complete approval caveat are preserved');
policyCheck($privacyDom->query('//details[summary="Document integrity"]/p[contains(.,"'.$privacy['hash'].'")]')->length === 1, 'Privacy Notice retains its own integrity hash');
foreach ($privacy['content']['sections'] as $section) {
    foreach ($section['paragraphs'] as $paragraph) {
        policyCheck(str_contains(html_entity_decode($privacyResponse->getContent(), ENT_QUOTES, 'UTF-8'), $paragraph), 'Privacy Notice content remains unchanged');
    }
}
$anonymous = $http->postJson('/terms-acceptance', ['accepted' => '1', 'version' => $terms['content']['version'], 'content_hash' => $terms['hash']]);
policyCheck($anonymous->getStatusCode() === 401, 'Unauthenticated acknowledgement remains prohibited');
$user = User::create(['name' => 'Terms fixture', 'email' => 'terms@example.invalid', 'password' => 'fixture-password', 'role' => 'viewer']);
$http->flushSession()->actingAs($user, 'web')->withSession(['security_version' => $user->security_version]);
$review = $http->get('/terms-acceptance');
$reviewHtml = $review->getContent();
$reviewDom = policyDom($reviewHtml);
policyCheck($review->getStatusCode() === 200 && $reviewDom->query('//input[@id="accept-terms" and @required and not(@checked)]')->length === 1, 'Existing acknowledgement checkbox remains explicit and unchecked');
policyCheck($reviewDom->query('//form[@action="'.route('legal.accept').'"]/input[@name="content_hash" and @value="'.$terms['hash'].'"]')->length === 1, 'Original content hash binding preserved');
$input = ['version' => $terms['content']['version'], 'content_hash' => $terms['hash']];
foreach ([[], ['accepted' => '0'], ['accepted' => '1', 'user_id' => 999], ['accepted' => '1', 'accepted_at' => '2020-01-01']] as $invalid) {
    policyCheck($http->postJson('/terms-acceptance', $invalid + $input)->getStatusCode() === 422 && DB::table('terms_acceptances')->count() === 0, 'Required acknowledgement and protected receipt fields still validated');
}
policyCheck($http->postJson('/terms-acceptance', ['accepted' => '1', 'version' => 'wrong-version', 'content_hash' => $terms['hash']])->getStatusCode() === 409, 'Wrong version rejected');
policyCheck($http->postJson('/terms-acceptance', ['accepted' => '1', 'version' => $terms['content']['version'], 'content_hash' => str_repeat('0', 64)])->getStatusCode() === 409, 'Wrong content hash rejected');
$accepted = $http->postJson('/terms-acceptance', ['accepted' => '1'] + $input);
policyCheck($accepted->getStatusCode() === 200 && DB::table('terms_acceptances')->count() === 1, 'Existing explicit acceptance workflow succeeds');
$receipt = DB::table('terms_acceptances')->first();
policyCheck($receipt->user_id === $user->id && $receipt->content_hash === $terms['hash'] && $receipt->policy_snapshot === $terms['snapshot'], 'Acceptance retains authenticated identity and exact immutable policy snapshot');
$acceptedAt = $receipt->accepted_at;
policyCheck($http->postJson('/terms-acceptance', ['accepted' => '1'] + $input)->getStatusCode() === 200
    && DB::table('terms_acceptances')->count() === 1 && DB::table('terms_acceptances')->first()->accepted_at === $acceptedAt, 'Repeated acceptance remains idempotent and preserves timestamp');
policyCheck($http->get('/terms-receipts/'.$receipt->id)->getStatusCode() === 200, 'Historical receipt still renders');
$other = User::create(['name' => 'Other fixture', 'email' => 'other-terms@example.invalid', 'password' => 'fixture-password', 'role' => 'viewer']);
$http->flushSession()->actingAs($other, 'web')->withSession(['security_version' => $other->security_version]);
policyCheck($http->getJson('/terms-receipts/'.$receipt->id)->getStatusCode() === 404, 'Historical receipt cannot be read by another account');
$declined = $http->postJson('/terms-decline');
policyCheck($declined->getStatusCode() === 200 && ! Auth::check() && DB::table('terms_acceptances')->count() === 1, 'Decline signs out without creating acceptance');
// Presentation-only synthetic variants: do not save or change configured policies.
$unpublished = $terms;
$unpublished['acknowledgement_release'] = null;
$unpublishedHtml = view('legal._document', ['document' => $unpublished])->render();
policyCheck(! str_contains($unpublishedHtml, 'Published for application use') && str_contains($unpublishedHtml, 'Pending approval'), 'Unpublished draft never claims application publication or invents a date');
$approved = $unpublished;
$approved['institutionally_approved'] = true;
$approved['content']['status'] = 'approved';
$approved['content']['approved_by'] = 'Fixture approval authority';
$approved['content']['approved_at'] = '2026-11-01T12:00:00Z';
$approved['content']['effective_at'] = '2026-11-02T00:00:00Z';
$approvedDom = policyDom(view('legal._document', ['document' => $approved])->render());
$approvedStatus = $approvedDom->query('//section[@aria-labelledby="document-status-heading"]')->item(0)->textContent;
policyCheck(str_contains($approvedStatus, 'Institutionally approved') && str_contains($approvedStatus, 'Fixture approval authority')
    && ! str_contains($approvedStatus, 'Pending Institutional Approval'), 'Approval presentation follows supplied backend status, not a hardcoded draft assumption');
policyCheck(str_contains($approvedStatus, 'Nov 1, 2026 8:00 PM PHT'), 'Approval metadata uses actual timezone conversion too');
$nonDraftRelease = $terms;
$nonDraftRelease['content']['status'] = 'approved';
$nonDraftDom = policyDom(view('legal._document', ['document' => $nonDraftRelease])->render());
$nonDraftStatus = $nonDraftDom->query('//section[@aria-labelledby="document-status-heading"]')->item(0)->textContent;
policyCheck(! str_contains($nonDraftStatus, 'source document remains a draft') && str_contains($nonDraftStatus, 'Institutional approval not confirmed'), 'Unconfirmed non-draft source is not incorrectly labelled draft');
policyCheck($policies->document('terms') === $terms && $policies->document('privacy') === $privacy && config('legal') === $configBefore, 'Source text, hashes, publication metadata and legal configuration remain unchanged');
echo "PASS: $policyChecks Terms content/navigation/status/acknowledgement checks (isolated SQLite; no live records).\n";
