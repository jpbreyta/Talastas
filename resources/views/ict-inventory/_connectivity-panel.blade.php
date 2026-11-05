@php($connectivityInstitution = $institution)
<div class="p-4" data-unified-connectivity>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-sm font-semibold text-gray-900">Internet Connectivity</h2>
        @if (auth()->user()?->canManageInventory())
            <a href="{{ route('ict-inventory.create', ['institution_id' => $institution->id, 'tab' => 'connectivity']) }}" class="text-sm font-semibold text-red-700 hover:underline">Manage connections</a>
        @endif
    </div>
    @include('ict-inventory._connectivity-infrastructure', ['editable' => false])
    @include('ict-inventory._q10-summary')
</div>
