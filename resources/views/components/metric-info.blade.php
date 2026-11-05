@props([
    'title',
    'formula',
    'description',
])

<button type="button"
    data-metric-info-trigger
    data-info-title="{{ $title }}"
    data-info-description="{{ $description }}"
    data-info-formula="{{ $formula }}"
    class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white text-[10px] text-slate-500 transition hover:border-slate-400 hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-100"
    aria-label="Information about {{ $title }}"
    aria-expanded="false">
    <i class="fa-solid fa-info" aria-hidden="true"></i>
</button>
