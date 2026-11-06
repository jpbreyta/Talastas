<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo_ict_mapping.png') }}">
    <title>@yield('title') | TALASTAS ICT Mapping</title>@vite('resources/css/app.css')
    @yield('styles')
</head>

<body class="min-h-screen bg-gray-100 text-gray-900">
    <main class="mx-auto @yield('container-class', 'max-w-xl') px-4 py-8 sm:py-12">
        <a href="{{ route('login') }}" class="text-sm font-semibold text-red-900">TALASTAS ICT Mapping</a>
        <section
            class="@yield('card-class') mt-5 space-y-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8">
            <header class="security-heading">
                <h1 class="text-2xl font-semibold">@yield('title')</h1>
                @hasSection('subtitle')
                    <p class="mt-2 text-gray-600">@yield('subtitle')</p>
                @endif
            </header>
            @if (session('status'))
                <p role="status" class="rounded-lg bg-blue-50 p-3 text-sm text-blue-900">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <div role="alert" class="rounded-lg bg-red-50 p-3 text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            @yield('content')
            @auth<form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"
                        class="@yield('signout-class', 'text-sm text-red-900 underline')">Sign out</button>
            </form>@endauth
        </section>
        <nav class="mt-5 flex gap-4 text-sm text-gray-600">
            <a href="{{ route('legal.terms') }}">Terms and Conditions</a>
            <a href="{{ route('legal.privacy') }}">Privacy Notice</a>
        </nav>
    </main>
    <script>
        document.querySelectorAll('form').forEach(form => form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                button.textContent = 'Please wait...';
            }
        }));
    </script>
    @yield('scripts')
</body>

</html>
