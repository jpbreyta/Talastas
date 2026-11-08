@php
    $restoreEdit = (string) old('_editing_user') === (string) $user->id;
    $editingRole = $restoreEdit ? old('role', $user->role) : $user->role;
    $editingAssignments = $restoreEdit ? (array) old('institution_ids', []) : $user->institutions->pluck('id')->all();
@endphp
<form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5 p-6" data-role-form @if ($restoreEdit && $errors->any()) data-reopen-user-editor @endif>
    @csrf
    @method('PUT')
    <input type="hidden" name="_editing_user" value="{{ $user->id }}">
    @if ($restoreEdit && $errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"><ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <p class="text-xs text-gray-500">{{ $user->invitation_accepted_at ? 'Invitation accepted '.$user->invitation_accepted_at->format('M j, Y') : 'No invitation acceptance recorded' }}</p>
    <label class="block text-sm font-medium" for="edit-name-{{ $user->id }}">Full name
        <input id="edit-name-{{ $user->id }}" name="name" value="{{ $restoreEdit ? old('name', $user->name) : $user->name }}" required maxlength="255" autocomplete="name" class="users-control mt-2" autofocus>
    </label>
    <label class="block text-sm font-medium" for="edit-email-{{ $user->id }}">Email
        <input id="edit-email-{{ $user->id }}" name="email" type="email" value="{{ $restoreEdit ? old('email', $user->email) : $user->email }}" required maxlength="255" autocomplete="email" class="users-control mt-2">
    </label>
    <label class="block text-sm font-medium" for="edit-role-{{ $user->id }}">Role
        <select id="edit-role-{{ $user->id }}" name="role" required data-user-role class="users-control mt-2" aria-controls="edit-assignments-{{ $user->id }}">
            <option value="admin" @selected($editingRole === 'admin')>System Administrator</option>
            <option value="editor" @selected($editingRole === 'editor')>ICT Coordinator / Editor</option>
            <option value="viewer" @selected($editingRole === 'viewer')>LGU City Planner / Viewer</option>
        </select>
    </label>
    <fieldset id="edit-assignments-{{ $user->id }}" data-editor-assignments @if ($editingRole !== 'editor') hidden disabled @endif>
        <legend class="text-sm font-medium">Barangay and institution assignments</legend>
        <p class="mt-2 text-xs font-medium text-gray-700">An Editor must have at least one active school or barangay hall assigned.</p>
        <div class="mt-2 max-h-48 space-y-3 overflow-y-auto rounded-lg border border-gray-200 p-4" aria-label="Barangay assignments">
            @foreach ($institutions->pluck('barangay')->filter()->unique('id')->sortBy('name') as $barangay)
                <label class="flex items-start gap-2 text-sm text-gray-700"><input type="checkbox" name="barangay_ids[]" value="{{ $barangay->id }}" @checked(in_array($barangay->id, ($restoreEdit ? (array) old('barangay_ids', []) : []))) class="users-checkbox" data-barangay-filter><span>{{ $barangay->name }}</span></label>
            @endforeach
        </div>
        <p class="mt-3 text-sm font-medium">Individual institutions</p>
        <div class="mt-2 max-h-60 space-y-3 overflow-y-auto rounded-lg border border-gray-200 p-4" data-institution-options>
            @forelse ($institutions as $institution)
                <label class="flex items-start gap-2 text-sm text-gray-700" data-institution-row data-barangay-id="{{ $institution->barangay_id }}"><input type="checkbox" name="institution_ids[]" value="{{ $institution->id }}" @checked(in_array($institution->id, $editingAssignments)) class="users-checkbox"><span>{{ $institution->name }} @if ($institution->barangay) <span class="text-gray-500">({{ $institution->barangay->name }})</span> @endif</span></label>
            @empty
                <p class="text-sm text-gray-500">No institutions available.</p>
            @endforelse
        </div>
    </fieldset>
    <div class="space-y-4 border-t border-gray-200 pt-5">
        <label class="block text-sm font-medium" for="edit-password-{{ $user->id }}">Your current password
            <input id="edit-password-{{ $user->id }}" name="current_password" type="password" required autocomplete="current-password" class="users-control mt-2">
        </label>
        <div class="flex justify-end gap-3"><button type="button" data-close-user-editor class="users-button users-button-secondary">Cancel</button><button type="submit" class="users-button">Save</button></div>
    </div>
</form>
@if (!auth()->user()->is($user))
    <details class="users-accordion border-t border-gray-200 p-6"><summary class="font-semibold">Security actions</summary>@include('admin.users._security-actions')</details>
@endif
