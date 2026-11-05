@extends('layouts.app')
@section('page-title', 'Accounts and Logs')
@section('page-icon', 'fa-users-gear')
@section('content')
@include('admin.users._styles')
<div class="users-module mx-auto w-full max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
    @include('admin.users._tabs', ['activeTab' => 'accounts'])
    @include('admin.users._feedback')
    <section class="users-card" aria-labelledby="accountsHeading">
        <h2 id="accountsHeading" class="border-b border-gray-200 px-5 py-4 text-lg font-semibold">Accounts</h2>
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route('admin.users.index') }}" class="w-full sm:max-w-sm">
                <input type="hidden" name="log_search" value="{{ request('log_search') }}">
                <label for="userSearch" class="sr-only">Search users by name or email</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                    <input id="userSearch" name="search" type="search" value="{{ request('search') }}" placeholder="Search users..." class="users-control" style="padding-left: 2.5rem">
                </div>
            </form>
            <a href="{{ route('admin.invitations.index') }}" class="users-button shrink-0"><i class="fa-solid fa-plus" aria-hidden="true"></i>Invite User</a>
        </div>
        <div class="users-table-scroll" tabindex="0" role="region" aria-label="Accounts table">
            <table class="users-table min-w-[960px]">
                <thead><tr>
                    @foreach (['Name', 'Email', 'Role', 'Assigned Institutions', 'Actions'] as $column)
                        <th scope="col">{{ $column }}</th>
                    @endforeach
                </tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="min-w-[180px]"><p class="font-medium">{{ $user->name }}</p><p class="mt-1 text-xs {{ $user->is_active ? 'text-gray-500' : 'text-red-700' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</p></td>
                            <td class="users-wrap min-w-[200px] text-gray-700">{{ $user->email }}</td>
                            <td class="min-w-[180px] text-gray-700">{{ $user->roleLabel() }}</td>
                            <td class="min-w-[200px] text-gray-700">
                                @forelse ($user->institutions as $institution)
                                    <span class="block">{{ $institution->name }}</span>
                                @empty
                                    <span class="text-gray-400">None assigned</span>
                                @endforelse
                            </td>
                            <td class="w-32">
                                <div class="flex flex-col items-start gap-1">
                                    <button type="button" data-open-user-editor="user-editor-{{ $user->id }}" aria-haspopup="dialog" aria-controls="user-editor-{{ $user->id }}" class="inline-flex items-center gap-2 rounded py-2 font-medium text-gray-600 hover:text-red-900">
                                        <i class="fa-solid fa-pen text-xs" aria-hidden="true"></i>Edit<span class="sr-only"> {{ $user->name }}</span>
                                    </button>
                                    @if (!auth()->user()->is($user))
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user account?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-2 rounded py-2 font-medium text-red-700 hover:text-red-800"><i class="fa-solid fa-trash text-xs" aria-hidden="true"></i>Delete<span class="sr-only"> {{ $user->name }}</span></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-gray-500">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="users-pagination">{{ $users->links() }}</div>
    </section>
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
@endsection
