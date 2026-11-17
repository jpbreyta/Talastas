<?php

// Reuse the existing isolated account schema/fixtures. No live records or mail.
require __DIR__.'/verify-accounts-page.php';

use App\Http\Controllers\InvitationManagementController;
use App\Http\Middleware\RequireFreshMfa;
use App\Jobs\SendAccountSecurityMail;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\InvitationService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

if (DB::connection()->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('Unsafe invitation fixture');
}
$app->instance('env', 'testing');
Queue::fake();
$invitationChecks = 0;
function invitationCheck(bool $condition, string $message): void
{
    global $invitationChecks;
    if (! $condition) {
        throw new RuntimeException($message);
    }
    $invitationChecks++;
}
function invitationRejected(callable $work, string $message): void
{
    try {
        $work();
    } catch (ValidationException|HttpException $error) {
        invitationCheck(true, $message);

        return;
    }
    throw new RuntimeException($message);
}
$manager = new InvitationManagementController;
$service = new InvitationService;
$input = ['name' => 'Invitation recipient', 'email' => 'recipient@example.invalid', 'role' => 'viewer', 'confirmed' => '1', 'current_password' => 'fixture-password'];
$request = accountsRequest();
$request->session()->start();
foreach ([['current_password' => 'wrong-password'], ['confirmed' => null], ['role' => 'invented'], ['role' => 'editor'], ['role' => 'viewer', 'institution_ids' => [1]], ['role' => 'editor', 'institution_ids' => [999999]]] as $invalid) {
    invitationRejected(fn () => $manager->store(accountsRequest(array_replace($input, $invalid), 'POST'), $service), 'Invalid invitation password, confirmation, role or scope rejected');
}
$response = $manager->store(accountsRequest($input, 'POST'), $service);
$invite = UserInvitation::where('email', $input['email'])->firstOrFail();
invitationCheck($response->isRedirect() && $invite->status === 'pending' && $invite->institutions()->count() === 0, 'Confirmed invitation creation still succeeds');
Queue::assertPushed(SendAccountSecurityMail::class);
invitationCheck(true, 'Existing invitation delivery job queued through normal service');
invitationRejected(fn () => $manager->store(accountsRequest($input, 'POST'), $service), 'Duplicate pending invitation rejected');
$editorInput = array_replace($input, ['email' => 'editor-invite@example.invalid', 'role' => 'editor', 'barangay_ids' => [1], 'institution_ids' => [1]]);
$manager->store(accountsRequest($editorInput, 'POST'), $service);
$editorInvite = UserInvitation::where('email', $editorInput['email'])->firstOrFail();
invitationCheck($editorInvite->institutions()->count() === 5, 'Existing barangay resolution and individual institution validation preserved');
$generation = $invite->generation;
invitationRejected(fn () => $manager->resend(accountsRequest($input, 'POST'), $invite->id, $service), 'Existing resend cooldown enforced');
foreach (['resend', 'revoke'] as $action) {
    invitationRejected(fn () => $manager->$action(accountsRequest(array_replace($input, ['current_password' => 'wrong']), 'POST'), $invite->id, $service), 'Action password remains mandatory');
    invitationRejected(fn () => $manager->$action(accountsRequest(array_replace($input, ['confirmed' => null]), 'POST'), $invite->id, $service), 'Action confirmation remains mandatory');
}
$invite->forceFill(['token_hash' => hash('sha256', 'fixture-token'), 'updated_at' => now()->subMinutes(2)])->save();
$oldState = $service->exchange($invite->id, 'fixture-token');
$manager->resend(accountsRequest($input, 'POST'), $invite->id, $service);
invitationCheck($invite->fresh()->generation === $generation + 1 && $invite->fresh()->token_hash === null, 'Resend invalidates prior token and generation');
invitationRejected(fn () => $service->exchange($invite->id, 'fixture-token'), 'Old invitation link rejected after resend');
$invite->forceFill(['expires_at' => now()->subMinute(), 'token_hash' => hash('sha256', 'expired-token')])->save();
invitationRejected(fn () => $service->exchange($invite->id, 'expired-token'), 'Expired pending invitation cannot be exchanged');
$invite->forceFill(['status' => 'expired', 'updated_at' => now()->subMinutes(2)])->save();
$manager->resend(accountsRequest($input, 'POST'), $invite->id, $service);
invitationCheck($invite->fresh()->status === 'pending' && $invite->fresh()->expires_at->isFuture(), 'Expired invitation can use existing resend lifecycle');
$manager->revoke(accountsRequest($input, 'POST'), $invite->id, $service);
invitationCheck($invite->fresh()->status === 'revoked' && $invite->fresh()->token_hash === null, 'Confirmed revoke invalidates invitation');
invitationRejected(fn () => $manager->resend(accountsRequest($input, 'POST'), $invite->id, $service), 'Revoked invitation cannot be resent');
$editorInvite->forceFill(['status' => 'accepted'])->save();
invitationRejected(fn () => $manager->revoke(accountsRequest($input, 'POST'), $editorInvite->id, $service), 'Accepted invitation cannot be revoked');
$notAdmin = new User(['role' => 'viewer']);
invitationRejected(fn () => $service->change($notAdmin, $invite->id, true), 'Non-administrator cannot manage invitations');
foreach (['invitation.created', 'invitation.resent', 'invitation.revoked'] as $event) {
    invitationCheck(DB::table('security_events')->where('event', $event)->exists(), 'Existing audit event retained: '.$event);
}
foreach (['admin.invitations.store', 'admin.invitations.resend', 'admin.invitations.revoke'] as $routeName) {
    $middleware = app('router')->getRoutes()->getByName($routeName)->gatherMiddleware();
    invitationCheck(in_array('rbac:admin', $middleware) && in_array('mfa.fresh', $middleware) && in_array('auth:web', $middleware), 'Existing authentication/admin/MFA route protection retained');
}

