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
        .terms-refined .legal-copy { font-size: 1rem; }
        .terms-refined .legal-copy p { margin: .75rem 0 1.1rem; line-height: 1.7; }
        @media (min-width: 640px) {
            .terms-refined .legal-copy section[aria-labelledby^="section-"] > p,
            .privacy-notice .legal-copy section[aria-labelledby^="section-"] > p { text-align: justify; text-align-last: left; }
        }
        .terms-refined .legal-copy h2, .terms-refined .legal-copy h3 { scroll-margin-top: 2rem; }
        .terms-refined #policy-title { scroll-margin-top: 2rem; }
        :is(.terms-refined, .privacy-notice) .legal-copy .terms-document-status p { margin: .4rem 0 0; font-size: .875rem; line-height: 1.6; }
        :is(.terms-refined, .privacy-notice) .legal-copy .terms-document-status .terms-status-label { font-size: .75rem; line-height: 1.5; }
        .terms-status-summary { display: flex; min-height: 5rem; align-items: center; gap: 1rem; padding: .75rem 1rem; list-style: none; }
        .terms-status-summary::-webkit-details-marker { display: none; }
        .terms-status-disclosure:not([open]) .terms-details-expanded,
        .terms-status-disclosure[open] .terms-details-collapsed { display: none; }
        .terms-status-disclosure[open] .fa-chevron-down { transform: rotate(180deg); }
        @media (max-width: 639px) {
            .terms-status-summary { flex-wrap: wrap; gap: .5rem; }
            .terms-status-summary > .min-w-0 { flex-basis: 100%; }
            .terms-status-toggle { min-height: 2.75rem; }
        }
        :is(.terms-refined, .privacy-notice) .legal-copy .terms-document-status .terms-status-explanation { margin-top: .75rem; }
        :is(.terms-refined, .privacy-notice) .terms-status-metadata dd { overflow-wrap: anywhere; }
        .terms-refined [data-toc-link] { transition: none; }
        .terms-refined [data-toc-link]:hover { padding-left: .75rem; }
        .terms-refined .legal-copy #policy-references + ul { font-size: .9375rem; line-height: 1.65; }
        .terms-refined .legal-copy details p { margin-bottom: 0; font-size: .875rem; }
        .terms-refined select:focus-visible { outline: 2px solid #800000; outline-offset: 3px; }
        @media (min-width: 1024px) {
            .terms-refined .legal-document-navigation { top: 1.5rem; }
            .terms-refined .legal-document-navigation nav { max-height: calc(100dvh - 3rem); }
        }
        @media (max-width: 1023px) { .terms-refined .legal-document-navigation { display: none; } }
        @media (max-width: 639px) {
            .terms-refined .legal-copy { padding: 1.25rem; font-size: .9375rem; }
            .terms-refined header nav { gap: .25rem .5rem; }
            .terms-refined .legal-actions[aria-labelledby="consent-heading"] .legal-button { width: 100%; }
        }
        @media print { .terms-jump, .legal-back-top { display: none; } }
        @media print { .legal-footer { position: static; } }
    </style>
</head>
<body class="flex min-h-screen flex-col bg-gray-50 font-sans text-gray-900 {{ ($document['kind'] ?? null) === 'terms' ? 'terms-refined' : (($document['kind'] ?? null) === 'privacy' ? 'privacy-notice' : '') }}">
@include('partials.global-loader')
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
    <main id="legal-content" class="mx-auto w-full max-w-7xl flex-1 px-4 py-8" tabindex="-1">
        @yield('content')
    </main>
    <div class="legal-footer sticky bottom-0 z-20 shrink-0">
        @include('layouts.footer')
    </div>
<script>
    document.querySelectorAll('[data-policy-document]').forEach(panel => {
        const links = [...panel.querySelectorAll('[data-toc-link]')];
        const headings = links.map(link => document.getElementById(link.hash.slice(1))).filter(Boolean);
        const jump = panel.querySelector('[data-policy-jump]');
        if (!headings.length) return;
        const update = () => {
            let current = headings[0];
            headings.forEach(heading => { if (heading.getBoundingClientRect().top <= (jump ? 64 : 120)) current = heading; });
            if (jump && window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 2
                && headings[headings.length - 1].getBoundingClientRect().top < window.innerHeight) current = headings[headings.length - 1];
            links.forEach(link => {
                if (link.hash === '#' + current.id) link.setAttribute('aria-current', 'location');
                else link.removeAttribute('aria-current');
            });
            if (jump && document.activeElement !== jump) jump.value = current.id;
        };
        let scheduled = false;
        window.addEventListener('scroll', () => {
            if (scheduled) return;
            scheduled = true;
            requestAnimationFrame(() => { update(); scheduled = false; });
        }, { passive: true });
        window.addEventListener('resize', update);
        window.addEventListener('hashchange', update);
        jump?.addEventListener('change', () => {
            const heading = headings.find(item => item.id === jump.value);
            if (!heading) return;
            window.location.hash = heading.id;
            heading.focus({ preventScroll: true });
            update();
        });
        update();
    });
</script>
</body>
</html>
