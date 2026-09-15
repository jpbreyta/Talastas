<header class="bg-red-900 border-b border-red-800 sticky top-0 z-[5000] isolate">
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex items-center h-16 gap-4">
            <div class="flex items-center gap-3 shrink-0 md:w-64">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-900" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V5.618a1 1 0 00-.553-.894L15 7m0 13V7" />
                        </svg>
                    </div>
                    <div class="hidden sm:block">
                        <span class="text-sm font-bold text-white block leading-tight">Lipa City ICT Mapping</span>
                        <span class="text-xs text-red-300 leading-tight">Digital Infrastructure &amp; Gap Analysis</span>
                    </div>
                </a>
            </div>

            <nav class="hidden md:flex items-center justify-center gap-1 flex-1" aria-label="Primary navigation">
                <a href="{{ route('home') }}"
                    class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium transition-colors
                        {{ request()->routeIs('home') || request()->routeIs('map') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <i class="fa-solid fa-map-location-dot text-xs"></i>
                    Map
                </a>

                <a href="{{ route('institutions.index') }}"
                    class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium transition-colors
                        {{ request()->routeIs('institutions.*') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <i class="fa-solid fa-building text-xs"></i>
                    {{ auth()->user()->isEditor() ? 'My Institutions' : 'Institutions' }}
                </a>

                <a href="{{ route('ict-inventory.index') }}"
                    class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium transition-colors
                        {{ request()->routeIs('ict-inventory.*') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <i class="fa-solid fa-boxes-stacked text-xs"></i>
                    {{ auth()->user()->isEditor() ? 'My Inventory' : 'Inventory' }}
                </a>

                <a href="{{ route('analytics') }}"
                    class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium transition-colors
                        {{ request()->routeIs('analytics') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <i class="fa-solid fa-chart-line text-xs"></i>
                    Analytics
                </a>

                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.users.index') }}"
                        class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium transition-colors
                            {{ request()->routeIs('admin.users.*') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                        <i class="fa-solid fa-users-gear text-xs"></i>
                        Users
                    </a>
                @endif
            </nav>

            <div class="ml-auto shrink-0">
                @include('partials.account-controls')
            </div>
        </div>
    </div>
</header>
