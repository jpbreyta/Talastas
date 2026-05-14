<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'GIS-Based Digital Map for ICT Infrastructure & Gap Analysis - Lipa City') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 @hasSection('dashboard-layout') overflow-hidden h-screen @endif">
    <div id="app" class="flex flex-col @hasSection('dashboard-layout') h-screen overflow-hidden @else min-h-screen @endif">
        @include('layouts.header')

        <main class="@hasSection('dashboard-layout') flex-1 min-h-0 overflow-hidden flex flex-col @else flex-1 @endif">
            @yield('content')
        </main>

        {{-- Footer always rendered; in dashboard-layout it is flex-shrink-0 and stays pinned --}}
        @include('layouts.footer')
    </div>
    @yield('scripts')
</body>
</html>
