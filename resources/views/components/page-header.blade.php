@props(['title', 'subtitle' => null, 'backUrl' => null, 'backLabel' => 'Go back',
    'padded' => false, 'contentClass' => '', 'heading' => 'h1', 'titleId' => null,
    'toolbar' => false])

@php
    $titleInGlobalHeader = $heading === 'h1' && $__env->hasSection('page-title');
    $hasContentContext = ! $titleInGlobalHeader || $backUrl || trim((string) $slot) !== '';
@endphp

@if ($hasContentContext || isset($actions))
<header {{ $attributes->class(['talastas-page-header shrink-0', 'px-5' => $padded,
    'talastas-page-header-context' => $titleInGlobalHeader,
    'talastas-page-toolbar' => $toolbar,
    'talastas-page-header-dialog' => $heading === 'h2']) }}>
    <div class="talastas-page-header-inner {{ $contentClass }}">
        @if ($hasContentContext)
        <div class="flex min-w-0 flex-1 items-start gap-3">
            @if ($backUrl)
                <a href="{{ $backUrl }}" aria-label="{{ $backLabel }}" title="{{ $backLabel }}"
                    class="talastas-page-header-back">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                </a>
            @endif
            <div class="min-w-0">
                @if ($titleInGlobalHeader)
                    @if ($subtitle && $backUrl)
                        <p class="text-sm font-semibold text-slate-800">{{ $title }}</p>
                        <p class="talastas-page-header-subtitle">{{ $subtitle }}</p>
                    @endif
                @elseif ($heading === 'h2')
                    <h2 @if ($titleId) id="{{ $titleId }}" @endif class="talastas-page-header-title">{{ $title }}</h2>
                @else
                    <h1 @if ($titleId) id="{{ $titleId }}" @endif class="talastas-page-header-title">{{ $title }}</h1>
                @endif
                @if ($subtitle && ! $titleInGlobalHeader)
                    <p class="talastas-page-header-subtitle">{{ $subtitle }}</p>
                @endif
                {{ $slot }}
            </div>
        </div>
        @endif
        @isset($actions)
            <div class="talastas-page-header-actions">{{ $actions }}</div>
        @endisset
    </div>
</header>
@endif
