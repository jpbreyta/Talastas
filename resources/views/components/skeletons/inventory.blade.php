@props([])

<!-- Inventory skeleton: search + filters + table placeholders -->
<div class="space-y-4">
    <!-- Search + filter toolbar -->
    <div class="flex flex-wrap gap-4 border-b border-gray-200 bg-gray-50/70 px-5 py-2">
        <div class="w-full sm:w-64">
            <div class="talastas-skeleton-card h-10 p-3">
                <div class="talastas-skeleton-text h-5 w-full"></div>
            </div>
        </div>
        <div class="w-full sm:w-60">
            <div class="talastas-skeleton-card h-10 p-3">
                <div class="talastas-skeleton-text h-5 w-full"></div>
            </div>
        </div>
        <div class="w-full sm:w-60">
            <div class="talastas-skeleton-card h-10 p-3">
                <div class="talastas-skeleton-text h-5 w-full"></div>
            </div>
        </div>
        <div class="ml-auto flex shrink-0 items-center gap-4">
            <div class="talastas-skeleton-card h-10 px-4">
                <div class="talastas-skeleton-text h-5 w-12"></div>
            </div>
        </div>
    </div>

    <!-- Inventory results placeholders -->
    <div class="space-y-3">
        @for($i = 0; $i < 5; $i++)
            <div class="talastas-skeleton-card p-4 border-t border-gray-200 pt-3">
                <div class="flex items-start gap-4">
                    <div class="talastas-skeleton-avatar h-10 w-10 shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <div class="talastas-skeleton-text h-4 w-1/2"></div>
                        <div class="talastas-skeleton-text h-3 w-2/5 mt-1"></div>
                        <div class="talastas-skeleton-text h-3 w-1/3 mt-1"></div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <div class="talastas-skeleton-placeholder h-6 w-16"></div>
                        <div class="talastas-skeleton-placeholder h-6 w-16"></div>
                        <div class="talastas-skeleton-placeholder h-6 w-16"></div>
                    </div>
                </div>
            </div>
        @endfor
    </div>
</div>

