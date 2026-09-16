@extends('layouts.app')

@section('content')
<style>
    .user-input {
        transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.2s ease;
    }

    .user-input:hover {
        border-color: #94a3b8;
    }

    .user-input:focus {
        border-color: #7A1F2B;
        box-shadow: 0 0 0 4px rgba(122, 31, 43, 0.10), 0 4px 12px rgba(122, 31, 43, 0.08);
        transform: translateY(-1px);
    }

    .user-button {
        position: relative;
        overflow: hidden;
        transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
    }

    .user-button::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        transform: translate(-50%, -50%);
        transition: width 0.6s ease, height 0.6s ease;
    }

    .user-button:hover::before {
        width: 300px;
        height: 300px;
    }

    .user-button:hover {
        background-color: #58151E;
        box-shadow: 0 10px 25px rgba(88, 21, 30, 0.25), 0 0 15px rgba(214, 168, 75, 0.15);
        transform: translateY(-2px);
    }

    .user-button:active {
        transform: translateY(0) scale(0.98);
    }

    .user-card {
        transition: box-shadow 0.3s ease, transform 0.2s ease;
    }

    .user-card:hover {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08), 0 0 1px rgba(0, 0, 0, 0.05);
        transform: translateY(-2px);
    }

    .alert-slide {
        animation: slideDown 0.4s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-[#7A1F2B] to-[#58151E] flex items-center justify-center shadow-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-[#7A1F2B]">Administration</p>
                <h1 class="text-2xl font-bold text-[#7A1F2B]">User Management</h1>
                <p class="mt-0.5 text-sm text-slate-600">
                    Manage portal roles and institution assignments for Talastas users.
                </p>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm alert-slide">
            <div class="flex items-start gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-emerald-900">Success</p>
                    <p class="mt-0.5 text-sm text-emerald-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Error Messages -->
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm alert-slide">
            <div class="flex items-start gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-red-100 text-red-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-red-900">Errors occurred</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Create User Section -->
    <section class="user-card mb-6 rounded-2xl border border-[#7A1F2B]/20 bg-white p-6 shadow-md">
        <div class="mb-5 flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-[#7A1F2B]/10 text-[#7A1F2B]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
            <div>
                <h2 class="text-base font-semibold text-[#7A1F2B]">Create user</h2>
                <p class="text-sm text-slate-500">
                    Institution assignments apply only to Editor / ICT Coordinator accounts.
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}" class="grid gap-4 lg:grid-cols-2">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-[#7A1F2B] mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Name
                </label>
                <input name="name" value="{{ old('name') }}" required placeholder="Enter full name"
                    class="user-input block w-full rounded-lg border border-[#7A1F2B]/30 px-3 py-2.5 text-sm outline-none shadow-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#7A1F2B] mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Email
                </label>
                <input name="email" type="email" value="{{ old('email') }}" required placeholder="user@example.com"
                    class="user-input block w-full rounded-lg border border-[#7A1F2B]/30 px-3 py-2.5 text-sm outline-none shadow-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#7A1F2B] mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Role
                </label>
                <select name="role" required
                    class="user-input block w-full rounded-lg border border-[#7A1F2B]/30 px-3 py-2.5 text-sm outline-none shadow-sm">
                    <option value="viewer">LGU City Planner / Viewer</option>
                    <option value="editor">Barangay Focal Person / IT Coordinator</option>
                    <option value="admin">System Administrator</option>
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold text-[#7A1F2B] mb-3">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Assigned institutions
                </label>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 p-4 rounded-lg border border-[#7A1F2B]/20 bg-[#F7ECEE]/30 max-h-60 overflow-y-auto">
                    @foreach ($institutions as $institution)
                        <label class="flex items-start gap-2.5 p-2.5 rounded-lg border border-[#7A1F2B]/10 bg-white hover:bg-[#F7ECEE]/50 hover:border-[#7A1F2B]/30 cursor-pointer transition-all duration-200">
                            <input type="checkbox" name="institution_ids[]" value="{{ $institution->id }}"
                                class="mt-0.5 h-4 w-4 rounded border-[#7A1F2B]/30 text-[#7A1F2B] focus:ring-[#7A1F2B] focus:ring-offset-0 cursor-pointer">
                            <span class="text-xs text-slate-700 leading-tight">
                                <span class="font-semibold text-[#7A1F2B]">{{ $institution->name }}</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5">{{ $institution->type }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-[#7A1F2B]/60">Select one or more institutions to assign.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#7A1F2B] mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z" />
                    </svg>
                    Temporary password
                </label>
                <input name="password" type="password" required autocomplete="new-password" placeholder="Enter password"
                    class="user-input block w-full rounded-lg border border-[#7A1F2B]/30 px-3 py-2.5 text-sm outline-none shadow-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#7A1F2B] mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Confirm password
                </label>
                <input name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Confirm password"
                    class="user-input block w-full rounded-lg border border-[#7A1F2B]/30 px-3 py-2.5 text-sm outline-none shadow-sm">
            </div>

            <div class="lg:col-span-2 flex justify-end pt-2">
                <button type="submit"
                    class="user-button inline-flex items-center gap-2 rounded-lg bg-[#7A1F2B] px-5 py-2.5 text-sm font-semibold text-white shadow-md focus:outline-none focus:ring-4 focus:ring-[#7A1F2B]/20 relative overflow-hidden">
                    <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span class="relative z-10">Create account</span>
                </button>
            </div>
        </form>
    </section>

    <!-- Existing Users Section -->
    <section class="overflow-hidden rounded-2xl border border-[#7A1F2B]/20 bg-white shadow-md">
        <div class="border-b border-[#7A1F2B]/10 bg-gradient-to-r from-[#F7ECEE] to-white px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-[#7A1F2B]/10 text-[#7A1F2B]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-[#7A1F2B]">Existing users</h2>
                    <p class="text-sm text-[#7A1F2B]/60">{{ $users->total() }} account(s)</p>
                </div>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach ($users as $user)
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="p-5 hover:bg-slate-50/50 transition-colors duration-200">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 lg:grid-cols-3 lg:items-start">
                        <div>
                            <label class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-[#7A1F2B]/70 mb-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Name
                            </label>
                            <input name="name" value="{{ $user->name }}" required
                                class="user-input block w-full rounded-lg border border-[#7A1F2B]/30 px-2.5 py-2 text-sm outline-none shadow-sm">
                        </div>

                        <div>
                            <label class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-[#7A1F2B]/70 mb-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Email
                            </label>
                            <input name="email" type="email" value="{{ $user->email }}" required
                                class="user-input block w-full rounded-lg border border-[#7A1F2B]/30 px-2.5 py-2 text-sm outline-none shadow-sm">
                        </div>

                        <div>
                            <label class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-[#7A1F2B]/70 mb-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Role
                            </label>
                            <select name="role" required
                                class="user-input block w-full rounded-lg border border-[#7A1F2B]/30 px-2.5 py-2 text-sm outline-none shadow-sm">
                                <option value="admin" @selected($user->role === 'admin')>System Administrator</option>
                                <option value="editor" @selected($user->role === 'editor')>ICT Coordinator / Editor</option>
                                <option value="viewer" @selected($user->role === 'viewer')>LGU City Planner / Viewer</option>
                            </select>
                        </div>

                        <div class="lg:col-span-5">
                            <label class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-[#7A1F2B]/70 mb-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Institution assignment
                            </label>
                            <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 p-3 rounded-lg border border-[#7A1F2B]/15 bg-[#F7ECEE]/20 max-h-40 overflow-y-auto">
                                @foreach ($institutions as $institution)
                                    <label class="flex items-start gap-2 p-2 rounded-md border border-[#7A1F2B]/10 bg-white hover:bg-[#F7ECEE]/40 hover:border-[#7A1F2B]/25 cursor-pointer transition-all duration-200">
                                        <input type="checkbox" name="institution_ids[]" value="{{ $institution->id }}"
                                            @checked($user->institutions->contains('id', $institution->id))
                                            class="mt-0.5 h-3.5 w-3.5 rounded border-[#7A1F2B]/30 text-[#7A1F2B] focus:ring-[#7A1F2B] focus:ring-offset-0 cursor-pointer">
                                        <span class="text-xs text-slate-700 leading-tight font-medium">{{ $institution->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if ($user->role !== 'editor')
                                <p class="mt-1.5 text-xs text-[#7A1F2B]/50">Ignored unless role is Editor.</p>
                            @endif
                        </div>

                        <div class="flex gap-2 lg:pt-6">
                            <button type="submit"
                                class="user-button inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#7A1F2B] px-3 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-4 focus:ring-[#7A1F2B]/20 relative overflow-hidden">
                                <svg class="w-3.5 h-3.5 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="relative z-10">Save</span>
                            </button>

                            @if (! auth()->user()->is($user))
                                <button type="submit"
                                    formaction="{{ route('admin.users.destroy', $user) }}"
                                    formmethod="POST"
                                    name="_method"
                                    value="DELETE"
                                    onclick="return confirm('Delete this user account?')"
                                    class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-red-300 bg-red-50 px-3 text-sm font-semibold text-red-700 hover:bg-red-100 transition-colors duration-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete
                                </button>
                            @endif
                        </div>
                    </div>

                    <details class="mt-4 rounded-lg border border-[#7A1F2B]/15 bg-[#F7ECEE] p-3">
                        <summary class="cursor-pointer text-sm font-semibold text-[#7A1F2B] flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z" />
                            </svg>
                            Reset password
                        </summary>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <input name="password" type="password" placeholder="New password" autocomplete="new-password"
                                class="user-input rounded-lg border border-[#7A1F2B]/30 px-3 py-2 text-sm outline-none shadow-sm">
                            <input name="password_confirmation" type="password" placeholder="Confirm password" autocomplete="new-password"
                                class="user-input rounded-lg border border-[#7A1F2B]/30 px-3 py-2 text-sm outline-none shadow-sm">
                        </div>
                    </details>
                </form>
            @endforeach
        </div>

        <div class="border-t border-[#7A1F2B]/10 bg-gradient-to-r from-white to-[#F7ECEE]/50 px-5 py-4">
            {{ $users->links() }}
        </div>
    </section>
</div>
@endsection
