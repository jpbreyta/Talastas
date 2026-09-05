@extends('layouts.app')

@section('dashboard-layout')
@endsection

@section('content')
    @php
        $isInstitutionLocked = $selectedInstitution !== null;
        $cancelUrl = $isInstitutionLocked
            ? route('institutions.show', $selectedInstitution)
            : route('ict-inventory.index');
    @endphp

    <div class="flex h-full min-h-0 flex-col bg-gray-100">
        <div class="shrink-0 bg-red-700 px-6 py-4">
            <div class="flex items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <a
                        href="{{ $cancelUrl }}"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-600 text-red-100 transition hover:bg-red-500 hover:text-white"
                        title="Go back"
                    >
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-600 text-red-100">
                        <i class="fa-solid fa-box-open"></i>
                    </div>

                    <div class="min-w-0">
                        <h1 class="text-xl font-semibold leading-tight tracking-tight text-white">
                            Add ICT Inventory Record
                        </h1>
                        <p class="mt-0.5 text-xs text-red-200">
                            Record a device or grouped ICT equipment with its technical specifications.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
            <div class="mx-auto max-w-5xl">
                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
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

                <form
                    method="POST"
                    action="{{ route('ict-inventory.store') }}"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    @csrf

                    @if ($isInstitutionLocked)
                        <input type="hidden" name="institution_id" value="{{ $selectedInstitution->id }}">
                        <input type="hidden" name="return_to" value="institution">
                    @endif

                    <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-100 text-red-700">
                                <i class="fa-solid fa-building"></i>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900">Assigned Institution</h2>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    The inventory record will belong to this institution.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6 p-5">
                        @if ($isInstitutionLocked)
                            <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-red-600 shadow-sm">
                                        <i class="fa-solid fa-building-columns"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900">{{ $selectedInstitution->name }}</p>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ $selectedInstitution->type }}
                                            <span class="mx-1 text-gray-300">•</span>
                                            {{ $selectedInstitution->barangay?->name ?? 'Barangay not recorded' }}
                                        </p>
                                        <p class="mt-2 text-xs text-red-700">
                                            The institution is locked because this form was opened from its details page.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div>
                                <label for="institution_id" class="mb-1.5 block text-xs font-semibold text-gray-600">
                                    Institution <span class="text-red-500">*</span>
                                </label>
                                <select
                                    name="institution_id"
                                    id="institution_id"
                                    required
                                    class="form-input w-full"
                                >
                                    <option value="">Select an institution</option>
                                    @foreach ($institutions as $institution)
                                        <option
                                            value="{{ $institution->id }}"
                                            @selected((string) old('institution_id') === (string) $institution->id)
                                        >
                                            {{ $institution->name }}
                                            @if ($institution->barangay)
                                                — {{ $institution->barangay->name }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('institution_id')
                                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        @include('ict-inventory._form-fields', [
                            'ictInventory' => null,
                            'inventoryConfig' => $inventoryConfig,
                        ])
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-end">
                        <a
                            href="{{ $cancelUrl }}"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                        >
                            <i class="fa-solid fa-xmark"></i>
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-red-700"
                        >
                            <i class="fa-solid fa-plus"></i>
                            Add Inventory Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .form-input {
            display: block;
            border: 1px solid rgb(209 213 219);
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: rgb(17 24 39);
            background-color: white;
            transition: border-color 150ms ease, box-shadow 150ms ease;
        }

        .form-input:focus {
            outline: none;
            border-color: rgb(220 38 38);
            box-shadow: 0 0 0 3px rgb(254 202 202 / 0.8);
        }

        .form-input:disabled {
            background-color: rgb(243 244 246);
            color: rgb(107 114 128);
            cursor: not-allowed;
        }
    </style>
@endsection
