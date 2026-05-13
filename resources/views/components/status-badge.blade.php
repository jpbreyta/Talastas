@props(['label', 'active' => false, 'activeColor' => 'green'])
@php
    $colors = [
        'green'  => ['bg' => 'bg-green-50',  'border' => 'border-green-200',  'text' => 'text-green-700',  'dot' => 'bg-green-500'],
        'red'    => ['bg' => 'bg-red-50',    'border' => 'border-red-200',    'text' => 'text-red-700',    'dot' => 'bg-red-500'],
        'yellow' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'text' => 'text-yellow-700', 'dot' => 'bg-yellow-500'],
    ];
    $c = $active ? ($colors[$activeColor] ?? $colors['green']) : null;
@endphp
<div class="flex items-center gap-2 px-3 py-2 rounded-lg border
    {{ $active ? $c['bg'].' '.$c['border'] : 'bg-gray-50 border-gray-200' }}">
    <span class="w-2 h-2 rounded-full shrink-0 {{ $active ? $c['dot'] : 'bg-gray-300' }}"></span>
    <span class="text-xs font-medium {{ $active ? $c['text'] : 'text-gray-400' }}">{{ $label }}</span>
</div>
