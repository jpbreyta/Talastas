@php
    $unreadCount = auth()->user()->unreadNotifications()->count();
    $recentNotifications = auth()->user()->notifications()->latest()->limit(6)->get();
@endphp

<details class="relative">
    <summary
        class="list-none cursor-pointer inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-red-700/20"
        aria-label="Notifications"
    >
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 01-6 0m6 0H9" />
        </svg>

        @if ($unreadCount > 0)
            <span class="absolute right-1 top-1 inline-flex min-h-4 min-w-4 items-center justify-center rounded-full bg-red-700 px-1 text-[10px] font-bold leading-4 text-white">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </summary>

    <div class="absolute right-0 z-[3000] mt-2 w-[22rem] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div>
                <p class="text-sm font-semibold text-slate-900">Notifications</p>
                <p class="text-xs text-slate-500">{{ $unreadCount }} unread</p>
            </div>

            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="text-xs font-medium text-red-700 hover:text-red-800">
                        Mark all read
                    </button>
                </form>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($recentNotifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = is_null($notification->read_at);
                @endphp

                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf
                    <button type="submit"
                        class="block w-full border-b border-slate-100 px-4 py-3 text-left transition hover:bg-slate-50 {{ $isUnread ? 'bg-red-50/40' : 'bg-white' }}">
                        <div class="flex items-start gap-3">
                            <span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full {{ $isUnread ? 'bg-red-700' : 'bg-slate-300' }}"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-900">
                                    {{ $data['title'] ?? 'System notification' }}
                                </span>
                                <span class="mt-0.5 block text-xs leading-5 text-slate-600">
                                    {{ $data['message'] ?? '' }}
                                </span>
                                <span class="mt-1 block text-[11px] text-slate-400">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                            </span>
                        </div>
                    </button>
                </form>
            @empty
                <div class="px-4 py-10 text-center">
                    <p class="text-sm font-medium text-slate-700">No notifications yet</p>
                    <p class="mt-1 text-xs text-slate-500">Audit and account updates will appear here.</p>
                </div>
            @endforelse
        </div>

        <a href="{{ route('notifications.index') }}"
            class="block border-t border-slate-100 px-4 py-3 text-center text-sm font-semibold text-red-700 hover:bg-slate-50">
            View all notifications
        </a>
    </div>
</details>
