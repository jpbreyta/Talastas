

<?php $__env->startSection('page-title', 'ICT Gap Analysis'); ?>
<?php $__env->startSection('page-icon', 'fa-chart-line'); ?>

<?php $__env->startSection('page-status'); ?>
    <span class="analysis-status <?php echo e(is_numeric($averageGapScore) ? 'is-complete' : ''); ?>">
        <span aria-hidden="true"></span>
        <?php echo e(is_numeric($averageGapScore) ? 'Calculated analysis available for selection' : 'No complete analysis for selection'); ?>

    </span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $overallGap = is_numeric($averageGapScore ?? null)
            ? (float) $averageGapScore
            : null;

        $gapDrivers = [
            [
                'label' => 'Infrastructure Deficit',
                'value' => $infrastructureDeficit,
                'icon' => 'fa-computer',
                'description' => 'Percentage of active applicable ICT units recorded as defective.',
                'formula' => '(Defective Active Applicable Units / Active Applicable Units) x 100',
            ],
            [
                'label' => 'Connectivity Divide',
                'value' => $connectivityDivide,
                'icon' => 'fa-wifi',
                'description' => 'Mean advertised-download shortfall, available only when every reported connection has usable data.',
                'formula' => 'Mean of [max(Advertised Download Mbps - Measured Download Mbps, 0) / Advertised Download Mbps] x 100 across all reported connections',
            ],
            [
                'label' => 'Technological Obsolescence Proxy',
                'value' => $technologicalObsolescence,
                'icon' => 'fa-clock-rotate-left',
                'description' => 'Retired applicable ICT units as a share of total applicable inventory.',
                'formula' => '(Retired Applicable Units / Total Applicable Units) x 100',
            ],
        ];

        $scoreBarClass = fn ($score): string =>
            is_numeric($score) ? 'bg-red-500' : 'bg-gray-300';

        $scoreTextClass = fn ($score): string =>
            is_numeric($score) ? 'text-gray-900' : 'text-gray-500';

        $topBarangays = collect($barangayScores ?? [])
            ->take(5);

        $hasFilters =
            request()->filled('q')
            || request()->filled('barangay')
            || request()->filled('type')
            || request()->filled('risk');
    ?>

    <div class="analytics-dashboard min-h-full pb-6">
        <div class="dashboard-content">

            <form method="GET" action="<?php echo e(route('analytics')); ?>" class="reporting-selection dashboard-card">
                <div class="reporting-heading"><h2>Reporting Period and Selection</h2><div class="reporting-exports"><?php if(auth()->user()->isAdmin() || auth()->user()->isViewer()): ?>
<a href="<?php echo e(route('analytics.report', request()->except('page'))); ?>" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-file-pdf"></i> LGU PDF Report</a>
<a href="<?php echo e(route('analytics.csv', request()->except('page'))); ?>"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                        <i class="fa-solid fa-file-csv"></i>
                        Export CSV
                    </a>
