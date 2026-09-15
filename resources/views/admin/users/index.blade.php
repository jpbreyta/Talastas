@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-red-700">Administration</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">User Management</h1>
            <p class="mt-1 text-sm text-slate-600">
                Manage portal roles and institution assignments for Talastas users.
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4">
            <h2 class="text-base font-semibold text-slate-900">Create user</h2>
            <p class="mt-1 text-sm text-slate-500">
                Institution assignments apply only to Editor / ICT Coordinator accounts.
            </p>
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}" class="grid gap-4 lg:grid-cols-2">
            @csrf

            <div>
                <label class="text-sm font-medium text-slate-700">Name</label>
                <input name="name" value="{{ old('name') }}" required
                    class="mt-1 block w-full rounded-lg border-slate-300 focus:border-red-700 focus:ring-red-700">
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" required
                    class="mt-1 block w-full rounded-lg border-slate-300 focus:border-red-700 focus:ring-red-700">
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700">Role</label>
                <select name="role" required
                    class="mt-1 block w-full rounded-lg border-slate-300 focus:border-red-700 focus:ring-red-700">
                    <option value="viewer">LGU City Planner / Viewer</option>
                    <option value="editor">Barangay Focal Person / IT Coordinator</option>
                    <option value="admin">System Administrator</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700">Assigned institutions</label>
                <select name="institution_ids[]" multiple size="5"
                    class="mt-1 block w-full rounded-lg border-slate-300 focus:border-red-700 focus:ring-red-700">
                    @foreach ($institutions as $institution)
                        <option value="{{ $institution->id }}">
                            {{ $institution->name }} — {{ $institution->type }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Hold Ctrl/Cmd to select multiple institutions.</p>
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700">Temporary password</label>
                <input name="password" type="password" required autocomplete="new-password"
                    class="mt-1 block w-full rounded-lg border-slate-300 focus:border-red-700 focus:ring-red-700">
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700">Confirm password</label>
                <input name="password_confirmation" type="password" required autocomplete="new-password"
                    class="mt-1 block w-full rounded-lg border-slate-300 focus:border-red-700 focus:ring-red-700">
            </div>

            <div class="lg:col-span-2 flex justify-end">
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-red-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-900">
                    <i class="fa-solid fa-user-plus"></i>
                    Create account
                </button>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Existing users</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $users->total() }} account(s)</p>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach ($users as $user)
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="p-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 lg:grid-cols-[1.1fr_1.2fr_1fr_1.5fr_auto] lg:items-start">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-400">Name</label>
                            <input name="name" value="{{ $user->name }}" required
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-red-700 focus:ring-red-700">
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email</label>
                            <input name="email" type="email" value="{{ $user->email }}" required
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-red-700 focus:ring-red-700">
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-400">Role</label>
                            <select name="role" required
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-red-700 focus:ring-red-700">
                                <option value="admin" @selected($user->role === 'admin')>System Administrator</option>
                                <option value="editor" @selected($user->role === 'editor')>ICT Coordinator / Editor</option>
                                <option value="viewer" @selected($user->role === 'viewer')>LGU City Planner / Viewer</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-400">Institution assignment</label>
                            <select name="institution_ids[]" multiple size="4"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-red-700 focus:ring-red-700">
                                @foreach ($institutions as $institution)
                                    <option value="{{ $institution->id }}"
                                        @selected($user->institutions->contains('id', $institution->id))>
                                        {{ $institution->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($user->role !== 'editor')
                                <p class="mt-1 text-xs text-slate-400">Ignored unless role is Editor.</p>
                            @endif
                        </div>

                        <div class="flex gap-2 lg:pt-5">
                            <button type="submit"
                                class="inline-flex h-9 items-center rounded-lg bg-red-800 px-3 text-sm font-semibold text-white hover:bg-red-900">
                                Save
                            </button>

                            @if (! auth()->user()->is($user))
                                <button type="submit"
                                    formaction="{{ route('admin.users.destroy', $user) }}"
                                    formmethod="POST"
                                    name="_method"
                                    value="DELETE"
                                    onclick="return confirm('Delete this user account?')"
                                    class="inline-flex h-9 items-center rounded-lg border border-red-200 bg-red-50 px-3 text-sm font-semibold text-red-700 hover:bg-red-100">
                                    Delete
                                </button>
                            @endif
                        </div>
                    </div>

                    <details class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <summary class="cursor-pointer text-sm font-medium text-slate-700">Reset password</summary>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <input name="password" type="password" placeholder="New password" autocomplete="new-password"
                                class="rounded-lg border-slate-300 text-sm focus:border-red-700 focus:ring-red-700">
                            <input name="password_confirmation" type="password" placeholder="Confirm password" autocomplete="new-password"
                                class="rounded-lg border-slate-300 text-sm focus:border-red-700 focus:ring-red-700">
                        </div>
                    </details>
                </form>
            @endforeach
        </div>

        <div class="border-t border-slate-200 px-5 py-4">
            {{ $users->links() }}
        </div>
    </section>
</div>
@endsection
