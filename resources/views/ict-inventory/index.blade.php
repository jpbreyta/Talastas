@extends('layouts.app')

@section('page-title', 'ICT Inventory')
@section('page-icon', 'fa-boxes-stacked')

@section('dashboard-layout')
@endsection

@section('page-tools')
<div class="relative">
                        <button type="submit" form="inventoryListFilters" aria-label="Search inventory" class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </button>

                        <input id="inventoryInstitutionSearch" name="search" form="inventoryListFilters" value="{{ request('search') }}" maxlength="255" aria-label="Search inventory"
                            type="search"
                            placeholder="Search institutions or components..."
                            autocomplete="off"
                            class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:ring-2 focus:ring-red-100 lg:w-64">
                    </div>

                    <a href="{{ route('ict-inventory.create') }}"
                        class="page-header-primary inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-red-100 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-50">
                        <i class="fa-solid fa-plus"></i>
                        Add Inventory
                    </a>
@endsection

@section('content')
    <div data-inventory-list class="flex h-full min-h-0 flex-col bg-gray-100">

        <form id="inventoryListFilters" method="GET" action="{{ route('ict-inventory.index') }}" class="flex shrink-0 flex-wrap items-center gap-4 border-b border-gray-200 bg-gray-50/70 px-5 py-2">
            <label for="inventoryInstitutionFilter" class="sr-only">Inventory status</label>
            <select id="inventoryInstitutionFilter" name="status" aria-label="Filter inventory"
                class="h-10 w-full appearance-auto rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-700 transition hover:bg-gray-50 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100 sm:w-60">
                <option value="all" @selected(request('status', 'all') === 'all')>Filter by Inventory Status</option>
                <option value="active" @selected(request('status', 'all') === 'active')>With Active Units</option>
                <option value="attention" @selected(request('status', 'all') === 'attention')>With Defective Units</option>
                <option value="empty" @selected(request('status', 'all') === 'empty')>No Inventory</option>
            </select>
            <label for="inventorySort" class="sr-only">Sort inventory</label>
            <select id="inventorySort" name="sort" class="h-10 w-full appearance-auto rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-700 transition hover:bg-gray-50 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100 sm:w-60">
                <option value="name" @selected(request('sort', 'name') === 'name')>Institution name</option>
                <option value="latest" @selected(request('sort') === 'latest')>Latest inventory update</option>
                <option value="most_complete" @selected(request('sort') === 'most_complete')>Most complete inventory</option>
            </select>
            <p class="w-full text-xs text-gray-500">Completeness ranks valid recorded inventory counts. A complete assessment also requires connectivity inputs and confirmed defective-within-Active counts.</p>
            <p id="inventorySearchStatus" role="status" aria-live="polite" hidden class="text-sm text-gray-600"></p>
        </form>

        @include('ict-inventory._table-results')
    </div>
@endsection

