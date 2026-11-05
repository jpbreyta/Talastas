<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use App\Services\AccountAccess;
use App\Services\InvitationService;
use App\Services\SecurityAudit;
use App\Services\SecurityMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'log_search' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim((string) $request->query('search', ''));
        $users = User::query()
            ->with('institutions:id,name,type')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'editor' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->paginate(20)->withQueryString();

        $institutions = Institution::query()
            ->where('is_active', true)
            ->whereIn('type', ['School', 'Barangay Hall'])
            ->with('barangay:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'barangay_id']);

        return view('admin.users.index', array_merge(
            compact('users', 'institutions'),
            app(UserActivityController::class)->activityData(trim((string) $request->query('log_search', '')))
        ));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.invitations.index');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('admin.invitations.index')->withErrors(['user' => 'Create an invitation. Administrators cannot set passwords for other users.']);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', 'string', Rule::in(['admin', 'editor', 'viewer'])],
            'password' => ['prohibited'], 'password_confirmation' => ['prohibited'],
            'barangay_ids' => ['nullable', 'array'],
            'barangay_ids.*' => ['integer', Rule::exists('barangays', 'id')],
            'institution_ids' => ['nullable', 'array'],
            'institution_ids.*' => ['integer', Rule::exists('institutions', 'id')],
        ]);

        if (User::whereRaw('LOWER(TRIM(email)) = ?', [$validated['email']])->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'This email already belongs to an account.']);
        }
        abort_if($request->user()->is($user), 422, 'Use your profile to manage your own account.');
        $validated['institution_ids'] = app(InvitationService::class)->resolveScope(
            $validated['role'], $validated['institution_ids'] ?? [], $validated['barangay_ids'] ?? []
        );
        DB::transaction(function () use ($validated, $user): void {
            AccountAccess::lockAdministrators();
            $user = User::lockForUpdate()->findOrFail($user->id);
            if ($validated['role'] !== 'admin' && $this->isLastAdministrator($user)) {
                throw ValidationException::withMessages(['role' => 'The last administrator cannot be demoted.']);
            }
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
            ]);

            $user->save();

            $this->syncAssignments(
                $user,
                $validated['role'],
                $validated['institution_ids'] ?? []
            );

            AccountAccess::invalidate($user);
            SecurityAudit::record('account.role_scope_updated', $user->id);
            if (SecurityMail::available()) {
                SecurityMail::queue('role_changed', (string) $user->id, $user->security_version);
            } else {
                SecurityAudit::record('notification.unavailable', $user->id, 'failed');
            }
        });

        return back()->with('success', 'User account updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors([
                'user' => 'You cannot delete the account you are currently using.',
            ]);
        }

        DB::transaction(function () use ($user): void {
            AccountAccess::lockAdministrators();
            if ($this->isLastAdministrator($user)) {
                throw ValidationException::withMessages(['user' => 'The last administrator account cannot be deleted.']);
            }
            $user->tokens()->delete();
            $user->institutions()->detach();
            $user->delete();
        });

        return back()->with('success', 'User account deleted successfully.');
    }

    private function syncAssignments(User $user, string $role, array $institutionIds): void
    {
        DB::afterCommit(fn () => Cache::forget('map.institutions.geojson.v4.baseline.editor:'.$user->id));

        if ($role !== 'editor') {
            $user->institutions()->sync([]);

            return;
        }

        $user->institutions()->sync(
            collect($institutionIds)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all()
        );
    }

    private function isLastAdministrator(User $user): bool
    {
        // Serialize removals of administrator privileges within the write transaction.
        $administrators = User::query()->where('role', 'admin')->where('is_active', true)->orderBy('id')->lockForUpdate()->get(['id']);

        return $administrators->contains('id', $user->id) && $administrators->count() <= 1;
    }
}
