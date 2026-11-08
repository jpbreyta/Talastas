        <main data-inventory-results class="flex min-h-0 flex-1 flex-col">

            <?php if($institutions->count() > 0): ?>
                <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-2 pt-4">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[900px] text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50 text-left">
                                        <th class="w-14 px-4 py-3"></th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">Institution</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">Barangay</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Total</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Active</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Retired</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Functional</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Defective</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Actions</th>
                                    </tr>
                                </thead>

                                <?php $__currentLoopData = $institutions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $institution): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tbody class="institution-group border-b border-gray-100 last:border-b-0"
                                        data-institution-group>

                                        <tr class="cursor-pointer bg-white transition hover:bg-red-50/50"
                                            data-accordion-trigger
                                            tabindex="0"
                                            role="button"
                                            aria-expanded="false"
                                            aria-controls="institution-assets-<?php echo e($institution->id); ?>">

                                            <td class="px-4 py-4 text-center">
                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                                    <i class="fa-solid fa-chevron-right text-xs transition-transform duration-200"
                                                        data-chevron></i>
                                                </span>
                                            </td>

                                            <td class="px-4 py-4">
                                                <p class="font-semibold text-gray-900">
                                                    <?php echo e($institution->name); ?>

                                                </p>
                                                <p class="mt-0.5 text-xs text-gray-500">
                                                    <?php echo e($institution->type); ?>

                                                    <?php if(request('sort') === 'most_complete'): ?>
                                                        · <?php echo e(number_format((float) $institution->inventory_input_coverage)); ?>% inventory inputs recorded and valid
                                                    <?php elseif(request('sort') === 'latest'): ?>
                                                        · Updated <?php echo e($institution->latest_inventory_update ? \Illuminate\Support\Carbon::parse($institution->latest_inventory_update)->format('M j, Y g:i A') : '—'); ?>

                                                    <?php endif; ?>
                                                </p>
                                            </td>

                                            <td class="px-4 py-4 text-gray-600">
                                                <?php echo e($institution->barangay?->name ?? 'Not assigned'); ?>

                                            </td>

                                            <?php $__currentLoopData = [
                                                $institution->total_assets_count,
                                                \App\Services\InventoryCounts::sum($institution->ictInventory, 'active_count'),
                                                \App\Services\InventoryCounts::sum($institution->ictInventory, 'retired_count'),
                                                $institution->ictInventory->isNotEmpty() && $institution->ictInventory->every(fn ($item) => $item->functional_count !== null) ? $institution->ictInventory->sum('functional_count') : null,
                                                \App\Services\InventoryCounts::sum($institution->ictInventory, 'defective_count'),
                                            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <td class="px-4 py-4 text-center">
                                                    <span class="inline-flex min-w-9 items-center justify-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                                        <?php echo e($value === null ? '—' : number_format((int) $value)); ?>

                                                    </span>
                                                </td>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="<?php echo e(route('ict-inventory.create', ['institution_id' => $institution->id])); ?>"
                                                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 transition hover:bg-red-100"
                                                        data-stop-accordion>
                                                        <i class="fa-solid fa-plus"></i>
                                                        Manage Inventory
                                                    </a>

                                                </div>
                                            </td>
                                        </tr>

                                        <tr id="institution-assets-<?php echo e($institution->id); ?>"
                                            class="hidden bg-slate-50"
                                            data-accordion-panel>
                                            <td colspan="9" class="p-0">
                                                <div class="border-t border-gray-200 px-5 py-5">
                                                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                                        <p class="text-xs text-gray-500">
                                                            Inventory records are grouped below by category. A dash means availability is unrecorded or incomplete; 0 is confirmed zero.
                                                        </p>

                                                    </div>

                                                    <?php if($institution->ictInventory->isNotEmpty()): ?>
                                                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                                                            <div class="overflow-x-auto">
                                                                <table class="w-full min-w-[900px] text-sm">
                                                                    <thead>
                                                                        <tr class="border-b border-gray-200 bg-gray-50">
                                                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Component</th>
                                                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Category</th>
                                                                            <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Total</th>
                                                                            <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Active</th>
                                                                            <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Retired</th>
                                                                            <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Functional</th>
                                                                            <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Defective</th>
                                                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                                                                        </tr>
                                                                    </thead>

                                                                    <tbody class="divide-y divide-gray-100">
                                                                        <?php $__currentLoopData = $institution->ictInventory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inventory): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                            <?php
                                                                                $categoryLabel = match ($inventory->component_type) {
                                                                                    'software' => 'Software & Systems',
                                                                                    'network' => 'Network Infrastructure',
                                            'connectivity' => 'Internet Connectivity',
                                                                                    default => 'Hardware & Equipment',
                                                                                };
                                                                            ?>

                                                                            <tr data-inventory-show-url="<?php echo e(route('ict-inventory.show', $inventory)); ?>"
                                                                                class="cursor-pointer transition hover:bg-red-50/40 focus-within:bg-red-50/40">
                                                                                <td class="px-4 py-3">
                                                                                    <a href="<?php echo e(route('ict-inventory.show', $inventory)); ?>"
                                                                                        class="rounded-sm font-medium text-gray-900 transition hover:text-red-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2">
                                                                                        <?php echo e($inventory->asset_name); ?>

                                                                                    </a>
                                                                                    <p class="mt-1 text-xs text-gray-500">Defective within Active: <?php echo e($inventory->defective_active_count === null ? 'Not recorded' : number_format($inventory->defective_active_count)); ?></p>
                                                                                    <p <?php if($inventory->availability_status === 'Availability Recorded'): ?> hidden <?php endif; ?> class="mt-1 text-xs text-amber-700"><?php echo e($inventory->availability_status === 'Availability Recorded' ? '' : $inventory->availability_status); ?></p>

                                                                                </td>

                                                                                <td class="px-4 py-3 text-xs text-gray-600">
                                                                                    <?php echo e($categoryLabel); ?>

                                                                                </td>

                                                                                <?php $__currentLoopData = [
                                                                                    $inventory->total_count,
                                                                                    $inventory->active_count,
                                                                                    $inventory->retired_count,
                                                                                    $inventory->functional_count,
                                                                                    $inventory->defective_count,
                                                                                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                                    <td class="px-3 py-3 text-center font-semibold text-gray-700">
                                                                                        <?php echo e($count === null ? '—' : number_format((int) $count)); ?>

                                                                                    </td>
                                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                                                                <td class="px-4 py-3">
                                                                                    <div class="flex items-center justify-end gap-1.5">
                                                                                        <a href="<?php echo e(route('ict-inventory.edit', $inventory)); ?>"
                                                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600">
                                                                                            <i class="fa-solid fa-pen text-xs"></i>
                                                                                        </a>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center">
                                                            <p class="text-sm font-medium text-gray-700">
                                                                No ICT inventory records yet.
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </table>
                        </div>

                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-200 bg-white px-5 py-3">
                    <?php echo e($institutions->links()); ?>

                </div>
            <?php else: ?>
                <div class="flex flex-1 flex-col items-center justify-center px-6 text-center">
                    <h2 class="text-base font-semibold text-gray-900">
                        <?php echo e(filled(request('search')) || request('status', 'all') !== 'all' ? 'No matching institutions' : 'No public schools or barangay halls available'); ?>

                    </h2>
                    <p class="mt-1 max-w-md text-sm text-gray-500">
                        <?php echo e(filled(request('search')) || request('status', 'all') !== 'all' ? 'Adjust the search or inventory filter.' : 'Register an in-scope institution before encoding ICT inventory data.'); ?>

                    </p>
                </div>
            <?php endif; ?>
        </main>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/ict-inventory/_table-results.blade.php ENDPATH**/ ?>