@extends('layouts.app')

@section('dashboard-layout')
@endsection

@section('content')
    @php
        $category = $inventoryConfig['categories'][$ictInventory->category] ?? [];
        $categoryLabel = $category['label'] ?? str($ictInventory->category)->headline();
        $specDefinitions = $category['specifications'] ?? [];

        $conditionLabel =
            $inventoryConfig['conditions'][$ictInventory->condition]
            ?? str($ictInventory->condition)->headline();

        $statusLabel =
            $inventoryConfig['statuses'][$ictInventory->status]
            ?? str($ictInventory->status)->headline();

        $conditionClass = match ($ictInventory->condition) {
            'new', 'excellent', 'good' => 'border-green-200 bg-green-50 text-green-700',
            'fair' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
            'poor', 'needs_repair' => 'border-red-200 bg-red-50 text-red-700',
            default => 'border-gray-200 bg-gray-50 text-gray-600',
        };

        $statusClass = match ($ictInventory->status) {
            'active' => 'border-green-200 bg-green-50 text-green-700',
            'under_repair' => 'border-orange-200 bg-orange-50 text-orange-700',
            'inactive' => 'border-gray-200 bg-gray-50 text-gray-600',
            'retired', 'disposed' => 'border-slate-200 bg-slate-50 text-slate-600',
            default => 'border-gray-200 bg-gray-50 text-gray-600',
        };

        $formatSpecificationValue = function ($value): string {
            if (is_bool($value)) {
                return $value ? 'Yes' : 'No';
            }

            if (is_array($value)) {
                return collect($value)->filter()->implode(', ');
            }

            return str((string) $value)
                ->replace('_', ' ')
                ->headline()
                ->toString();
        };

        $title = $ictInventory->asset_name ?: $categoryLabel;
    @endphp

    <div class="flex h-full min-h-0 flex-col bg-gray-100">
        <div class="shrink-0 bg-red-700 px-6 py-4">
            <div class="flex items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <a
                        href="{{ route('ict-inventory.index') }}"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-600 text-red-100 transition hover:bg-red-500 hover:text-white"
                        title="Back to ICT inventory"
                    >
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-600 text-red-100">
                        <i class="fa-solid fa-box"></i>
                    </div>

                    <div class="min-w-0">
                        <h1 class="truncate text-xl font-semibold leading-tight tracking-tight text-white">
                            {{ $title }}
                        </h1>
                        <p class="mt-0.5 text-xs text-red-200">
                            {{ $categoryLabel }}
                            <span class="mx-1 text-red-300">•</span>
                            Record #{{ $ictInventory->asset_id }}
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <a
                        href="{{ route('ict-inventory.edit', $ictInventory) }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-100 bg-white px-4 py-2 text-sm font-medium text-red-700 shadow-sm transition hover:bg-red-50"
                    >
                        <i class="fa-solid fa-pen"></i>
                        Edit
                    </a>

                    <form method="POST" action="{{ route('ict-inventory.destroy', $ictInventory) }}">
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            onclick="return confirm('Delete this inventory record?')"
                            class="inline-flex items-center gap-2 rounded-lg border border-red-300/30 bg-red-500/20 px-4 py-2 text-sm font-medium text-red-100 transition hover:bg-red-600 hover:text-white"
                        >
                            <i class="fa-solid fa-trash"></i>
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="mx-5 mt-4 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                <i class="fa-solid fa-circle-check mt-0.5"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
            <div class="mx-auto max-w-6xl">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Quantity</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900">
                            {{ number_format($ictInventory->quantity ?? 1) }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Condition</p>
                        <span class="mt-2 inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $conditionClass }}">
                            {{ $conditionLabel }}
                        </span>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Status</p>
                        <span class="mt-2 inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Year Acquired</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900">
                            {{ $ictInventory->acquisition_year ?? '—' }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:col-span-2">
                        <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-100 text-red-700">
                                    <i class="fa-solid fa-microchip"></i>
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-gray-900">Technical Specifications</h2>
                                    <p class="mt-0.5 text-xs text-gray-500">
                                        Recorded specifications for this {{ strtolower($categoryLabel) }}.
                                    </p>
                                </div>
                            </div>
                        </div>

                        @if (!empty($ictInventory->specifications))
                            <div class="grid grid-cols-1 gap-px bg-gray-200 sm:grid-cols-2">
                                @foreach ($ictInventory->specifications as $key => $value)
                                    <div class="bg-white px-5 py-4">
                                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                            {{ $specDefinitions[$key]['label'] ?? str($key)->headline() }}
                                        </p>
                                        <p class="mt-1.5 text-sm font-semibold text-gray-900">
                                            {{ $formatSpecificationValue($value) }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="px-5 py-10 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                    <i class="fa-solid fa-microchip"></i>
                                </div>
                                <p class="mt-3 text-sm font-medium text-gray-700">
                                    No technical specifications recorded
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Edit this record to add category-specific specifications.
                                </p>
                            </div>
                        @endif
                    </section>

                    <div class="space-y-5">
                        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                                <h2 class="text-sm font-semibold text-gray-800">Asset Details</h2>
                            </div>

                            <div class="divide-y divide-gray-100">
                                <div class="px-4 py-3">
                                    <p class="text-xs text-gray-500">Category</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $categoryLabel }}</p>
                                </div>

                                <div class="px-4 py-3">
                                    <p class="text-xs text-gray-500">Brand</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $ictInventory->brand ?: '—' }}</p>
                                </div>

                                <div class="px-4 py-3">
                                    <p class="text-xs text-gray-500">Model</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $ictInventory->model ?: '—' }}</p>
                                </div>

                                <div class="px-4 py-3">
                                    <p class="text-xs text-gray-500">Internal Location</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $ictInventory->location ?: '—' }}</p>
                                </div>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                                <h2 class="text-sm font-semibold text-gray-800">Institution</h2>
                            </div>

                            <div class="p-4">
                                <p class="font-semibold text-gray-900">
                                    {{ $ictInventory->institution->name }}
                                </p>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ $ictInventory->institution->type }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $ictInventory->institution->barangay?->name ?? 'Barangay not recorded' }}
                                </p>

                                <a
                                    href="{{ route('institutions.show', $ictInventory->institution) }}"
                                    class="mt-4 inline-flex items-center gap-2 text-xs font-medium text-red-600 transition hover:text-red-800"
                                >
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    View institution
                                </a>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                                <h2 class="text-sm font-semibold text-gray-800">Maintenance</h2>
                            </div>

                            <div class="p-4">
                                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium {{ $ictInventory->needs_maintenance
                                    ? 'border-orange-200 bg-orange-50 text-orange-700'
                                    : 'border-green-200 bg-green-50 text-green-700' }}">
                                    <i class="fa-solid {{ $ictInventory->needs_maintenance
                                        ? 'fa-screwdriver-wrench'
                                        : 'fa-circle-check' }}"></i>
                                    {{ $ictInventory->needs_maintenance
                                        ? 'Maintenance needed'
                                        : 'No maintenance issue reported' }}
                                </span>
                            </div>
                        </section>
                    </div>
                </div>

                @if ($ictInventory->notes)
                    <section class="mt-5 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                            <h2 class="text-sm font-semibold text-gray-900">Notes</h2>
                        </div>
                        <p class="whitespace-pre-line px-5 py-4 text-sm leading-relaxed text-gray-600">
                            {{ $ictInventory->notes }}
                        </p>
                    </section>
                @endif
            </div>
        </div>
    </div>
@endsection
