@extends('layouts.app')
@section('page-title', 'Accounts and Logs')
@section('page-icon', 'fa-users-gear')
@section('content')
@include('admin.users._styles')
@php
    $activityTab = request('tab') === 'changes' ? 'changes' : 'sessions';
    $securityView = $activityTab === 'changes' && request('view') === 'security';
@endphp
<div class="users-module mx-auto w-full max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
    @include('admin.users._tabs', ['activeTab' => $activityTab])
    @include('admin.users._feedback')
    @if ($activityTab === 'changes')
        <nav class="mb-4 flex flex-wrap gap-1" aria-label="Audit record type">
            @foreach (['changes' => 'Data Changes', 'security' => 'Security Events'] as $view => $label)
                <a href="{{ route('admin.users.logs', ['tab' => 'changes', 'view' => $view, 'search' => request('search')]) }}" @if ($securityView === ($view === 'security')) aria-current="page" @endif
                    class="inline-flex min-h-11 items-center rounded-lg border px-3 py-2 text-sm font-medium {{ $securityView === ($view === 'security') ? 'border-red-900 bg-red-50 text-red-900' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">{{ $label }}</a>
            @endforeach
        </nav>
    @endif
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('admin.users.logs') }}" class="flex min-w-0 flex-wrap items-center gap-2">
            <input type="hidden" name="tab" value="{{ $activityTab }}">
            @if ($activityTab === 'changes')<input type="hidden" name="view" value="{{ $securityView ? 'security' : 'changes' }}">@endif
            <label for="activitySearch" class="sr-only">{{ $securityView ? 'Search security events by event, outcome or account name' : 'Search user logs' }}</label>
            <div class="relative w-full sm:w-64">
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                <input id="activitySearch" type="search" name="search" value="{{ request('search') }}" placeholder="{{ $securityView ? 'Search event, outcome or name' : 'Search user name' }}" class="users-control" style="padding-left: 2.5rem">
            </div>
            <button type="submit" class="users-button">Search</button>
            <a href="{{ route('admin.users.logs', $securityView ? ['tab' => $activityTab, 'view' => 'security'] : ['tab' => $activityTab]) }}" class="users-button users-button-secondary">Refresh</a>
        </form>
    </div>
    @if ($securityView)
        @include('admin.users._security-events')
    @else
        @include('admin.users._activity')
    @endif
</div>
@endsection
@include('admin.users._activity-scripts')
