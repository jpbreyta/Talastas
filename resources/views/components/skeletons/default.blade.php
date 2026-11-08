@props([])

<!-- Default page skeleton -->
<div class="space-y-4">
    <div class="talastas-skeleton-card p-6">
        <div class="talastas-skeleton-text h-4 w-1/3 mb-3"></div>
        <div class="talastas-skeleton-text h-3 w-full mb-2"></div>
        <div class="talastas-skeleton-text h-3 w-3/4"></div>
        <div class="talastas-skeleton-text h-3 w-1/2 mt-3"></div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @for($i = 0; $i < 3; $i++)
            <div class="talastas-skeleton-card p-4 h-48">
                <div class="talastas-skeleton-placeholder h-4 w-1/2 mb-3"></div>
                <div class="talastas-skeleton-text h-3 w-full mb-2"></div>
                <div class="talastas-skeleton-text h-3 w-3/4"></div>
            </div>
        @endfor
    </div>
</div>

