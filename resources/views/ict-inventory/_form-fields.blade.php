@php
    $existingInventory = $ictInventory ?? null;

    $counts = [
        'total_count' => old(
            'total_count',
            $existingInventory?->total_count ?? 0
        ),
        'active_count' => old(
            'active_count',
            $existingInventory?->active_count
        ),
        'retired_count' => old(
            'retired_count',
            $existingInventory?->retired_count
        ),
        'defective_active_count' => old('defective_active_count', $existingInventory?->defective_active_count),
        'functional_count' => old('functional_count', $existingInventory?->functional_count),
        'defective_count' => old(
            'defective_count',
            $existingInventory?->defective_count
        ),
    ];
@endphp

<div class="space-y-5">
    <input type="hidden" name="component_type" value="{{ $componentType }}">

    <div class="flex items-center gap-3">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-700">
            <i class="fa-solid {{ $sectionMeta['icon'] }}"></i>
        </div>
        <h2 class="text-base font-semibold text-gray-900">{{ $sectionMeta['title'] }}</h2>
    </div>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-200 bg-gray-50 px-5 py-3">
            <h3 class="text-sm font-semibold text-gray-900">Component and Inventory Counts</h3>
        </div>
        <div class="space-y-5 p-5">
            <div>
                <label for="asset_name" class="mb-1.5 block text-xs font-semibold text-gray-600">
                    Component Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="asset_name" id="asset_name" data-field="asset_name" maxlength="150" required
                    value="{{ old('asset_name', $existingInventory?->asset_name) }}"
                    @error('asset_name') aria-invalid="true"  @enderror
                    class="form-input w-full" placeholder="Enter custom item name or N/A">

            </div>

            <div class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
                @foreach (['total_count' => 'Total', 'active_count' => 'Active', 'retired_count' => 'Retired', 'functional_count' => 'Functional', 'defective_count' => 'Defective', 'defective_active_count' => 'Defective within Active'] as $field => $label)
                    <div class="min-w-0">
                        <label for="{{ $field }}" class="mb-1.5 block text-xs font-semibold text-gray-600">
                            {{ $label }} @if ($field === 'total_count')<span class="text-red-500">*</span>@endif
                        </label>
                        @include('ict-inventory._count-input', [
                            'field' => $field,
                            'inputId' => $field,
                            'inputName' => $field,
                            'inputValue' => $counts[$field],
                            'errorKey' => $field,
                            'countsOptional' => $field !== 'total_count',
                            'quantityErrorId' => 'inventory-quantity-error-edit',
                        ])
                    </div>
                @endforeach
            </div>
            <div data-row-feedback role="status" aria-live="polite">
                @include('ict-inventory._quantity-errors', [
                    'errorPrefix' => '', 'countValues' => $counts,
                    'quantityErrorId' => 'inventory-quantity-error-edit',
                ])
            </div>
        </div>
    </section>

    <p class="text-xs text-gray-500">Blank means unknown; 0 means confirmed zero. Active means deployed/in-service, including defective units. Defective within Active is a subset of Active and recorded general Defective. Functional remains separate.</p>
    <p @if ($existingInventory?->availability_status === 'Availability Recorded') hidden @endif id="availabilityStatus" class="text-xs text-amber-700">{{ $existingInventory?->availability_status === 'Availability Recorded' ? '' : $existingInventory?->availability_status }}</p>

</div>

<script src="{{ asset('js/inventory-counts.js') }}?v={{ filemtime(public_path('js/inventory-counts.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ids = [
        'total_count',
        'active_count',
        'retired_count',
        'functional_count',
        'defective_count',
        'defective_active_count',
    ];

    const fields = Object.fromEntries(
        ids.map((id) => [
            id,
            document.getElementById(id),
        ])
    );

    const row = document.querySelector('[data-row-feedback]')?.closest('section');
    const message = row?.querySelector('[data-row-feedback]');

    function validateCounts(checkRequired = false) {
        const values = Object.fromEntries(ids.map(id => [id, fields[id]?.value ?? '']));
        const errors = window.InventoryCounts.errors(values, true);
        if (checkRequired === true) {
            if (!document.getElementById('asset_name')?.value.trim()) errors.push({field: 'asset_name', message: 'Component Name: enter an item name.'});
            if (!document.getElementById('institution_id')?.value) errors.push({field: 'institution_id', message: 'Institution: select an institution.'});
        }
        const status = document.getElementById('availabilityStatus');
        const label = window.InventoryCounts.status(values);
        status.hidden = label === 'Availability Recorded';
        status.textContent = status.hidden ? '' : label;
        window.InventoryFeedback.showRowErrors(row, values, errors.filter(error => error.field !== 'institution_id'));
        if (checkRequired === true && errors.some(error => error.field === 'institution_id')) {
            window.InventoryFeedback.showFieldError(document.getElementById('institution_id'), ['Select an institution.']);
        }
        return errors.length === 0;
    }

    Object.values(fields).forEach((field) => {
        field?.addEventListener(
            'input',
            validateCounts
        );
    });

    document.getElementById('asset_name')?.addEventListener('input', () => validateCounts(true));

    let saving = false;
    message.closest('form')
        ?.addEventListener('submit', (event) => {
            if (saving) { event.preventDefault(); return; }
            if (!validateCounts(true)) {
                event.preventDefault();
                const invalid = event.target.querySelector('[aria-invalid="true"]');
                (invalid || message).scrollIntoView({behavior: 'smooth', block: 'center'});
                invalid?.focus({preventScroll: true});
            } else {
                saving = true;
                window.InventoryFeedback.beginSaving(event.target);
                const button = event.target.querySelector('button[type="submit"]');
                if (button) { button.disabled = true; button.textContent = 'Saving…'; }
            }
        });

    const submitButton = message.closest('form')?.querySelector('button[type="submit"]');
    const submitLabel = submitButton?.innerHTML;
    window.addEventListener('pageshow', () => {
        window.InventoryFeedback.endSaving(message.closest('form'));
        saving = false;
        if (submitButton) { submitButton.disabled = false; submitButton.innerHTML = submitLabel; }
    });
    validateCounts();
});
</script>
