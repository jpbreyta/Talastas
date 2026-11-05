@props(['label', 'icon', 'value' => null, 'metricKey' => null, 'description' => null, 'formula' => null, 'count' => false])
<section class="dashboard-card dashboard-metric">
    <span class="dashboard-metric-icon" aria-hidden="true"><i class="fa-solid {{ $icon }}"></i></span>
    <div class="min-w-0">
        <div class="flex items-center gap-1.5">
            <h2>{{ $label }}</h2>
            @if ($formula)<x-metric-info :title="$label" :formula="$formula" :description="$description" />@endif
        </div>
        <p class="dashboard-metric-value {{ is_numeric($value) ? 'has-value' : '' }}" @if($metricKey) data-dashboard-metric="{{ $metricKey }}" @endif>
            {{ is_numeric($value) ? ($count ? number_format($value) : number_format($value, 1).'%') : ($metricKey ? 'Loading…' : 'No calculated data') }}
        </p>
        @if($count)<p class="dashboard-muted">Active Lipa City schools &amp; offices</p>@endif
    </div>
</section>
