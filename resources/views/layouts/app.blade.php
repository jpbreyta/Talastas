<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'TALASTAS') }}</title>

    <link rel="icon" type="image/png" href="/images/talastas.svg">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @auth
        {{--
            Presentation-only role guard.
            Real authorization is enforced by route middleware; these rules simply
            keep controls that the current user cannot execute out of the UI.
        --}}
        @if (! auth()->user()->canManageInstitutions())
            <style>
                [data-open-institution-modal],
                [data-delete-institution],
                .delete-institution-button,
                [data-open-notes-modal],
                [data-infrastructure-analytics-form],
                a[href$="/institutions/create"],
                a[href*="/institutions/"][href$="/edit"] {
                    display: none !important;
                }
            </style>
        @endif

        @if (! auth()->user()->canManageInventory())
            <style>
                a[href$="/ict-inventory/create"],
                a[href*="/ict-inventory/create?"],
                a[href*="/ict-inventory/"][href$="/edit"],
                a[href*="/ict-inventory/"][href*="/edit?"] {
                    display: none !important;
                }
            </style>
        @endif

        @if (! auth()->user()->canDeleteInventory())
            <style>
                form:has(input[name="_method"][value="DELETE"]) {
                    display: none !important;
                }
            </style>
        @endif
    @endauth
</head>
<body class="font-sans antialiased bg-gray-50 @auth role-{{ auth()->user()->role }} @endauth @hasSection('dashboard-layout') overflow-hidden h-screen @endif">
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
