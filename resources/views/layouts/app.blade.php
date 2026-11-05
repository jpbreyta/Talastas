<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="TALASTAS is an ICT mapping and spatial analytics system for institutional ICT assessment and planning in Lipa City.">

    <title>{{ config('app.name', 'TALASTAS') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo_ict_mapping.png') }}">

    <!-- Fonts -->
    @include('partials.fonts')
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Core assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- GIS dependencies are loaded only on screens that render maps. --}}
    @if (request()->routeIs('home') || request()->routeIs('map'))
        @vite('resources/js/gis.js')
    @elseif (
        request()->routeIs('institutions.index')
        || request()->routeIs('institutions.create')
        || request()->routeIs('institutions.edit')
        || request()->routeIs('institutions.show')
    )
        @vite('resources/js/institution-location.js')
    @endif

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
<body class="talastas-app font-sans antialiased bg-gray-50 @auth role-{{ auth()->user()->role }} @endauth @hasSection('dashboard-layout') overflow-hidden h-screen @endif">
    @include('layouts.sidebar')
    <div id="app" class="talastas-shell min-w-0 flex flex-col @hasSection('dashboard-layout') h-screen overflow-hidden @else min-h-screen @endif">
        @include('layouts.header')

        <main class="min-w-0 @hasSection('dashboard-layout') flex-1 min-h-0 overflow-hidden flex flex-col @else flex-1 @endif">
            @yield('content')
        </main>

        @hasSection('dashboard-layout')
        @else
            @include('layouts.footer')
        @endif
    </div>
    @include('partials.notifications')
    @auth
    <script>
    (() => {
        const beat = () => {
            if (document.visibilityState !== 'visible') return;
            fetch(@json(route('activity.heartbeat')), {method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},credentials:'same-origin'}).catch(() => {});
        };
        setInterval(beat, 60000);
        document.addEventListener('visibilitychange',beat);
    })();
    </script>
    @endauth
    @yield('scripts')
</body>
</html>
