@extends('layouts.app')

@section('page-title', 'Create Inventory')
@section('page-icon', 'fa-boxes-stacked')

@section('dashboard-layout')
@endsection

@section('content')
    @include('ict-inventory._form-styles')
    @php
        $isInstitutionLocked = $selectedInstitution !== null;

        $cancelUrl = $isInstitutionLocked
            ? route('institutions.show', $selectedInstitution)
            : route('ict-inventory.index');

        $tabOrder = ['hardware', 'software', 'network', 'connectivity'];

        $tabLabels = [
            'hardware' => 'Hardware & Equipment',
            'software' => 'Software & Systems',
            'network' => 'Network Infrastructure',
            'connectivity' => 'Internet Connectivity',
        ];

        $tabIcons = [
            'hardware' => 'fa-computer',
            'software' => 'fa-code',
            'network' => 'fa-network-wired',
            'connectivity' => 'fa-globe',
        ];

        $activeTab = old('active_tab', request('tab', 'hardware'));

        if (!in_array($activeTab, $tabOrder, true)) {
            $activeTab = 'hardware';
        }

        $connectivityInstitution = $selectedInstitution;

        $boolValue = function (string $field) use ($connectivityInstitution) {
            $oldValue = old("connectivity.{$field}");

            if ($oldValue !== null) {
                return (string) $oldValue;
            }

            $value = $connectivityInstitution?->{$field};

            if ($value === null) {
                return '';
            }

            return $value ? '1' : '0';
        };
    @endphp

    <div class="inventory-form flex h-full min-h-0 flex-col bg-gray-100">
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
            <div class="mx-auto max-w-7xl">

                @include('ict-inventory._validation-errors')
                <details id="inventorySaveError" hidden class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <summary class="cursor-pointer font-semibold" data-save-error-title>Unable to save inventory</summary>
                    <ul class="mt-2 list-disc space-y-1 pl-5" data-save-errors></ul>
                </details>

                <form method="POST" novalidate
                    action="{{ route('ict-inventory.store') }}"
                    id="unifiedInventoryForm"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    @csrf
                    @foreach (old('removed_asset_ids', []) as $removedId)
                        <input type="hidden" name="removed_asset_ids[]" value="{{ $removedId }}">
                    @endforeach

                    <input type="hidden"
                        name="unified_inventory"
                        value="1">

                    <input type="hidden"
                        name="active_tab"
                        id="activeInventoryTab"
                        value="{{ $activeTab }}">

                    @if ($isInstitutionLocked)
                        <input type="hidden"
                            name="institution_id"
                            value="{{ $selectedInstitution->id }}">

                        <input type="hidden"
                            name="return_to"
                            value="institution">
                    @endif

                    <div class="flex items-center gap-4 border-b border-gray-200 bg-gray-50 px-5 py-4">
                        <a href="{{ $cancelUrl }}" aria-label="Go back" title="Go back"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                        </a>
                        <div class="min-w-0 flex-1">
                        @if ($isInstitutionLocked)
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-red-600 shadow-sm">
                                    <i class="fa-solid fa-building-columns"></i>
                                </div>

                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900">
                                        {{ $selectedInstitution->name }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-gray-500">
                                        {{ $selectedInstitution->type }}
                                        <span class="mx-1 text-gray-300">•</span>
                                        {{ $selectedInstitution->barangay?->name ?? 'Barangay not recorded' }}
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_auto] lg:items-end">
                                <div>
                                    <label for="institution_id"
                                        class="mb-1.5 block text-xs font-semibold text-gray-600">
                                        Institution <span class="text-red-500">*</span>
                                    </label>

                                    <select name="institution_id"
                                        id="institution_id"
                                        required
                                        class="form-input w-full">
                                        <option value="">Select an institution</option>
                                        @foreach ($institutions as $institution)
                                            <option value="{{ $institution->id }}"
                                                @selected((string) old('institution_id') === (string) $institution->id)>
                                                {{ $institution->name }}
                                                — {{ $institution->barangay?->name ?? 'No barangay' }}
                                            </option>
                                        @endforeach
                                    </select>


                                    <p class="mt-1.5 text-xs text-gray-500">
                                        Select an institution, then load its inventory.
                                    </p>
                                </div>

                                <button type="button"
                                    id="loadInstitutionInventory"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                    <i class="fa-solid fa-folder-open"></i>
                                    Load Inventory
                                </button>
                            </div>
                        @endif
                        </div>
                        <details class="relative shrink-0">
                            <summary aria-label="Inventory input instructions" title="Inventory input instructions"
                                class="flex h-7 w-7 cursor-pointer list-none items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-700 [&::-webkit-details-marker]:hidden">
                                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            </summary>
                            <div class="absolute right-0 top-full z-30 mt-2 w-72 max-w-[80vw] rounded-lg border border-gray-200 bg-white p-4 text-xs leading-5 text-gray-700 shadow-lg">
                                <p class="mb-2 font-semibold text-gray-900">Inventory input guide</p>
                                <p>Enter 0 for a confirmed zero. Leave an entire row blank when unrecorded. Only Total is required on a recorded row. Blank availability counts stay unrecorded; 0 means confirmed zero.</p>
                                <p class="mt-2">For newly encoded data, Active means deployed/in-service and may include defective units. Defective within Active counts defective units in that population. General Defective and Functional remain separate. Each recorded subset cannot exceed Total; Defective within Active cannot exceed Active or recorded general Defective. Templates are optional. Removing a saved item requires confirmation. Unknown subtotals display —.</p>
                                @foreach ($tabOrder as $helpTab)
                                    <p class="mt-2"><strong>{{ $tabLabels[$helpTab] }}:</strong> {{ $helpTab === 'connectivity' ? 'Record each Internet connection separately.' : $sections[$helpTab]['meta']['description'] }}</p>
                                @endforeach
                            </div>
                        </details>
                    </div>

                    <div class="border-b border-gray-200 bg-white px-5 pt-4">
                        <div class="flex flex-wrap gap-2" role="tablist" aria-label="ICT inventory categories">
                            @foreach ($tabOrder as $tab)
                                <button type="button"
                                    data-inventory-tab="{{ $tab }}"
                                    role="tab"
                                    aria-selected="{{ $activeTab === $tab ? 'true' : 'false' }}"
                                    class="inventory-tab inline-flex items-center gap-2 rounded-t-lg border border-b-0 px-4 py-2.5 text-sm font-semibold transition
                                        {{ $activeTab === $tab
                                            ? 'border-red-200 bg-red-50 text-red-700'
                                            : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
                                    <i class="fa-solid {{ $tabIcons[$tab] }}"></i>
                                    {{ $tabLabels[$tab] }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @foreach ($tabOrder as $tab)
                        @php
                            $section = $sections[$tab];
                            $items = $section['items'];
                        @endphp

                        <input type="hidden" name="sections[{{ $tab }}][submitted]" value="1">
                        <section data-inventory-panel="{{ $tab }}"
                            class="{{ $activeTab === $tab ? '' : 'hidden' }} p-5"
                            role="tabpanel">

                            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h2 class="text-base font-semibold text-gray-900">
                                        {{ $section['meta']['title'] }}
                                    </h2>
                                </div>

                                @if ($tab !== 'connectivity')
                                <button type="button"
                                    data-add-row="{{ $tab }}"
                                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                    <i class="fa-solid fa-plus"></i>
                                    Add Item
                                </button>
                                @endif
                            </div>

                            @if ($tab === 'connectivity')
                                @include('ict-inventory._connectivity-infrastructure', ['editable' => true, 'inventoryItems' => $items])
                                @include('ict-inventory._q10-fields')
                            @endif

                            @if ($tab !== 'connectivity')


                            <div class="overflow-hidden rounded-xl border border-gray-200">
                                <div class="overflow-x-auto">
                                    <table class="inventory-quantity-table w-full min-w-[760px] text-sm">
                                        <thead>
                                            <tr class="border-b border-gray-200 bg-gray-50">
                                                <th class="min-w-[220px] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                                    Component
                                                </th>
                                                <th class="w-20 px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Total</th>
                                                <th class="w-20 px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Active</th>
                                                <th class="w-20 px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Retired</th>
                                                <th class="w-20 px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Functional</th>
                                                <th class="w-20 px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Defective</th>
                                                <th class="w-28 px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Defective within Active</th>
                                                <th class="w-16 px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide">Actions</th>
                                            </tr>
                                        </thead>

                                        <tbody data-inventory-rows="{{ $tab }}"
                                            class="divide-y divide-gray-100 bg-white">
                                            @php $previousSubsection = null; @endphp
                                            @foreach ($items as $index => $item)
                                                @php
                                                    $isStandard = (bool) ($item['is_standard'] ?? false);
                                                    $subsection = $item['subsection']
                                                        ?? ($isStandard ? 'Standard Component' : 'Other Equipment');
                                                @endphp

                                                @if ($subsection !== $previousSubsection)
                                                    <tr class="bg-gray-50 font-semibold text-gray-700" data-subtotal-group="{{ $subsection }}">
                                                        <th scope="row" class="px-3 py-3 text-left">{{ $subsection }} (subtotal)</th>
                                                        @foreach (['total_count', 'active_count', 'retired_count', 'functional_count', 'defective_count', 'defective_active_count'] as $subtotalField)
                                                            <td class="px-2 py-3 text-center" data-subtotal-field="{{ $subtotalField }}">—</td>
                                                        @endforeach
                                                        <td></td>
                                                    </tr>
                                                @endif
                                                @php $previousSubsection = $subsection; @endphp
                                                <tr data-inventory-row data-category="{{ $tab }}" data-subsection="{{ $subsection }}" @if (!$isStandard && empty($item['asset_id'])) data-new-item @endif>
                                                    <td class="px-3 py-3 align-top">
                                                        <input type="hidden"
                                                            name="sections[{{ $tab }}][items][{{ $index }}][asset_id]"
                                                            value="{{ $item['asset_id'] ?? '' }}"
                                                            data-field="asset_id">

                                                        <input type="hidden"
                                                            name="sections[{{ $tab }}][items][{{ $index }}][component_id]"
                                                            value="{{ $item['component_id'] ?? '' }}"
                                                            data-field="component_id">

                                                        @if ($isStandard)
                                                            <input type="hidden"
                                                                name="sections[{{ $tab }}][items][{{ $index }}][asset_name]"
                                                                value="{{ $item['asset_name'] ?? '' }}"
                                                                data-field="asset_name">

                                                            <p class="font-semibold text-gray-900">
                                                                {{ $item['asset_name'] ?? 'Unnamed Component' }}
                                                            </p>
                                                        @else
                                                            <input type="text"
                                                                name="sections[{{ $tab }}][items][{{ $index }}][asset_name]"
                                                                value="{{ $item['asset_name'] ?? '' }}"
                                                                maxlength="150"
                                                                data-field="asset_name"
                                                                list="inventory-types-{{ $tab }}"
                                                                aria-label="Device or item type"
                                                                class="form-input w-full"
                                                                placeholder="Enter item name">
                                                        @endif


                                                        <p data-availability-status @if (\App\Services\InventoryCounts::status($item) === 'Availability Recorded') hidden @endif class="mt-1 text-xs text-amber-700">{{ \App\Services\InventoryCounts::status($item) === 'Availability Recorded' ? '' : \App\Services\InventoryCounts::status($item) }}</p>
                                                        @if ($isStandard)
                                                            <input type="hidden" data-field="subsection" name="sections[{{ $tab }}][items][{{ $index }}][subsection]" value="{{ $subsection }}">
                                                            <p class="mt-1 text-xs text-gray-500">{{ $subsection }}</p>
                                                        @else
                                                            <label class="mt-2 block text-xs font-medium text-gray-500">Category
                                                                <input type="text" data-field="subsection" name="sections[{{ $tab }}][items][{{ $index }}][subsection]"
                                                                    value="{{ $subsection }}" list="inventory-groups-{{ $tab }}" maxlength="150"
                                                                    aria-label="Subtotal category" placeholder="Choose or enter category" class="form-input mt-1 w-full">
                                                            </label>
                                                        @endif
                                                    </td>

                                                    <td colspan="6" class="py-3 align-top">
                                                        @php($quantityErrorId = 'inventory-quantity-error-' . $tab . '-' . $index)
                                                        <div class="grid grid-cols-6">
                                                            @foreach (['total_count', 'active_count', 'retired_count', 'functional_count', 'defective_count', 'defective_active_count'] as $field)
                                                                <div class="min-w-0 px-2">
                                                                    @include('ict-inventory._count-input', [
                                                                        'field' => $field,
                                                                        'inputName' => "sections[{$tab}][items][{$index}][{$field}]",
                                                                        'inputValue' => $item[$field] ?? '',
                                                                        'countsOptional' => true,
                                                                        'errorKey' => "sections.{$tab}.items.{$index}.{$field}",
                                                                        'quantityErrorId' => $quantityErrorId,
                                                                    ])
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <div class="px-2" data-row-feedback role="status" aria-live="polite">
                                                            @include('ict-inventory._quantity-errors', [
                                                                'errorPrefix' => "sections.{$tab}.items.{$index}.",
                                                                'countValues' => $item,
                                                                'quantityErrorId' => $quantityErrorId,
                                                            ])
                                                        </div>
                                                    </td>

                                                    <td class="px-2 py-3 text-center align-top">
                                                        <button type="button"
                                                            data-remove-row
                                                            @if (!empty($item['asset_id']) && !auth()->user()?->isAdmin()) hidden @endif
                                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600"
                                                            title="Remove item">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div data-empty-state="{{ $tab }}"
                                    class="{{ count($items) > 0 ? 'hidden' : '' }} px-6 py-10 text-center">
                                    <i class="fa-solid fa-layer-group text-2xl text-gray-300"></i>
                                    <p class="mt-2 text-sm font-semibold text-gray-700">
                                        No items included
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Add an item if needed.
                                    </p>
                                </div>
                            </div>




                            @endif
                        </section>
                    @endforeach

                    <div class="sticky bottom-0 z-10 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-t border-gray-200 bg-white/95 px-5 py-4 backdrop-blur">
                        <p class="hidden text-xs text-gray-500 sm:block">
                            Save all inventory categories together.
                        </p>

                        <div class="flex w-full flex-col-reverse gap-3 sm:ml-auto sm:w-auto sm:flex-row sm:items-center">
                            <a href="{{ $cancelUrl }}"
                                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                                Cancel
                            </a>

                            <button type="submit"
                                id="saveUnifiedInventory"
                                @disabled(!$isInstitutionLocked)
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">
                                <i class="fa-solid fa-plus"></i>
                                Save Inventory
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach (['hardware', 'software', 'network'] as $tab)
        <datalist id="inventory-types-{{ $tab }}">
            @foreach (collect($sections[$tab]['items'])->pluck('asset_name')->merge($sections[$tab]['components']->pluck('name'))->filter()->unique() as $typeName)
                <option value="{{ $typeName }}"></option>
            @endforeach
        </datalist>
        <datalist id="inventory-groups-{{ $tab }}">
            @foreach (collect($sections[$tab]['items'])->pluck('subsection')->merge($sections[$tab]['components']->pluck('subsection'))->filter()->unique() as $groupName)
                <option value="{{ $groupName }}"></option>
            @endforeach
        </datalist>
        <template id="inventoryRowTemplate-{{ $tab }}">
            <tr data-inventory-row data-new-item data-category="{{ $tab }}" data-subsection="Other Equipment">
                <td class="px-3 py-3 align-top">
                    <input type="hidden" value="" data-field="asset_id">
                    <input type="hidden" value="" data-field="component_id">

                    <div class="inventory-type-picker">
                        <input type="text" maxlength="150" data-field="asset_name"
                            list="inventory-types-{{ $tab }}" aria-label="Device or item type"
                            class="form-input w-full" placeholder="Choose or enter device type">
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </div>

                    <p data-availability-status class="mt-1 text-xs text-amber-700">Availability Not Recorded</p>
                    <label class="mt-2 block text-xs font-medium text-gray-500">Category
                        <input type="text" data-field="subsection" list="inventory-groups-{{ $tab }}" maxlength="150"
                            aria-label="Subtotal category" placeholder="Choose or enter category" class="form-input mt-1 w-full">
                    </label>
                </td>

                <td colspan="6" class="py-3 align-top">
                    <div class="grid grid-cols-6">
                        @foreach (['total_count', 'active_count', 'retired_count', 'functional_count', 'defective_count', 'defective_active_count'] as $field)
                            <div class="min-w-0 px-2">
                                <input type="number" value="" min="0" max="1000000" step="1"
                                    data-field="{{ $field }}" data-count="{{ $field }}"
                                    aria-label="{{ $field === 'defective_active_count' ? 'Defective within Active' : ucwords(str_replace('_', ' ', $field)) }}"
                                    class="form-input w-full text-center">
                            </div>
                        @endforeach
                    </div>
                    <div class="px-2" data-row-feedback role="status" aria-live="polite"></div>
                </td>


                <td class="px-2 py-3 text-center align-top">
                    <button type="button"
                        data-remove-row
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600"
                        title="Remove item">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        </template>
    @endforeach
    @include('ict-inventory._save-feedback')
    <dialog id="deleteInventoryItemModal" class="w-[calc(100%-2rem)] max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/60" aria-labelledby="deleteInventoryItemTitle">
        <div class="border-b border-slate-200 px-6 py-5">
            <h2 id="deleteInventoryItemTitle" class="text-lg font-semibold text-slate-900">Delete inventory item?</h2>
            <p class="mt-1 text-sm text-slate-600">This saved item will be deleted when you save the inventory form.</p>
        </div>
        <div class="flex justify-end gap-3 bg-slate-50 px-6 py-4">
            <button type="button" data-cancel-delete-inventory-item class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700">Cancel</button>
            <button type="button" data-confirm-delete-inventory-item class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Delete item</button>
        </div>
    </dialog>
@endsection

@section('scripts')
<script src="{{ asset('js/inventory-counts.js') }}?v={{ filemtime(public_path('js/inventory-counts.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('unifiedInventoryForm');
    const activeTabInput = document.getElementById('activeInventoryTab');
    const tabButtons = Array.from(
        document.querySelectorAll('[data-inventory-tab]')
    );
    const panels = Array.from(
        document.querySelectorAll('[data-inventory-panel]')
    );
    const deleteItemModal = document.getElementById('deleteInventoryItemModal');
    let resolveDeleteItem = null;

    function confirmInventoryItemDelete() {
        return new Promise((resolve) => {
            resolveDeleteItem = resolve;
            deleteItemModal?.showModal();
        });
    }

    deleteItemModal?.querySelector('[data-cancel-delete-inventory-item]')?.addEventListener('click', () => deleteItemModal.close());
    deleteItemModal?.querySelector('[data-confirm-delete-inventory-item]')?.addEventListener('click', () => {
        deleteItemModal.close();
        resolveDeleteItem?.(true);
        resolveDeleteItem = null;
    });
    deleteItemModal?.addEventListener('cancel', () => {
        resolveDeleteItem?.(false);
        resolveDeleteItem = null;
    });
    deleteItemModal?.addEventListener('close', () => {
        resolveDeleteItem?.(false);
        resolveDeleteItem = null;
    });

    const labels = {
        hardware: 'Hardware & Equipment',
        software: 'Software & Systems',
        network: 'Network Infrastructure',
        connectivity: 'Internet Connectivity',
    };

    function activateTab(tab) {
        tabButtons.forEach((button) => {
            const active =
                button.dataset.inventoryTab === tab;

            button.setAttribute(
                'aria-selected',
                String(active)
            );

            button.classList.toggle(
                'border-red-200',
                active
            );
            button.classList.toggle(
                'bg-red-50',
                active
            );
            button.classList.toggle(
                'text-red-700',
                active
            );

            button.classList.toggle(
                'border-gray-200',
                !active
            );
            button.classList.toggle(
                'bg-white',
                !active
            );
            button.classList.toggle(
                'text-gray-600',
                !active
            );
        });

        panels.forEach((panel) => {
            panel.classList.toggle(
                'hidden',
                panel.dataset.inventoryPanel !== tab
            );
        });

        if (activeTabInput) {
            activeTabInput.value = tab;
        }
    }

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activateTab(
                button.dataset.inventoryTab
            );
        });
    });

    function rowsFor(tab) {
        return Array.from(
            document.querySelectorAll(
                `[data-inventory-rows="${tab}"] [data-inventory-row]`
            )
        );
    }

    function updateEmptyState(tab) {
        document
            .querySelector(`[data-empty-state="${tab}"]`)
            ?.classList.toggle(
                'hidden',
                rowsFor(tab).length > 0
            );
    }

    function reindexRows(tab) {
        rowsFor(tab).forEach((row, index) => {
            row.querySelectorAll('[data-field]').forEach((input) => {
                const field = input.dataset.field;

                input.name =
                    `sections[${tab}][items][${index}][${field}]`;
            });

        });

        updateEmptyState(tab);
    }

    function validateTab(tab, subsection = null, validate = true, affectedRow = null) {
        const countFields = ['total_count', 'active_count', 'retired_count', 'functional_count', 'defective_count', 'defective_active_count'];
        const tbody = document.querySelector(`[data-inventory-rows="${tab}"]`);
        const normalizeName = value => value.trim().replace(/\s+/g, ' ').toLowerCase();
        tbody?.querySelectorAll('[data-subtotal-group]').forEach(group => {
            if (subsection !== null && group.dataset.subtotalGroup !== subsection) return;
            const rows = rowsFor(tab).filter(row => row.dataset.subsection === group.dataset.subtotalGroup &&
                (tab === 'connectivity'
                    ? row.querySelector('[data-field="asset_id"]')?.value || countFields.some(field => row.querySelector(`[data-count="${field}"]`)?.value)
                    : row.querySelector('[data-count="total_count"]')?.value !== ''));
            group.querySelectorAll('[data-subtotal-field]').forEach(cell => {
                const values = rows.map(row => row.querySelector(`[data-count="${cell.dataset.subtotalField}"]`)?.value ?? '');
                const subtotal = window.InventoryCounts.subtotal(values);
                cell.textContent = subtotal === null ? '—' : String(subtotal);
            });
        });
        const rowValues = row => Object.fromEntries(countFields.map(field => [field, row.querySelector(`[data-count="${field}"]`)?.value ?? '']));
        const rowsToCheck = affectedRow ? [affectedRow] : rowsFor(tab).filter(row => subsection === null || row.dataset.subsection === subsection);
        rowsToCheck.forEach(row => {
            const status = row.querySelector('[data-availability-status]');
            if (status) {
                const label = window.InventoryCounts.status(rowValues(row));
                status.hidden = label === 'Availability Recorded';
                status.textContent = status.hidden ? '' : label;
            }
        });
        let valid = true;
        rowsToCheck.forEach(row => {
            const values = rowValues(row);
            const errors = window.InventoryCounts.errors(values);
            const hasRecordedData = countFields.some(field => values[field] !== '')
                || Boolean(row.querySelector('[data-field="asset_id"]')?.value);
            const name = normalizeName(row.querySelector('[data-field="asset_name"]')?.value ?? '');
            if (countFields.some(field => values[field] !== '') && !name) {
                errors.push({field: 'asset_name', message: 'Enter an item name.'});
            }
            if (validate && hasRecordedData && name && rowsFor(tab).some(other => {
                if (other === row) return false;
                const otherValues = rowValues(other);
                const otherRecorded = countFields.some(field => otherValues[field] !== '')
                    || Boolean(other.querySelector('[data-field="asset_id"]')?.value);
                return otherRecorded && normalizeName(other.querySelector('[data-field="asset_name"]')?.value ?? '') === name;
            })) {
                errors.push({field: 'asset_name', message: 'Each component name may appear only once in the same category.'});
            }
            window.InventoryFeedback.showRowErrors(row, values, errors);
            if (errors.length) valid = false;
        });
        return valid;
    }

    function placeInventoryRow(tab, row, value) {
        const tbody = document.querySelector(`[data-inventory-rows="${tab}"]`);
        const groups = Array.from(tbody.querySelectorAll('[data-subtotal-group]'));
        const name = value.trim().replace(/\s+/g, ' ') || 'Other Equipment';
        let group = groups.find(group => group.dataset.subtotalGroup.toLowerCase() === name.toLowerCase());
        if (!group) {
            group = document.createElement('tr');
            group.dataset.subtotalGroup = name;
            group.className = 'bg-gray-50 font-semibold text-gray-700';
            const heading = document.createElement('th');
            heading.scope = 'row'; heading.className = 'px-3 py-3 text-left';
            heading.textContent = `${name} (subtotal)`;
            group.appendChild(heading);
            ['total_count', 'active_count', 'retired_count', 'functional_count', 'defective_count', 'defective_active_count'].forEach(field => {
                const cell = document.createElement('td');
                cell.dataset.subtotalField = field; cell.className = 'px-2 py-3 text-center';
                group.appendChild(cell);
            });
            group.appendChild(document.createElement('td'));
            tbody.appendChild(group);
        }
        row.dataset.subsection = group.dataset.subtotalGroup;
        row.querySelector('[data-field="subsection"]').value = row.dataset.subsection;
        let end = group.nextElementSibling;
        while (end && !end.hasAttribute('data-subtotal-group')) end = end.nextElementSibling;
        tbody.insertBefore(row, row.hasAttribute('data-new-item') ? group.nextElementSibling : end);
        const options = document.getElementById(`inventory-groups-${tab}`);
        if (options && !Array.from(options.options).some(option => option.value === row.dataset.subsection)) {
            const option = document.createElement('option'); option.value = row.dataset.subsection; options.appendChild(option);
        }
        groups.forEach(heading => {
            if (!rowsFor(tab).some(item => item.dataset.subsection === heading.dataset.subtotalGroup)) heading.remove();
        });
        reindexRows(tab);
        validateTab(tab);
    }

    function bindRow(tab, row) {
        row.querySelector('[data-field="subsection"]')?.addEventListener('change', event => {
            placeInventoryRow(tab, row, event.target.value);
        });
        const nameInput = row.querySelector('input[type="text"][data-field="asset_name"]');
        nameInput?.addEventListener('change', () => {
            const existing = rowsFor(tab).find(item => item !== row &&
                item.querySelector('[data-field="asset_name"]')?.value.trim().toLowerCase() === nameInput.value.trim().toLowerCase());
            if (existing && !Array.from(row.querySelectorAll('[data-count]')).some(input => input.value !== '')) {
                row.remove();
                const heading = document.querySelector(`[data-inventory-rows="${tab}"]`);
                heading.querySelectorAll('[data-subtotal-group]').forEach(group => {
                    if (!rowsFor(tab).some(item => item.dataset.subsection === group.dataset.subtotalGroup)) group.remove();
                });
                reindexRows(tab); validateTab(tab);
                existing.querySelector('[data-count="total_count"]')?.focus();
            }
        });
        row.querySelectorAll('[data-count], [data-field="asset_name"]').forEach((input) => {
            input.addEventListener(
                'input',
                () => validateTab(tab, row.dataset.subsection, true, row)
            );
            input.addEventListener('change', () => validateTab(tab, row.dataset.subsection, true, row));
        });

        row.querySelector('[data-remove-row]')
            ?.addEventListener('click', async () => {
                const assetId = row.querySelector('[data-field="asset_id"]')?.value;
                if (assetId) {
                    if (!@json(auth()->user()?->isAdmin() ?? false)) return;
                    if (!await confirmInventoryItemDelete()) return;
                    const removal = document.createElement('input');
                    removal.type = 'hidden';
                    removal.name = 'removed_asset_ids[]';
                    removal.value = assetId;
                    form.appendChild(removal);
                }
                row.remove();
                reindexRows(tab);
                validateTab(tab);
            });
    }

    ['hardware', 'software', 'network', 'connectivity'].forEach((tab) => {
        rowsFor(tab).forEach((row) => {
            bindRow(tab, row);
        });

        document
            .querySelector(`[data-add-row="${tab}"]`)
            ?.addEventListener('click', () => {
                const template = document.getElementById(
                    `inventoryRowTemplate-${tab}`
                );

                const tbody = document.querySelector(
                    `[data-inventory-rows="${tab}"]`
                );

                if (!template || !tbody) {
                    return;
                }

                const fragment =
                    template.content.cloneNode(true);

                const row = fragment.querySelector(
                    '[data-inventory-row]'
                );

                const category = row.querySelector('[data-field="subsection"]');
                category.value = document.getElementById(`inventory-groups-${tab}`)?.options[0]?.value || 'Other Equipment';
                placeInventoryRow(tab, row, category.value);
                reindexRows(tab);
                bindRow(tab, row);

                row.querySelector(
                    '[data-field="asset_name"]'
                )?.focus();

                validateTab(tab);
            });

        reindexRows(tab);
        validateTab(tab);
    });

    let saving = false;
    form?.addEventListener('submit', async (event) => {
        if (saving) { event.preventDefault(); return; }
        let firstInvalidTab = null;

        ['hardware', 'software', 'network', 'connectivity'].forEach((tab) => {
            reindexRows(tab);

            if (
                !validateTab(tab)
                && firstInvalidTab === null
            ) {
                firstInvalidTab = tab;
            }
        });

        if (firstInvalidTab === null && !form.checkValidity()) {
            event.preventDefault();
            const invalid = form.querySelector(':invalid');
            activateTab(invalid?.closest('[data-inventory-panel]')?.dataset.inventoryPanel || 'connectivity');
            form.reportValidity();
            window.TalastasToast?.notify('error', 'Please correct the highlighted inventory fields before saving.');
            return;
        }
        if (firstInvalidTab !== null) {
            event.preventDefault();
            activateTab(firstInvalidTab);
            const invalid = document.querySelector(`[data-inventory-panel="${firstInvalidTab}"] [aria-invalid="true"]`);
            invalid?.scrollIntoView({behavior: 'smooth', block: 'center'});
            invalid?.focus({preventScroll: true});
            window.TalastasToast?.notify('error', 'Please correct the highlighted inventory fields before saving.');
            return;
        }
        event.preventDefault();
        saving = true;
        let navigating = false;
        let firstErrorInput = null;
        const button = document.getElementById('saveUnifiedInventory');
        const errorPanel = document.getElementById('inventorySaveError');
        button.disabled = true;
        errorPanel.hidden = true;
        try {
            // JSON preserves every field in large inventory forms.
            const payload = {};
            new FormData(form).forEach((value, name) => {
                if (name === 'removed_asset_ids[]') {
                    (payload.removed_asset_ids ??= []).push(value);
                    return;
                }
                const keys = name.match(/[^\[\]]+/g);
                let target = payload;
                keys.forEach((key, index) => {
                    if (index === keys.length - 1) target[key] = value;
                    else target = target[key] ??= {};
                });
            });
            window.InventoryFeedback.beginSaving(form);
            const response = await fetch(form.action, {
                method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': payload._token},
                body: JSON.stringify(payload),
            });
            const result = await response.json().catch(() => ({}));
            if (response.ok && result.redirect) {
                window.location.assign(result.redirect);
                navigating = true;
                return;
            }
            const errors = Object.entries(result.errors || {});
            const fallback = [];
            const rowErrors = new Map();
            let firstErrorTab = null;
            errors.forEach(([path, messages]) => {
                const parts = path.split('.');
                if (parts[0] === 'sections' && parts[2] === 'items') {
                    const row = rowsFor(parts[1])[Number(parts[3])];
                    if (row) {
                        if (!rowErrors.has(row)) rowErrors.set(row, []);
                        messages.forEach(message => rowErrors.get(row).push({field: parts[4] || 'asset_name', message}));
                        firstErrorTab ||= parts[1];
                        return;
                    }
                }
                const inputName = parts[0] + parts.slice(1).map(part => `[${part}]`).join('');
                const control = form.elements.namedItem(inputName);
                const input = control && typeof control.setAttribute !== 'function'
                    ? Array.from(control).find(option => option.checked) || control[0] : control;
                if (window.InventoryFeedback.showFieldError(input, messages)) {
                    firstErrorInput ||= input;
                    firstErrorTab ||= input.closest('[data-inventory-panel]')?.dataset.inventoryPanel;
                } else {
                    fallback.push(...messages);
                }
            });
            rowErrors.forEach((errors, row) => {
                const values = Object.fromEntries(['total_count', 'active_count', 'retired_count', 'functional_count', 'defective_count', 'defective_active_count'].map(field => [field, row.querySelector(`[data-count="${field}"]`)?.value ?? '']));
                window.InventoryFeedback.showRowErrors(row, values, errors);
                firstErrorInput ||= row.querySelector('[aria-invalid="true"]:not([type="hidden"])') || row.querySelector('[data-count="total_count"]');
            });
            if (labels[firstErrorTab]) {
                activateTab(firstErrorTab);
                firstErrorInput = document.querySelector(`[data-inventory-panel="${firstErrorTab}"] [aria-invalid="true"]:not([type="hidden"])`) || firstErrorInput;
            }
            const list = errorPanel.querySelector('[data-save-errors]');
            list.replaceChildren();
            fallback.forEach(message => {
                const text = document.createElement('li'); text.textContent = message; list.appendChild(text);
            });
            errorPanel.hidden = errors.length > 0 && fallback.length === 0;
            errorPanel.querySelector('[data-save-error-title]').textContent = fallback.length
                ? 'Check the highlighted details'
                : (response.status === 419 ? 'Session expired. Refresh and try again.' : 'Unable to save. Please try again.');
            window.TalastasToast?.notify('error', fallback[0] || Object.values(result.errors || {}).flat()[0]
                || errorPanel.querySelector('[data-save-error-title]').textContent);
        } catch {
            errorPanel.querySelector('[data-save-errors]').replaceChildren();
            errorPanel.querySelector('[data-save-error-title]').textContent = 'Connection interrupted. Please try again.';
            errorPanel.hidden = false;
            window.TalastasToast?.notify('error', errorPanel.querySelector('[data-save-error-title]').textContent);
        } finally {
            if (!navigating) {
                saving = false;
                button.disabled = false;
                window.InventoryFeedback.endSaving(form);
                button.textContent = 'Save Inventory';
                if (firstErrorInput) {
                    firstErrorInput.scrollIntoView({block: 'center', behavior: 'smooth'});
                    firstErrorInput.focus({preventScroll: true});
                } else if (!errorPanel.hidden) {
                    errorPanel.scrollIntoView({block: 'center', behavior: 'smooth'});
                }
            }
        }
    });

    const institutionSelect =
        document.getElementById('institution_id');

    document
        .getElementById('loadInstitutionInventory')
        ?.addEventListener('click', () => {
            const institutionId =
                institutionSelect?.value;

            if (!institutionId) {
                institutionSelect?.focus();
                return;
            }

            const url = new URL(
                '{{ route('ict-inventory.create') }}',
                window.location.origin
            );

            url.searchParams.set(
                'institution_id',
                institutionId
            );

            window.location.assign(url.toString());
        });

    const connectionList = document.getElementById('internetConnectionList');
    const addConnection = document.getElementById('addInternetConnection');
    const maximumConnections = @json(\App\Http\Controllers\IctInventoryController::MAX_INTERNET_CONNECTIONS);
    function markConnectivityUnsaved() {
        connectionList.querySelectorAll('[data-connection-result]').forEach(result => result.hidden = true);
    }
    connectionList?.addEventListener('input', markConnectivityUnsaved);
    addConnection?.addEventListener('click', () => {
        const numbers = Array.from(connectionList.querySelectorAll('[data-q10-connection]'), fieldset => Number(fieldset.dataset.q10Connection));
        const removedNumbers = Array.from(document.querySelectorAll('#removedInternetConnections input'), input => Number(input.value));
        const number = Math.max(0, ...numbers, ...removedNumbers) + 1;
        if (number > maximumConnections) return;
        const fragment = document.getElementById('internetConnectionTemplate').content.cloneNode(true);
        const fieldset = fragment.querySelector('[data-q10-connection]');
        fieldset.dataset.q10Connection = String(number);
        fieldset.setAttribute('data-new-connection', '');
        fieldset.querySelector('[data-connection-label]').textContent = `Connection ${number}`;
        fieldset.querySelector('[data-remove-connection]').setAttribute('aria-label', `Remove connection ${number}`);
        fieldset.querySelectorAll('[data-connection-field]').forEach(input => {
            input.name = `q10[connections][${number}][${input.dataset.connectionField}]`;
            input.value = '';
        });
        connectionList.appendChild(fragment);
        markConnectivityUnsaved();
        document.getElementById('internetConnectionsEmpty').hidden = true;
        addConnection.disabled = number >= maximumConnections;
        fieldset.scrollIntoView({behavior: 'smooth', block: 'start'});
        fieldset.querySelector('input')?.focus({preventScroll: true});
    });
    connectionList?.addEventListener('click', event => {
        const removeButton = event.target.closest('[data-remove-connection]');
        if (!removeButton) return;
        const fieldset = removeButton.closest('[data-q10-connection]');
        const number = fieldset.dataset.q10Connection;
        if (!fieldset.hasAttribute('data-new-connection')) {
            if (!window.confirm('Remove this saved Internet connection?')) return;
            const removed = document.createElement('input');
            removed.type = 'hidden'; removed.name = 'q10[removed_connection_numbers][]'; removed.value = number;
            document.getElementById('removedInternetConnections').appendChild(removed);
        }
        fieldset.remove();
        markConnectivityUnsaved();
        const numbers = Array.from(connectionList.querySelectorAll('[data-q10-connection]'), fieldset => Number(fieldset.dataset.q10Connection));
        document.getElementById('internetConnectionsEmpty').hidden = numbers.length > 0;
        addConnection.disabled = Math.max(0, ...numbers) >= maximumConnections;
        addConnection.focus({preventScroll: true});
    });
    connectionList?.addEventListener('wheel', event => {
        const input = event.target.closest('[data-connection-measurement]');
        if (input && document.activeElement === input) event.preventDefault();
    }, {passive: false});
    window.addEventListener('pageshow', () => {
        window.InventoryFeedback.endSaving(form);
        saving = false;
        const button = document.getElementById('saveUnifiedInventory');
        if (button && @json($isInstitutionLocked)) { button.disabled = false; button.textContent = 'Save Inventory'; }
    });
    activateTab(activeTabInput?.value || 'hardware');
});
</script>
@endsection
