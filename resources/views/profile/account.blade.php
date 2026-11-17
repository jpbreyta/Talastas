@extends('layouts.app')

@section('page-title', 'User Profile')
@section('page-icon', 'fa-user')

@section('content')
@php
    $profileTab = $errors->getBag('profileName')->any() ? 'personal'
        : (($errors->getBag('profileEmail')->any() || $errors->getBag('profileEmailConfirm')->any()) ? 'email'
        : ($errors->getBag('default')->any() ? 'security' : ($pendingEmailChange ? 'email' : 'personal')));
    $initials = collect(preg_split('/\s+/u', trim($user->name), -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $assignedInstitutionCount = $user->relationLoaded('institutions')
        ? $user->institutions->count() : $user->institutions()->count();
@endphp
<style>
    .profile-settings { --profile-maroon: #75151d; min-width: 0; }
    .profile-settings .profile-tabs { display: flex; gap: .5rem; min-width: 0; overflow-x: auto; border-bottom: 1px solid #dfe2e7; }
    .profile-settings .profile-tabs button { flex-shrink: 0; min-height: 44px; border-bottom: 3px solid transparent; padding: .75rem 1rem; font-size: .875rem; font-weight: 500; color: #374151; white-space: nowrap; }
    .profile-settings .profile-tabs button[aria-selected="true"] { border-color: var(--profile-maroon); color: var(--profile-maroon); }
    .profile-settings .profile-tabs button:hover { color: var(--profile-maroon); background: #faf7f7; }
    .profile-settings :is(a, button, [role="tabpanel"]):focus-visible { outline: 2px solid var(--profile-maroon); outline-offset: 3px; }
    .profile-settings .profile-input { min-width: 0; min-height: 44px; }
    .profile-settings .profile-input:focus { outline: none; border-color: var(--profile-maroon); box-shadow: 0 0 0 3px #75151d15; }
    .profile-settings .profile-button:hover { background: #58151E; }
    .profile-settings .toggle-password-btn { min-width: 44px; min-height: 44px; justify-content: center; padding-right: 0; }
    .profile-settings .toggle-password-btn:hover { color: var(--profile-maroon); }
    .profile-settings [role="tabpanel"][hidden] { display: none; }
</style>
<noscript><style>.profile-settings [role="tabpanel"][hidden] { display: block; } .profile-tabs { display: none !important; }</style></noscript>

<div class="min-w-0 w-full px-4 py-5 sm:px-6 lg:px-8">
    <div class="min-w-0 w-full">
    <!-- Page Header -->
    <x-page-header title="User profile" subtitle="Manage your personal information, email address, and password." class="mb-4">

    </x-page-header>

    <!-- Success Message -->
    @if (session('status') === 'password-updated')
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-3 shadow-sm" role="status">
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

    <!-- Main Grid -->
    @if (session('profile_status'))
        <p role="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('profile_status') }}</p>
    @endif
    <div class="profile-settings overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" data-profile-settings>
        <header class="flex items-center gap-4 border-b border-gray-200 px-5 py-4 sm:px-6 sm:py-5">
            <div aria-hidden="true" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[#7A1F2B]/10 text-xl font-semibold text-[#7A1F2B]">{{ $initials }}</div>
            <div class="min-w-0">
                <h2 class="break-words text-lg font-semibold text-gray-900">{{ $user->name }}</h2>
                <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-600">
                    <span>{{ $user->roleLabel() }}</span>
                    <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium {{ $user->is_active ? 'bg-green-50 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
            </div>
        </header>
        <div class="profile-tabs px-2 sm:px-4" role="tablist" aria-label="Profile settings">
            @foreach (['personal' => 'Personal Information', 'email' => 'Email Address', 'security' => 'Password & Security'] as $key => $label)
                <button type="button" role="tab" id="profile-tab-{{ $key }}" aria-controls="profile-panel-{{ $key }}" aria-selected="{{ $profileTab === $key ? 'true' : 'false' }}" tabindex="{{ $profileTab === $key ? '0' : '-1' }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="grid items-start gap-6 px-5 pt-5 pb-4 sm:px-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] lg:gap-8 lg:px-8">
            <div class="min-w-0">
            <section id="profile-panel-personal" role="tabpanel" aria-labelledby="profile-tab-personal" tabindex="0" class="w-full min-w-0" @if ($profileTab !== 'personal') hidden @endif>
                <h2 id="profilePersonalHeading" class="text-sm font-semibold text-[#7A1F2B]">Personal Information</h2>
                <p class="mt-1 text-xs text-slate-500">Update your display name. Roles and institution assignments remain managed by your System Administrator.</p>
                <form method="POST" action="{{ route('profile.name.update') }}" class="mt-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <div id="profileNameErrors" role="alert" @if (!$errors->getBag('profileName')->any()) hidden @endif class="text-xs text-red-700">
                        @foreach ($errors->getBag('profileName')->all() as $message)<p>{{ $message }}</p>@endforeach
                    </div>
                    <label for="profileDisplayName" class="block text-xs font-semibold text-[#7A1F2B]">Display name
                        <input id="profileDisplayName" name="name" value="{{ ($errors->getBag('profileName')->any() && is_string(old('name'))) ? old('name') : $user->name }}" required maxlength="255" autocomplete="name" aria-describedby="profileNameErrors"
                            class="profile-input mt-1.5 block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900">
                    </label>
                    <label for="profileNamePassword" class="block text-xs font-semibold text-[#7A1F2B]">Current password
                        <input id="profileNamePassword" name="current_password" type="password" required autocomplete="current-password" aria-describedby="profileNameErrors"
                            class="profile-input mt-1.5 block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900">
                    </label>
                    <button type="submit" class="min-h-11 rounded-lg bg-[#7A1F2B] px-4 py-2 text-sm font-semibold text-white hover:bg-[#58151E] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#7A1F2B]">Save name</button>
                </form>

            </section>
            <section id="profile-panel-email" role="tabpanel" aria-labelledby="profile-tab-email" tabindex="0" class="w-full min-w-0" @if ($profileTab !== 'email') hidden @endif>
                <h2 id="profileEmailHeading" class="text-sm font-semibold text-[#7A1F2B]">Email Address</h2>
                <p class="mt-2 break-all text-sm font-semibold text-[#7A1F2B]">{{ $user->email }}</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">Your current email remains active until the new address is verified. Confirmation signs you out and invalidates existing access.</p>
                @if ($emailChangeSupported)
                    <div id="profileEmailConfirmErrors" role="alert" @if (!$errors->getBag('profileEmailConfirm')->any()) hidden @endif class="mt-3 text-xs text-red-700">
                        @foreach ($errors->getBag('profileEmailConfirm')->all() as $message)<p>{{ $message }}</p>@endforeach
                    </div>
                    @if ($pendingEmailChange)
                        <div role="status" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-900">
                            Pending verification: <strong class="break-all">{{ $pendingEmailChange['email'] }}</strong>.
                            Expires {{ \Carbon\Carbon::createFromTimestamp($pendingEmailChange['expires_at'])->timezone('Asia/Manila')->format('g:i A') }} (Asia/Manila).
                        </div>
                        <form method="POST" action="{{ route('profile.email.confirm') }}" class="mt-6 space-y-4">
                            @csrf
                            <label for="profileEmailCode" class="block text-xs font-semibold text-[#7A1F2B]">Code from your new email
                                <input id="profileEmailCode" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required aria-describedby="profileEmailConfirmErrors"
                                    class="profile-input mt-1.5 block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900">
                            </label>
                            <label for="profileEmailConfirmPassword" class="block text-xs font-semibold text-[#7A1F2B]">Current password
                                <input id="profileEmailConfirmPassword" name="current_password" type="password" autocomplete="current-password" required aria-describedby="profileEmailConfirmErrors"
                                    class="profile-input mt-1.5 block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900">
                            </label>
                            <button type="submit" class="min-h-11 rounded-lg bg-[#7A1F2B] px-4 py-2 text-sm font-semibold text-white hover:bg-[#58151E] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#7A1F2B]">Confirm new email</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('profile.email.request') }}" class="mt-6 space-y-4">
                        @csrf
                        <div id="profileEmailErrors" role="alert" @if (!$errors->getBag('profileEmail')->any()) hidden @endif class="text-xs text-red-700">
                            @foreach ($errors->getBag('profileEmail')->all() as $message)<p>{{ $message }}</p>@endforeach
                        </div>
                        <label for="profileNewEmail" class="block text-xs font-semibold text-[#7A1F2B]">New email address
                            <input id="profileNewEmail" name="email" type="email" value="{{ ($errors->getBag('profileEmail')->any() && is_string(old('email')) && old('email') !== $user->email) ? old('email') : ($pendingEmailChange['email'] ?? '') }}" required maxlength="255" autocomplete="email" aria-describedby="profileEmailErrors"
                                class="profile-input mt-1.5 block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900">
                        </label>
                        <label for="profileEmailPassword" class="block text-xs font-semibold text-[#7A1F2B]">Current password
                            <input id="profileEmailPassword" name="current_password" type="password" autocomplete="current-password" required aria-describedby="profileEmailErrors"
                                class="profile-input mt-1.5 block w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900">
                        </label>
                        <button type="submit" class="min-h-11 rounded-lg border border-[#7A1F2B] px-4 py-2 text-sm font-semibold text-[#7A1F2B] hover:bg-[#F7ECEE] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#7A1F2B]">{{ $pendingEmailChange ? 'Send a replacement code' : 'Send verification code' }}</button>
                    </form>
                @else
                    <p class="mt-3 text-xs text-amber-800">Email changes are unavailable with the current cache configuration. Contact your System Administrator.</p>
                @endif
            </section>

        <section id="profile-panel-security" role="tabpanel" aria-labelledby="profile-tab-security" tabindex="0" class="w-full min-w-0" @if ($profileTab !== 'security') hidden @endif>
            <div class="flex items-center gap-2.5 mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-[#7A1F2B]/10 text-[#7A1F2B]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-[#7A1F2B]">Password &amp; Security</h2>
                    <p class="text-[11px] text-slate-500">
                        Use a strong, unique password
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.password.update') }}" class="mt-6 space-y-4">
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
                        <button type="button" aria-label="Show password" aria-pressed="false" class="toggle-password-btn absolute inset-y-0 right-0 flex items-center pr-3 text-[#7A1F2B]/40"
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
                        <div role="alert" class="flex items-center gap-1 mt-1.5 text-[11px] text-red-700">
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
                        <button type="button" aria-label="Show password" aria-pressed="false" class="toggle-password-btn absolute inset-y-0 right-0 flex items-center pr-3 text-[#7A1F2B]/40"
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
                        <div role="alert" class="flex items-center gap-1 mt-1.5 text-[11px] text-red-700">
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
                        <button type="button" aria-label="Show password" aria-pressed="false" class="toggle-password-btn absolute inset-y-0 right-0 flex items-center pr-3 text-[#7A1F2B]/40"
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
                <div class="flex justify-start pt-2">
                    <button type="submit"
                        class="profile-button min-h-11 inline-flex items-center gap-2 rounded-lg bg-[#7A1F2B] px-5 py-2.5 text-xs font-semibold text-white shadow-md focus:outline-none focus:ring-4 focus:ring-[#7A1F2B]/20 relative overflow-hidden">
                        <span class="relative z-10">Update password</span>
                        <svg class="w-3.5 h-3.5 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </div>
            </form>
        </section>
            </div>
            <aside aria-labelledby="profileOverviewHeading" class="min-w-0 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:p-5">
                <h2 id="profileOverviewHeading" class="text-sm font-semibold text-[#7A1F2B]">Account Overview</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">Account status</dt>
                        <dd class="mt-1"><span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium {{ $user->is_active ? 'bg-green-50 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Assigned role</dt>
                        <dd class="mt-1 break-words font-medium text-slate-700">{{ $user->roleLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Assigned institutions</dt>
                        <dd class="mt-1 text-slate-700">{{ $assignedInstitutionCount ? $assignedInstitutionCount.' '.Str::plural('institution', $assignedInstitutionCount) : 'None assigned' }}</dd>
                    </div>
                </dl>
                <div class="mt-4 border-t border-slate-200 pt-2">
                    <a href="{{ route('legal.review') }}" class="inline-flex min-h-11 items-center text-xs text-[#7A1F2B] underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#7A1F2B]">Review Terms status and my acceptance history</a>
                </div>
            </aside>
        </div>
    </div>
    </div>
</div>

<script>
    (() => {
        const settings = document.querySelector('[data-profile-settings]');
        if (!settings) return;
        const tabs = Array.from(settings.querySelectorAll('[role="tab"]'));
        const panels = Array.from(settings.querySelectorAll('[role="tabpanel"]'));
        // Record sensitive inputs while they are still masked, so revealed passwords
        // are cleared too. Non-sensitive drafts stay in their own original forms.
        const sensitive = panels.flatMap(panel => Array.from(panel.querySelectorAll('input[type="password"], input[name="code"]')));
        function clearSensitive(panel) {
            sensitive.filter(input => panel.contains(input)).forEach(input => {
                input.value = '';
                if (input.name === 'code') return;
                input.type = 'password';
                const button = input.parentElement.querySelector('.toggle-password-btn');
                if (!button) return;
                button.setAttribute('aria-label', 'Show password');
                button.setAttribute('aria-pressed', 'false');
                button.querySelector('.eye-open').classList.remove('hidden');
                button.querySelector('.eye-closed').classList.add('hidden');
            });
        }
        function selectTab(tab, focus = false) {
            panels.forEach(panel => {
                const selected = panel.id === tab.getAttribute('aria-controls');
                if (!selected && !panel.hidden) clearSensitive(panel);
                panel.hidden = !selected;
            });
            tabs.forEach(item => {
                item.setAttribute('aria-selected', String(item === tab));
                item.tabIndex = item === tab ? 0 : -1;
            });
            if (focus) tab.focus();
            // Scroll only the tab strip; never move the surrounding page.
            const strip = tab.parentElement;
            if (strip.scrollWidth > strip.clientWidth) {
                strip.scrollLeft += tab.getBoundingClientRect().left - strip.getBoundingClientRect().left - (strip.clientWidth - tab.offsetWidth) / 2;
            }
        }
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => selectTab(tab));
            tab.addEventListener('keydown', event => {
                let next;
                if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
                else if (event.key === 'ArrowLeft') next = (index - 1 + tabs.length) % tabs.length;
                else if (event.key === 'Home') next = 0;
                else if (event.key === 'End') next = tabs.length - 1;
                else return;
                event.preventDefault();
                selectTab(tabs[next], true);
            });
        });
        const initial = tabs.find(tab => tab.getAttribute('aria-selected') === 'true') || tabs[0];
        selectTab(initial);
        window.addEventListener('pageshow', () => panels.forEach(clearSensitive));
    })();

    function togglePasswordVisibility(inputId, button) {
        const input = document.getElementById(inputId);
        const eyeOpen = button.querySelector('.eye-open');
        const eyeClosed = button.querySelector('.eye-closed');

        if (input.type === 'password') {
            input.type = 'text';
            eyeOpen.classList.add('hidden');
            eyeClosed.classList.remove('hidden');
            button.setAttribute('aria-label', 'Hide password');
            button.setAttribute('aria-pressed', 'true');
        } else {
            input.type = 'password';
            eyeOpen.classList.remove('hidden');
            eyeClosed.classList.add('hidden');
            button.setAttribute('aria-label', 'Show password');
            button.setAttribute('aria-pressed', 'false');
        }
    }
</script>
@endsection
