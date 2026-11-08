<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

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
                    <?php for($i = 0; $i < 5; $i++): ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">
                            <div class="talastas-skeleton-text h-4 w-24"></div>
                        </th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php for($i = 0; $i < 5; $i++): ?>
                    <tr class="talastas-skeleton-table-row">
                        <?php for($j = 0; $j < 5; $j++): ?>
                            <td class="px-6 py-4">
                                <div class="talastas-skeleton-text h-4 w-full"></div>
                                <?php if($j < 2): ?>
                                    <div class="talastas-skeleton-text h-3 w-1/3 mt-1"></div>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination placeholder -->
    <div class="talastas-skeleton-placeholder h-10 w-1/3 mt-4"></div>
</div>

<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/components/skeletons/users.blade.php ENDPATH**/ ?>