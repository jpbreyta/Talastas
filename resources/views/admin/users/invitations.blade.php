@extends('layouts.app')
@section('page-title', 'Accounts and Logs')
@section('page-icon', 'fa-users-gear')
@section('content')
@include('admin.users._styles')
<div class="users-module mx-auto w-full max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8" data-invitations-page>
    @include('admin.users._tabs', ['activeTab' => 'invitations'])
    @include('admin.users._feedback')
    @if (!\App\Services\SecurityMail::available())
        <p role="alert" class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">System email is not configured on the server. Sender and queue setup must be completed before sending invitations, recovery instructions or administrator verification codes. Signing in as an administrator does not configure email delivery.</p>
    @endif
    <div class="grid items-start gap-6 lg:grid-cols-5 lg:gap-8">
        <section class="users-card p-5 sm:p-6 lg:col-span-2" aria-labelledby="inviteHeading">
            <h2 id="inviteHeading" class="mb-4 text-lg font-semibold">Invite a user</h2>
            <form method="POST" action="{{ route('admin.invitations.store') }}" class="space-y-4" data-role-form data-invitation-create-form>
                @csrf
                <fieldset class="min-w-0 space-y-4">
                    <legend class="mb-3 text-sm font-semibold text-red-900">Invitation Details</legend>
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
                @php
                    $selectedBarangayIds = array_map('strval', (array) old('barangay_ids', []));
                @endphp
                <fieldset id="inviteAssignments" data-editor-assignments @if (old('role', 'viewer') !== 'editor') hidden disabled @endif>
                    <legend class="text-sm font-medium">Barangay and institution assignments</legend>
                    <div class="mt-2 max-h-48 space-y-3 overflow-y-auto rounded-lg border border-gray-200 p-4" aria-label="Barangay assignments">
                        @foreach ($institutions->pluck('barangay')->filter()->unique('id')->sortBy('name') as $barangay)
                            <label class="flex items-start gap-2 text-sm text-gray-700"><input type="checkbox" name="barangay_ids[]" value="{{ $barangay->id }}" @checked(in_array($barangay->id, (array) old('barangay_ids', []))) class="users-checkbox" data-barangay-filter><span>{{ $barangay->name }}</span></label>
                        @endforeach
                    </div>
                    <p class="mt-3 text-sm font-medium">Individual institutions</p>
                    <div class="mt-2 max-h-60 space-y-3 overflow-y-auto rounded-lg border border-gray-200 p-4" data-institution-options>
                        @forelse ($institutions as $institution)
                            <label class="flex items-start gap-2 text-sm text-gray-700" data-institution-row data-barangay-id="{{ $institution->barangay_id }}" @if (!in_array((string) $institution->barangay_id, $selectedBarangayIds, true)) hidden @endif><input type="checkbox" name="institution_ids[]" value="{{ $institution->id }}" @checked(in_array($institution->id, (array) old('institution_ids', []))) class="users-checkbox"><span>{{ $institution->name }} @if ($institution->barangay) <span class="text-gray-500">({{ $institution->barangay->name }})</span> @endif</span></label>
                        @empty
                            <p class="text-sm text-gray-500">No institutions available.</p>
                        @endforelse
                        @if ($institutions->isNotEmpty())
                            <p class="text-sm text-gray-500" data-institutions-select-barangay @if ($selectedBarangayIds !== []) hidden @endif>Select a barangay to view its institutions.</p>
                            <p class="text-sm text-gray-500" data-institutions-no-match @if ($selectedBarangayIds === [] || $institutions->contains(fn ($item) => in_array((string) $item->barangay_id, $selectedBarangayIds, true))) hidden @endif>No institutions are available in the selected barangay.</p>
                        @endif
                    </div>
                </fieldset>
                </fieldset>
                <fieldset class="min-w-0 space-y-4 border-t border-gray-200 pt-4">
                    <legend class="px-1 text-sm font-semibold text-red-900">Authorization &amp; Confirmation</legend>
                    <label class="block text-sm font-medium" for="invitePassword">Your current password
                        <input id="invitePassword" name="current_password" type="password" required autocomplete="current-password" class="users-control mt-2">
                    </label>
                    <label class="flex items-start gap-2 text-sm text-gray-600"><input type="checkbox" name="existing_account_confirmed" value="1" @checked(old('existing_account_confirmed')) class="users-checkbox"><span>If this account already exists, I authorize updating its access after authenticated acceptance.</span></label>
                    <label class="flex items-start gap-2 text-sm text-gray-600"><input type="checkbox" name="confirmed" value="1" required @checked(!old('invitation_action') && old('confirmed')) class="users-checkbox"><span>I confirm the recipient, role and institution assignments and authorize this invitation email.</span></label>
                    <button type="submit" class="users-button" @disabled(!\App\Services\SecurityMail::available())>Send Invitation</button>
                </fieldset>
            </form>
            <a href="{{ route('security.mfa') }}" class="mt-5 inline-block text-xs text-gray-500 underline hover:text-red-900">Email verification and trusted browsers</a>
        </section>
        <section class="min-w-0 space-y-5 lg:col-span-3" aria-labelledby="historyHeading">
            <h2 id="historyHeading" class="text-lg font-semibold">Invitation history</h2>
            @forelse ($invitations as $invite)
                @php
                    $invitationStatus = $invite->status === 'pending' && $invite->expires_at->isPast() ? 'expired' : $invite->status;
                    $deliveryLabel = match ($invite->delivery_status) {
                        'transport_accepted' => 'Accepted by email service',
                        'queued' => 'Email queued',
                        'failed' => 'Email sending failed',
                        default => 'Email: '.str_replace('_', ' ', $invite->delivery_status),
                    };
                    $mayManageInvitation = auth()->user()?->isAdmin() && auth()->user()->is_active && in_array($invite->status, ['pending', 'expired'], true);
                @endphp
                <article class="users-card p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1 basis-40">
                            <h3 class="users-wrap font-semibold">{{ $invite->name }}</h3>
                            <p class="users-wrap mt-0.5 text-sm text-gray-600">{{ $invite->email }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ (new \App\Models\User(['role' => $invite->role]))->roleLabel() }}</p>
                        </div>
                        <div class="ml-auto flex h-11 shrink-0 items-center gap-1">
                            <span class="inline-flex min-h-5 items-center whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-semibold {{ $invitationStatus === 'pending' ? 'bg-amber-50 text-amber-800' : ($invitationStatus === 'accepted' ? 'bg-green-50 text-green-800' : 'bg-gray-100 text-gray-600') }}">{{ $invitationStatus }}</span>
                            @if ($mayManageInvitation)
                                <button type="button" data-invite-menu-toggle aria-controls="invitation-menu-{{ $invite->id }}" aria-expanded="false" aria-haspopup="menu" aria-label="Invitation actions for {{ $invite->name }}" class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100"><i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i></button>
                                <div id="invitation-menu-{{ $invite->id }}" class="users-account-menu" role="menu" aria-label="Invitation actions for {{ $invite->name }}" hidden>
                                    @foreach (['resend' => 'Resend Invitation', 'revoke' => 'Revoke Invitation'] as $action => $label)
                                        <button type="button" role="menuitem" data-invite-action="{{ $action }}" data-invite-id="{{ $invite->id }}" data-recipient-name="{{ $invite->name }}" data-recipient-email="{{ $invite->email }}" data-action-url="{{ route('admin.invitations.'.$action, $invite->id) }}" aria-haspopup="dialog" aria-controls="invitationActionDialog" class="{{ $action === 'revoke' ? 'text-red-700' : 'text-gray-700' }}"><i class="fa-solid {{ $action === 'revoke' ? 'fa-ban' : 'fa-paper-plane' }}" aria-hidden="true"></i>{{ $label }}</button>
                                    @endforeach
                                </div>
                            @else
                                <span aria-hidden="true" class="h-11 w-11 shrink-0"></span>
                            @endif
                        </div>
                    </div>
                    <dl class="mt-3 grid gap-x-4 gap-y-2 text-xs sm:grid-cols-2">
                        <div><dt class="text-gray-500">Created</dt><dd class="mt-0.5 text-gray-700">@if ($invite->created_at)<time datetime="{{ $invite->created_at->toIso8601String() }}">{{ $invite->created_at->copy()->timezone('Asia/Manila')->format('M j, Y g:i A') }} PHT</time>@else Not recorded @endif</dd></div>
                        <div><dt class="text-gray-500">Expires</dt><dd class="mt-0.5 text-gray-700"><time datetime="{{ $invite->expires_at->toIso8601String() }}">{{ $invite->expires_at->copy()->timezone('Asia/Manila')->format('M j, Y g:i A') }} PHT</time></dd></div>
                        @if ($invite->accepted_at)
                            <div class="sm:col-span-2"><dt class="text-gray-500">Accepted</dt><dd class="mt-0.5 text-gray-700"><time datetime="{{ $invite->accepted_at->toIso8601String() }}">{{ $invite->accepted_at->copy()->timezone('Asia/Manila')->format('M j, Y g:i A') }} PHT</time> | Account #{{ $invite->accepted_user_id }}</dd></div>
                        @endif
                    </dl>
                    <div class="mt-3 text-xs text-gray-600">
                        <p class="font-medium">{{ $invite->institutions->count() }} assigned {{ Str::plural('institution', $invite->institutions->count()) }}</p>
                        @foreach ($invite->institutions->take(2) as $institution)<p class="users-wrap mt-1">{{ $institution->name }}</p>@endforeach
                        @if ($invite->institutions->count() > 2)
                            <details class="users-assignment-details mt-1">
                                <summary class="cursor-pointer rounded py-1 font-medium text-red-900"><span class="users-assignment-collapsed">+{{ $invite->institutions->count() - 2 }} more {{ Str::plural('institution', $invite->institutions->count() - 2) }}</span><span class="users-assignment-expanded">Show fewer institutions</span></summary>
                                <ul tabindex="0" aria-label="Additional institutions for {{ $invite->name }}" class="mt-1 max-h-40 space-y-1 overflow-y-auto pr-2">
                                    @foreach ($invite->institutions->skip(2) as $institution)<li class="users-wrap">{{ $institution->name }}</li>@endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                    <details class="mt-3 text-xs text-gray-500">
                        <summary class="cursor-pointer rounded py-1 {{ $invite->delivery_status === 'failed' ? 'text-amber-800' : '' }}">{{ $deliveryLabel }}</summary>
                        <p class="mt-1 leading-5">Email status: {{ str_replace('_', ' ', $invite->delivery_status) }}. Transport acceptance does not confirm inbox delivery or that the email was opened.</p>
                        @if ($invite->sent_at)<p class="mt-1">Sent <time datetime="{{ $invite->sent_at->toIso8601String() }}">{{ $invite->sent_at->copy()->timezone('Asia/Manila')->format('M j, Y g:i A') }} PHT</time></p>@endif
                    </details>
                </article>
            @empty
                <p class="users-card p-6 text-sm text-gray-500">No invitations yet.</p>
            @endforelse
            {{ $invitations->links() }}
        </section>
    </div>
    <dialog id="invitationActionDialog" class="users-confirmation" aria-labelledby="invitationActionTitle" aria-describedby="invitationActionRecipient invitationActionDescription" data-reopen-id="{{ is_string(old('invitation_id')) ? old('invitation_id') : '' }}" data-reopen-action="{{ is_string(old('invitation_action')) ? old('invitation_action') : '' }}">
        <form method="POST" data-invitation-action-form class="p-5 sm:p-6">
            @csrf
            <input type="hidden" name="invitation_id" data-action-id>
            <input type="hidden" name="invitation_action" data-action-kind>
            <h2 id="invitationActionTitle" class="text-lg font-semibold" data-action-title>Confirm invitation action</h2>
            <p id="invitationActionRecipient" class="mt-2 text-sm text-gray-700"><strong data-action-recipient></strong> (<span data-action-email></span>)</p>
            <p id="invitationActionDescription" class="mt-2 text-sm text-gray-600" data-action-description></p>
            @if (old('invitation_id') && $errors->any())
                <div role="alert" data-action-errors data-error-invite-id="{{ is_string(old('invitation_id')) ? old('invitation_id') : '' }}" class="mt-3 text-sm text-red-700">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
            @endif
            <label for="invitationActionConfirmed" class="mt-4 flex items-start gap-2 text-sm text-gray-600"><input id="invitationActionConfirmed" name="confirmed" value="1" type="checkbox" required class="users-checkbox"><span data-action-confirmation-text></span></label>
            <label for="invitationActionPassword" class="mt-4 block text-sm font-medium">Your current password
                <input id="invitationActionPassword" name="current_password" type="password" required autocomplete="current-password" class="users-control mt-2">
            </label>
            <div class="mt-5 flex flex-wrap justify-end gap-3">
                <button type="button" data-cancel-invitation-action class="users-button users-button-secondary" autofocus>Cancel</button>
                <button type="submit" data-invitation-submit class="users-button">Confirm</button>
            </div>
        </form>
    </dialog>
</div>
@endsection
@section('scripts')
    @include('admin.users._presentation-scripts')
    @include('admin.users._invitation-scripts')
@endsection
