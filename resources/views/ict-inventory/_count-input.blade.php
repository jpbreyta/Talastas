@php($invalidCount = isset($errorKey) && $errors->has($errorKey))
<input type="text" inputmode="numeric" pattern="[0-9]+"
    @if (isset($inputName)) name="{{ $inputName }}" @endif
    @if (isset($inputId)) id="{{ $inputId }}" data-count-field @endif
    value="{{ $inputValue }}"
    maxlength="7" @if ($field !== 'functional_count' && !($countsOptional ?? false)) required @endif
    data-field="{{ $field }}" data-count="{{ $field }}"
    aria-label="{{ $field === 'defective_active_count' ? 'Defective within Active' : ucwords(str_replace('_', ' ', $field)) }}"
    @if ($invalidCount) aria-invalid="true" @if (isset($quantityErrorId)) aria-describedby="{{ $quantityErrorId }}" @endif @endif
    class="form-input w-full text-center">
