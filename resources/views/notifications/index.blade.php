@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-red-700">System activity</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Notifications</h1>
            <p class="mt-1 text-sm text-slate-600">
                Audit confirmations, validation updates, analytics results, and account-security notices.
            </p>
        </div>

        <div class="flex gap-2">
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Mark all read
                </button>
            </form>

            <form method="POST" action="{{ route('notifications.destroy-read') }}"
                onsubmit="return confirm('Clear all read notifications?');">
                @csrf
                @method('DELETE')
                <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Clear read
                </button>
            </form>
        </div>
    </div>

    @if (session('status') === 'notifications-read')
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            All notifications marked as read.
        </div>
    @elseif (session('status') === 'read-notifications-cleared')
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            Read notifications cleared.
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @forelse ($notifications as $notification)
            @php
                $data = $notification->data;
                $isUnread = is_null($notification->read_at);
            @endphp

            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                @csrf
                <button type="submit"
                    class="flex w-full items-start gap-4 border-b border-slate-100 px-5 py-4 text-left transition hover:bg-slate-50 {{ $isUnread ? 'bg-red-50/30' : 'bg-white' }}">
                    <span class="mt-2 h-2.5 w-2.5 flex-shrink-0 rounded-full {{ $isUnread ? 'bg-red-700' : 'bg-slate-300' }}"></span>

                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="font-semibold text-slate-900">{{ $data['title'] ?? 'System notification' }}</span>
                            @if ($isUnread)
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-red-800">New</span>
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
        @empty
            <div class="px-6 py-16 text-center">
                <p class="text-base font-semibold text-slate-800">No notifications</p>
                <p class="mt-1 text-sm text-slate-500">New system and audit activity will appear here.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
