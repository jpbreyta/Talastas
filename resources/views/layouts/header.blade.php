<header class="bg-red-900 border-b border-red-800 sticky top-0 z-100">
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex items-center h-16">

            <!-- Logo & Branding (left) -->
            <div class="flex items-center gap-3 shrink-0 w-64">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V5.618a1 1 0 00-.553-.894L15 7m0 13V7" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-sm font-bold text-white block leading-tight">Lipa City ICT Mapping</span>
                        <span class="text-xs text-red-300 leading-tight">Digital Infrastructure &amp; Gap
                            Analysis</span>
                    </div>
                </a>
            </div>

            <!-- Navigation (center) -->
            <nav class="hidden md:flex items-center justify-center gap-1 flex-1">
                <a href="{{ route('home') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('home') || request()->routeIs('map') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Home
                </a>
                <a href="{{ route('institutions.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('institutions.*') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Institutions
                </a>
                <a href="{{ route('ict-inventory.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('ict-inventory.*') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Inventory
                </a>
                <a href="{{ route('analytics') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('analytics') ? 'bg-red-800 text-white' : 'text-red-200 hover:text-white hover:bg-red-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Analytics
                </a>
            </nav>

            <!-- Notification Bell (right, before user dropdown) -->
            <div class="relative shrink-0 flex items-center" id="notificationTrigger">
                <button onclick="toggleNotifications()"
                    class="relative p-2 rounded-md text-red-200 hover:text-white hover:bg-red-800 transition-colors focus:outline-none"
                    aria-label="Notifications">
                    <!-- Bell icon -->
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <!-- Unread badge -->
                    <span id="notifBadge"
                        class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 leading-none">
                        3
                    </span>
                </button>
            </div>

            <!-- User Profile Dropdown (right) -->
            <div class="relative shrink-0 flex justify-end w-64" id="userDropdown">
                <button onclick="toggleDropdown()"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-md hover:bg-red-800 transition-colors focus:outline-none">
                    <div
                        class="h-8 w-8 shrink-0 rounded-full bg-red-500 border-2 border-red-300 flex items-center justify-center text-xs font-bold text-white select-none">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}{{ strtoupper(substr(Auth::user()->name, strrpos(Auth::user()->name, ' ') + 1, 1)) }}
                    </div>
                    <span class="hidden sm:block text-sm font-medium text-white">{{ Auth::user()->name }}</span>
                    <svg class="w-4 h-4 shrink-0 text-red-200 transition-transform" id="dropdownArrow"
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
                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors">
                            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile Settings
                        </a>
                        <a href="#"
                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors">
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

<div id="toastContainer" class="fixed top-5 right-5 z-[10001] flex flex-col gap-3 pointer-events-none"></div>

<!-- =====================================================================
     NOTIFICATIONS PANEL
     Appended to <body> via JS (same pattern as userDropdown) so it
     escapes the header's stacking context.
