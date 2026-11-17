@props(['label' => 'Loading…'])
<div {{ $attributes->class(['absolute inset-0 z-[1300] flex items-center justify-center bg-white/70 text-[#0F2C59]']) }} hidden>
    <x-talastas-loader variant="compact" :label="$label" />
</div>
