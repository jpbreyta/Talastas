@extends('layouts.app')
@section('page-title', 'Accounts and Logs')
@section('page-icon', 'fa-users-gear')
@section('content')
@include('admin.users._styles')
<div class="users-module mx-auto w-full max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
    @include('admin.users._tabs', ['activeTab' => 'accounts'])
    @include('admin.users._feedback')
    <section class="users-card" data-accounts-table aria-labelledby="accountsHeading">
        <h2 id="accountsHeading" class="border-b border-gray-200 px-5 py-4 text-lg font-semibold">Accounts</h2>
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route('admin.users.index') }}" class="w-full sm:max-w-sm">
                <input type="hidden" name="log_search" value="{{ request('log_search') }}">
                <label for="userSearch" class="sr-only">Search users by name or email</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                    <input id="userSearch" name="search" type="search" value="{{ request('search') }}" placeholder="Search users..." class="users-control users-account-search">
                    <button type="button" data-users-clear-search @if (!request()->filled('search')) hidden @endif class="users-clear-search" aria-label="Clear user search" title="Clear search"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                </div>
            </form>
            <a href="{{ route('admin.invitations.index') }}" class="users-button shrink-0"><i class="fa-solid fa-plus" aria-hidden="true"></i>Invite User</a>
        </div>
        <div class="users-table-scroll" tabindex="0" role="region" aria-label="Accounts table">
            <table class="users-table users-accounts-table min-w-[960px]">
                <thead><tr>
                    @foreach (['Name', 'Email', 'Role', 'Assigned Institutions', 'Actions'] as $column)
                        <th scope="col">{{ $column }}</th>
                    @endforeach
                </tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="users-wrap min-w-[160px]"><p class="font-semibold">{{ $user->name }}</p><span class="mt-1 inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-medium {{ $user->is_active ? 'bg-green-50 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="users-wrap min-w-[200px] text-gray-700">{{ $user->email }}</td>
                            <td class="min-w-[160px] text-gray-700">{{ $user->roleLabel() }}</td>
                            <td class="users-wrap min-w-[200px] text-gray-700">
                                @forelse ($user->institutions->take(2) as $institution)
                                    <span class="block">{{ $institution->name }}</span>
                                @empty
                                    <span class="text-gray-400">None assigned</span>
                                @endforelse
                                @if ($user->institutions->count() > 2)
                                    <details class="users-assignment-details mt-1 text-xs">
                                        <summary class="cursor-pointer rounded py-1 font-medium text-red-900">
                                            <span class="users-assignment-collapsed">+{{ $user->institutions->count() - 2 }} more {{ Str::plural('institution', $user->institutions->count() - 2) }}</span>
                                            <span class="users-assignment-expanded">Show fewer institutions</span>
                                        </summary>
                                        <ul tabindex="0" aria-label="Additional assigned institutions for {{ $user->name }}" class="mt-1 max-h-48 space-y-1 overflow-y-auto pr-2 text-gray-700">
                                            @foreach ($user->institutions->skip(2) as $institution)<li>{{ $institution->name }}</li>@endforeach
                                        </ul>
                                    </details>
                                @endif
                            </td>
                            <td class="w-16">
                                @if (!auth()->user()->is($user))
                                <div>
                                    <button type="button" data-user-actions-toggle aria-controls="user-actions-{{ $user->id }}" aria-expanded="false" aria-haspopup="menu" aria-label="Actions for {{ $user->name }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100"><i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i></button>
                                    <div id="user-actions-{{ $user->id }}" data-user-actions-menu class="users-account-menu" role="menu" aria-label="Actions for {{ $user->name }}" hidden>
                                    <button type="button" role="menuitem" data-open-user-editor="user-editor-{{ $user->id }}" aria-haspopup="dialog" aria-controls="user-editor-{{ $user->id }}" class="inline-flex items-center gap-2 rounded py-2 font-medium text-gray-600 hover:text-red-900">
                                        <i class="fa-solid fa-pen text-xs" aria-hidden="true"></i>Edit Account<span class="sr-only"> {{ $user->name }}</span>
                                    </button>
                                        <form id="delete-user-{{ $user->id }}" method="POST" action="{{ route('admin.users.destroy', $user) }}" data-account-delete-form data-user-name="{{ $user->name }}" data-user-email="{{ $user->email }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" role="menuitem" data-confirm-user-delete class="inline-flex items-center gap-2 rounded py-2 font-medium text-red-700 hover:text-red-800"><i class="fa-solid fa-trash text-xs" aria-hidden="true"></i>Delete Account<span class="sr-only"> {{ $user->name }}</span></button>
                                        </form>
                                    </div>
                                </div>
                                @else
                                    <span class="text-xs text-gray-500">Current account</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-gray-500">{{ request()->filled('search') ? 'No accounts match this search. Try another name or email, or clear the search.' : 'No accounts found.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="users-pagination">{{ $users->links() }}</div>
    </section>
    <dialog id="deleteUserAccountDialog" class="users-confirmation" aria-labelledby="deleteUserAccountTitle" aria-describedby="deleteUserAccountDescription deleteUserAccountWarning">
        <div class="border-b border-gray-200 px-6 py-5">
            <h2 id="deleteUserAccountTitle" class="text-lg font-semibold">Delete account?</h2>
            <p id="deleteUserAccountDescription" class="mt-2 text-sm leading-6 text-gray-600">Permanently delete <strong data-delete-account-name class="text-gray-900"></strong> (<span data-delete-account-email></span>)? This cannot be undone.</p>
            <p id="deleteUserAccountWarning" class="mt-2 text-xs leading-5 text-gray-600">Existing deletion rules may also remove related quarterly audit records. To retain the account and its history, cancel and use Deactivate account in Edit Account instead.</p>
        </div>
        <div class="flex flex-wrap justify-end gap-3 bg-gray-50 px-6 py-4">
            <button type="button" data-cancel-account-delete class="users-button users-button-secondary" autofocus>Cancel</button>
            <button type="button" data-submit-account-delete class="users-button users-button-danger">Delete Account</button>
        </div>
    </dialog>
    @foreach ($users as $user)
        <dialog id="user-editor-{{ $user->id }}" class="users-drawer" aria-labelledby="user-editor-title-{{ $user->id }}" data-user-editor>
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-gray-200 bg-white px-6 py-5">
                <div><h2 id="user-editor-title-{{ $user->id }}" class="text-lg font-semibold">Edit account</h2><p class="mt-1 text-sm text-gray-500">{{ $user->name }}</p></div>
                <button type="button" data-close-user-editor class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100" aria-label="Close account editor"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </div>
            @include('admin.users._edit-form')
        </dialog>
    @endforeach
</div>
@endsection
@section('scripts')
    @include('admin.users._presentation-scripts')
    @include('admin.users._account-scripts')
@endsection
