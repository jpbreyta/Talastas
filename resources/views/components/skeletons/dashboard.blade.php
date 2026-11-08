@props([])

<!-- Dashboard skeleton: metric cards + map workspace -->
<div class="space-y-6">
    <!-- Metric cards row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @for ($i = 0; $i < 3; $i++)
            <div class="talastas-skeleton-card p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="talastas-skeleton-avatar h-8 w-8"></div>
                    <div class="talastas-skeleton-text h-4 w-1/3"></div>
                </div>
                <div class="talastas-skeleton-placeholder h-4 w-3/4 mb-2"></div>
                <div class="talastas-skeleton-placeholder h-3 w-full"></div>
                <div class="talastas-skeleton-placeholder h-3 w-1/2 mt-2"></div>
            </div>
        @endfor
    </div>

    <!-- Map workspace placeholder -->
    <div class="talastas-skeleton-card h-[400px] p-4">
        <div class="talastas-skeleton-placeholder h-4 w-1/3 mb-3"></div>
        <div class="talastas-skeleton-placeholder h-[300px] w-full rounded-lg"></div>
    </div>

    <!-- Insights sidebar placeholder -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:col-span-3">
        @for ($i = 0; $i < 2; $i++)
            <div class="talastas-skeleton-card p-4 h-48">
                <div class="talastas-skeleton-placeholder h-4 w-1/2 mb-3"></div>
                <div class="talastas-skeleton-text h-3 w-full mb-2"></div>
                <div class="talastas-skeleton-text h-3 w-3/4"></div>
                <div class="talastas-skeleton-text h-3 w-1/2 mt-3"></div>
            </div>
        @endfor
    </div>
</div>
