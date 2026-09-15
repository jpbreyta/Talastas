@php
    $user = auth()->user();
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<details class="relative">
    <summary class="list-none cursor-pointer flex items-center gap-2 rounded-lg px-2 py-1.5 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-red-700/20">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-red-800 text-xs font-bold text-white">
            {{ $initials ?: 'U' }}
        </span>
        <span class="hidden min-w-0 text-left md:block">
            <span class="block max-w-40 truncate text-sm font-semibold text-slate-900">{{ $user->name }}</span>
            <span class="block max-w-52 truncate text-[11px] text-slate-500">{{ $user->roleLabel() }}</span>
        </span>
        <svg class="hidden h-4 w-4 text-slate-400 md:block" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </summary>

    <div class="absolute right-0 z-[3000] mt-2 w-72 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
        <div class="border-b border-slate-100 px-4 py-3">
            <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
            <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
            <span class="mt-2 inline-flex rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">
                {{ $user->roleLabel() }}
            </span>
        </div>

        <a href="{{ route('profile.account') }}"
            class="flex items-center gap-2 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.118a7.5 7.5 0 0115 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.5-1.632z" />
            </svg>
            My profile
        </a>

        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100">
            @csrf
            <button type="submit"
                class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm font-medium text-red-700 hover:bg-red-50">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3-3H9.75m9 0l-3-3m3 3l-3 3" />
                </svg>
                Sign out
            </button>
        </form>
    </div>
</details>
