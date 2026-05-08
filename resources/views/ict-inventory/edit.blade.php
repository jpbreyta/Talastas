@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit ICT Inventory Item</h1>

            <form method="POST" action="{{ route('ict-inventory.update', $ictInventory) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="institution_id" class="block text-sm font-medium text-gray-700">Institution</label>
                        <select name="institution_id" id="institution_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select Institution</option>
                            @foreach($institutions as $institution)
                                <option value="{{ $institution->id }}" {{ old('institution_id', $ictInventory->institution_id) == $institution->id ? 'selected' : '' }}>
                                    {{ $institution->name }} ({{ $institution->barangay_name }})
                                </option>
                            @endforeach
                        </select>
                        @error('institution_id') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                        <select name="category" id="category" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select Category</option>
                            <option value="computer" {{ old('category', $ictInventory->category) == 'computer' ? 'selected' : '' }}>Computer</option>
                            <option value="smart_tv" {{ old('category', $ictInventory->category) == 'smart_tv' ? 'selected' : '' }}>Smart TV</option>
                            <option value="projector" {{ old('category', $ictInventory->category) == 'projector' ? 'selected' : '' }}>Projector</option>
                            <option value="pa_system" {{ old('category', $ictInventory->category) == 'pa_system' ? 'selected' : '' }}>PA System</option>
                            <option value="other" {{ old('category', $ictInventory->category) == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('category') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="condition" class="block text-sm font-medium text-gray-700">Condition</label>
                        <select name="condition" id="condition" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select Condition</option>
                            <option value="new" {{ old('condition', $ictInventory->condition) == 'new' ? 'selected' : '' }}>New</option>
                            <option value="good" {{ old('condition', $ictInventory->condition) == 'good' ? 'selected' : '' }}>Good</option>
                            <option value="fair" {{ old('condition', $ictInventory->condition) == 'fair' ? 'selected' : '' }}>Fair</option>
                            <option value="poor" {{ old('condition', $ictInventory->condition) == 'poor' ? 'selected' : '' }}>Poor</option>
                            <option value="needs_repair" {{ old('condition', $ictInventory->condition) == 'needs_repair' ? 'selected' : '' }}>Needs Repair</option>
                        </select>
                        @error('condition') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="acquisition_year" class="block text-sm font-medium text-gray-700">Acquisition Year</label>
                        <input type="number" name="acquisition_year" id="acquisition_year" value="{{ old('acquisition_year', $ictInventory->acquisition_year) }}" required min="1900" max="{{ date('Y') + 1 }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('acquisition_year') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select Status</option>
                            <option value="active" {{ old('status', $ictInventory->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="retired" {{ old('status', $ictInventory->status) == 'retired' ? 'selected' : '' }}>Retired</option>
                        </select>
                        @error('status') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Update Inventory Item
                    </button>
                    <a href="{{ route('ict-inventory.index') }}" class="ml-4 text-gray-600 hover:text-gray-900">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection