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
<body class="font-sans antialiased bg-gray-50 flex flex-col h-screen overflow-hidden">
    <div id="app" class="flex-1 flex flex-col min-h-0">
        @include('layouts.header')

        <main class="flex-1 flex flex-col min-h-0 overflow-hidden">
            @yield('content')
        </main>

        @include('layouts.footer')
    </div>
    @yield('scripts')
</body>
</html>
