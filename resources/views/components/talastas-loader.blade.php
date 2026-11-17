@props(['variant' => 'inline', 'active' => true, 'label' => 'Loading content'])
<div {{ $attributes->class([
        'talastas-loader-overlay' => $variant === 'page',
        'talastas-loader-inline' => $variant !== 'page',
        'talastas-loader-compact' => $variant === 'compact',
    ]) }} @if (!$active) hidden @endif aria-hidden="{{ $active ? 'false' : 'true' }}"
    role="status" aria-live="polite" aria-atomic="true" @if ($variant === 'page') tabindex="-1" @endif>
    <div class="talastas-loader-body" aria-hidden="true">
        <div class="talastas-loader-mark">
            <span class="talastas-loader-ring"></span>
            <img src="{{ asset('images/logo_ict_mapping.png') }}" width="1080" height="1080" alt="">
        </div>
        <p class="talastas-loader-label">Loading</p>
    </div>
    <span class="sr-only" data-talastas-loader-status>{{ $label }}</span>
</div>