<?php endif; ?></div></div>
                <div class="reporting-fields">
                    <label class="text-xs text-gray-600">From<input type="date" name="date_from" value="<?php echo e(request('date_from')); ?>" class="mt-1 block rounded-lg border border-gray-300 p-2 text-sm"></label>
                    <label class="text-xs text-gray-600">Through<input type="date" name="date_to" value="<?php echo e(request('date_to')); ?>" class="mt-1 block rounded-lg border border-gray-300 p-2 text-sm"></label>
                        <label class="text-xs text-gray-600">Barangay<select aria-label="Filter by barangay" name="barangay" class="mt-1 block rounded-lg border border-gray-300 p-2 text-sm"><option value="">All barangays</option><?php $__currentLoopData = $barangays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $barangay): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($barangay->id); ?>" <?php if((string)request('barangay') === (string)$barangay->id): echo 'selected'; endif; ?>><?php echo e($barangay->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
                        <label class="text-xs text-gray-600">Institution Type<select aria-label="Filter by institution type" name="type" class="mt-1 block rounded-lg border border-gray-300 p-2 text-sm"><option value="">All types</option><?php $__currentLoopData = ['School', 'Barangay Hall']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option <?php if(request('type') === $type): echo 'selected'; endif; ?>><?php echo e($type); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
                        <?php $__currentLoopData = ['q', 'risk']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><input type="hidden" name="<?php echo e($filter); ?>" value="<?php echo e(request($filter)); ?>"><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Apply Selection</button>
                    <a href="<?php echo e(route('analytics')); ?>" class="px-2 py-2 text-sm text-gray-600">Reset</a>
                </div>
                <?php $__errorArgs = ['date_from'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-sm text-red-700"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php $__errorArgs = ['date_to'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-sm text-red-700"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </form>

            
            <div class="dashboard-summary-grid mb-4">
                <?php
                    $cityMetrics = [
                        [
                            'label' => 'Overall ICT Gap',
                            'value' => $overallGap,
                            'icon' => 'fa-chart-line',
                            'formula' => '(Infrastructure Deficit + Connectivity Divide + Technological Obsolescence Proxy) / 3',
                            'description' => 'Mean of the three finalized core gap dimensions across institutions with complete core assessments.',
                        ],
                        [
                            'label' => 'Infrastructure Deficit',
                            'value' => $infrastructureDeficit,
                            'icon' => 'fa-building-circle-exclamation',
                            'formula' => '(Defective Active Applicable Units / Active Applicable Units) x 100',
                            'description' => 'Average infrastructure-condition gap from defective active applicable ICT units.',
                        ],
                        [
                            'label' => 'Connectivity Divide',
                            'value' => $connectivityDivide,
                            'icon' => 'fa-wifi',
                            'formula' => 'Mean of [max(Advertised Download Mbps - Measured Download Mbps, 0) / Advertised Download Mbps] x 100 across all reported connections',
                            'description' => 'Average measured Internet-performance shortfall relative to recorded allocation.',
                        ],
                        [
                            'label' => 'Technological Obsolescence Proxy',
                            'value' => $technologicalObsolescence,
                            'icon' => 'fa-clock-rotate-left',
                            'formula' => '(Retired Applicable Units / Total Applicable Units) x 100',
                            'description' => 'Average retirement-based obsolescence proxy for applicable ICT inventory.',
                        ],
                    ];
                ?>

                <?php $__currentLoopData = $cityMetrics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $metric): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if (isset($component)) { $__componentOriginal1985a495c28d4fc2dad8060196c2a792 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1985a495c28d4fc2dad8060196c2a792 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-metric','data' => ['label' => $metric['label'],'icon' => $metric['icon'],'value' => $metric['value'],'formula' => $metric['formula'],'description' => $metric['description']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-metric'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($metric['label']),'icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($metric['icon']),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($metric['value']),'formula' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($metric['formula']),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($metric['description'])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1985a495c28d4fc2dad8060196c2a792)): ?>
<?php $attributes = $__attributesOriginal1985a495c28d4fc2dad8060196c2a792; ?>
<?php unset($__attributesOriginal1985a495c28d4fc2dad8060196c2a792); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1985a495c28d4fc2dad8060196c2a792)): ?>
<?php $component = $__componentOriginal1985a495c28d4fc2dad8060196c2a792; ?>
<?php unset($__componentOriginal1985a495c28d4fc2dad8060196c2a792); ?>
<?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            
            <section class="planning-banner" aria-label="Overall assessment and priority actions">
                <div class="planning-banner-icon" aria-hidden="true"><i class="fa-solid fa-lightbulb"></i></div>
                <div class="planning-banner-body">
                    <h2>AI Prescription &amp; Planning Priorities</h2>
                    <p class="mt-2 text-sm"><?php echo e($prescription['summary']); ?></p>
                    <p class="mt-2 text-sm"><?php echo e($prescription['next_step']); ?></p>
                    <?php if(count($prescription['actions'])): ?>
                        <h3 class="mt-3 text-sm font-semibold text-white">Priority actions for the selected results</h3>
                        <ol class="planning-preview text-sm" style="list-style-type: decimal;">
                            <?php $__currentLoopData = collect($prescription['actions'])->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($action['action']); ?><small><?php echo e($action['institution']); ?> &middot; <?php echo e($action['source']); ?> &middot; <?php echo e(str_replace('_', ' ', $action['urgency'])); ?></small></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ol>
                    <?php else: ?>
                        <p class="mt-2 text-xs">No stored priority actions are available for this selection. Complete and validate the records, then run institution analytics.</p>
                    <?php endif; ?>
                    <p class="mt-2 text-xs">Overall assessment uses the selected calculated results. Priority actions come from stored institution recommendations, ordered by overall ICT gap; source labels identify AI recommendations and fallback guidance.</p>
                </div>
            </section>
            <div class="analytics-chart-grid">
                
                <section class="dashboard-card p-4">
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <div>

                            <h2 class="mt-1 text-base font-semibold text-gray-900">
                                City-wide Gap Drivers
                            </h2>
                        </div>

                        <?php if($primaryCityConcern): ?>
                            <div class="max-w-52 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-right">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-red-500">
                                    Primary Concern
                                </p>

                                <p class="mt-0.5 text-xs font-semibold text-red-800">
                                    <?php echo e($primaryCityConcern['label']); ?>

                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-4">
                        <?php $__currentLoopData = $gapDrivers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $driver): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $driverValue = is_numeric($driver['value'])
                                    ? min(100, max(0, (float) $driver['value']))
                                    : null;
                            ?>

                            <div class="group cursor-help">
                                <div class="mb-2 flex items-center justify-between gap-4">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 transition group-hover:bg-red-50 group-hover:text-red-600">
                                            <i class="fa-solid <?php echo e($driver['icon']); ?>"></i>
                                        </span>

                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <p class="text-sm font-semibold text-gray-800 transition group-hover:text-red-700">
                                                    <?php echo e($driver['label']); ?>

                                                </p>
                                                <?php if (isset($component)) { $__componentOriginaleaf1255a69d338004fbcd79e97a19a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaleaf1255a69d338004fbcd79e97a19a9d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.metric-info','data' => ['title' => $driver['label'],'formula' => $driver['formula'],'description' => $driver['description']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('metric-info'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($driver['label']),'formula' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($driver['formula']),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($driver['description'])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaleaf1255a69d338004fbcd79e97a19a9d)): ?>
<?php $attributes = $__attributesOriginaleaf1255a69d338004fbcd79e97a19a9d; ?>
<?php unset($__attributesOriginaleaf1255a69d338004fbcd79e97a19a9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaleaf1255a69d338004fbcd79e97a19a9d)): ?>
<?php $component = $__componentOriginaleaf1255a69d338004fbcd79e97a19a9d; ?>
<?php unset($__componentOriginaleaf1255a69d338004fbcd79e97a19a9d); ?>
<?php endif; ?>
                                            </div>

                                            <p class="text-xs text-gray-500" title="<?php echo e($driver['description']); ?>">
                                                <?php echo e($driver['description']); ?>

                                            </p>
                                        </div>
                                    </div>

                                    <span class="shrink-0 text-sm font-bold <?php echo e($scoreTextClass($driverValue)); ?>">
                                        <?php echo e($driverValue !== null ? number_format($driverValue, 1) . '%' : 'No data'); ?>

                                    </span>
                                </div>

                                <div class="h-2 overflow-hidden rounded-full bg-gray-100 transition group-hover:shadow-inner">
                                    <div class="h-full rounded-full transition-all duration-500 driver-color-<?php echo e($loop->index); ?>"
                                        style="width: <?php echo e($driverValue ?? 0); ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <?php if($primaryCityConcern): ?>
                        <div class="mt-5 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                            <p class="text-xs leading-5 text-gray-600">
                                <strong class="font-semibold text-gray-900">
                                    Planning focus:
                                </strong>
                                <?php echo e($primaryCityConcern['label']); ?> currently has the highest average deficit at
                                <strong class="font-semibold text-gray-900">
                                    <?php echo e(number_format((float) $primaryCityConcern['score'], 1)); ?>%
                                </strong>
                                among the three research dimensions.
                            </p>
                        </div>
                    <?php endif; ?>
                </section>

                
                <section class="dashboard-card p-4">
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <div>

                            <h2 class="mt-1 text-base font-semibold text-gray-900">
                                Priority Barangays
                            </h2>
                        </div>

                        <span class="rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                            Top 5
                        </span>
                    </div>

                    <?php if($topBarangays->isEmpty()): ?>
                        <div class="flex min-h-56 flex-col items-center justify-center text-center">
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                <i class="fa-solid fa-map-location-dot text-lg"></i>
                            </span>

                            <p class="mt-3 text-sm font-semibold text-gray-700">
                                No barangay gap summary available
                            </p>

                            <p class="mt-1 max-w-sm text-xs leading-5 text-gray-500">
                                Barangay summaries will appear after institutions have complete ICT gap assessments.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="barangay-chart" role="img" aria-label="Highest calculated barangay mean ICT gap scores">
                            <?php $__currentLoopData = $topBarangays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $barangay): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a class="barangay-chart-column" href="<?php echo e(route('analytics', array_merge(request()->except('page'), ['barangay' => $barangay['id']]))); ?>"
                                    title="<?php echo e($barangay['name']); ?>: <?php echo e(number_format($barangay['gap_score'], 1)); ?>% mean ICT gap; <?php echo e($barangay['institution_count']); ?> analyzed institutions">
                                    <div class="barangay-chart-track"><strong style="bottom: calc(<?php echo e(min(100, max(0, $barangay['gap_score']))); ?>% + 0.375rem)"><?php echo e(number_format($barangay['gap_score'], 1)); ?>%</strong><span class="chart-color-<?php echo e($index); ?>" style="height: <?php echo e(min(100, max(0, $barangay['gap_score']))); ?>%"></span></div>
                                    <span><?php echo e($barangay['name']); ?></span>
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Mean of available institution scores in this selection; colors distinguish barangays and do not indicate severity.</p>

                        <?php if($topBarangays->count() >= 5): ?>
                            <div class="mt-4 text-center">
                                <a href="<?php echo e(route('map')); ?>"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-600 hover:text-red-700 transition">
                                    <span>View All Barangays on Map</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            </div>

            
            <?php if (! (auth()->user()->isViewer())): ?>
            <section id="institution-list" class="dashboard-card institution-priority-table overflow-hidden">
                <div class="border-b border-gray-200 bg-gray-50 p-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div>

                            <h2 class="mt-1 text-base font-semibold text-gray-900">
                                Institution Priority List
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Highest gap scores first, with the main concern and recommended first action.
                            </p>
                        </div>

                        <form method="GET" action="<?php echo e(url()->current()); ?>"
                            class="w-full xl:w-auto">
                            <input type="hidden" name="date_from" value="<?php echo e(request('date_from')); ?>">
                            <input type="hidden" name="date_to" value="<?php echo e(request('date_to')); ?>">
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-5">
                                <div class="relative lg:col-span-1">
                                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"></i>

                                    <input type="search" aria-label="Search institution" name="q" value="<?php echo e(request('q')); ?>"
                                        placeholder="Search institution"
                                        class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-700 transition focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                </div>

                                <select aria-label="Filter by barangay" name="barangay"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 transition focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                    <option value="">All Barangays</option>

                                    <?php $__currentLoopData = $barangays ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $barangay): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($barangay->id); ?>"
                                            <?php if((string) request('barangay') === (string) $barangay->id): echo 'selected'; endif; ?>>
                                            <?php echo e($barangay->name); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>

                                <select aria-label="Filter by institution type" name="type"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 transition focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                    <option value="">All Types</option>
                                    <option value="School" <?php if(request('type') === 'School'): echo 'selected'; endif; ?>>School</option>
                                    <option value="Barangay Hall" <?php if(request('type') === 'Barangay Hall'): echo 'selected'; endif; ?>>Barangay Hall</option>
                                </select>

                                <select aria-label="Filter by assessment status" name="risk"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 transition focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                    <option value="">All Assessment Statuses</option>
                                    <option value="assessed" <?php if(request('risk') === 'assessed'): echo 'selected'; endif; ?>>Assessed</option>
                                    <option value="insufficient_data" <?php if(request('risk') === 'insufficient_data'): echo 'selected'; endif; ?>>
                                        Insufficient Data
                                    </option>
                                </select>

                                <div class="flex gap-2">
                                    <button type="submit"
                                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                                        <i class="fa-solid fa-filter text-xs"></i>
                                        Apply
                                    </button>

                                    <?php if($hasFilters): ?>
                                        <a href="<?php echo e(url()->current()); ?>"
                                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
                                            title="Clear all filters">
                                            <i class="fa-solid fa-xmark"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if($hasFilters): ?>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-medium text-gray-500">Active filters:</span>

                                    <?php if(request('q')): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            Search: "<?php echo e(request('q')); ?>"
                                            <a href="<?php echo e(url()->current()); ?>?<?php echo e(http_build_query(array_filter(request()->except('q')))); ?>"
                                                class="text-gray-400 hover:text-gray-600">
                                                <i class="fa-solid fa-xmark text-[10px]"></i>
                                            </a>
                                        </span>
                                    <?php endif; ?>

                                    <?php if(request('barangay')): ?>
                                        <?php
                                            $selectedBarangay = collect($barangays ?? [])->firstWhere('id', request('barangay'));
                                        ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            Barangay: <?php echo e($selectedBarangay->name ?? 'Unknown'); ?>

                                            <a href="<?php echo e(url()->current()); ?>?<?php echo e(http_build_query(array_filter(request()->except('barangay')))); ?>"
                                                class="text-gray-400 hover:text-gray-600">
                                                <i class="fa-solid fa-xmark text-[10px]"></i>
                                            </a>
                                        </span>
                                    <?php endif; ?>

                                    <?php if(request('type')): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            Type: <?php echo e(request('type')); ?>

                                            <a href="<?php echo e(url()->current()); ?>?<?php echo e(http_build_query(array_filter(request()->except('type')))); ?>"
                                                class="text-gray-400 hover:text-gray-600">
                                                <i class="fa-solid fa-xmark text-[10px]"></i>
                                            </a>
                                        </span>
                                    <?php endif; ?>

                                    <?php if(request('risk')): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            Status: <?php echo e(ucfirst(str_replace('_', ' ', request('risk')))); ?>

                                            <a href="<?php echo e(url()->current()); ?>?<?php echo e(http_build_query(array_filter(request()->except('risk')))); ?>"
                                                class="text-gray-400 hover:text-gray-600">
                                                <i class="fa-solid fa-xmark text-[10px]"></i>
                                            </a>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="analytics-table-scroll overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 py-3 text-left text-xs text-gray-500">#</th>
                                <th class="group px-5 py-3 text-left">
                                    <a href="<?php echo e(url()->current()); ?>?<?php echo e(http_build_query(array_merge(request()->except('sort', 'order'), ['sort' => 'name', 'order' => request('sort') === 'name' && request('order') === 'asc' ? 'desc' : 'asc']))); ?>"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500 transition hover:text-gray-700">
                                        Institution
                                        <?php if(request('sort') === 'name'): ?>
                                            <i class="fa-solid fa-sort-<?php echo e(request('order') === 'desc' ? 'down' : 'up'); ?> text-[10px]"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-sort text-[10px] opacity-0 group-hover:opacity-50"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Barangay
                                </th>

                                <th class="group px-4 py-3 text-left">
                                    <a href="<?php echo e(url()->current()); ?>?<?php echo e(http_build_query(array_merge(request()->except('sort', 'order'), ['sort' => 'gap_score', 'order' => request('sort') === 'gap_score' && request('order') === 'desc' ? 'asc' : 'desc']))); ?>"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500 transition hover:text-gray-700">
                                        Overall ICT Gap
                                        <?php if(request('sort') === 'gap_score' || !request('sort')): ?>
                                            <i class="fa-solid fa-sort-<?php echo e(request('order') === 'asc' ? 'up' : 'down'); ?> text-[10px]"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-sort text-[10px] opacity-0 group-hover:opacity-50"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Main Concern
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Recommended First Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            <?php $__empty_1 = true; $__currentLoopData = $analytics ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $analytic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $institution = $analytic->institution;

                                    $gapScore = is_numeric($analytic->gap_score)
                                        ? (float) $analytic->gap_score
                                        : null;

                                    $riskLevel = strtolower(
                                        (string) ($analytic->risk_level ?? 'insufficient_data')
                                    );

                                    $riskLabel = $analytic->processing_status === 'failed' ? 'Calculation Failed' : ($riskLevel === 'assessed'
                                        ? 'Assessed'
                                        : 'Insufficient Data');

                                    $riskBadge = $riskLevel === 'assessed'
                                        ? 'bg-blue-100 text-blue-700'
                                        : 'bg-gray-100 text-gray-700';

                                    $recommendationDetails = $analytic->recommendations_detail;

                                    if (is_string($recommendationDetails)) {
                                        $recommendationDetails = json_decode(
                                            $recommendationDetails,
                                            true
                                        );
                                    }

                                    if (!is_array($recommendationDetails)) {
                                        $recommendationDetails = [];
                                    }

                                    $rowPriorities = $recommendationDetails['priorities'] ?? [];

                                    if (!is_array($rowPriorities)) {
                                        $rowPriorities = [];
                                    }

                                    $firstPriority = $rowPriorities[0] ?? null;

                                    $firstAction = is_array($firstPriority)
                                        ? ($firstPriority['priority'] ?? null)
                                        : (is_string($firstPriority) ? $firstPriority : null);

                                    $firstAction = $firstAction
                                        ?: $analytic->policy_recommendation;

                                    $urgency = is_array($firstPriority)
                                        ? ($firstPriority['urgency'] ?? null)
                                        : null;

                                    $urgencyLabel = $urgency
                                        ? str_replace('_', ' ', $urgency)
                                        : null;

                                    $urgencyClasses = match ($urgency) {
                                        'immediate' => 'border-red-200 bg-red-50 text-red-700',
                                        'short_term' => 'border-orange-200 bg-orange-50 text-orange-700',
                                        'medium_term' => 'border-blue-200 bg-blue-50 text-blue-700',
                                        default => 'border-gray-200 bg-gray-50 text-gray-600',
                                    };

                                    $rowDimensions = collect([
                                        [
                                            'label' => 'Infrastructure Deficit',
                                            'score' => $analytic->infrastructure_deficit_score,
                                            'formula' => '(Defective Active Applicable Units / Active Applicable Units) x 100',
                                            'description' => 'Percentage of active applicable ICT units recorded as defective.',
                                        ],
                                        [
                                            'label' => 'Connectivity Divide',
                                            'score' => $analytic->connectivity_divide_score,
                                            'formula' => 'Mean of [max(Advertised Download Mbps - Measured Download Mbps, 0) / Advertised Download Mbps] x 100 across all reported connections',
                                            'description' => 'Relative bandwidth shortfall between recorded allocation and measured download speed.',
                                        ],
                                        [
                                            'label' => 'Technological Obsolescence Proxy',
                                            'score' => $analytic->technological_obsolescence_score,
                                            'formula' => '(Retired Applicable Units / Total Applicable Units) x 100',
                                            'description' => 'Retirement-based proxy for applicable ICT inventory.',
                                        ],
                                    ])
                                        ->filter(fn ($item) => is_numeric($item['score']))
                                        ->sortByDesc(fn ($item) => (float) $item['score'])
                                        ->values();

                                    $mainConcern = $rowDimensions->first();
                                ?>

                                <tr class="align-top transition hover:bg-gray-50">
                                    <td class="px-3 py-4 text-xs text-gray-500"><?php echo e(($analytics->firstItem() ?? 1) + $loop->index); ?></td>
                                    <td class="px-5 py-4">
                                        <?php if($institution): ?>
                                            <a href="<?php echo e(route('institutions.show', $institution)); ?>"
                                                class="group block">
                                                <p
                                                    class="max-w-64 text-sm font-semibold text-gray-900 transition group-hover:text-red-700">
                                                    <?php echo e($institution->name); ?>

                                                </p>

                                                <p class="mt-1 text-xs text-gray-500">
                                                    <?php echo e($institution->type ?? 'Type not specified'); ?>

                                                </p>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-400">
                                                Institution unavailable
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-4 py-4">
                                        <?php if($institution?->barangay): ?>
                                            <a href="<?php echo e(url()->current()); ?>?barangay=<?php echo e($institution->barangay->id); ?>"
                                                class="text-sm font-medium text-gray-600 transition hover:text-red-600 hover:underline"
                                                title="Filter by <?php echo e($institution->barangay->name); ?>">
                                                <?php echo e($institution->barangay->name); ?>

                                            </a>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-400">Not assigned</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-4 py-4">
                                        <div class="flex flex-col items-start gap-1.5">
                                            <span class="text-sm font-bold <?php echo e($scoreTextClass($gapScore)); ?>">
                                                <?php echo e($gapScore !== null ? number_format($gapScore, 1) . '%' : '—'); ?>

                                            </span>

                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold <?php echo e($riskBadge); ?>">
                                                <?php echo e($riskLabel); ?>

                                            </span>
                                            <?php echo $__env->make('ict-inventory._connectivity-assessment', ['assessment' => $recommendationDetails['connectivity_assessment'] ?? null, 'compact' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                            <?php if($institution): ?>
                                                <a href="<?php echo e(route('institutions.show', ['institution' => $institution->id, 'tab' => 'connectivity'])); ?>#institutionInventory" class="text-xs font-semibold text-red-700 hover:underline">Internet Connectivity</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4">
                                        <?php if($mainConcern): ?>
                                            <div class="flex items-center gap-1.5">
                                                <p class="text-sm font-semibold text-gray-800">
                                                    <?php echo e($mainConcern['label']); ?>

                                                </p>
                                                <?php if (isset($component)) { $__componentOriginaleaf1255a69d338004fbcd79e97a19a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaleaf1255a69d338004fbcd79e97a19a9d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.metric-info','data' => ['title' => $mainConcern['label'],'formula' => $mainConcern['formula'],'description' => $mainConcern['description']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('metric-info'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($mainConcern['label']),'formula' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($mainConcern['formula']),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($mainConcern['description'])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaleaf1255a69d338004fbcd79e97a19a9d)): ?>
<?php $attributes = $__attributesOriginaleaf1255a69d338004fbcd79e97a19a9d; ?>
<?php unset($__attributesOriginaleaf1255a69d338004fbcd79e97a19a9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaleaf1255a69d338004fbcd79e97a19a9d)): ?>
<?php $component = $__componentOriginaleaf1255a69d338004fbcd79e97a19a9d; ?>
<?php unset($__componentOriginaleaf1255a69d338004fbcd79e97a19a9d); ?>
<?php endif; ?>
                                            </div>

                                            <p class="mt-1 text-xs text-gray-500">
                                                <?php echo e(number_format((float) $mainConcern['score'], 1)); ?>% deficit
                                            </p>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-400">
                                                Not available
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-5 py-4">
                                        <?php if($firstAction): ?>
                                            <div class="max-w-xl">
                                                <p class="text-sm font-semibold leading-relaxed <?php echo e(match($urgency) {
                                                    'immediate' => 'text-red-700',
                                                    'short_term' => 'text-orange-700',
                                                    'medium_term' => 'text-blue-700',
                                                    default => 'text-gray-800',
                                                }); ?>" title="<?php echo e($firstAction); ?>">
                                                    <?php echo e(Str::limit($firstAction, 150)); ?>

                                                </p>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-sm italic text-gray-400">
                                                No recommendation available.
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="6" class="px-5 py-14 text-center">
                                        <span
                                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                            <i class="fa-solid fa-chart-column"></i>
                                        </span>

                                        <p class="mt-3 text-sm font-semibold text-gray-700">
                                            <?php echo e($hasFilters ? 'No institutions match the selected filters.' : 'No analytics records available.'); ?>

                                        </p>

                                        <p class="mt-1 text-xs text-gray-500">
                                            <?php echo e($hasFilters
                                                ? 'Clear or change the filters to review other institutions.'
                                                : 'Run institution analytics to generate decision-support results.'); ?>

                                        </p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if(method_exists($analytics, 'links')): ?>
                    <div class="border-t border-gray-200">
                        <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm text-gray-600">
                                Showing
                                <span class="font-semibold text-gray-900"><?php echo e($analytics->firstItem() ?? 0); ?></span>
                                to
                                <span class="font-semibold text-gray-900"><?php echo e($analytics->lastItem() ?? 0); ?></span>
                                of
                                <span class="font-semibold text-gray-900"><?php echo e($analytics->total()); ?></span>
                                institutions
                            </div>

                            <?php echo e($analytics->links()); ?>

                        </div>
                    </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/analytics.blade.php ENDPATH**/ ?>