// Render real history data, including fixed UTC timestamps and full assignment disclosure.
$fixed = Carbon\Carbon::parse('2026-11-13 20:30:00', 'UTC');
$editorInvite->forceFill(['created_at' => $fixed, 'expires_at' => $fixed->copy()->addDay(), 'delivery_status' => 'transport_accepted'])->save();
accountsRequest();
$renderSource = str_replace("@extends('layouts.app')", '', file_get_contents(resource_path('views/admin/users/invitations.blade.php')))."\n@yield('content')";
$html = Blade::render($renderSource, $manager->index(accountsRequest())->getData() + ['errors' => new ViewErrorBag], deleteCachedView: true);
$dom = new DOMDocument;
$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
$xpath = new DOMXPath($dom);
invitationCheck(str_contains($html, 'Nov 14, 2026 4:30 AM PHT') && str_contains($html, 'Nov 15, 2026 4:30 AM PHT'), 'UTC creation and expiration converted across date boundary to PHT');
invitationCheck($editorInvite->fresh()->created_at->format('H:i') === '20:30', 'Presentation does not change stored timestamp');
invitationCheck(str_contains($html, '5 assigned institutions') && str_contains($html, '+3 more institutions'), 'History shows accurate assignment count and disclosure');
invitationCheck($xpath->query('//article//details//ul/li')->length === 3, 'All remaining assignments remain accessible');
invitationCheck($xpath->query('//article//input[@type="radio"]')->length === 0 && $xpath->query('//article//form')->length === 0, 'History has no always-visible action forms');
invitationCheck($xpath->query('//article//button[@data-invite-menu-toggle]')->length === 0, 'Accepted/revoked histories have no management menu');
invitationCheck($xpath->query('//dialog//input[@name="confirmed" and @required]')->length === 1 && $xpath->query('//dialog//input[@name="current_password" and @required and not(@value)]')->length === 1, 'Dialog preserves explicit confirmation and empty current-password field');
invitationCheck(str_contains($html, 'Accepted by email service') && str_contains($html, 'does not confirm inbox delivery'), 'Delivery summary remains accurate and technical context accessible');
invitationCheck($xpath->query('//form[@data-invitation-create-form]/fieldset')->length === 2, 'Create form has two clear groups');
invitationCheck($xpath->query('//form[@data-invitation-create-form]//input[@name="confirmed" and @required]')->length === 1, 'Creation authorization checkbox preserved');
$request = accountsRequest();
$request->session()->flash('_old_input', ['invitation_id' => $invite->id, 'invitation_action' => 'revoke', 'confirmed' => '1', 'current_password' => 'never render']);
$request->session()->flash('success', 'Fixture success feedback');
$errors = (new ViewErrorBag)->put('default', new MessageBag(['current_password' => 'Fixture password error']));
$feedbackHtml = Blade::render($renderSource, $manager->index($request)->getData() + ['errors' => $errors], deleteCachedView: true);
$feedbackDom = new DOMDocument;
$feedbackDom->loadHTML('<?xml encoding="UTF-8">'.$feedbackHtml);
$feedbackXpath = new DOMXPath($feedbackDom);
invitationCheck($feedbackXpath->query('//*[@role="alert" and contains(.,"Fixture password error")]')->length > 0
    && $feedbackXpath->query('//*[@role="status" and contains(.,"Fixture success feedback")]')->length === 1, 'Validation and success feedback remain accessible');
invitationCheck($feedbackXpath->query('//dialog[@data-reopen-id="'.$invite->id.'" and @data-reopen-action="revoke"]')->length === 1, 'Validation return retains recipient and action context');
invitationCheck($feedbackXpath->query('//input[@type="password" and @value]')->length === 0
    && $feedbackXpath->query('//form[@data-invitation-create-form]//input[@name="confirmed" and @checked]')->length === 0, 'Action validation never reuses password or checks creation authorization');

// Actual MFA middleware remains mandatory, even when a dialog has confirmed the action.
$request = accountsRequest();
$request->headers->set('Accept', 'application/json');
$response = (new RequireFreshMfa)->handle($request, fn () => response('unexpected'));
invitationCheck($response->getStatusCode() === 403, 'Actual stale administrator MFA rejects protected action');
echo "PASS: $invitationChecks invitation creation/lifecycle/security/rendering checks (isolated SQLite, fake queue).\n";
