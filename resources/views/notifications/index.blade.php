@extends('layouts.app')

@section('page-title', 'Notifications')
@section('page-icon', 'fa-bell')

@section('page-tools')
<div data-notifications-page-tools class="contents">
<form method="POST" action="{{ route('notifications.read-all') }}" data-notification-action="read-all">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-red-700 disabled:opacity-60">
                    Mark all as read
                </button>
            </form>

            <form method="POST" action="{{ route('notifications.destroy-read') }}" data-notification-action="clear-read"
                onsubmit="return confirm('Clear all read notifications?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-red-700 disabled:opacity-60">
                    Clear read notifications
                </button>
            </form>
</div>
@endsection

@section('content')
<div data-notifications-page data-current-page="{{ $notifications->currentPage() }}"
    data-last-page="{{ $notifications->lastPage() }}"
    class="mx-auto w-full max-w-5xl px-4 pb-8 sm:px-6 lg:px-8">
            <p data-notifications-page-count hidden class="my-3 text-xs text-slate-500">
                <span data-notifications-page-unread></span> unread
            </p>

    @if (session('status') === 'notifications-read')
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            All notifications marked as read.
        </div>
    @elseif (session('status') === 'read-notifications-cleared')
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            Read notifications cleared.
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @foreach ($notifications as $notification)
            @php
                $data = $notification->data;
                $isUnread = is_null($notification->read_at);
            @endphp

            <form method="POST" action="{{ route('notifications.read', $notification->id) }}"
                data-notification-action="read" data-notification-row data-notification-id="{{ $notification->id }}"
                data-unread="{{ $isUnread ? '1' : '0' }}">
                @csrf
                <button type="submit"
                    class="flex w-full items-start gap-4 border-b border-slate-100 px-5 py-4 text-left transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-red-700 focus-visible:-outline-offset-2 disabled:opacity-60 {{ $isUnread ? 'bg-red-50/30' : 'bg-white' }}">
                    <span data-notification-dot class="mt-2 h-2.5 w-2.5 flex-shrink-0 rounded-full {{ $isUnread ? 'bg-red-700' : 'bg-slate-300' }}"></span>

                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="font-semibold text-slate-900">{{ $data['title'] ?? 'System notification' }}</span>
                            @if ($isUnread)
                                <span data-notification-new class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-red-800">New</span>
                            @endif
                        </span>
                        <span class="mt-1 block text-sm leading-6 text-slate-600">{{ $data['message'] ?? '' }}</span>
                        <span class="mt-2 block text-xs text-slate-400">
                            {{ $notification->created_at->format('M d, Y g:i A') }} · {{ $notification->created_at->diffForHumans() }}
                        </span>
                    </span>

                    <svg class="mt-2 h-4 w-4 flex-shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.51 4.25a.75.75 0 010 1.08l-4.51 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                    </svg>
                </button>
            </form>
        @endforeach
            <div data-notifications-empty @if ($notifications->count() > 0) hidden @endif class="px-6 py-16 text-center">
                <p class="text-base font-semibold text-slate-800">No notifications</p>
                <p class="mt-1 text-sm text-slate-500">New system and analytics activity will appear here.</p>
            </div>
    </div>

    <div data-notifications-pagination class="mt-6">
        {{ $notifications->links() }}
    </div>
    <p data-notifications-page-feedback role="status" aria-live="polite" class="mt-3 text-xs text-slate-500"></p>
    <a data-notifications-refresh hidden href="{{ url()->full() }}" class="mt-3 inline-block text-sm font-medium text-red-700 underline">Refresh notifications</a>
</div>
@endsection
