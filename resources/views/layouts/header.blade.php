<header class="bg-red-900 border-b border-red-800 sticky top-0 z-[5000] isolate">
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex items-center h-16 gap-4">
            <!-- Logo & Branding -->
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

            <!-- Primary Navigation -->
            <nav class="hidden md:flex items-center justify-center gap-1 flex-1" aria-label="Primary navigation">
                <a href="{{ route('home') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors
                        {{ request()->routeIs('home') || request()->routeIs('map') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a2 2 0 002 2h2a2 2 0 002-2v-4a1 1 0 011-1h2a1 1 0 011 1v4a2 2 0 002 2h2" />
                    </svg>
                    Home
                </a>

                <a href="{{ route('institutions.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors
                        {{ request()->routeIs('institutions.*') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Institutions
                </a>

                <a href="{{ route('ict-inventory.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors
                        {{ request()->routeIs('ict-inventory.*') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Inventory
                </a>

                <a href="{{ route('analytics') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors
                        {{ request()->routeIs('analytics') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Analytics
                </a>
            </nav>

            <!-- Live notification + profile controls -->
            <div class="ml-auto shrink-0">
                @include('partials.account-controls')
            </div>
        </div>
    </div>
</header>
