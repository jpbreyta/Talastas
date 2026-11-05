@extends('layouts.app')
@section('page-title', 'Accounts and Logs')
@section('page-icon', 'fa-users-gear')
@section('content')
@include('admin.users._styles')
@php
    $activityTab = request('tab') === 'changes' ? 'changes' : 'sessions';
@endphp
<div class="users-module mx-auto w-full max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
    @include('admin.users._tabs', ['activeTab' => $activityTab])
    @include('admin.users._feedback')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('admin.users.logs') }}" class="flex min-w-0 flex-wrap items-center gap-2">
            <input type="hidden" name="tab" value="{{ $activityTab }}">
            <label for="activitySearch" class="sr-only">Search user logs</label>
            <div class="relative w-full sm:w-64">
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                <input id="activitySearch" type="search" name="search" value="{{ request('search') }}" placeholder="Search user name" class="users-control" style="padding-left: 2.5rem">
            </div>
            <button type="submit" class="users-button">Search</button>
            <a href="{{ route('admin.users.logs', ['tab' => $activityTab]) }}" class="users-button users-button-secondary">Refresh</a>
        </form>
    </div>
    @include('admin.users._activity')
</div>
@endsection
@include('admin.users._activity-scripts')
