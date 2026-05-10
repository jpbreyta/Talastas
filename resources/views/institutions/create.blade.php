@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Add Institution</h1>

            <form method="POST" action="{{ route('institutions.store') }}">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('name') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>



                    <div>
                        <label for="sector_type" class="block text-sm font-medium text-gray-700">Sector Type</label>
                        <select name="sector_type" id="sector_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="School" {{ old('sector_type', 'School') == 'School' ? 'selected' : '' }}>School</option>
                            <option value="Barangay Hall" {{ old('sector_type') == 'Barangay Hall' ? 'selected' : '' }}>Barangay Hall</option>
                            <option value="City Government Office" {{ old('sector_type') == 'City Government Office' ? 'selected' : '' }}>City Government Office</option>
                            <option value="Public Library" {{ old('sector_type') == 'Public Library' ? 'selected' : '' }}>Public Library</option>
                            <option value="Health Center" {{ old('sector_type') == 'Health Center' ? 'selected' : '' }}>Health Center</option>
                            <option value="Other Public" {{ old('sector_type') == 'Other Public' ? 'selected' : '' }}>Other Public</option>
                        </select>
                        @error('sector_type') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="barangay_name" class="block text-sm font-medium text-gray-700">Barangay Name</label>
                        <input type="text" name="barangay_name" id="barangay_name" value="{{ old('barangay_name') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('barangay_name') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                        <input type="number" step="0.000001" name="latitude" id="latitude" value="{{ old('latitude') }}" placeholder="e.g. 13.9411" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('latitude') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                        <input type="number" step="0.000001" name="longitude" id="longitude" value="{{ old('longitude') }}" placeholder="e.g. 121.1644" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('longitude') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="student_population" class="block text-sm font-medium text-gray-700">Student Population</label>
                        <input type="number" name="student_population" id="student_population" value="{{ old('student_population') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('student_population') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="internet_speed" class="block text-sm font-medium text-gray-700">Internet Speed (Mbps)</label>
                        <input type="number" step="0.1" name="internet_speed" id="internet_speed" value="{{ old('internet_speed') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('internet_speed') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">ICT Facilities</label>
                        <div class="mt-2 space-y-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="has_computer_lab" value="1" {{ old('has_computer_lab') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span class="ml-2">Has Computer Lab</span>
                            </label>
                            <label class="inline-flex items-center ml-4">
                                <input type="checkbox" name="has_smart_tv" value="1" {{ old('has_smart_tv') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span class="ml-2">Has Smart TV</span>
                            </label>
                            <label class="inline-flex items-center ml-4">
                                <input type="checkbox" name="has_projected_whiteboard" value="1" {{ old('has_projected_whiteboard') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span class="ml-2">Has Projected Whiteboard</span>
                            </label>
                            <label class="inline-flex items-center ml-4">
                                <input type="checkbox" name="has_public_address_system" value="1" {{ old('has_public_address_system') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span class="ml-2">Has Public Address System</span>
                            </label>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <div class="mt-2 space-y-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_pilot_site" value="1" {{ old('is_pilot_site') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span class="ml-2">Pilot Site</span>
                            </label>
                            <label class="inline-flex items-center ml-4">
                                <input type="checkbox" name="needs_maintenance" value="1" {{ old('needs_maintenance') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span class="ml-2">Needs Maintenance</span>
                            </label>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('notes') }}</textarea>
                        @error('notes') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Create Institution
                    </button>
                    <a href="{{ route('institutions.index') }}" class="ml-4 text-gray-600 hover:text-gray-900">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection