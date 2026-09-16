<header class="bg-red-900 border-b border-red-800 sticky top-0 z-[5000] isolate">
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex items-center h-16 gap-4 justify-between relative">
            <div class="flex items-center gap-2.5 shrink-0">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shrink-0 p-1">
                        <img src="{{ asset('images/logo_ict_mapping.png') }}" alt="TALASTAS Logo" class="w-full h-full object-contain">
                    </div>
                    <div class="hidden lg:block leading-none">
                        <span class="text-base font-bold text-white block">TALASTAS</span>
                        <span class="text-[11px] text-red-300 block mt-0.5">Technology And Location Analytics Spatial Tracking & Assessment System</span>
                    </div>
                </a>
            </div>

            <nav class="hidden md:flex items-center justify-center gap-0.5 absolute left-1/2 transform -translate-x-1/2" aria-label="Primary navigation">
                <a href="{{ route('home') }}"
                    class="flex items-center gap-1.5 px-2 py-2 rounded-md text-xs font-medium transition-colors
                        {{ request()->routeIs('home') || request()->routeIs('map') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <i class="fa-solid fa-map-location-dot text-xs"></i>
                    Map
                </a>

                <a href="{{ route('institutions.index') }}"
                    class="flex items-center gap-1.5 px-2 py-2 rounded-md text-xs font-medium transition-colors
                        {{ request()->routeIs('institutions.*') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <i class="fa-solid fa-building text-xs"></i>
                    {{ auth()->user()->isEditor() ? 'My Institutions' : 'Institutions' }}
                </a>

                <a href="{{ route('ict-inventory.index') }}"
                    class="flex items-center gap-1.5 px-2 py-2 rounded-md text-xs font-medium transition-colors
                        {{ request()->routeIs('ict-inventory.*') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <i class="fa-solid fa-boxes-stacked text-xs"></i>
                    {{ auth()->user()->isEditor() ? 'My Inventory' : 'Inventory' }}
                </a>

                <a href="{{ route('analytics') }}"
                    class="flex items-center gap-1.5 px-2 py-2 rounded-md text-xs font-medium transition-colors
                        {{ request()->routeIs('analytics') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <i class="fa-solid fa-chart-line text-xs"></i>
                    Analytics
                </a>

                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.users.index') }}"
                        class="flex items-center gap-1.5 px-2 py-2 rounded-md text-xs font-medium transition-colors
                            {{ request()->routeIs('admin.users.*') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                        <i class="fa-solid fa-users-gear text-xs"></i>
                        Users
                    </a>
                @endif
            </nav>

            <div class="shrink-0">
                @include('partials.account-controls')
            </div>
        </div>
    </div>
</header>
