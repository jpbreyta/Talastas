<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Policies') | TALASTAS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    @vite(['resources/css/app.css'])
    <style>
        [data-policy-document] { gap: 1.75rem; }
        .legal-copy { padding: 2rem; }
        .legal-copy #policy-title { margin: 0; padding: 0; border: 0; font-size: clamp(1.75rem, 2.5vw, 2.25rem); font-weight: 750; line-height: 1.2; letter-spacing: -.025em; }
        [data-policy-document] aside nav { padding: 1rem; }
        [data-toc-link] { padding: .75rem; line-height: 1.5; color: #374151; }
        [data-policy-document] aside ul { display: grid; gap: .25rem; }
        [data-policy-document] aside li { margin: 0; }
        .legal-copy p { margin-top: 1rem; margin-bottom: 1.5rem; line-height: 1.8; overflow-wrap: anywhere; }
        .legal-copy h2, .legal-copy h3 { font-weight: 700; padding-bottom: .65rem; border-bottom: 1px solid #d1d5db; scroll-margin-top: 2rem; line-height: 1.4; }
        .legal-copy h2 { font-size: 1.35rem; margin-top: 2.25rem; }
        .legal-copy h3 { font-size: 1.15rem; margin-top: 2rem; }
        .legal-copy a { color: #7a1f2b; text-decoration: underline; text-underline-offset: 3px; }
        .legal-copy, main { overflow-wrap: anywhere; min-width: 0; }
        .legal-navigation a { text-decoration: none; }
        .legal-navigation a[aria-current="location"] { background: #fef2f2; color: #800000; box-shadow: inset 3px 0 #800000; font-weight: 700; }
        header nav a { display: inline-flex; min-height: 44px; align-items: center; border-radius: .375rem; padding-inline: .5rem; }
        header nav a:hover { background: rgb(255 255 255 / .15); }
        header a:focus-visible { outline-color: white; }
        a:focus-visible, button:focus-visible, input:focus-visible, summary:focus-visible, [tabindex="-1"]:focus { outline: 2px solid #800000; outline-offset: 4px; box-shadow: 0 0 0 2px white; }
        @media (min-width: 1024px) {
            [data-policy-document] { grid-template-columns: minmax(15rem, 17rem) minmax(0, 1fr); }
            [data-policy-document] > aside, [data-policy-document] > article { grid-column: auto; }
            .legal-copy { padding: 2.5rem; }
        }
        @media (max-width: 639px) {
            .legal-copy { padding: 1.5rem; }
            [data-policy-document] { gap: 1.25rem; }
        }
        .legal-status { display: flex; align-items: flex-start; gap: .75rem; padding: 1rem; border: 1px solid; border-radius: .75rem; line-height: 1.65; }
        .legal-status > i { flex-shrink: 0; margin-top: .3rem; }
        .legal-status-success { color: #166534; background: #f0fdf4; border-color: #bbf7d0; }
        .legal-status-pending { color: #92400e; background: #fffbeb; border-color: #fde68a; }
        .legal-button { display: inline-flex; min-height: 48px; align-items: center; justify-content: center; gap: .6rem; border: 1px solid #800000; border-radius: .6rem; padding: .75rem 1.25rem; font-size: .875rem; font-weight: 650; line-height: 1.5; text-decoration: none; }
        .legal-button-primary { background: #800000; color: white; }
        .legal-button-primary:hover { background: #600000; }
        .legal-button-secondary { background: white; color: #800000; }
        .legal-button-secondary:hover { background: #fef2f2; }
        @media (max-width: 479px) { .legal-action-row > a, .legal-action-row > button, .legal-action-row > form, .legal-action-row > form > button { width: 100%; } }
        @media print { .legal-navigation, .legal-actions { display: none; } .legal-copy { box-shadow: none; border: 0; } }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900">
    <a href="#legal-content" class="sr-only bg-white p-3 text-gray-900 focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:ring-2 focus:ring-offset-2">Skip to policy content</a>
    <header class="bg-[#800000] text-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-5">
            <span class="text-lg font-bold">TALASTAS - ICT Mapping</span>
            <nav aria-label="Policy navigation" class="legal-navigation flex flex-wrap gap-4 text-sm">
                <a href="{{ route('legal.terms') }}">Terms and Conditions</a>
                <a href="{{ route('legal.privacy') }}">Privacy Notice</a>
                @auth
                    <a href="{{ route('legal.review') }}">My acknowledgement</a>
                    <a href="{{ route('home') }}">Dashboard</a>
                @else
                    <a href="{{ route('login') }}">Sign in</a>
                @endauth
            </nav>
        </div>
    </header>
    <main id="legal-content" class="mx-auto max-w-7xl px-4 py-8" tabindex="-1">
        @yield('content')
    </main>
<script>
    document.querySelectorAll('[data-policy-document]').forEach(panel => {
        const links = [...panel.querySelectorAll('[data-toc-link]')];
        const headings = links.map(link => document.getElementById(link.hash.slice(1))).filter(Boolean);
        if (!headings.length) return;
        const update = () => {
            let current = headings[0];
            headings.forEach(heading => { if (heading.getBoundingClientRect().top <= 120) current = heading; });
            links.forEach(link => {
                if (link.hash === '#' + current.id) link.setAttribute('aria-current', 'location');
                else link.removeAttribute('aria-current');
            });
        };
        let scheduled = false;
        window.addEventListener('scroll', () => {
            if (scheduled) return;
            scheduled = true;
            requestAnimationFrame(() => { update(); scheduled = false; });
        }, { passive: true });
        window.addEventListener('resize', update);
        update();
    });
</script>
</body>
</html>
