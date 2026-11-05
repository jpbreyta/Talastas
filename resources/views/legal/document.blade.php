@extends('legal.layout')
@section('title', $document['content']['title'])
@section('content')
    @include('legal._document')
    @if (request()->routeIs('legal.terms'))
        <section class="legal-actions mt-8 rounded-xl border border-gray-200 bg-white p-6 sm:p-8" aria-labelledby="consent-heading">
            <h2 id="consent-heading" class="text-xl font-bold">Your acknowledgement</h2>
            <p id="consent-help" class="mt-3 mb-6 max-w-prose leading-relaxed">Review the Terms and the separate Privacy Notice before continuing. The next step shows your acknowledgement status and asks for confirmation. Accepting the current Terms is required to access the system.</p>
            <a href="{{ route('legal.review') }}#acknowledgement" aria-describedby="consent-help" class="legal-button legal-button-primary"><i class="fa-regular fa-file-lines" aria-hidden="true"></i>Review current Terms and acceptance</a>
        </section>
    @endif
    <p class="mt-5 text-sm text-gray-600">This page can be printed or saved as PDF using your browser's print command.</p>
@endsection
