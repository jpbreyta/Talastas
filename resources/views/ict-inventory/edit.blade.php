@extends('layouts.app')

@section('page-title', 'Edit Inventory')
@section('page-icon', 'fa-boxes-stacked')

@section('dashboard-layout')
@endsection

@section('content')
    @include('ict-inventory._form-styles')
    @php
        $returnToInstitution =
            request('return_to') === 'institution'
            || old('return_to') === 'institution';

        $cancelUrl = $returnToInstitution
            ? route('institutions.show', $ictInventory->institution_id)
            : route('ict-inventory.index');
    @endphp

    <div class="inventory-form flex h-full min-h-0 flex-col bg-gray-100">
        <x-page-header title="Edit Inventory"  :back-url="$cancelUrl" :padded="true" content-class="mx-auto w-full max-w-7xl" class="border-b border-gray-200 bg-white">

        </x-page-header>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
            <div class="mx-auto max-w-7xl">
                @include('ict-inventory._validation-errors')

                <form novalidate
                    method="POST"
                    action="{{ route('ict-inventory.update', $ictInventory) }}"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    @csrf
                    @method('PUT')

                    @if ($returnToInstitution)
                        <input type="hidden" name="return_to" value="institution">
                    @endif

                    <div class="space-y-3 border-b border-gray-200 bg-gray-50 px-5 py-4">
                        <div>
                            <label for="institution_id" class="mb-1.5 block text-xs font-semibold text-gray-600">
                                Institution <span class="text-red-500">*</span>
                            </label>
                            <select
                                name="institution_id"
                                id="institution_id"
                                @error('institution_id') aria-invalid="true" @enderror
                                required
                                class="form-input w-full"
                            >
                                <option value="">Select an institution</option>
                                @foreach ($institutions as $institution)
                                    <option
                                        value="{{ $institution->id }}"
                                        @selected(
                                            (string) old('institution_id', $ictInventory->institution_id)
                                            === (string) $institution->id
                                        )
                                    >
                                        {{ $institution->name }}
                                        @if ($institution->barangay)
                                            — {{ $institution->barangay->name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                        </div>

                    </div>

                    <div class="p-5">
                        @include('ict-inventory._form-fields', [
                            'ictInventory' => $ictInventory,
                        ])
                    </div>

                    <div class="sticky bottom-0 z-10 flex flex-col-reverse gap-3 border-t border-gray-200 bg-white/95 px-5 py-4 backdrop-blur sm:flex-row sm:items-center sm:justify-end">
                        <a
                            href="{{ $cancelUrl }}"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                        >
                            <i class="fa-solid fa-xmark"></i>
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700"
                        >
                            <i class="fa-solid fa-floppy-disk"></i>
                            Update Inventory
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('ict-inventory._save-feedback')
@endsection
