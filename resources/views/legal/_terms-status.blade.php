@php
    $formatPolicyDate = static function ($value) {
        if (! is_string($value) || trim($value) === '') return 'Pending approval';
        try {
            $date = \Carbon\Carbon::parse($value, 'UTC');
            return preg_match('/\A\d{4}-\d{2}-\d{2}\z/D', $value)
                ? $date->format('M j, Y') : $date->timezone('Asia/Manila')->format('M j, Y g:i A').' PHT';
        } catch (\Throwable) {
            return $value;
        }
    };
@endphp
<section aria-labelledby="document-status-heading" class="terms-document-status mt-5 rounded-lg border {{ $institutionallyPublished ? 'border-gray-200 bg-gray-50 text-gray-800' : 'border-amber-200 bg-amber-50 text-amber-950' }}">
    <{{ $sectionTag }} id="document-status-heading" class="sr-only">Document status</{{ $sectionTag }}>
    <details class="terms-status-disclosure">
        <summary aria-controls="document-status-details" class="terms-status-summary cursor-pointer rounded-lg">
            <span class="min-w-0 flex-1">
                <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="terms-status-label inline-flex rounded-full border border-current bg-white/70 px-2 py-0.5 font-semibold">
                        @if ($institutionallyPublished)
                            Institutionally approved
                        @elseif (($policy['status'] ?? null) === 'draft')
                            Draft &mdash; Pending Institutional Approval
                        @else
                            {{ Str::headline($policy['status'] ?? 'Status unavailable') }} &mdash; Institutional approval not confirmed
                        @endif
                    </span>
                    <span class="text-xs">{{ $developerPublication ? 'Source version' : 'Version' }} {{ $policy['version'] }}</span>
                </span>
                <span class="terms-status-brief mt-1 block text-sm leading-relaxed">
                    @if ($developerPublication && ! $institutionallyPublished)
                        Published for application use. Institutional approval is still pending.
                    @elseif ($institutionallyPublished)
                        Approved by {{ $policy['approved_by'] }}.
                    @else
                        Institutional approval is not confirmed.
                    @endif
                </span>
            </span>
            <span class="terms-status-toggle inline-flex shrink-0 items-center gap-2 text-xs font-semibold">
                <span class="terms-details-collapsed">View details</span><span class="terms-details-expanded">Hide details</span>
                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            </span>
        </summary>
        <div id="document-status-details" class="border-t border-current/15 px-4 pb-4 pt-1">
    <dl class="terms-status-metadata mt-3 grid gap-x-5 gap-y-2 text-sm sm:grid-cols-2">
        <div><dt class="font-medium">{{ $developerPublication ? 'Source version' : 'Version' }}</dt><dd>{{ $policy['version'] }}</dd></div>
        <div><dt class="font-medium">{{ $developerPublication ? 'Application use from' : 'Effective date' }}</dt><dd>{{ $formatPolicyDate($developerPublication ? $acknowledgementRelease['effective_at'] : ($policy['effective_at'] ?? null)) }}</dd></div>
        @if ($developerPublication)
            <div><dt class="font-medium">Published by</dt><dd>{{ $acknowledgementRelease['published_by'] }}</dd></div>
            <div><dt class="font-medium">Application publication date</dt><dd>{{ $formatPolicyDate($acknowledgementRelease['published_at']) }}</dd></div>
        @endif
        @if ($institutionallyPublished)
            <div><dt class="font-medium">Approved by</dt><dd>{{ $policy['approved_by'] }}</dd></div>
            <div><dt class="font-medium">Approval date</dt><dd>{{ $formatPolicyDate($policy['approved_at']) }}</dd></div>
        @endif
    </dl>
    @if ($developerPublication && ! $institutionallyPublished && ($policy['status'] ?? null) === 'draft')
        <p class="terms-status-explanation">The project developers have enabled this document for startup account setup. The source document remains a draft; institutional approval and the outstanding governance and privacy disclosures are still pending. Application acknowledgement does not constitute institutional approval.</p>
    @elseif ($developerPublication && ! $institutionallyPublished)
        <p class="terms-status-explanation">Application acknowledgement does not constitute institutional approval.</p>
    @elseif (! $institutionallyPublished)
        <p class="terms-status-explanation">@if (($policy['status'] ?? null) === 'draft')<strong>Draft pending review.</strong>@endif Not approved or effective. Account acknowledgement does not constitute institutional approval. Institutional details marked for confirmation must be resolved before publication.</p>
    @endif
        </div>
    </details>
</section>
