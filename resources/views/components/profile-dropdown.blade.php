@props(['sidebar' => false])
@php
    $user = auth()->user();
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<details class="relative {{ $sidebar ? 'talastas-sidebar-account' : '' }}">
    <summary class="list-none cursor-pointer flex items-center gap-2 rounded-md px-3 py-2 transition-colors text-sm font-medium text-red-200 hover:text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
        <span class="inline-flex shrink-0 {{ $sidebar ? 'h-8 w-8 text-xs' : 'h-6 w-6 text-[10px]' }} items-center justify-center rounded-full bg-white/20 font-bold text-white">
            {{ $initials ?: 'U' }}
        </span>
        <span class="{{ $sidebar ? 'block flex-1' : 'hidden md:block' }} min-w-0 text-left">
            <span class="block {{ $sidebar ? 'break-words text-xs text-white' : 'max-w-40 truncate' }} leading-tight">{{ $user->name }}</span>
            <span class="block {{ $sidebar ? 'mt-0.5 text-[10px]' : 'max-w-52 truncate text-[11px]' }} leading-tight opacity-80">{{ $user->roleLabel() }}</span>
        </span>
        <i class="fa-solid fa-chevron-down h-3 w-3 shrink-0 text-xs" aria-hidden="true"></i>
    </summary>

    <div class="{{ $sidebar ? 'talastas-sidebar-account-menu absolute bottom-full left-0 mb-2 overflow-y-auto' : 'absolute right-0 mt-2 w-72 overflow-hidden' }} z-[3000] rounded-xl border border-slate-200 bg-white shadow-xl">
        <div class="border-b border-slate-100 px-4 py-3">
            <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
            <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
            <span class="mt-2 inline-flex rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">
                {{ $user->roleLabel() }}
            </span>
        </div>

        <nav class="profile-menu-list" aria-label="Account menu">
            <a href="{{ route('profile.account') }}" class="profile-menu-item"><i class="fa-regular fa-user" aria-hidden="true"></i><span>My profile</span></a>
            <a href="{{ route('legal.review') }}" class="profile-menu-item"><i class="fa-solid fa-file-circle-check" aria-hidden="true"></i><span>My Terms acknowledgement</span></a>
            @include('partials.legal-links', ['menu' => true])
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 py-1">
            @csrf
            <button type="submit" class="profile-menu-item profile-menu-signout w-full text-left"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span>Sign out</span></button>
        </form>
    </div>
</details>
