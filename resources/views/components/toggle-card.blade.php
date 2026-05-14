@props(['name', 'label', 'checked' => false, 'color' => 'blue'])
@php
    $colorMap = [
        'blue'   => ['active' => 'bg-blue-50 border-blue-400',   'text' => 'text-blue-700',   'track' => 'bg-blue-500'],
        'green'  => ['active' => 'bg-green-50 border-green-400',  'text' => 'text-green-700',  'track' => 'bg-green-500'],
        'red'    => ['active' => 'bg-red-50 border-red-400',      'text' => 'text-red-700',    'track' => 'bg-red-500'],
        'yellow' => ['active' => 'bg-yellow-50 border-yellow-400','text' => 'text-yellow-700', 'track' => 'bg-yellow-500'],
    ];
    $c = $colorMap[$color] ?? $colorMap['blue'];
    $uid = 'tc_' . $name . '_' . uniqid();
@endphp
<label for="{{ $uid }}" class="toggle-card-label relative cursor-pointer select-none block">
    <input type="checkbox" name="{{ $name }}" id="{{ $uid }}" value="1"
           {{ $checked ? 'checked' : '' }}
           class="sr-only toggle-card-input">
    <div class="toggle-card-body flex items-center gap-3 px-4 py-3 rounded-lg border border-gray-200 bg-white transition-all"
         data-active-class="{{ $c['active'] }}"
         data-text-class="{{ $c['text'] }}"
         data-track-class="{{ $c['track'] }}">
        {{-- Track --}}
        <div class="toggle-track w-9 h-5 rounded-full bg-gray-200 relative transition-colors shrink-0">
            <span class="toggle-thumb absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform block"></span>
        </div>
        <span class="toggle-label-text text-sm font-medium text-gray-500 transition-colors">{{ $label }}</span>
    </div>
</label>
