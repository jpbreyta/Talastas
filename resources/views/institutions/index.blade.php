@extends('layouts.app')

@section('page-title', 'Institutions')
@section('page-icon', 'fa-building')

@section('dashboard-layout')
@endsection

@section('page-tools')
<div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </div>

                        <input id="institutionSearch" name="search" form="institutionListFilters" value="{{ request('search') }}" aria-label="Search institutions" type="search" placeholder="Search institutions..."
                            class="w-36 sm:w-56 rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:ring-2 focus:ring-red-100">
                    </div>

                    @if (auth()->user()->canManageInstitutions())
                        <button type="button" id="openAddModal" data-open-institution-modal
                            class="page-header-primary inline-flex items-center gap-2 rounded-lg border border-red-100 bg-white px-4 py-2 text-sm font-medium text-red-700 shadow-sm transition hover:bg-red-50">
                            <i class="fa-solid fa-plus"></i>
                            Add Institution
                        </button>
                    @endif
@endsection

@section('content')
    @php($canManageInstitutions = auth()->user()->canManageInstitutions())

    <div data-institution-list class="flex h-full min-h-0 flex-col bg-gray-100">
        {{-- Page Header --}}

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="mx-5 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <div>
                        <p class="font-semibold">Please correct the following:</p>
                        <ul class="mt-1 list-disc space-y-0.5 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Sort and Filter Toolbar --}}
        <div class="shrink-0 border-b border-gray-200 bg-gray-50/70 px-5 py-2">
            <form id="institutionListFilters" method="GET" action="{{ route('institutions.index') }}"
                class="talastas-filter-controls flex flex-wrap items-center gap-4">

                <div class="flex w-full flex-wrap items-center gap-4 sm:w-auto">
                    <div class="w-full sm:w-60">
                        <label for="barangayFilter"
                            class="sr-only">
                            Barangay
                        </label>
                        <select id="barangayFilter" name="barangay"
                            class="h-10 w-full appearance-auto rounded-md border border-gray-300 bg-white px-3 text-gray-700 transition hover:bg-gray-50 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                            <option value="">Filter by Barangay</option>
                            @foreach ($barangays as $barangay)
                                <option value="{{ $barangay->id }}"
                                    @selected((string) request('barangay') === (string) $barangay->id)>
                                    {{ $barangay->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-full sm:w-60">
                        <label for="institutionSort"
                            class="sr-only">
                            Sort By
                        </label>
                        <select id="institutionSort" name="sort"
                            class="h-10 w-full appearance-auto rounded-md border border-gray-300 bg-white px-3 text-gray-700 transition hover:bg-gray-50 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                            <option value="barangay_asc"
                                @selected(request('sort', 'barangay_asc') === 'barangay_asc')>
                                Sort By: Barangay A-Z
                            </option>
                            <option value="barangay_desc"
                                @selected(request('sort') === 'barangay_desc')>
                                Barangay Z-A
                            </option>
                            <option value="name_asc"
                                @selected(request('sort') === 'name_asc')>
                                Institution A-Z
                            </option>
                            <option value="name_desc"
                                @selected(request('sort') === 'name_desc')>
                                Institution Z-A
                            </option>
                            <option value="newest"
                                @selected(request('sort') === 'newest')>
                                Newest
                            </option>
                        </select>
                    </div>
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-4">
                    <button type="submit"
                        class="inline-flex h-10 items-center gap-2 rounded-md border border-red-700 bg-transparent px-4 text-sm font-medium text-red-700 transition hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                        <i class="fa-solid fa-filter text-xs" aria-hidden="true"></i>
                        Apply
                    </button>

                    <a data-institution-reset href="{{ route('institutions.index') }}"
                        class="inline-flex h-10 items-center gap-2 rounded-md border-0 bg-transparent px-4 text-sm font-medium text-gray-600 transition hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                        <i class="fa-solid fa-rotate-left text-xs" aria-hidden="true"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <div data-institution-search-status @if (!filled(request('search'))) hidden @endif
            class="shrink-0 px-5 py-3">
            <div data-institution-list-status role="status" aria-live="polite" aria-atomic="true"
                class="inline-flex max-w-full items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm">
                <i data-institution-status-icon class="fa-solid fa-building-columns text-red-700" aria-hidden="true"></i>
                <span data-institution-status-label>{{ number_format($institutions->total()) }} {{ $institutions->total() === 1 ? 'institution' : 'institutions' }}</span>
            </div>
        </div>
        @include('institutions._table-results')

        @if ($canManageInstitutions)
            @include('institutions.modals')
        @endif
    </div>
@endsection
