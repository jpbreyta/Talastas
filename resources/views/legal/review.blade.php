@extends('legal.layout')
@section('title', 'Terms acknowledgement')
@section('content')
    <section class="legal-actions mb-5 rounded-xl border border-gray-200 bg-white p-5" aria-labelledby="review-heading">
        <h1 id="review-heading" class="text-2xl font-bold">Review and acknowledge the Terms</h1>
        @if ($status['acceptance_required'])
            <p class="mt-3">Review and acknowledge the applicable Terms before accessing protected system features. Your existing role and institution permissions still apply after acceptance.</p>
        @else
            <p class="mt-3">This page lets you review the Terms and your acknowledgement status. Your acceptance is saved for this account and Terms version.</p>
        @endif
        <nav aria-label="Review navigation" class="mt-4 flex flex-wrap gap-x-5 gap-y-3 text-sm text-red-900 underline">
            <a href="#acknowledgement">Jump to acknowledgement</a>
            <a href="{{ route('legal.terms', ['version' => $status['version']]) }}">Read the full Terms on a separate page</a>
            <a href="{{ route('legal.privacy') }}">Read the separate Privacy Notice</a>
        </nav>
    </section>
    @if ($errors->any())
        <div role="alert" class="mb-5 rounded-lg bg-red-50 p-4 text-red-800">
            <p class="font-semibold">Your acknowledgement was not recorded.</p>
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            @error('accepted')<a href="#accept-terms" class="mt-2 inline-block underline">Go to the acknowledgement checkbox</a>@enderror
        </div>
    @endif
    @include('legal._document', ['embedded' => true])
    <section id="acknowledgement" tabindex="-1" aria-labelledby="acknowledgement-heading" class="legal-actions mt-6 rounded-xl border border-gray-200 bg-white p-5">
        <h2 id="acknowledgement-heading" class="text-lg font-semibold">Your acknowledgement</h2>
        @if (! $status['acceptance_available'])
            <p class="mt-3">The applicable Terms are unavailable. Access remains restricted until they can be reviewed.</p>
        @elseif ($status['accepted'])
            <div class="legal-status legal-status-success mt-4"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><p>You have acknowledged the applicable Terms. No additional acceptance is needed for this version.</p></div>
        @else
            <div class="legal-status legal-status-pending mt-4"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><p>Acceptance required. Review the Terms above and the separate <a href="{{ route('legal.privacy') }}" class="text-red-900 underline">Privacy Notice</a> before continuing.</p></div>
            <form method="POST" action="{{ route('legal.accept') }}" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="version" value="{{ $status['version'] }}">
                <input type="hidden" name="content_hash" value="{{ $status['content_hash'] }}">
                <label class="flex items-start gap-3" for="accept-terms">
                    <input id="accept-terms" type="checkbox" name="accepted" value="1" required aria-describedby="accept-terms-help{{ $errors->has('accepted') ? ' accept-terms-error' : '' }}"
                        aria-invalid="{{ $errors->has('accepted') ? 'true' : 'false' }}" @if ($errors->has('accepted')) autofocus @endif class="mt-1 h-4 w-4 accent-red-900">
                    <span>I acknowledge and agree to the Terms and Conditions, version {{ $status['version'] }}. This is not blanket consent to personal-data processing.</span>
                </label>
                <p id="accept-terms-help" class="text-sm text-gray-600">Select the checkbox yourself to acknowledge this Terms version. Reading the documents does not record acceptance.</p>
                @error('accepted')<p id="accept-terms-error" class="text-sm font-medium text-red-800">{{ $message }}</p>@enderror
                <div class="legal-action-row flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="legal-button legal-button-primary"><i class="fa-solid fa-check" aria-hidden="true"></i>I Agree</button>
                    <button type="submit" form="decline-terms" class="legal-button legal-button-secondary"><i class="fa-solid fa-xmark" aria-hidden="true"></i>Disagree &amp; Sign Out</button>
                </div>
            </form>
            <form id="decline-terms" method="POST" action="{{ route('legal.decline') }}">@csrf</form>
        @endif
        @if (! $status['accepted'])
            <p class="mt-4 text-sm leading-relaxed text-gray-600">Accepting the current Terms is required to access the system. Choosing Disagree signs you out without recording acceptance.</p>
        @endif
        <p class="mt-5 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500"><i class="fa-solid fa-shield-halved mr-2" aria-hidden="true"></i>Mandatory enforcement: {{ $status['enforcement_active'] ? 'Active for protected web and API features' : 'Disabled' }}.</p>
        <div class="legal-action-row mt-5 flex flex-wrap items-center gap-3">
            @if (! $status['acceptance_required'])<a href="{{ route('home') }}" class="legal-button legal-button-primary"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i>Continue to Dashboard</a>@endif
            @if ($status['accepted'])
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="legal-button legal-button-secondary"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>Sign Out</button></form>
            @endif
        </div>
        <p class="mt-4 text-sm text-gray-600">For help, use your institution's established administrative contact. An official policy-support contact still requires confirmation.</p>
    </section>
    <section aria-labelledby="receipt-heading" class="mt-6 rounded-xl border border-gray-200 bg-white p-5">
        <h2 id="receipt-heading" class="text-lg font-semibold">Your acceptance history</h2>
        <ul class="mt-3 space-y-3 text-sm">
            @forelse ($receipts as $receipt)
                <li><a href="{{ route('legal.receipt', $receipt->id) }}" class="text-red-900 underline">Version {{ $receipt->policy_version }}</a> &middot; {{ $receipt->accepted_at }} (server time)</li>
            @empty
                <li>No Terms acceptance has been recorded for your account.</li>
            @endforelse
        </ul>
    </section>
@endsection
