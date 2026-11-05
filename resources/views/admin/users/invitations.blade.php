@extends('layouts.app')
@section('page-title', 'Accounts and Logs')
@section('page-icon', 'fa-users-gear')
@section('content')
@include('admin.users._styles')
<div class="users-module mx-auto w-full max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
    @include('admin.users._tabs', ['activeTab' => 'invitations'])
    @include('admin.users._feedback')
    @if (!\App\Services\SecurityMail::available())
        <p role="alert" class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">System email is not configured. Complete the Gmail sender and queue setup before sending invitations or recovery instructions. No credentials are displayed here.</p>
    @endif
    <div class="grid items-start gap-6 lg:grid-cols-5 lg:gap-8">
        <section class="users-card p-5 sm:p-6 lg:col-span-2" aria-labelledby="inviteHeading">
            <h2 id="inviteHeading" class="mb-6 text-lg font-semibold">Invite a user</h2>
            <form method="POST" action="{{ route('admin.invitations.store') }}" class="space-y-5" data-role-form>
                @csrf
                <label class="block text-sm font-medium" for="inviteName">Full name
                    <input id="inviteName" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" class="users-control mt-2">
                </label>
                <label class="block text-sm font-medium" for="inviteEmail">Email
                    <input id="inviteEmail" name="email" value="{{ old('email') }}" required type="email" maxlength="255" autocomplete="email" class="users-control mt-2">
                </label>
                <label class="block text-sm font-medium" for="inviteRole">Role
                    <select id="inviteRole" name="role" class="users-control mt-2" data-user-role aria-controls="inviteAssignments">
                        <option value="viewer" @selected(old('role', 'viewer') === 'viewer')>LGU City Planner</option>
                        <option value="editor" @selected(old('role') === 'editor')>Barangay Focal Person / ICT Coordinator</option>
                        <option value="admin" @selected(old('role') === 'admin')>System Administrator (MFA required)</option>
                    </select>
                </label>
                <fieldset id="inviteAssignments" data-editor-assignments @if (old('role', 'viewer') !== 'editor') hidden disabled @endif>
                    <legend class="text-sm font-medium">Barangay and institution assignments</legend>
                    <p class="mt-2 text-xs text-gray-500">Select a barangay to add all its current active public schools and barangay halls, or select individual institutions below. Saved assignments appear as checked institutions. Newly added institutions must be assigned separately.</p>
                    <div class="mt-2 max-h-48 space-y-3 overflow-y-auto rounded-lg border border-gray-200 p-4" aria-label="Barangay assignments">
                        @foreach ($institutions->pluck('barangay')->filter()->unique('id')->sortBy('name') as $barangay)
                            <label class="flex items-start gap-2 text-sm text-gray-700"><input type="checkbox" name="barangay_ids[]" value="{{ $barangay->id }}" @checked(in_array($barangay->id, (array) old('barangay_ids', []))) class="users-checkbox"><span>{{ $barangay->name }}</span></label>
                        @endforeach
                    </div>
                    <p class="mt-3 text-sm font-medium">Individual institutions</p>
                    <div class="mt-2 max-h-60 space-y-3 overflow-y-auto rounded-lg border border-gray-200 p-4">
                        @forelse ($institutions as $institution)
                            <label class="flex items-start gap-2 text-sm text-gray-700"><input type="checkbox" name="institution_ids[]" value="{{ $institution->id }}" @checked(in_array($institution->id, (array) old('institution_ids', []))) class="users-checkbox"><span>{{ $institution->name }} @if ($institution->barangay) <span class="text-gray-500">({{ $institution->barangay->name }})</span> @endif</span></label>
                        @empty
                            <p class="text-sm text-gray-500">No institutions available.</p>
                        @endforelse
                    </div>
                </fieldset>
                <div class="space-y-4 border-t border-gray-200 pt-5">
                    <label class="block text-sm font-medium" for="invitePassword">Your current password
                        <input id="invitePassword" name="current_password" type="password" required autocomplete="current-password" class="users-control mt-2">
                    </label>
                    <label class="flex items-start gap-2 text-sm text-gray-600"><input type="checkbox" name="existing_account_confirmed" value="1" @checked(old('existing_account_confirmed')) class="users-checkbox"><span>If this account already exists, I authorize updating its access after authenticated acceptance.</span></label>
                    <label class="flex items-start gap-2 text-sm text-gray-600"><input type="checkbox" name="confirmed" value="1" required @checked(old('confirmed')) class="users-checkbox"><span>I confirm the recipient, role and institution assignments and authorize this invitation email.</span></label>
                </div>
                <button type="submit" class="users-button" @disabled(!\App\Services\SecurityMail::available())>Send Invitation</button>
            </form>
            <a href="{{ route('security.mfa') }}" class="mt-5 inline-block text-xs text-gray-500 underline hover:text-red-900">Email verification and trusted browsers</a>
        </section>
        <section class="min-w-0 space-y-5 lg:col-span-3" aria-labelledby="historyHeading">
            <h2 id="historyHeading" class="text-lg font-semibold">Invitation history</h2>
            @forelse ($invitations as $invite)
                <article class="users-card space-y-4 p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="font-semibold">{{ $invite->name }}</h3>
                            <p class="mt-1 break-words text-sm text-gray-600">{{ $invite->email }} | {{ $invite->role }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold">{{ $invite->status === 'pending' && $invite->expires_at->isPast() ? 'expired' : $invite->status }}</span>
                    </div>
                    <p class="text-sm text-gray-600">Expires {{ $invite->expires_at->format('M j, Y H:i') }} UTC.@if ($invite->accepted_at)<br>Accepted {{ $invite->accepted_at->format('M j, Y H:i') }} UTC | Account #{{ $invite->accepted_user_id }}@endif</p>
                    @if ($invite->institutions->isNotEmpty())
                        <div class="space-y-1 text-sm text-gray-600">@foreach ($invite->institutions as $institution)<p>{{ $institution->name }}</p>@endforeach</div>
                    @endif
                    <details class="users-accordion text-sm text-gray-600" @if (in_array($invite->delivery_status, ['failed', 'unavailable'])) open @endif>
                        <summary>Email: {{ str_replace('_', ' ', $invite->delivery_status) }}</summary>
                        <p class="mt-2 text-xs leading-5">Transport acceptance does not confirm inbox delivery.</p>
                    </details>
                    @if (in_array($invite->status, ['pending', 'expired']))
                        <details class="users-accordion border-t border-gray-100 pt-4">
                            <summary class="text-sm font-medium text-red-900">Resend or revoke</summary>
                            <form method="POST" action="{{ route('admin.invitations.resend', $invite->id) }}" class="mt-4 space-y-4">
                                @csrf
                                <label class="block text-sm font-medium">Your current password<input name="current_password" type="password" required autocomplete="current-password" class="users-control mt-2"></label>
                                <label class="flex items-start gap-2 text-sm text-gray-600"><input name="confirmed" type="checkbox" value="1" required class="users-checkbox"><span>I confirm this action; existing links and setup sessions will stop working.</span></label>
                                <div class="flex flex-wrap gap-3"><button type="submit" class="users-button">Resend invitation</button><button type="submit" formaction="{{ route('admin.invitations.revoke', $invite->id) }}" class="users-button users-button-secondary">Revoke invitation</button></div>
                            </form>
                        </details>
                    @endif
                </article>
            @empty
                <p class="users-card p-6 text-sm text-gray-500">No invitations yet.</p>
            @endforelse
            {{ $invitations->links() }}
            <details class="users-accordion users-card p-5 sm:p-6">
                <summary class="font-semibold">Recent security events</summary>
                <div class="mt-4 max-h-80 space-y-3 overflow-y-auto">
                    @forelse ($events as $event)
                        <p class="break-words text-xs leading-5 text-gray-600">{{ $event->occurred_at }} UTC | {{ $event->event }} | {{ $event->outcome }} | Actor #{{ $event->actor_id ?? 'system/guest' }} | Target #{{ $event->target_id ?? '-' }}</p>
                    @empty
                        <p class="text-sm text-gray-500">No security events recorded.</p>
                    @endforelse
                </div>
            </details>
        </section>
    </div>
</div>
@endsection
@section('scripts')
    @include('admin.users._presentation-scripts')
@endsection
