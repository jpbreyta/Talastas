@props(['message' => null, 'size' => 'sm', 'color' => 'red-600', 'class' => null])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 ' . ($class ?? '')])->class(['global-loading']) }}
    role="status"
    aria-live="polite"
>
    <span
        class="flex shrink-0 items-center justify-center {{ match($size) {
            'xs' => 'h-4 w-4',
            'sm' => 'h-5 w-5',
            'md' => 'h-6 w-6',
            'lg' => 'h-8 w-8',
            default => 'h-5 w-5',
        } }} text-{{ $color }}"
        aria-hidden="true"
    >
        <i class="fa-solid fa-circle-notch fa-spin motion-reduce:animate-none"></i>
    </span>

    @if ($message)
        <span class="text-xs text-gray-600 leading-none">{{ $message }}</span>
    @endif
</div>

<style>
    .global-loading { display: inline-flex; }
</style>
