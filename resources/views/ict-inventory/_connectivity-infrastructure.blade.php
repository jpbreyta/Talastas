@php
    $editable = $editable ?? false;
    $connectivityInventory = app(\App\Services\ConnectivityInventory::class);
    $sourceItems = $editable ? ($inventoryItems ?? []) : ($connectivityInstitution?->ictInventory->where('component_type', 'connectivity') ?? collect());
    $connectivityRows = $connectivityInventory->rows($sourceItems);
    $connectivityGroups = $connectivityRows->groupBy('subsection', true);
    $countFields = \App\Services\ConnectivityInventory::FIELDS;
@endphp
<section class="mb-5 overflow-hidden rounded-xl border border-gray-200 bg-white" data-connectivity-infrastructure aria-label="Internet Connectivity Inventory">
    <h3 class="sr-only">Internet Connectivity Inventory</h3>
    <div class="overflow-x-auto">
        <table class="inventory-quantity-table w-full min-w-[800px] text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50">
                    <th scope="col" class="min-w-[220px] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Component</th>
                    @foreach (['Total', 'Active', 'Retired', 'Functional', 'Defective', 'Defective within Active'] as $label)
                        <th scope="col" class="w-20 px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">{{ $label }}</th>
                    @endforeach
                    @if ($editable)
                        <th scope="col" class="w-16 px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white" @if ($editable) data-inventory-rows="connectivity" @endif>
                @foreach ($connectivityGroups as $subsection => $groupRows)
                    @php($subtotals = $connectivityInventory->subtotals($groupRows))
                    <tr class="bg-gray-50 font-semibold text-gray-700" data-subtotal-group="{{ $subsection }}">
                        <th scope="row" class="px-3 py-3 text-left">{{ $subsection }} (subtotal)</th>
                        @foreach ($countFields as $field)
                            <td class="px-2 py-3 text-center tabular-nums" data-subtotal-field="{{ $field }}">{{ $subtotals[$field] === null ? '—' : number_format($subtotals[$field]) }}</td>
                        @endforeach
                        @if ($editable)<td></td>@endif
                    </tr>
                    @foreach ($groupRows as $index => $item)
                        <tr @if ($editable) data-inventory-row data-category="connectivity" data-subsection="{{ $subsection }}" @endif @if (!empty($item['asset_id'])) data-connectivity-asset="{{ $item['asset_id'] }}" @endif>
                            <td class="px-3 py-3 align-top">
                                <p class="font-semibold text-gray-900">{{ $item['component_label'] }}</p>
                                @if ($editable)
                                    @foreach (['asset_id', 'component_id', 'asset_name', 'subsection'] as $field)
                                        <input type="hidden" name="sections[connectivity][items][{{ $index }}][{{ $field }}]" data-field="{{ $field }}" value="{{ $item[$field] ?? '' }}">
                                    @endforeach
                                    @php($availability = \App\Services\InventoryCounts::status($item))
                                    <p data-availability-status @if ($availability === 'Availability Recorded') hidden @endif class="mt-1 text-xs text-amber-700">{{ $availability === 'Availability Recorded' ? '' : $availability }}</p>
                                @endif
                                <p class="mt-1 text-xs text-gray-500">{{ $subsection }}</p>
                            </td>
                            @if ($editable)
                                <td colspan="6" class="py-3 align-top">
                                    @php($quantityErrorId = 'inventory-quantity-error-connectivity-'.$index)
                                    <div class="grid grid-cols-6">
                                        @foreach ($countFields as $field)
                                            <div class="min-w-0 px-2">
                                                @include('ict-inventory._count-input', [
                                                    'field' => $field,
                                                    'inputName' => "sections[connectivity][items][{$index}][{$field}]",
                                                    'inputValue' => $item[$field] ?? '', 'countsOptional' => true,
                                                    'errorKey' => "sections.connectivity.items.{$index}.{$field}",
                                                    'quantityErrorId' => $quantityErrorId,
                                                ])
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="px-2" data-row-feedback role="status" aria-live="polite">
                                        @include('ict-inventory._quantity-errors', [
                                            'errorPrefix' => "sections.connectivity.items.{$index}.",
                                            'countValues' => $item,
                                            'quantityErrorId' => $quantityErrorId,
                                        ])
                                    </div>
                                </td>
                                <td class="px-2 py-3 text-center align-top">
                                    <button type="button" data-remove-row
                                        @if (!empty($item['asset_id']) && !auth()->user()?->isAdmin()) hidden @endif
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600"
                                        title="Remove item" aria-label="Remove {{ $item['component_label'] }}">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </td>
                            @else
                                @foreach ($countFields as $field)
                                    <td class="px-2 py-3 text-center tabular-nums">{{ ($item[$field] ?? null) === null ? 'Not recorded' : number_format($item[$field]) }}</td>
                                @endforeach
                            @endif
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</section>
