@props(['page' => 'default', 'message' => null])

@php
    $reducedMotion = request()->cookie('talastas_reduced_motion', '0') === '1';
@endphp

<!-- Page skeleton overlay - shown during server-side page transitions -->
<div
    id="talastasPageSkeleton"
    {{ $attributes->merge(['class' => 'fixed inset-0 z-[1900] flex items-center justify-center bg-white/95 backdrop-blur-sm opacity-0 transition-opacity duration-200 lg:left-[200px]', 'hidden' => true]) }}
    role="status"
    aria-live="polite"
    aria-label="Loading page content"
>
    <div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <!-- Page header placeholder -->
        <div class="space-y-4 mb-8">
            <div class="h-8 bg-slate-200 rounded w-1/3 motion-reduce:animate-none {{ $reducedMotion ? '' : 'animate-pulse' }}"></div>
            <div class="h-4 bg-slate-200 rounded w-1/5 motion-reduce:animate-none {{ $reducedMotion ? '' : 'animate-pulse' }}"></div>
        </div>

        @if ($page === 'dashboard')
            @include('components.skeletons.dashboard')
        @elseif ($page === 'institutions')
            @include('components.skeletons.institutions')
        @elseif ($page === 'inventory')
            @include('components.skeletons.inventory')
        @elseif ($page === 'analytics')
            @include('components.skeletons.analytics')
        @elseif ($page === 'users')
            @include('components.skeletons.users')
        @else
            @include('components.skeletons.default')
        @endif

        @if ($message)
            <p class="mt-4 text-xs text-gray-500 text-center">{{ $message }}</p>
        @endif
    </div>
</div>
<script>
    (() => {
        const key = 'talastas:navigation-pending';
        try {
            if (sessionStorage.getItem(key) !== '1') return;
            sessionStorage.removeItem(key);
        } catch {
            return;
        }
        const skeleton = document.getElementById('talastasPageSkeleton');
        if (!skeleton) return;
        skeleton.hidden = false;
        skeleton.classList.remove('opacity-0');
        skeleton.removeAttribute('aria-hidden');
    })();
</script>

<style>
    .talastas-skeleton-placeholder {
        background: #e2e8f0;
        border-radius: 0.5rem;
        animation: talastas-skeleton-pulse 1.5s ease-in-out infinite;
    }
    .talastas-skeleton-text {
        background: #cbd5e1;
        border-radius: 0.25rem;
        animation: talastas-skeleton-pulse 1.5s ease-in-out infinite;
    }
    .talastas-skeleton-avatar {
        background: #e2e8f0;
        border-radius: 9999px;
        animation: talastas-skeleton-pulse 1.5s ease-in-out infinite;
    }
    .talastas-skeleton-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        animation: talastas-skeleton-pulse 1.5s ease-in-out infinite;
        overflow: hidden;
    }
    .talastas-skeleton-table-row {
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        animation: talastas-skeleton-pulse 1.5s ease-in-out infinite;
    }
    @keyframes talastas-skeleton-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    @media (prefers-reduced-motion: reduce) {
        .talastas-skeleton-placeholder,
        .talastas-skeleton-text,
        .talastas-skeleton-avatar,
        .talastas-skeleton-card,
        .talastas-skeleton-table-row {
            animation: none;
            opacity: 1;
        }
    }
</style>
