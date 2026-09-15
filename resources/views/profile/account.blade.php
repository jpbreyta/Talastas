@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-[0.14em] text-red-700">Account</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900">User profile</h1>
        <p class="mt-1 text-sm text-slate-600">
            Review your authenticated account information and manage your password.
        </p>
    </div>

    @if (session('status') === 'password-updated')
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            Password updated successfully. Existing API/mobile tokens were revoked.
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_1.1fr]">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Profile information</h2>
            <p class="mt-1 text-sm text-slate-500">
                Identity and role fields are controlled by the System Administrator.
            </p>

            <dl class="mt-6 space-y-5">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Name</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email / account identifier</dt>
                    <dd class="mt-1 break-all text-sm font-medium text-slate-900">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Assigned role</dt>
                    <dd class="mt-1">
                        <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-800 ring-1 ring-inset ring-red-200">
                            {{ $user->roleLabel() }}
                        </span>
                    </dd>
                </div>
            </dl>

            <div class="mt-6 rounded-xl bg-slate-50 p-4 text-xs leading-5 text-slate-600">
                For accountability and role-based access control, contact the System Administrator if your name, account identifier, or assigned role is incorrect.
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Change password</h2>
            <p class="mt-1 text-sm text-slate-500">
                Use a strong password that is not reused on another service.
            </p>

            <form method="POST" action="{{ route('profile.password.update') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="text-sm font-medium text-slate-700">Current password</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-red-700 focus:ring-red-700" />
                    @error('current_password')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="text-sm font-medium text-slate-700">New password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-red-700 focus:ring-red-700" />
                    <p class="mt-1 text-xs text-slate-500">Minimum 12 characters with uppercase, lowercase, and a number.</p>
                    @error('password')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="text-sm font-medium text-slate-700">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-red-700 focus:ring-red-700" />
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="inline-flex items-center rounded-lg bg-red-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2">
                        Update password
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
