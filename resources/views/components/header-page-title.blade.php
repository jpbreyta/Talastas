<h1 {{ $attributes->class(['talastas-header-page-title min-w-0 text-white']) }}>
    @hasSection('page-icon')
        <i class="fa-solid {{ trim($__env->yieldContent('page-icon')) }}" aria-hidden="true"></i>
    @endif
    <span class="min-w-0 truncate">@yield('page-title')</span>
</h1>
