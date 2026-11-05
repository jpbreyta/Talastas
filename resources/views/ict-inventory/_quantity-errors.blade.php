@php
    $quantityMessages = [];
    $exceededFields = [];
    foreach (['total_count', 'active_count', 'retired_count', 'functional_count', 'defective_count', 'defective_active_count'] as $quantityField) {
        $quantityKey = ($errorPrefix ?? '') . $quantityField;
        foreach ($errors->get($quantityKey) as $quantityMessage) {
            if (preg_match('/^([A-Z][a-z]+) units cannot exceed Total units\.$/', $quantityMessage, $quantityMatch)) {
                $exceededFields[] = $quantityMatch[1];
            } elseif ($quantityMessage === 'Recorded Active + Retired cannot exceed Total. Other units may be unclassified.') {
                $quantityMessages[] = 'Check quantities: Active and Retired together cannot exceed Total (' . ($countValues['total_count'] ?? '—') . ').';
            } else {
                $quantityMessages[] = $quantityMessage;
            }
        }
    }
    $exceededFields = array_values(array_unique($exceededFields));
    if ($exceededFields) {
        $lastQuantityLabel = array_pop($exceededFields);
        $quantityLabels = $exceededFields ? implode(', ', $exceededFields) . ' and ' . $lastQuantityLabel : $lastQuantityLabel;
        $quantityMessages[] = 'Check quantities: ' . $quantityLabels . ' ' . ($exceededFields ? 'counts' : 'count') . ' cannot exceed Total (' . ($countValues['total_count'] ?? '—') . ').';
    }
@endphp
@if ($quantityMessages)
    <div id="{{ $quantityErrorId }}" class="inventory-quantity-errors mt-2 space-y-1 text-xs leading-5 text-red-800" role="status">
        @foreach (array_unique($quantityMessages) as $quantityMessage)
            <p>{{ $quantityMessage }}</p>
        @endforeach
    </div>
@endif
