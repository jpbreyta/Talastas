<label for="{{ $fieldId }}" class="mb-1.5 block text-xs font-semibold text-gray-600">
    Sector Type <span class="text-red-500">*</span>
</label>
<select name="type" id="{{ $fieldId }}" required
    class="form-input w-full @error('type') border-red-400 @enderror"
    @error('type') aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @enderror>
    <option value="" @selected(old('type', $sectorValue) === '') disabled>Select institution type</option>
    @foreach (['School', 'Barangay Hall'] as $sector)
        <option value="{{ $sector }}" @selected(old('type', $sectorValue) === $sector)>{{ $sector === 'School' ? 'Public School' : $sector }}</option>
    @endforeach
</select>
@error('type')
    <p id="{{ $fieldId }}-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
@enderror

@if ($fieldId === 'modalType')
    <p id="institutionTypeHelp" class="mt-2 text-xs text-gray-500" aria-live="polite">Select the institution type manually, or select a map result to check its type. Only public schools and barangay halls are allowed.</p>
@endif
