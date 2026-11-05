<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\AccountAccess;
use App\Services\InvitationService;
use App\Services\MfaService;
use App\Services\RecoveryService;
use App\Services\SecurityAudit;
use App\Services\SecurityMail;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvitationManagementController extends Controller
{
    public function index(Request $request)
    {
        $invitations = UserInvitation::with('institutions')->latest()->paginate(20);
        $institutions = Institution::with('barangay:id,name')->where('is_active', true)->whereIn('type', ['School', 'Barangay Hall'])->orderBy('name')->get();
        $events = DB::table('security_events')->orderByDesc('id')->limit(50)->get();

        return view('admin.users.invitations', compact('invitations', 'institutions', 'events'));
    }

    private function confirm(Request $request): void
    {
        $request->validate(['confirmed' => ['required', 'accepted'], 'current_password' => ['required', 'current_password']]);
    }

    public function store(Request $request, InvitationService $service)
    {
        $this->confirm($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'], 'role' => ['required', 'in:admin,editor,viewer'],
            'barangay_ids' => ['nullable', 'array'], 'barangay_ids.*' => ['integer', 'distinct', 'exists:barangays,id'],
            'institution_ids' => ['nullable', 'array'], 'institution_ids.*' => ['integer', 'distinct'], 'existing_account_confirmed' => ['sometimes', 'accepted']]);
        try {
            $service->create($request->user(), $data);
        } catch (UniqueConstraintViolationException $e) {
            return back()->withErrors(['email' => 'A pending invitation already exists for this email.']);
        }

        return back()->with('success', 'Invitation queued. Transport acceptance and invitation acceptance are tracked separately.');
    }

    public function resend(Request $request, string $id, InvitationService $service)
    {
        $this->confirm($request);
        try {
            $service->change($request->user(), $id, false);
        } catch (UniqueConstraintViolationException $exception) {
            return back()->withErrors(['invitation' => 'Another pending invitation exists for this email. Manage that invitation instead.']);
        }

        return back()->with('success', 'A replacement invitation was queued. Older links and setup sessions are invalid.');
    }

    public function revoke(Request $request, string $id, InvitationService $service)
    {
        $this->confirm($request);
        $service->change($request->user(), $id, true);

        return back()->with('success', 'Invitation revoked.');
    }

    public function reset(Request $request, User $user, RecoveryService $service, MfaService $mfa)
    {
        $this->confirm($request);
        abort_if($request->user()->is($user) || ! $user->is_active, 422);
        SecurityMail::requireConfigured();
        if ($user->isAdmin()) {
            $request->validate(['email_code' => ['required', 'digits:6']]);
            abort_unless($user->mfa_confirmed_at, 409, 'This administrator must verify their email at sign-in before remote password recovery is available.');
            if (! $mfa->verify($request->user(), $request->input('email_code'))) {
                throw ValidationException::withMessages(['email_code' => 'A new six-digit email code is required.']);
            }
        }
        $service->request($user->email, true);

        return back()->with('success', 'If eligible, the account will receive recovery instructions. No password was set by the administrator.');
    }

    public function status(Request $request, User $user)
    {
        $this->confirm($request);
        abort_if($request->user()->is($user), 422, 'You cannot deactivate your own account.');
        DB::transaction(function () use ($request, $user) {
            AccountAccess::lockAdministrators();
            $user = User::lockForUpdate()->findOrFail($user->id);
            if ($user->is_active) {
                AccountAccess::protectLastAdmin($user);
            }
            $user->is_active = ! $user->is_active;
            $user->deactivated_at = $user->is_active ? null : now();
            $user->deactivated_by = $user->is_active ? null : $request->user()->id;
            AccountAccess::invalidate($user);
            SecurityAudit::record($user->is_active ? 'account.reactivated' : 'account.deactivated', $user->id);
            if (SecurityMail::available()) {
                SecurityMail::queue($user->is_active ? 'activated' : 'deactivated', (string) $user->id, $user->security_version);
            } else {
                SecurityAudit::record('notification.unavailable', $user->id, 'failed');
            }
        });

        return back()->with('success', 'Account access updated. Existing sessions and API tokens were invalidated.');
    }
}
