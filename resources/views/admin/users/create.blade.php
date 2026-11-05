@extends('layouts.app')
@section('page-title','Invite User')
@section('content')<div class="mx-auto max-w-3xl p-6"><a href="{{ route('admin.invitations.index') }}" class="rounded-lg bg-red-900 px-4 py-3 font-semibold text-white">Manage user invitations</a><p class="mt-4 text-sm text-gray-600">Users establish their own passwords after accepting an invitation. Public registration is disabled.</p></div>@endsection
