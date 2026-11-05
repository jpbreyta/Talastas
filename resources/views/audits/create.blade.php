@extends('layouts.app')

@section('page-title', 'Submit Quarterly ICT Audit')
@section('page-icon', 'fa-clipboard-check')

@section('content')
<div class="min-h-full bg-gray-50 pb-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div class="mb-4 flex items-center gap-3 border-b border-gray-200 bg-white px-4 py-4 rounded-t-xl">
            <a href="{{ route('institutions.show', $institution) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                <i class="fa-solid fa-arrow-left"></i>
                Back
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">Submit Quarterly ICT Audit</h1>
                <p class="mt-0.5 text-xs text-gray-500">{{ $institution->name }} &middot; {{ $institution->type }}</p>
            </div>
        </div>

        <div class="mx-auto max-w-2xl">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                    <h2 class="text-base font-semibold text-gray-900">{{ $institution->name }}</h2>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $institution->type }} &middot; {{ $institution->barangay?->name ?? 'No Barangay' }}</p>
                </div>

                <form method="POST" action="{{ route('audits.store', $institution) }}" class="p-6 space-y-5">
                    @csrf

                    <div>
                        <label for="audit_date" class="block text-sm font-medium text-gray-700">Audit Date</label>
                        <input type="date" name="audit_date" id="audit_date" value="{{ old('audit_date', now()->format('Y-m-d')) }}"
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                        @error('audit_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@endif
                    </div>

                    <div>
                        <label for="validation_status" class="block text-sm font-medium text-gray-700">Validation Status</label>
                        <select name="validation_status" id="validation_status"
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                            <option value="pending" @selected(old('validation_status') === 'pending')>Pending</option>
                            <option value="validated" @selected(old('validation_status') === 'validated')>Validated</option>
                            <option value="approved" @selected(old('validation_status') === 'approved')>Approved</option>
                            <option value="flagged" @selected(old('validation_status') === 'flagged')>Flagged</option>
                            <option value="revision_required" @selected(old('validation_status') === 'revision_required')>Revision Required</option>
                        </select>
                        @error('validation_status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@endif
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" id="notes" rows="4" maxlength="5000"
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"
                            placeholder="Record findings, observations, or recommendations...">{{ old('notes') }}</textarea>
                        @error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@endif
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('institutions.show', $institution) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                            <i class="fa-solid fa-paper-plane"></i>
                            Submit Audit
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
