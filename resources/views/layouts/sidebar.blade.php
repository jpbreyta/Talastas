@php
    $navigation = [
        ['route' => 'home', 'active' => request()->routeIs('home', 'map'), 'label' => 'Dashboard', 'icon' => 'fa-map-location-dot'],
        ['route' => 'institutions.index', 'active' => request()->routeIs('institutions.*'), 'label' => auth()->user()->isEditor() ? 'Institutions' : 'Institutions', 'icon' => 'fa-building'],
        ['route' => 'ict-inventory.index', 'active' => request()->routeIs('ict-inventory.*'), 'label' => auth()->user()->isEditor() ? 'Inventory' : 'Inventory', 'icon' => 'fa-boxes-stacked'],
        ['route' => 'analytics', 'active' => request()->routeIs('analytics'), 'label' => 'Analysis', 'icon' => 'fa-chart-line'],
    ];
    if (auth()->user()->isAdmin()) {
        $navigation[] = ['route' => 'admin.users.index', 'active' => request()->routeIs('admin.users.*'), 'label' => 'Users', 'icon' => 'fa-users-gear'];
    }
@endphp

<div data-sidebar-backdrop class="talastas-sidebar-backdrop" hidden aria-hidden="true"></div>
<aside id="talastasSidebar" class="talastas-sidebar" aria-label="Main navigation">
    <div class="flex h-15 shrink-0 items-center justify-between gap-2 px-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-white">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white p-0.5">
                <img src="{{ asset('images/logo_ict_mapping.png') }}" alt="" width="32" height="32" class="h-full w-full object-contain">
            </span>
            <span class="talastas-brand">TALASTAS</span>
        </a>
        <button type="button" data-sidebar-close aria-label="Close navigation"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-100 hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-white lg:hidden">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
    <nav class="min-h-0 flex-1 overflow-y-auto px-4 pb-4 pt-6" aria-label="Primary navigation">
        <p class="mb-3 px-3 text-[10px] font-medium uppercase tracking-[0.12em] text-red-100/60">MAIN MENU</p>
        <div class="space-y-1">
        @foreach ($navigation as $item)
            <a href="{{ route($item['route']) }}" @if ($item['active']) aria-current="page" @endif
                class="talastas-sidebar-link">
                <i class="fa-solid {{ $item['icon'] }} w-5 shrink-0 text-center" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
        </div>
        @if(request()->routeIs('home', 'map'))
            <fieldset class="sidebar-map-styles mt-5 border-t border-white/15 pt-4">
                <legend class="px-3 text-[10px] font-semibold uppercase tracking-wide text-red-100/70">Map background</legend>
                @foreach(['default' => 'Default streets', 'satellite' => 'Satellite', 'terrain' => 'Terrain', 'blank' => 'Blank (no labels)'] as $value => $label)
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-xs text-white hover:bg-white/10">
                        <input type="radio" name="map-background" value="{{ $value }}" data-map-background @checked($value === 'default') class="accent-white">
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
                <p id="mapBackgroundStatus" class="px-3 pt-2 text-[10px] leading-4 text-red-100" role="status"></p>
            </fieldset>
        @endif
    </nav>
    @auth
        <div class="mt-auto shrink-0 px-2 py-3">
            <x-profile-dropdown :sidebar="true" />
        </div>
    @endauth
</aside>
