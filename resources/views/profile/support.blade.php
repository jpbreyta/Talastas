@extends('layouts.app')

@section('page-title', 'Support')
@section('page-icon', 'fa-circle-question')

@section('content')
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-semibold text-slate-900">Support</h1>
        <p class="mt-3 text-slate-600">
            For account access or technical assistance, contact your system administrator.
            Include a description of the issue and any error message you received.
            Do not share your password.
        </p>
        <a href="{{ route('profile.account') }}" class="mt-4 inline-block font-medium text-[#7A1F2B]">
            Back to your profile
        </a>
    </div>
@endsection
