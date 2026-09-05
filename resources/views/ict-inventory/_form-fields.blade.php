@php
    $existingInventory = $ictInventory ?? null;
    $existingSpecifications = old(
        'specifications',
        $existingInventory?->specifications ?? []
    );

    if (!is_array($existingSpecifications)) {
        $existingSpecifications = [];
    }

    $selectedCategory = old(
        'category',
        $existingInventory?->category
    );

    $categories = $inventoryConfig['categories'] ?? [];
    $conditions = $inventoryConfig['conditions'] ?? [];
    $statuses = $inventoryConfig['statuses'] ?? [];

    $maintenanceChecked = old(
        'needs_maintenance',
        $existingInventory?->needs_maintenance ?? false
    );
@endphp

<div class="space-y-6">
    <section>
        <div class="mb-4 flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-100 text-red-700">
                <i class="fa-solid fa-box"></i>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Asset Information</h2>
                <p class="mt-0.5 text-xs text-gray-500">
                    One record may represent one device or a group of identical or similar devices.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="category" class="mb-1.5 block text-xs font-semibold text-gray-600">
                    Equipment Category <span class="text-red-500">*</span>
                </label>
                <select
                    name="category"
                    id="category"
                    required
                    class="form-input w-full"
                    data-category-select
                >
                    <option value="">Select a category</option>
                    @foreach ($categories as $categoryKey => $category)
                        <option value="{{ $categoryKey }}" @selected($selectedCategory === $categoryKey)>
                            {{ $category['label'] ?? str($categoryKey)->headline() }}
                        </option>
                    @endforeach
                </select>
                @error('category')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="asset_name" class="mb-1.5 block text-xs font-semibold text-gray-600">
                    Asset / Group Name
                </label>
                <input
                    type="text"
                    name="asset_name"
                    id="asset_name"
                    value="{{ old('asset_name', $existingInventory?->asset_name) }}"
                    maxlength="150"
                    placeholder="e.g. Computer Lab Desktop Set A"
                    class="form-input w-full"
                >
                @error('asset_name')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="brand" class="mb-1.5 block text-xs font-semibold text-gray-600">Brand</label>
                <input
                    type="text"
                    name="brand"
                    id="brand"
                    value="{{ old('brand', $existingInventory?->brand) }}"
                    maxlength="100"
                    placeholder="e.g. Dell"
                    class="form-input w-full"
                >
                @error('brand')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="model" class="mb-1.5 block text-xs font-semibold text-gray-600">Model</label>
                <input
                    type="text"
                    name="model"
                    id="model"
                    value="{{ old('model', $existingInventory?->model) }}"
                    maxlength="150"
                    placeholder="e.g. OptiPlex 3080"
                    class="form-input w-full"
                >
                @error('model')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="quantity" class="mb-1.5 block text-xs font-semibold text-gray-600">
                    Quantity <span class="text-red-500">*</span>
                </label>
                <input
                    type="number"
                    name="quantity"
                    id="quantity"
                    value="{{ old('quantity', $existingInventory?->quantity ?? 1) }}"
                    required
                    min="1"
                    max="1000000"
                    class="form-input w-full"
                >
                <p class="mt-1.5 text-xs text-gray-500">Use 1 for an individually tracked device.</p>
                @error('quantity')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="location" class="mb-1.5 block text-xs font-semibold text-gray-600">
                    Location Within Institution
                </label>
                <input
                    type="text"
                    name="location"
                    id="location"
                    value="{{ old('location', $existingInventory?->location) }}"
                    maxlength="150"
                    placeholder="e.g. Computer Laboratory 1"
                    class="form-input w-full"
                >
                @error('location')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <section class="border-t border-gray-200 pt-6">
        <div class="mb-4 flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-100 text-red-700">
                <i class="fa-solid fa-microchip"></i>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Technical Specifications</h2>
                <p class="mt-0.5 text-xs text-gray-500">
                    Specification fields change according to the selected equipment category.
                </p>
            </div>
        </div>

        <div
            data-spec-placeholder
            class="{{ $selectedCategory ? 'hidden' : '' }} rounded-xl border border-dashed border-gray-300 bg-gray-50 px-5 py-8 text-center"
        >
            <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-white text-gray-400 shadow-sm">
                <i class="fa-solid fa-list-check"></i>
            </div>
            <p class="mt-3 text-sm font-medium text-gray-700">Select an equipment category first</p>
            <p class="mt-1 text-xs text-gray-500">
                The applicable technical specification fields will appear here.
            </p>
        </div>

        @foreach ($categories as $categoryKey => $category)
            @php
                $specifications = $category['specifications'] ?? [];
                $sectionVisible = $selectedCategory === $categoryKey;
            @endphp

            <div
                data-spec-section="{{ $categoryKey }}"
                class="{{ $sectionVisible ? '' : 'hidden' }}"
            >
                @if (empty($specifications))
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-5 py-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 text-gray-400">
                                <i class="fa-solid fa-circle-info"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">
                                    No predefined specification fields for this category.
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Use the brand, model, asset name, and notes fields to describe the equipment.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        @foreach ($specifications as $specKey => $definition)
                            @php
                                $fieldName = "specifications[{$specKey}]";
                                $errorKey = "specifications.{$specKey}";
                                $fieldId = "specifications_{$categoryKey}_{$specKey}";
                                $type = $definition['type'] ?? 'text';
                                $value = old(
                                    $errorKey,
                                    data_get($existingSpecifications, $specKey)
                                );
                                $disabled = !$sectionVisible;
                            @endphp

                            <div>
                                @if ($type === 'boolean')
                                    <input
                                        type="hidden"
                                        name="{{ $fieldName }}"
                                        value="0"
                                        data-spec-input
                                        @disabled($disabled)
                                    >

                                    <label
                                        for="{{ $fieldId }}"
                                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 transition hover:bg-gray-100"
                                    >
                                        <input
                                            type="checkbox"
                                            name="{{ $fieldName }}"
                                            id="{{ $fieldId }}"
                                            value="1"
                                            data-spec-input
                                            @checked(filter_var($value, FILTER_VALIDATE_BOOLEAN))
                                            @disabled($disabled)
                                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500"
                                        >
                                        <span>
                                            <span class="block text-sm font-medium text-gray-800">
                                                {{ $definition['label'] ?? str($specKey)->headline() }}
                                            </span>
                                            <span class="mt-0.5 block text-xs text-gray-500">
                                                Check if this specification applies.
                                            </span>
                                        </span>
                                    </label>
                                @elseif ($type === 'select')
                                    <label for="{{ $fieldId }}" class="mb-1.5 block text-xs font-semibold text-gray-600">
                                        {{ $definition['label'] ?? str($specKey)->headline() }}
                                    </label>
                                    <select
                                        name="{{ $fieldName }}"
                                        id="{{ $fieldId }}"
                                        class="form-input w-full"
                                        data-spec-input
                                        @disabled($disabled)
                                    >
                                        <option value="">Not recorded</option>
                                        @foreach (($definition['options'] ?? []) as $optionValue => $optionLabel)
                                            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>
                                                {{ $optionLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <label for="{{ $fieldId }}" class="mb-1.5 block text-xs font-semibold text-gray-600">
                                        {{ $definition['label'] ?? str($specKey)->headline() }}
                                    </label>
                                    <input
                                        type="{{ $type === 'number' ? 'number' : 'text' }}"
                                        name="{{ $fieldName }}"
                                        id="{{ $fieldId }}"
                                        value="{{ $value }}"
                                        class="form-input w-full"
                                        data-spec-input
                                        @disabled($disabled)
                                        @if ($type === 'number') min="0" step="any" @endif
                                    >
                                @endif

                                @error($errorKey)
                                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </section>

    <section class="border-t border-gray-200 pt-6">
        <div class="mb-4 flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-100 text-red-700">
                <i class="fa-solid fa-clipboard-check"></i>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Condition and Operational Status</h2>
                <p class="mt-0.5 text-xs text-gray-500">
                    Record the present physical and operational state of the equipment.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="condition" class="mb-1.5 block text-xs font-semibold text-gray-600">
                    Physical Condition <span class="text-red-500">*</span>
                </label>
                <select
                    name="condition"
                    id="condition"
                    required
                    class="form-input w-full"
                    data-condition-select
                >
                    <option value="">Select a condition</option>
                    @foreach ($conditions as $conditionKey => $conditionLabel)
                        <option
                            value="{{ $conditionKey }}"
                            @selected(old('condition', $existingInventory?->condition) === $conditionKey)
                        >
                            {{ $conditionLabel }}
                        </option>
                    @endforeach
                </select>
                @error('condition')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="mb-1.5 block text-xs font-semibold text-gray-600">
                    Current Status <span class="text-red-500">*</span>
                </label>
                <select
                    name="status"
                    id="status"
                    required
                    class="form-input w-full"
                    data-status-select
                >
                    <option value="">Select a status</option>
                    @foreach ($statuses as $statusKey => $statusLabel)
                        <option
                            value="{{ $statusKey }}"
                            @selected(old('status', $existingInventory?->status ?? 'active') === $statusKey)
                        >
                            {{ $statusLabel }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="acquisition_year" class="mb-1.5 block text-xs font-semibold text-gray-600">
                    Year Acquired
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fa-solid fa-calendar"></i>
                    </div>
                    <input
                        type="number"
                        name="acquisition_year"
                        id="acquisition_year"
                        value="{{ old('acquisition_year', $existingInventory?->acquisition_year) }}"
                        min="1900"
                        max="{{ date('Y') + 1 }}"
                        placeholder="{{ date('Y') }}"
                        class="form-input w-full pl-9"
                    >
                </div>
                @error('acquisition_year')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <span class="mb-1.5 block text-xs font-semibold text-gray-600">Maintenance</span>

                <input type="hidden" name="needs_maintenance" value="0">

                <label
                    for="needs_maintenance"
                    class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 transition hover:bg-gray-100"
                >
                    <input
                        type="checkbox"
                        name="needs_maintenance"
                        id="needs_maintenance"
                        value="1"
                        @checked(filter_var($maintenanceChecked, FILTER_VALIDATE_BOOLEAN))
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500"
                        data-maintenance-checkbox
                    >
                    <span>
                        <span class="block text-sm font-medium text-gray-800">Needs maintenance</span>
                        <span class="mt-0.5 block text-xs text-gray-500">
                            Automatically enabled when the item is under repair or marked as needing repair.
                        </span>
                    </span>
                </label>

                @error('needs_maintenance')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <section class="border-t border-gray-200 pt-6">
        <div class="mb-3">
            <label for="notes" class="block text-xs font-semibold text-gray-600">Notes</label>
            <p class="mt-0.5 text-xs text-gray-500">
                Add survey observations or information that does not fit the structured fields.
            </p>
        </div>
        <textarea
            name="notes"
            id="notes"
            rows="4"
            maxlength="5000"
            class="form-input w-full resize-y"
            placeholder="Optional notes about this asset or equipment group..."
        >{{ old('notes', $existingInventory?->notes) }}</textarea>
        @error('notes')
            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </section>
</div>

<script>
    (() => {
        'use strict';

        const categorySelect = document.querySelector('[data-category-select]');
        const placeholder = document.querySelector('[data-spec-placeholder]');
        const sections = Array.from(document.querySelectorAll('[data-spec-section]'));
        const conditionSelect = document.querySelector('[data-condition-select]');
        const statusSelect = document.querySelector('[data-status-select]');
        const maintenanceCheckbox = document.querySelector('[data-maintenance-checkbox]');

        function refreshSpecificationSections() {
            const selectedCategory = categorySelect?.value || '';

            placeholder?.classList.toggle('hidden', selectedCategory !== '');

            sections.forEach((section) => {
                const active = section.dataset.specSection === selectedCategory;

                section.classList.toggle('hidden', !active);

                section
                    .querySelectorAll('[data-spec-input]')
                    .forEach((input) => {
                        input.disabled = !active;
                    });
            });
        }

        function syncMaintenanceState() {
            if (!maintenanceCheckbox) {
                return;
            }

            const requiresMaintenance =
                conditionSelect?.value === 'needs_repair'
                || statusSelect?.value === 'under_repair';

            if (requiresMaintenance) {
                maintenanceCheckbox.checked = true;
            }
        }

        categorySelect?.addEventListener('change', refreshSpecificationSections);
        conditionSelect?.addEventListener('change', syncMaintenanceState);
        statusSelect?.addEventListener('change', syncMaintenanceState);

        refreshSpecificationSections();
        syncMaintenanceState();
    })();
</script>
