@extends('legal.layout')
@section('title', $document['content']['title'])
@section('content')
    @include('legal._document')
    @if (request()->routeIs('legal.terms'))
        <section class="legal-actions mt-6 rounded-xl border border-gray-200 bg-white p-5 sm:p-6" aria-labelledby="consent-heading">
            <h2 id="consent-heading" class="text-lg font-bold">Your acknowledgement</h2>
            <p id="consent-help" class="mt-2 mb-4 max-w-prose leading-relaxed">Review the Terms and the separate Privacy Notice before continuing. The next step shows your acknowledgement status and asks for confirmation. Accepting the current Terms is required to access the system.</p>
            <a href="{{ route('legal.review') }}#acknowledgement" aria-describedby="consent-help" class="legal-button legal-button-primary"><i class="fa-regular fa-file-lines" aria-hidden="true"></i>Review Current Terms and Acceptance</a>
        </section>
    @endif
@endsection
