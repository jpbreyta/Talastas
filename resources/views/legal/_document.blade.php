@php
    $policy = $document['content'];
    $acknowledgementRelease = $document['acknowledgement_release'] ?? null;
    $developerPublication = is_array($acknowledgementRelease)
        && ($acknowledgementRelease['authority'] ?? null) === 'project-developers';
    $institutionallyPublished = $document['institutionally_approved'] ?? false;
    $titleTag = ($embedded ?? false) ? 'h2' : 'h1';
    $sectionTag = ($embedded ?? false) ? 'h3' : 'h2';
@endphp
<div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12" data-policy-document>
<aside class="legal-navigation lg:col-span-3 lg:sticky lg:top-8">
    <nav aria-label="Document contents" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm lg:max-h-[calc(100dvh-4rem)] lg:overflow-y-auto">
        <p class="mb-3 text-xs font-bold uppercase tracking-widest text-gray-600">On this page</p>
        <ul class="space-y-2 text-sm">
            @foreach ($policy['sections'] as $section)
                <li><a data-toc-link class="block rounded-lg px-3 py-3 no-underline transition-all hover:bg-red-50 hover:pl-4 focus:ring-2 focus:ring-offset-2 motion-reduce:transition-none" href="#section-{{ $loop->iteration }}">{{ $section['heading'] }}</a></li>
            @endforeach
<li><a data-toc-link href="#policy-references" class="block rounded-lg px-3 py-3 hover:bg-red-50 focus:ring-2 focus:ring-offset-2">References for review</a></li>
        </ul>
    </nav>
</aside>
<article class="legal-copy lg:col-span-9 rounded-xl border border-gray-200 bg-white px-8 py-8 shadow-sm lg:px-12" aria-labelledby="policy-title">
    <div>
    <{{ $titleTag }} id="policy-title" class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $policy['title'] }}</{{ $titleTag }}>
    <div class="mt-4 mb-6 flex flex-wrap items-center gap-3 text-sm">
        <span class="inline-flex rounded-full border border-gray-300 bg-gray-100 px-3 py-1 font-semibold text-gray-900">{{ $developerPublication ? 'Source version' : 'Version' }} {{ $policy['version'] }}</span>
        <span class="text-gray-600">{{ $developerPublication ? 'Application use from:' : 'Effective date:' }} {{ $developerPublication ? $acknowledgementRelease['effective_at'] : ($policy['effective_at'] ?? 'Pending approval') }}</span>
    </div>
    @if ($developerPublication)
        <div role="note" class="mt-5 rounded-lg border-l-4 border-green-600 bg-green-50 p-4 text-sm leading-relaxed text-gray-900">
            <span class="inline-flex rounded-full border border-green-200 bg-white px-3 py-1 font-semibold text-green-900">Published for application use</span>
            <p class="mt-3">The project developers have enabled this document for startup account setup. The source document remains a draft; institutional approval and the outstanding governance and privacy disclosures are still pending. Application acknowledgement does not constitute institutional approval.</p>
            <p class="mt-2 text-xs text-gray-700">Published by {{ $acknowledgementRelease['published_by'] }} &middot; Application publication date: {{ $acknowledgementRelease['published_at'] }}</p>
        </div>
    @elseif ($institutionallyPublished)
        <p class="text-sm text-gray-600">Approved by {{ $policy['approved_by'] }} &middot; Approval date: {{ $policy['approved_at'] }}</p>
    @else
        <div role="note" class="legal-draft-alert mt-5 rounded-lg border-l-4 border-yellow-500 bg-yellow-50 p-4 text-sm text-gray-900 leading-relaxed">
            <svg class="mr-2 inline h-5 w-5 shrink-0 text-yellow-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m12 3 10 18H2L12 3Z"/><path d="M12 9v4m0 3v1"/></svg>
            <strong>Draft pending review.</strong> Not approved or effective. Account acknowledgement does not constitute institutional approval.
            Institutional details marked for confirmation must be resolved before publication.
        </div>
    @endif
    <p class="text-sm text-gray-600">{{ $policy['change_summary'] }}</p>
    @foreach ($policy['sections'] as $section)
        <section aria-labelledby="section-{{ $loop->iteration }}">
            <{{ $sectionTag }} id="section-{{ $loop->iteration }}" tabindex="-1">{{ $section['heading'] }}</{{ $sectionTag }}>
            @foreach ($section['paragraphs'] as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </section>
    @endforeach
    <section aria-labelledby="policy-references">
        <{{ $sectionTag }} id="policy-references" tabindex="-1">References for review</{{ $sectionTag }}>
        <ul class="mt-3 space-y-2 text-sm">
            @foreach ($policy['references'] as $reference)
                <li><a href="{{ $reference['url'] }}" rel="noreferrer">{{ $reference['label'] }}</a></li>
            @endforeach
        </ul>
    </section>
    <details class="mt-6 text-xs text-gray-600">
        <summary class="cursor-pointer">Document integrity</summary>
        <p class="break-all">SHA-256: {{ $document['hash'] }}</p>
    </details>
</div>
</article>
</div>