====================================================================== -->
<div id="notificationsPanel"
    class="hidden fixed z-[9999] w-[390px] bg-white rounded-2xl shadow-2xl ring-1 ring-black/8 overflow-hidden"
    style="top:0;right:0;">

    <!-- Panel Header -->
    <div class="bg-red-700 px-4 py-3 flex items-center gap-3">
        <svg class="w-5 h-5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span class="text-white font-semibold text-base flex-1">Notifications</span>
        <span id="notifNewBadge"
            class="bg-red-500 text-white text-xs font-semibold px-2 py-0.5 rounded-full border border-red-400">
            3 new
        </span>
        <button onclick="closeNotifications()"
            class="ml-2 text-red-200 hover:text-white transition-colors focus:outline-none"
            aria-label="Close notifications">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Action Bar -->
    <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-white">
        <button onclick="markAllRead()"
            class="flex items-center gap-1.5 text-sm text-red-600 hover:text-red-800 font-medium transition-colors focus:outline-none">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Mark all read
        </button>
        <button onclick="clearAllNotifications()"
            class="text-sm text-gray-400 hover:text-gray-600 font-medium transition-colors focus:outline-none">
            Clear all
        </button>
    </div>

    <!-- Notification List -->
    <div id="notifList" class="overflow-y-auto max-h-[420px] divide-y divide-gray-100">

        <!-- Critical -->
        <div class="notif-item flex items-start gap-3 px-4 py-3.5 hover:bg-red-50/60 transition-colors cursor-pointer bg-red-50/30"
            data-unread="true" onclick="markRead(this)">
            <!-- Icon: Critical -->
            <div class="shrink-0 w-9 h-9 rounded-full bg-red-100 border-2 border-red-300 flex items-center justify-center mt-0.5">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-semibold text-gray-900 leading-snug">Critical Gap Detected</p>
                    <span class="text-[11px] text-gray-400 shrink-0 mt-0.5">2m ago</span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Bagong Silang barangay gap score reached 82% — immediate audit recommended.</p>
            </div>
            <!-- Unread dot -->
            <span class="unread-dot shrink-0 w-2.5 h-2.5 rounded-full bg-red-600 mt-1.5"></span>
        </div>

        <!-- Warning -->
        <div class="notif-item flex items-start gap-3 px-4 py-3.5 hover:bg-red-50/60 transition-colors cursor-pointer bg-red-50/30"
            data-unread="true" onclick="markRead(this)">
            <!-- Icon: Warning -->
            <div class="shrink-0 w-9 h-9 rounded-full bg-amber-100 border-2 border-amber-300 flex items-center justify-center mt-0.5">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-semibold text-gray-900 leading-snug">Equipment Retired</p>
                    <span class="text-[11px] text-gray-400 shrink-0 mt-0.5">1h ago</span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">3 network switches in San Roque NHS have been flagged as retired due to poor condition.</p>
            </div>
            <!-- Unread dot -->
            <span class="unread-dot shrink-0 w-2.5 h-2.5 rounded-full bg-red-600 mt-1.5"></span>
        </div>

        <!-- Info -->
        <div class="notif-item flex items-start gap-3 px-4 py-3.5 hover:bg-red-50/60 transition-colors cursor-pointer bg-red-50/30"
            data-unread="true" onclick="markRead(this)">
            <!-- Icon: Info -->
            <div class="shrink-0 w-9 h-9 rounded-full bg-red-100 border-2 border-red-300 flex items-center justify-center mt-0.5">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-semibold text-gray-900 leading-snug">New Institution Added</p>
                    <span class="text-[11px] text-gray-400 shrink-0 mt-0.5">3h ago</span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Batasan Hills Community Learning Center has been registered in the system.</p>
            </div>
            <!-- Unread dot -->
            <span class="unread-dot shrink-0 w-2.5 h-2.5 rounded-full bg-red-600 mt-1.5"></span>
        </div>

        <!-- Success -->
        <div class="notif-item flex items-start gap-3 px-4 py-3.5 hover:bg-gray-50/80 transition-colors cursor-pointer"
            data-unread="false" onclick="markRead(this)">
            <!-- Icon: Success -->
            <div class="shrink-0 w-9 h-9 rounded-full bg-green-100 border-2 border-green-300 flex items-center justify-center mt-0.5">
                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium text-gray-700 leading-snug">Audit Completed</p>
                    <span class="text-[11px] text-gray-400 shrink-0 mt-0.5">Yesterday</span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Maliwanag TVET School audit for Q2 2026 was successfully submitted.</p>
            </div>
            <span class="unread-dot hidden shrink-0 w-2.5 h-2.5 rounded-full bg-red-600 mt-1.5"></span>
        </div>

        <!-- Warning 2 -->
        <div class="notif-item flex items-start gap-3 px-4 py-3.5 hover:bg-gray-50/80 transition-colors cursor-pointer"
            data-unread="false" onclick="markRead(this)">
            <!-- Icon: Warning -->
            <div class="shrink-0 w-9 h-9 rounded-full bg-amber-100 border-2 border-amber-300 flex items-center justify-center mt-0.5">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium text-gray-700 leading-snug">Audit Deadline Approaching</p>
                    <span class="text-[11px] text-gray-400 shrink-0 mt-0.5">Yesterday</span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">12 institutions have pending audits due before June 30, 2026.</p>
            </div>
            <span class="unread-dot hidden shrink-0 w-2.5 h-2.5 rounded-full bg-red-600 mt-1.5"></span>
        </div>

        <!-- Info 2 -->
        <div class="notif-item flex items-start gap-3 px-4 py-3.5 hover:bg-gray-50/80 transition-colors cursor-pointer"
            data-unread="false" onclick="markRead(this)">
            <!-- Icon: Info -->
            <div class="shrink-0 w-9 h-9 rounded-full bg-red-100 border-2 border-red-300 flex items-center justify-center mt-0.5">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium text-gray-700 leading-snug">System Update</p>
                    <span class="text-[11px] text-gray-400 shrink-0 mt-0.5">2 days ago</span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">GIS layer data has been refreshed with June 2026 barangay boundary updates.</p>
            </div>
            <span class="unread-dot hidden shrink-0 w-2.5 h-2.5 rounded-full bg-red-600 mt-1.5"></span>
        </div>

    </div>

    <!-- Empty State (hidden until Clear All) -->
    <div id="notifEmpty" class="hidden py-12 flex flex-col items-center justify-center text-center px-6">
        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-3">
            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
        </div>
        <p class="text-sm font-semibold text-gray-500">No new notifications</p>
        <p class="text-xs text-gray-400 mt-1">You're all caught up!</p>
    </div>

