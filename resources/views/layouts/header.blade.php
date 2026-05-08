<header class="bg-blue-900 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo & Branding -->
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V5.618a1 1 0 00-.553-.894L15 7m0 13V7"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-bold text-white block leading-tight">Lipa City ICT Mapping</span>
                        <span class="text-xs text-blue-200">Digital Infrastructure & Gap Analysis</span>
                    </div>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="hidden md:flex space-x-1">
                <a href="{{ route('home') }}" class="px-4 py-2 rounded-md text-sm font-medium text-blue-100 hover:text-white hover:bg-blue-800 transition-colors">
                    Home
                </a>

                <a href="{{ route('analytics') }}" class="px-4 py-2 rounded-md text-sm font-medium text-blue-100 hover:text-white hover:bg-blue-800 transition-colors">
                    Analytics Dashboard
                </a>
            </nav>

            <!-- Auth Buttons -->
            <div class="flex items-center space-x-3">
                @auth
                    <span class="text-sm text-blue-200 hidden sm:inline">
                        {{ Auth::user()->name }} ({{ Auth::user()->role }})
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-blue-900 bg-white border border-transparent rounded-md hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-blue-900 bg-white border border-transparent rounded-md hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        LGU Admin Login
                    </a>
                @endauth
            </div>
        </div>
    </div>
</header>
