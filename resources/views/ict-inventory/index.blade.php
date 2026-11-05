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
    <div class="flex h-full min-h-0 flex-col bg-gray-100">

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
            <p id="inventorySearchError" role="alert" hidden class="text-sm text-red-700">Unable to load results. Try again.</p>
        </form>

        @include('ict-inventory._table-results')
    </div>
@endsection

@section('scripts')
<script>
(() => {
    'use strict';

    const searchInput = document.getElementById('inventoryInstitutionSearch');
    const sortSelect = document.getElementById('inventorySort');
    const filterSelect = document.getElementById('inventoryInstitutionFilter');
    const form = document.getElementById('inventoryListFilters');
    const errorMessage = document.getElementById('inventorySearchError');
    const results = () => document.querySelector('[data-inventory-results]');
    let controller, timer, sequence = 0;

    function setExpanded(group, expanded) {
        group.querySelector('[data-accordion-trigger]')?.setAttribute('aria-expanded', String(expanded));
        group.querySelector('[data-accordion-panel]')?.classList.toggle('hidden', !expanded);
        group.querySelector('[data-chevron]')?.classList.toggle('rotate-90', expanded);
    }

    // Delegation keeps the inventory accordions working after a search refresh.
    function toggleAccordion(event) {
        const trigger = event.target.closest('[data-accordion-trigger]');
        if (!trigger || event.target.closest('[data-stop-accordion]')) return;
        if (event.type === 'keydown') {
            if (event.target !== trigger || !['Enter', ' '].includes(event.key)) return;
            event.preventDefault();
        }
        setExpanded(trigger.closest('[data-institution-group]'), trigger.getAttribute('aria-expanded') !== 'true');
    }
    document.addEventListener('click', toggleAccordion);
    document.addEventListener('keydown', toggleAccordion);

    function searchUrl() {
        const url = new URL(form.action);
        const search = searchInput.value.trim();
        if (search) url.searchParams.set('search', search);
        if (filterSelect.value !== 'all') url.searchParams.set('status', filterSelect.value);
        if (sortSelect.value !== 'name') url.searchParams.set('sort', sortSelect.value);
        return url;
    }

    function setBusy(busy) {
        results().setAttribute('aria-busy', String(busy));
        results().toggleAttribute('inert', busy);
        results().classList.toggle('opacity-60', busy);
    }

    function cancelPending() {
        clearTimeout(timer);
        controller?.abort();
        sequence++;
        setBusy(false);
    }

    async function refresh(url, history = 'replace') {
        cancelPending();
        const requestId = sequence;
        controller = new AbortController();
        errorMessage.hidden = true;
        setBusy(true);
        try {
            const response = await fetch(url.href, {
                signal: controller.signal, credentials: 'same-origin',
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest', 'X-Talastas-Fragment': 'inventory' },
            });
            if (response.redirected || !response.ok) throw new Error('Unable to load inventory.');
            const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
            const replacement = parsed.querySelector('[data-inventory-results]');
            if (!replacement) throw new Error('Invalid inventory response.');
            if (requestId !== sequence) return;
            results().replaceWith(replacement);
            if (history !== 'none' && url.href !== window.location.href) {
                window.history[history === 'push' ? 'pushState' : 'replaceState'](null, '', url.href);
            }
        } catch (error) {
            if (requestId === sequence && error.name !== 'AbortError') errorMessage.hidden = false;
        } finally {
            if (requestId === sequence) setBusy(false);
        }
    }

    searchInput.addEventListener('input', () => {
        cancelPending();
        timer = setTimeout(() => refresh(searchUrl()), 300);
    });
    form.addEventListener('submit', event => {
        event.preventDefault();
        void refresh(searchUrl());
    });
    sortSelect.addEventListener('change', () => { void refresh(searchUrl()); });
    filterSelect.addEventListener('change', () => { void refresh(searchUrl()); });
    document.addEventListener('click', event => {
        const link = event.target.closest('[data-inventory-results] nav a');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const url = new URL(link.href);
        if (url.origin !== window.location.origin) return;
        event.preventDefault();
        void refresh(url, 'push');
    });
    window.addEventListener('popstate', () => {
        const url = new URL(window.location.href);
        searchInput.value = url.searchParams.get('search') || '';
        filterSelect.value = url.searchParams.get('status') || 'all';
        sortSelect.value = url.searchParams.get('sort') || 'name';
        void refresh(url, 'none');
    });
})();
</script>
@endsection