</div>

<script>
(function () {
    // Move the panel to <body> to escape stacking contexts (same pattern as user dropdown)
    const panel = document.getElementById('notificationsPanel');
    document.body.appendChild(panel);

    // ── State ─────────────────────────────────────────────────────────────
    let unreadCount = document.querySelectorAll('.notif-item[data-unread="true"]').length;
    updateBadge();

    // ── Toggle ────────────────────────────────────────────────────────────
    window.toggleNotifications = function () {
        const btn = document.querySelector('#notificationTrigger button');
        const isOpen = !panel.classList.contains('hidden');

        if (isOpen) {
            panel.classList.add('hidden');
            return;
        }

        // Close user dropdown if open
        const userMenu = document.getElementById('dropdownMenu');
        if (userMenu && !userMenu.classList.contains('hidden')) {
            userMenu.classList.add('hidden');
            const arrow = document.getElementById('dropdownArrow');
            if (arrow) arrow.style.transform = 'rotate(0deg)';
        }

        // Position panel below the bell button
        const rect = btn.getBoundingClientRect();
        panel.style.top  = (rect.bottom + window.scrollY + 8) + 'px';
        panel.style.left = 'auto';
        panel.style.right = (window.innerWidth - rect.right - 4) + 'px';
        panel.classList.remove('hidden');
    };

    window.closeNotifications = function () {
        panel.classList.add('hidden');
    };

    // ── Mark single item as read ──────────────────────────────────────────
    window.markRead = function (item) {
        if (item.dataset.unread === 'true') {
            item.dataset.unread = 'false';
            item.classList.remove('bg-red-50/30');
            // Hide the unread dot
            const dot = item.querySelector('.unread-dot');
            if (dot) dot.classList.add('hidden');
            // De-bold the title
            const title = item.querySelector('p.font-semibold');
            if (title) {
                title.classList.remove('font-semibold', 'text-gray-900');
                title.classList.add('font-medium', 'text-gray-700');
            }
            unreadCount = Math.max(0, unreadCount - 1);
            updateBadge();
        }
    };

    // ── Mark all read ─────────────────────────────────────────────────────
    window.markAllRead = function () {
        document.querySelectorAll('.notif-item[data-unread="true"]').forEach(item => markRead(item));
    };

    // ── Clear all ─────────────────────────────────────────────────────────
    window.clearAllNotifications = function () {
        const list = document.getElementById('notifList');
        const emptyState = document.getElementById('notifEmpty');
        list.innerHTML = '';
        emptyState.classList.remove('hidden');
        unreadCount = 0;
        updateBadge();
    };

    // ── Badge helper ──────────────────────────────────────────────────────
    function updateBadge() {
        const badge    = document.getElementById('notifBadge');
        const newBadge = document.getElementById('notifNewBadge');

        if (unreadCount > 0) {
            badge.textContent = unreadCount;
            badge.classList.remove('hidden');
            newBadge.textContent = unreadCount + ' new';
        } else {
            badge.classList.add('hidden');
            newBadge.textContent = '0 new';
        }
    }

    // ── Close on outside click ────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        const trigger = document.getElementById('notificationTrigger');
        if (!panel.contains(e.target) && !trigger.contains(e.target)) {
            panel.classList.add('hidden');
        }
    });
})();
</script>
<script>
(function () {
    const toastContainer = document.getElementById('toastContainer');

    const toastStyles = {
        success: {
            border: 'border-emerald-200',
            ring: 'ring-emerald-500/10',
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />',
            title: 'Success',
            color: 'text-emerald-600',
        },
        error: {
            border: 'border-rose-200',
            ring: 'ring-rose-500/10',
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />',
            title: 'Error',
            color: 'text-rose-600',
        },
        warning: {
            border: 'border-amber-200',
            ring: 'ring-amber-500/10',
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M4.93 19.07a10 10 0 1114.14 0 10 10 0 01-14.14 0z" />',
            title: 'Warning',
            color: 'text-amber-600',
        },
        info: {
            border: 'border-sky-200',
            ring: 'ring-sky-500/10',
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
            title: 'Info',
            color: 'text-sky-600',
        },
    };

    function removeToast(toast) {
        if (!toast) return;
        toast.classList.add('opacity-0', 'translate-x-3');
        setTimeout(() => toast.remove(), 180);
    }

    function installToastGestures(toast) {
        let startX = 0;
        let currentX = 0;
        let dragging = false;

        function updateTransform() {
            toast.style.transform = `translateX(${currentX}px)`;
            toast.style.opacity = `${Math.max(0, 1 - Math.abs(currentX) / 240)}`;
        }

        function onPointerMove(event) {
            if (!dragging) return;
            currentX = Math.max(0, event.clientX - startX);
            updateTransform();
        }

        function onPointerUp() {
            if (!dragging) return;
            dragging = false;
            toast.releasePointerCapture(toast.pointerId);
            toast.style.transition = 'transform .2s ease, opacity .2s ease';

            if (currentX > 100) {
                toast.style.transform = 'translateX(120%)';
                toast.style.opacity = '0';
                setTimeout(() => removeToast(toast), 180);
            } else {
                currentX = 0;
                toast.style.transform = 'translateX(0)';
                toast.style.opacity = '1';
            }

            window.removeEventListener('pointermove', onPointerMove);
            window.removeEventListener('pointerup', onPointerUp);
        }

        toast.addEventListener('pointerdown', function (event) {
            event.preventDefault();
            startX = event.clientX;
            currentX = 0;
            dragging = true;
            toast.style.transition = 'none';
            toast.setPointerCapture(event.pointerId);
            window.addEventListener('pointermove', onPointerMove);
            window.addEventListener('pointerup', onPointerUp);
        });
    }

    window.showToast = function (type, message, duration = 4500) {
        if (!toastContainer || !message) return;

        const style = toastStyles[type] || toastStyles.info;
        const toast = document.createElement('div');
        toast.className = `pointer-events-auto w-full overflow-hidden rounded-3xl border bg-white px-4 py-3 shadow-2xl ring-1 ${style.ring} transition-all duration-200 ease-out opacity-0 translate-x-3`
        toast.innerHTML = `
            <div class="flex gap-3 items-start">
                <div class="mt-0.5 shrink-0 rounded-2xl bg-slate-100 p-2 text-slate-700 ${style.color}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">${style.icon}</svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-900">${style.title}</p>
                        <button type="button" class="text-slate-400 hover:text-slate-600 focus:outline-none" aria-label="Close toast">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-slate-600 mt-1 break-words">${message}</p>
                </div>
            </div>
        `;

        const closeButton = toast.querySelector('button');
        closeButton.addEventListener('click', () => removeToast(toast));
        installToastGestures(toast);

        toastContainer.appendChild(toast);
        requestAnimationFrame(() => {
            toast.classList.remove('opacity-0', 'translate-x-3');
            toast.style.transform = 'translateX(0)';
        });

        setTimeout(() => removeToast(toast), duration);
    };

    @if(session('success'))
        window.showToast('success', @json(session('success')));
    @endif
    @if(session('error'))
        window.showToast('error', @json(session('error')));
    @endif
    @if(session('warning'))
        window.showToast('warning', @json(session('warning')));
    @endif
    @if(session('info'))
        window.showToast('info', @json(session('info')));
    @endif
})();
</script>
