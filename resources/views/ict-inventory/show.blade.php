@extends('layouts.app')

@section('page-title', 'Inventory Details')
@section('page-icon', 'fa-boxes-stacked')

@section('dashboard-layout')
@endsection

@section('content')
    @php
        $total = max(0, (int) ($ictInventory->total_count ?? 0));
        $active = $ictInventory->active_count;
        $retired = $ictInventory->retired_count;
        $defective = $ictInventory->defective_count;


        $retirementRate = $total > 0 && $retired !== null
            ? round(($retired / $total) * 100, 1)
            : null;

        $categoryLabel = match ($ictInventory->component_type) {
            'software' => 'Software & Systems',
            'network' => 'Network Infrastructure',
                                            'connectivity' => 'Internet Connectivity',
            default => 'Hardware & Equipment',
        };
    @endphp

    <div class="flex h-full min-h-0 flex-col bg-gray-100">
        <x-page-header :title="$ictInventory->asset_name" :subtitle="$categoryLabel . ' - Inventory item #' . $ictInventory->asset_id" :back-url="route('ict-inventory.index')" back-label="Back to inventory" :padded="true" content-class="mx-auto w-full max-w-6xl" class="border-b border-gray-200 bg-white">

            <x-slot:actions>
<a href="{{ route('ict-inventory.edit', $ictInventory) }}"
                    class="page-header-primary inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-red-700">
                    <i class="fa-solid fa-pen"></i>
                    Edit
                </a>
            </x-slot:actions>
        </x-page-header>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
            <div class="mx-auto max-w-6xl space-y-5">
                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900">
                                Inventory Counts
                            </h2>
                            <p class="mt-1 text-xs text-gray-500">
                                Blank availability quantities are not recorded. Active means deployed/in-service and may include defective units. Defective within Active is recorded separately.
                            </p>
                        </div>

                            </div>

                    <p @if ($ictInventory->availability_status === 'Availability Recorded') hidden @endif class="mb-3 text-sm text-amber-700">{{ $ictInventory->availability_status === 'Availability Recorded' ? '' : $ictInventory->availability_status }}</p>
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-6">
                        @foreach ([
                            ['Total', $total, 'text-gray-900'],
                            ['Active', $active, 'text-blue-700'],
                            ['Retired', $retired, 'text-slate-700'],
                            ['Functional', $ictInventory->functional_count, 'text-emerald-700'],
                                    ['Defective', $defective, 'text-red-700'],
                            ['Defective within Active', $ictInventory->defective_active_count, 'text-red-700'],
                        ] as [$label, $value, $class])
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-center">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    {{ $label }}
                                </p>
                                <p class="mt-2 text-2xl font-bold {{ $class }}">
                                    {{ $value === null ? 'Not recorded' : number_format($value) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
                        <h2 class="text-sm font-semibold text-gray-900">
                            Derived ICT Indicators
                        </h2>

                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            

                            <div class="rounded-lg border border-gray-200 p-4">
                                <p class="text-xs uppercase tracking-wide text-gray-500">
                                    Retirement Rate
                                </p>
                                <p class="mt-2 text-2xl font-bold text-gray-900">
                                    {{ $retirementRate !== null
                                        ? number_format($retirementRate, 1) . '%'
                                        : '—' }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Retired ÷ Total × 100
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-gray-900">
                            Institution
                        </h2>

                        <p class="mt-3 font-semibold text-gray-900">
                            {{ $ictInventory->institution->name }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ $ictInventory->institution->type }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $ictInventory->institution->barangay?->name ?? 'Barangay not recorded' }}
                        </p>
                    </section>
                </div>

                            </div>
        </div>
    </div>
@endsection
