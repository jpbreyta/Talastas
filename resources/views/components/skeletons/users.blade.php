@props([])

<!-- Users skeleton: search + table placeholders -->
<div class="space-y-4">
    <!-- Search + actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5">
        <div class="w-full sm:max-w-sm">
            <div class="talastas-skeleton-card h-10">
                <div class="talastas-skeleton-text h-5 w-full"></div>
            </div>
        </div>
        <div class="flex gap-2">
            <div class="talastas-skeleton-card h-10 px-4">
                <div class="talastas-skeleton-text h-5 w-20"></div>
            </div>
        </div>
    </div>

    <!-- Users table placeholders -->
    <div class="talastas-skeleton-card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @for($i = 0; $i < 5; $i++)
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">
                            <div class="talastas-skeleton-text h-4 w-24"></div>
                        </th>
                    @endfor
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @for($i = 0; $i < 5; $i++)
                    <tr class="talastas-skeleton-table-row">
                        @for($j = 0; $j < 5; $j++)
                            <td class="px-6 py-4">
                                <div class="talastas-skeleton-text h-4 w-full"></div>
                                @if($j < 2)
                                    <div class="talastas-skeleton-text h-3 w-1/3 mt-1"></div>
                                @endif
                            </td>
                        @endfor
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>

    <!-- Pagination placeholder -->
    <div class="talastas-skeleton-placeholder h-10 w-1/3 mt-4"></div>
</div>

