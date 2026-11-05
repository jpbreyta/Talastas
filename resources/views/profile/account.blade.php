@extends('layouts.app')

@section('page-title', 'User Profile')
@section('page-icon', 'fa-user')

@section('content')
<style>
    .profile-input {
        transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.2s ease;
    }

    .profile-input:hover {
        border-color: #94a3b8;
    }

    .profile-input:focus {
        border-color: #7A1F2B;
        box-shadow: 0 0 0 4px rgba(122, 31, 43, 0.10), 0 4px 12px rgba(122, 31, 43, 0.08);
        transform: translateY(-1px);
    }

    .profile-button {
        position: relative;
        overflow: hidden;
        transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
    }

    .profile-button::before {
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

    .profile-button:hover::before {
        width: 300px;
        height: 300px;
    }

    .profile-button:hover {
        background-color: #58151E;
        box-shadow: 0 10px 25px rgba(88, 21, 30, 0.25), 0 0 15px rgba(214, 168, 75, 0.15);
        transform: translateY(-2px);
    }

    .profile-button:active {
        transform: translateY(0) scale(0.98);
    }

    .profile-card {
        transition: box-shadow 0.3s ease, transform 0.2s ease;
    }

    .profile-card:hover {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08), 0 0 1px rgba(0, 0, 0, 0.05);
        transform: translateY(-2px);
    }

    .toggle-password-btn {
        transition: color 0.2s ease;
    }

    .toggle-password-btn:hover {
        color: #7A1F2B;
    }

    .success-alert {
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

<div class="mx-auto w-full max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <x-page-header title="User profile" subtitle="Review your account and manage your password." class="mb-4">

    </x-page-header>

    <!-- Success Message -->
    @if (session('status') === 'password-updated')
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-3 shadow-sm success-alert">
            <div class="flex items-start gap-2.5">
                <div class="flex items-center justify-center w-6 h-6 rounded-md bg-emerald-100 text-emerald-600 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-emerald-900">Password updated successfully</p>
                    <p class="mt-0.5 text-[11px] text-emerald-700">Existing API/mobile tokens were revoked for security.</p>
                </div>
            </div>
        </div>
    @endif

    <section aria-label="Policies" class="mb-5 rounded-xl border border-gray-200 bg-white p-4 text-sm">
        @include('partials.legal-links')
        <a href="{{ route('legal.review') }}" class="mt-3 inline-block font-medium text-red-900 underline">Review Terms status and my acceptance history</a>
    </section>

    <!-- Main Grid -->
    <div class="grid gap-5 lg:grid-cols-[1fr_1.1fr]">
        
        <!-- Profile Information Card -->
        <section class="profile-card rounded-xl border border-[#7A1F2B]/20 bg-white p-5 shadow-md">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-[#7A1F2B]/10 text-[#7A1F2B]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-[#7A1F2B]">Profile information</h2>
                    <p class="text-[11px] text-slate-500">
                        Controlled by System Administrator
                    </p>
                </div>
            </div>

            <dl class="mt-4 space-y-3.5">
                <!-- Name -->
                <div class="rounded-lg bg-[#F7ECEE] p-3 border border-[#7A1F2B]/15">
                    <dt class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-[#7A1F2B]/70">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Name
                    </dt>
                    <dd class="mt-1.5 text-sm font-semibold text-[#7A1F2B]">{{ $user->name }}</dd>
                </div>

                <!-- Email -->
                <div class="rounded-lg bg-[#F7ECEE] p-3 border border-[#7A1F2B]/15">
                    <dt class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-[#7A1F2B]/70">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Email / account identifier
                    </dt>
                    <dd class="mt-1.5 break-all text-sm font-semibold text-[#7A1F2B]">{{ $user->email }}</dd>
                </div>

                <!-- Role -->
                <div class="rounded-lg bg-[#F7ECEE] p-3 border border-[#7A1F2B]/15">
                    <dt class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-[#7A1F2B]/70">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Assigned role
                    </dt>
                    <dd class="mt-1.5">
                        <span class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-[#7A1F2B] to-[#58151E] px-2.5 py-1 text-[11px] font-bold text-white shadow-md">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                            {{ $user->roleLabel() }}
                        </span>
                    </dd>
                </div>
            </dl>

            <div class="mt-4 rounded-lg bg-amber-50 border border-amber-200 p-3">
                <div class="flex gap-2.5">
                    <div class="flex items-center justify-center w-6 h-6 rounded-md bg-amber-100 text-amber-700 shrink-0">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <p class="text-[11px] leading-relaxed text-amber-900">
                        For accountability and role-based access control, contact the System Administrator if your name, account identifier, or assigned role is incorrect.
                    </p>
                </div>
            </div>
        </section>

        <!-- Change Password Card -->
        <section class="profile-card rounded-xl border border-[#7A1F2B]/20 bg-white p-5 shadow-md">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-[#7A1F2B]/10 text-[#7A1F2B]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-[#7A1F2B]">Change password</h2>
                    <p class="text-[11px] text-slate-500">
                        Use a strong, unique password
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.password.update') }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <!-- Current Password -->
                <div>
                    <label for="current_password" class="block text-xs font-semibold text-[#7A1F2B] mb-1.5">
                        Current password
                    </label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[#7A1F2B]/40">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required
                            class="profile-input block w-full pl-9 pr-9 py-2.5 border border-[#7A1F2B]/30 rounded-lg text-sm text-slate-900 placeholder:text-slate-400 outline-none shadow-sm" 
                            placeholder="Enter current password" />
                        <button type="button" class="toggle-password-btn absolute inset-y-0 right-0 flex items-center pr-3 text-[#7A1F2B]/40" 
                            onclick="togglePasswordVisibility('current_password', this)">
                            <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.223-3.592m3.31-2.048A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.132 5.411M15 12a3 3 0 01-3 3m-2.121-5.121A3 3 0 0112 9m9 12L3 3" />
                            </svg>
                        </button>
                    </div>
                    @error('current_password')
                        <div class="flex items-center gap-1 mt-1.5 text-[11px] text-red-700">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <!-- New Password -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-[#7A1F2B] mb-1.5">
                        New password
                    </label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[#7A1F2B]/40">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z" />
                            </svg>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="new-password" required
                            class="profile-input block w-full pl-9 pr-9 py-2.5 border border-[#7A1F2B]/30 rounded-lg text-sm text-slate-900 placeholder:text-slate-400 outline-none shadow-sm" 
                            placeholder="Enter new password" />
                        <button type="button" class="toggle-password-btn absolute inset-y-0 right-0 flex items-center pr-3 text-[#7A1F2B]/40" 
                            onclick="togglePasswordVisibility('password', this)">
                            <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.223-3.592m3.31-2.048A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.132 5.411M15 12a3 3 0 01-3 3m-2.121-5.121A3 3 0 0112 9m9 12L3 3" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-1.5 text-[11px] text-[#7A1F2B]/60 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        Minimum 12 characters with uppercase, lowercase, and a number.
                    </p>
                    @error('password')
                        <div class="flex items-center gap-1 mt-1.5 text-[11px] text-red-700">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-[#7A1F2B] mb-1.5">
                        Confirm new password
                    </label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[#7A1F2B]/40">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                            class="profile-input block w-full pl-9 pr-9 py-2.5 border border-[#7A1F2B]/30 rounded-lg text-sm text-slate-900 placeholder:text-slate-400 outline-none shadow-sm" 
                            placeholder="Re-enter new password" />
                        <button type="button" class="toggle-password-btn absolute inset-y-0 right-0 flex items-center pr-3 text-[#7A1F2B]/40" 
                            onclick="togglePasswordVisibility('password_confirmation', this)">
                            <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.223-3.592m3.31-2.048A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.132 5.411M15 12a3 3 0 01-3 3m-2.121-5.121A3 3 0 0112 9m9 12L3 3" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="profile-button inline-flex items-center gap-2 rounded-lg bg-[#7A1F2B] px-5 py-2.5 text-xs font-semibold text-white shadow-md focus:outline-none focus:ring-4 focus:ring-[#7A1F2B]/20 relative overflow-hidden">
                        <span class="relative z-10">Update password</span>
                        <svg class="w-3.5 h-3.5 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>

<script>
    function togglePasswordVisibility(inputId, button) {
        const input = document.getElementById(inputId);
        const eyeOpen = button.querySelector('.eye-open');
        const eyeClosed = button.querySelector('.eye-closed');
        
        if (input.type === 'password') {
            input.type = 'text';
            eyeOpen.classList.add('hidden');
            eyeClosed.classList.remove('hidden');
        } else {
            input.type = 'password';
            eyeOpen.classList.remove('hidden');
            eyeClosed.classList.add('hidden');
        }
    }
</script>
@endsection
