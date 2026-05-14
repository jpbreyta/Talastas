<header class="bg-blue-900 border-b border-blue-800 sticky top-0 z-100">
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex items-center h-16">

            <!-- Logo & Branding (left) -->
            <div class="flex items-center gap-3 shrink-0 w-64">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V5.618a1 1 0 00-.553-.894L15 7m0 13V7" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-sm font-bold text-white block leading-tight">Lipa City ICT Mapping</span>
                        <span class="text-xs text-blue-300 leading-tight">Digital Infrastructure &amp; Gap
                            Analysis</span>
                    </div>
                </a>
            </div>

            <!-- Navigation (center) -->
            <nav class="hidden md:flex items-center justify-center gap-1 flex-1">
                <a href="{{ route('home') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('home') || request()->routeIs('map') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:text-white hover:bg-blue-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Home
                </a>
                <a href="{{ route('analytics') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('analytics') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:text-white hover:bg-blue-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Analytics
                </a>
            </nav>

            <!-- User Profile Dropdown (right) -->
            <div class="relative shrink-0 flex justify-end w-64" id="userDropdown">
                <button onclick="toggleDropdown()"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-md hover:bg-blue-800 transition-colors focus:outline-none">
                    <div
                        class="h-8 w-8 shrink-0 rounded-full bg-blue-500 border-2 border-blue-300 flex items-center justify-center text-xs font-bold text-white select-none">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}{{ strtoupper(substr(Auth::user()->name, strrpos(Auth::user()->name, ' ') + 1, 1)) }}
                    </div>
                    <span class="hidden sm:block text-sm font-medium text-white">{{ Auth::user()->name }}</span>
                    <svg class="w-4 h-4 shrink-0 text-blue-200 transition-transform" id="dropdownArrow"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div id="dropdownMenu"
                    class="hidden fixed bg-white rounded-md shadow-xl ring-1 ring-black ring-opacity-5 z-9999 overflow-hidden w-56"
                    style="top:0;right:0;">

                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-100">
                        <p class="text-sm text-gray-500">Signed in as</p>
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ Auth::user()->role ?? 'Administrator' }}</p>
                    </div>

                    <div class="py-1">
                        <a href="#"
                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile Settings
                        </a>
                        <a href="#"
                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Help &amp; Support
                        </a>
                    </div>

                    <div class="border-t border-gray-100 py-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <script>
        (function() {
            const menu = document.getElementById('dropdownMenu');
            document.body.appendChild(menu);
        })();

        function toggleDropdown() {
            const btn = document.querySelector('#userDropdown button');
            const menu = document.getElementById('dropdownMenu');
            const arrow = document.getElementById('dropdownArrow');
            const isOpen = !menu.classList.contains('hidden');

            if (isOpen) {
                menu.classList.add('hidden');
                arrow.style.transform = 'rotate(0deg)';
                return;
            }

            const rect = btn.getBoundingClientRect();
            menu.style.top = (rect.bottom + window.scrollY + 6) + 'px';
            menu.style.left = 'auto';
            menu.style.right = (window.innerWidth - rect.right) + 'px';
            menu.classList.remove('hidden');
            arrow.style.transform = 'rotate(180deg)';
        }

        document.addEventListener('click', function(event) {
            const trigger = document.getElementById('userDropdown');
            const menu = document.getElementById('dropdownMenu');

            if (!trigger.contains(event.target) && !menu.contains(event.target)) {
                menu.classList.add('hidden');

                const arrow = document.getElementById('dropdownArrow');

                if (arrow) {
                    arrow.style.transform = 'rotate(0deg)';
                }
            }
        });
    </script>
</header>
