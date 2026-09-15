<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with('institutions:id,name,type')
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'editor' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->paginate(20);

        $institutions = Institution::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return view('admin.users.index', compact('users', 'institutions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'editor', 'viewer'])],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->numbers(),
            ],
            'institution_ids' => ['nullable', 'array'],
            'institution_ids.*' => ['integer', Rule::exists('institutions', 'id')],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'password' => $validated['password'],
            ]);

            $this->syncAssignments(
                $user,
                $validated['role'],
                $validated['institution_ids'] ?? []
            );
        });

        return back()->with('success', 'User account created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', Rule::in(['admin', 'editor', 'viewer'])],
            'password' => [
                'nullable',
                'confirmed',
                Password::min(12)->mixedCase()->numbers(),
            ],
            'institution_ids' => ['nullable', 'array'],
            'institution_ids.*' => ['integer', Rule::exists('institutions', 'id')],
        ]);

        if (
            $user->isAdmin()
            && $validated['role'] !== 'admin'
            && $this->isLastAdministrator($user)
        ) {
            return back()
                ->withErrors(['role' => 'The last administrator cannot be demoted.'])
                ->withInput();
        }

        DB::transaction(function () use ($validated, $user): void {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
            ]);

            if (! empty($validated['password'])) {
                $user->password = $validated['password'];
            }

            $user->save();

            $this->syncAssignments(
                $user,
                $validated['role'],
                $validated['institution_ids'] ?? []
            );

            if (! empty($validated['password'])) {
                $user->tokens()->delete();
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

        if ($user->isAdmin() && $this->isLastAdministrator($user)) {
            return back()->withErrors([
                'user' => 'The last administrator account cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->institutions()->detach();
            $user->delete();
        });

        return back()->with('success', 'User account deleted successfully.');
    }

    private function syncAssignments(User $user, string $role, array $institutionIds): void
    {
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
        return $user->isAdmin()
            && User::query()->where('role', 'admin')->count() <= 1;
    }
}
