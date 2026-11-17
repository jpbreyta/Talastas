@extends('layouts.app')

@section('page-title', 'ICT Inventory')
@section('page-icon', 'fa-boxes-stacked')

@section('dashboard-layout', 'enabled')

@section('page-tools')
<div class="relative">
    <button type="submit" form="inventoryListFilters" aria-label="Search inventory"
        class="inventory-search-trigger absolute inset-y-0 left-0 z-10 flex w-9 items-center justify-center">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false">
            <circle cx="10.5" cy="10.5" r="6.5" /><path d="m16 16 4.5 4.5" />
        </svg>
    </button>
    <input id="inventoryInstitutionSearch" name="search" form="inventoryListFilters" type="search" value="{{ request('search') }}" maxlength="255" aria-label="Search institutions" placeholder="Search" autocomplete="off"
        class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-100 lg:w-64">
</div>
                    <a href="{{ route('ict-inventory.create') }}"
                        class="page-header-primary inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-red-100 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-50">
                        <i class="fa-solid fa-plus"></i>
                        Add Inventory
                    </a>
@endsection

@section('content')
    <div data-inventory-list class="flex min-h-0 flex-1 flex-col bg-gray-100">

        <form id="inventoryListFilters" method="GET" action="{{ route('ict-inventory.index') }}" class="flex shrink-0 flex-wrap items-center gap-4 border-b border-gray-200 bg-gray-50/70 px-5 py-2">
            <div class="flex w-full flex-wrap items-center gap-4 sm:w-auto">
                <label for="inventoryBarangayFilter" class="sr-only">Filter by Barangay</label>
                <select id="inventoryBarangayFilter" name="barangay"
                    class="h-10 w-full rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-700 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100 sm:w-60">
                    <option value="">All Barangays</option>
                    @foreach ($barangays as $barangay)
                        <option value="{{ $barangay->id }}" @selected((string) request('barangay') === (string) $barangay->id)>{{ $barangay->name }}</option>
                    @endforeach
                </select>
                <label for="inventorySectorFilter" class="sr-only">Filter by Sector</label>
                <select id="inventorySectorFilter" name="sector"
                    class="h-10 w-full rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-700 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100 sm:w-60">
                    <option value="">All Sectors</option>
                    <option value="School" @selected(request('sector') === 'School')>School</option>
                    <option value="Barangay Hall" @selected(request('sector') === 'Barangay Hall')>Barangay Hall</option>
                </select>
                <label for="inventoryInstitutionFilter" class="sr-only">Inventory status</label>
                <select id="inventoryInstitutionFilter" name="status" aria-label="Filter inventory"
                    class="h-10 w-full appearance-auto rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-700 transition hover:bg-gray-50 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100 sm:w-60">
                    <option value="all" @selected(request('status', 'all') === 'all')>Filter by Inventory Status</option>
                    <option value="active" @selected(request('status', 'all') === 'active')>With Active Units</option>
                    <option value="attention" @selected(request('status', 'all') === 'attention')>With Defective Units</option>
                    <option value="empty" @selected(request('status', 'all') === 'empty')>No Inventory</option>
                </select>
            </div>
            <div class="ml-auto flex shrink-0 items-center gap-4">
                <button type="submit"
                    class="inline-flex h-10 items-center gap-2 rounded-md border border-red-700 bg-transparent px-4 text-sm font-medium text-red-700 transition hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                    <i class="fa-solid fa-filter text-xs" aria-hidden="true"></i>
                    Apply
                </button>
                <a data-inventory-reset href="{{ route('ict-inventory.index') }}"
                    class="inline-flex h-10 items-center gap-2 rounded-md border-0 bg-transparent px-4 text-sm font-medium text-gray-600 transition hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                    <i class="fa-solid fa-rotate-left text-xs" aria-hidden="true"></i> Reset
                </a>
            </div>
            <p id="inventorySearchStatus" role="status" aria-live="polite" hidden class="text-sm text-gray-600"></p>
        </form>

        @include('ict-inventory._table-results')
    </div>
@endsection

