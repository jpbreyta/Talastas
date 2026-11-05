<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function create(User $actor, array $input): UserInvitation
    {
        abort_unless($actor->isAdmin() && $actor->is_active, 403);
        SecurityMail::requireConfigured();
        $email = strtolower(trim($input['email']));

        return DB::transaction(function () use ($actor, $input, $email) {
            $expired = UserInvitation::where('email', $email)->where('status', 'pending')->where('expires_at', '<=', now())->lockForUpdate()->get();
            foreach ($expired as $row) {
                $row->update(['status' => 'expired', 'token_hash' => null, 'generation' => $row->generation + 1]);
                SecurityAudit::record('invitation.expired', null, 'success', $row->id);
            }
            if (UserInvitation::where('email', $email)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['email' => 'An invitation is already pending for this email. Resend or revoke it instead.']);
            }
            $input['institution_ids'] = $this->resolveScope($input['role'], $input['institution_ids'] ?? [], $input['barangay_ids'] ?? []);
            $existing = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
            if ($existing && ($existing->is($actor) || ! $existing->is_active)) {
                throw ValidationException::withMessages(['email' => 'Use account management for your own account or an inactive account.']);
            }
            if ($existing && empty($input['existing_account_confirmed'])) {
                throw ValidationException::withMessages(['email' => 'An account already exists. Confirm that this invitation will update its authorized access.']);
            }
            $invite = UserInvitation::create(['id' => (string) Str::uuid(), 'name' => $input['name'], 'email' => $email, 'role' => $input['role'], 'invited_by' => $actor->id, 'expires_at' => now()->addDay()]);
            $invite->refresh();
            $invite->institutions()->sync($input['role'] === 'editor' ? ($input['institution_ids'] ?? []) : []);
            SecurityAudit::record('invitation.created', $existing?->id, 'success', $invite->id);
            SecurityMail::queue('invitation', $invite->id, $invite->generation);

            return $invite;
        });
    }

    public function resolveScope(string $role, array $institutionIds, array $barangayIds = []): array
    {
        validator(['barangay_ids' => $barangayIds], [
            'barangay_ids' => ['array'],
            'barangay_ids.*' => ['integer', 'exists:barangays,id'],
        ])->validate();

        if ($barangayIds && $role !== 'editor') {
            throw ValidationException::withMessages(['barangay_ids' => 'Only ICT Coordinators use barangay assignments.']);
        }

        $barangayInstitutions = Institution::query()
            ->whereIn('barangay_id', $barangayIds)
            ->where('is_active', true)
            ->whereIn('type', ['School', 'Barangay Hall'])
            ->get(['id', 'barangay_id']);

        foreach (array_unique($barangayIds) as $barangayId) {
            if (! $barangayInstitutions->contains('barangay_id', (int) $barangayId)) {
                throw ValidationException::withMessages(['barangay_ids' => 'Each selected barangay must have an active public school or barangay hall.']);
            }
        }

        $ids = array_values(array_unique(array_map('intval', array_merge(
            $institutionIds, $barangayInstitutions->pluck('id')->all()
        ))));
        $this->validateScope($role, $ids);

        return $ids;
    }

    public function validateScope(string $role, array $ids): void
    {
        if (! in_array($role, ['admin', 'editor', 'viewer'], true)) {
            abort(422);
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (($role !== 'editor' && $ids) || ($role === 'editor' && ! $ids)
         || Institution::whereIn('id', $ids)->where('is_active', true)->whereIn('type', ['School', 'Barangay Hall'])->count() !== count($ids)) {
            throw ValidationException::withMessages(['institution_ids' => 'Select valid active public schools or barangay halls for an Editor. Other roles do not use institution assignments.']);
        }
    }

    public function change(User $actor, string $id, bool $revoke): void
    {
        abort_unless($actor->isAdmin() && $actor->is_active, 403);
        if (! $revoke) {
            SecurityMail::requireConfigured();
        }
        DB::transaction(function () use ($actor, $id, $revoke) {
            $invite = UserInvitation::lockForUpdate()->findOrFail($id);
            abort_unless(in_array($invite->status, ['pending', 'expired'], true), 409, 'This invitation can no longer be changed.');
            if (! $revoke && UserInvitation::where('email', $invite->email)->where('status', 'pending')->whereKeyNot($invite->id)->exists()) {
                throw ValidationException::withMessages(['invitation' => 'Another pending invitation exists for this email. Manage that invitation instead.']);
            }
            if (! $revoke && $invite->updated_at->gt(now()->subMinute())) {
                throw ValidationException::withMessages(['invitation' => 'Wait 60 seconds before resending.']);
            }
            $invite->generation++;
            $invite->token_hash = null;
            if ($revoke) {
                $invite->status = 'revoked';
                $invite->revoked_at = now();
                $invite->revoked_by = $actor->id;
            } else {
                $invite->status = 'pending';
                $invite->expires_at = now()->addDay();
                $invite->delivery_status = 'queued';
            }
            $invite->save();
            SecurityAudit::record($revoke ? 'invitation.revoked' : 'invitation.resent', null, 'success', $id);
            if (! $revoke) {
                SecurityMail::queue('invitation', $id, $invite->generation);
            }
        });
    }

    public function exchange(string $id, string $token): array
    {
        $invite = UserInvitation::find($id);
        abort_unless($invite && $invite->status === 'pending' && $invite->expires_at->isFuture() && $invite->token_hash
         && hash_equals($invite->token_hash, hash('sha256', $token)), 422, 'This invitation is invalid or expired. Ask the System Admin for a new invitation.');

        return ['id' => $invite->id, 'generation' => $invite->generation, 'token_hash' => $invite->token_hash, 'expires' => now()->addMinutes(10)->timestamp];
    }

    public function accept(array $state, array $input, ?User $authenticated): User
    {
        return DB::transaction(function () use ($state, $input, $authenticated) {
            AccountAccess::lockAdministrators();
            $invite = UserInvitation::lockForUpdate()->find($state['id'] ?? '');
            abort_unless($invite && ($state['expires'] ?? 0) > time() && $invite->status === 'pending' && $invite->expires_at->isFuture()
             && $invite->generation === ($state['generation'] ?? null) && hash_equals((string) $invite->token_hash, (string) ($state['token_hash'] ?? '')), 422, 'This invitation is invalid or expired. Open the latest email link.');
            abort_unless(strtolower(trim($input['email'])) === $invite->email, 422, 'Confirm the email address that received this invitation.');
            $ids = $invite->institutions()->pluck('institutions.id')->all();
            $this->validateScope($invite->role, $ids);
            $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$invite->email])->lockForUpdate()->first();
            if ($authenticated) {
                abort_unless(strtolower(trim($authenticated->email)) === $invite->email, 403, 'Sign out and sign in with the invited email address.');
            }
            if ($user) {
                abort_unless($authenticated && $authenticated->is($user) && $user->is_active, 403, 'Sign in with the invited account before accepting.');
                if ($user->isAdmin() && $invite->role !== 'admin') {
                    AccountAccess::protectLastAdmin($user);
                }
            } else {
                validator($input, ['password' => PasswordPolicy::rules()])->validate();
                $user = new User(['name' => $invite->name, 'email' => $invite->email, 'password' => $input['password'], 'role' => $invite->role]);
            }
            $policies = app(LegalPolicies::class);
            $terms = $policies->document('terms');
            $privacy = $policies->document('privacy');
            $policyState = $policies->invitationPolicyState($terms, $privacy);
            abort_unless($policyState['can_accept'], 503, $policyState['message']);
            validator($input, ['accepted' => ['required', 'accepted'], 'privacy_accepted' => ['required', 'accepted'],
                'version' => ['required', 'string'], 'content_hash' => ['required', 'string'], 'privacy_hash' => ['required', 'string']])->validate();
            abort_unless(hash_equals($privacy['hash'], $input['privacy_hash']), 409, 'The Privacy Notice changed. Review it again.');
            $user->role = $invite->role;
            $user->email_verified_at = $user->email_verified_at ?? now();
            $user->invitation_accepted_at = now();
            $user->is_active = true;
            $user->save();
            $user->institutions()->sync($ids);
            DB::afterCommit(fn () => Cache::forget('map.institutions.geojson.v4.baseline.editor:'.$user->id));
            $receipt = $policies->accept($user, $input['version'], $input['content_hash']);
            DB::table('terms_acceptances')->where('id', $receipt->id)->update(['privacy_version' => $privacy['content']['version'], 'privacy_content_hash' => $privacy['hash'], 'privacy_snapshot' => $privacy['snapshot']]);
            AccountAccess::invalidate($user);
            $invite->update(['status' => 'accepted', 'accepted_at' => now(), 'accepted_user_id' => $user->id, 'token_hash' => null, 'generation' => $invite->generation + 1]);
            SecurityAudit::record('invitation.accepted', $user->id, 'success', $invite->id);
            SecurityAudit::record('account.activated', $user->id);
            SecurityMail::queue('activated', (string) $user->id, $user->security_version);

            return $user;
        });
    }
}
