@php($hasMapControls = request()->routeIs('home', 'map'))
<header class="talastas-header bg-red-900 sticky top-0 z-[5000] isolate shrink-0">
    <div class="{{ $hasMapControls ? 'map-header-row' : 'talastas-feature-header px-4 sm:px-6 lg:px-8' }}">
        <div class="flex min-w-0 items-center gap-2 {{ $hasMapControls ? 'map-header-brand' : 'flex-1' }}">
            <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white lg:hidden"
                data-sidebar-toggle aria-controls="talastasSidebar" aria-expanded="false" aria-label="Open navigation">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>
            <a href="{{ route('home') }}" class="talastas-header-branding flex items-center gap-1.5 text-white lg:hidden">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white p-0.5">
                    <img src="{{ asset('images/logo_ict_mapping.png') }}" alt="" width="28" height="28" class="h-full w-full object-contain">
                </span>
                <span class="talastas-brand">TALASTAS</span>
            </a>
            @if (! $hasMapControls && $__env->hasSection('page-title'))
                <x-header-page-title class="hidden sm:flex sm:ml-3 lg:ml-0" />
                @hasSection('page-status')<div class="header-analysis-status">@yield('page-status')</div>@endif
            @endif
        </div>
        @if ($hasMapControls)
            @include('partials.map-header-controls')
        @elseif ($__env->hasSection('page-tools'))
            <div class="talastas-header-tools">@yield('page-tools')</div>
        @endif
        <div class="shrink-0 {{ $hasMapControls ? 'map-header-account' : '' }}">
            @include('partials.account-controls')
        </div>
        @if (! $hasMapControls && $__env->hasSection('page-title'))
            <x-header-page-title class="talastas-mobile-page-title flex pb-2 sm:hidden" />
        @endif
    </div>
</header>
