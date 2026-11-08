@props([])

<!-- Analytics skeleton: metric cards + chart placeholders -->
<div class="space-y-6">
    <!-- Report selection form -->
    <div class="talastas-skeleton-card p-4">
        <div class="talastas-skeleton-placeholder h-4 w-1/3 mb-3"></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @for($i = 0; $i < 3; $i++)
                <div class="talastas-skeleton-card p-3">
                    <div class="talastas-skeleton-text h-4 w-full mb-2"></div>
                    <div class="talastas-skeleton-text h-3 w-1/2 mb-2"></div>
                    <button class="talastas-skeleton-placeholder h-10 w-full rounded-lg"></button>
                </div>
            @endfor
        </div>
    </div>

    <!-- Metric cards grid -->
    <div class="dashboard-summary-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @for($i = 0; $i < 3; $i++)
            <div class="talastas-skeleton-card p-4">
                <div class="flex items-center gap-3">
                    <div class="talastas-skeleton-avatar h-8 w-8"></div>
                    <div class="talastas-skeleton-text h-4 w-1/3"></div>
                </div>
                <div class="talastas-skeleton-placeholder h-4 w-2/3 mt-2"></div>
                <div class="talastas-skeleton-placeholder h-3 w-full mt-2"></div>
            </div>
        @endfor
    </div>

    <!-- Chart placeholders -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="talastas-skeleton-card h-[300px] p-4">
            <div class="talastas-skeleton-placeholder h-4 w-1/3 mb-3"></div>
            <div class="talastas-skeleton-placeholder h-[200px] w-full rounded-lg"></div>
        </div>
        <div class="talastas-skeleton-card h-[300px] p-4">
            <div class="talastas-skeleton-placeholder h-4 w-1/3 mb-3"></div>
            <div class="talastas-skeleton-placeholder h-[200px] w-full rounded-lg"></div>
        </div>
    </div>
</div>

