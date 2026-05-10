@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">ICT Inventory Item</h1>
                <div>
                    <a href="{{ route('ict-inventory.edit', $ictInventory) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mr-2">Edit</a>
                    <a href="{{ route('ict-inventory.index') }}" class="text-gray-600 hover:text-gray-900">Back to List</a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Details</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Institution</dt>
                            <dd class="text-sm text-gray-900">{{ $ictInventory->institution->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Category</dt>
                            <dd class="text-sm text-gray-900">{{ $ictInventory->category }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Condition</dt>
                            <dd class="text-sm text-gray-900">{{ $ictInventory->condition }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Acquisition Year</dt>
                            <dd class="text-sm text-gray-900">{{ $ictInventory->acquisition_year }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="text-sm text-gray-900">{{ $ictInventory->status }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection