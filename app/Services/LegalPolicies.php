<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LegalPolicies
{
    public function document(string $kind, ?string $version = null): array
    {
        abort_unless(in_array($kind, ['terms', 'privacy'], true), 404);
        $version ??= config("legal.current_{$kind}");
        $entry = config('legal.documents', [])[$kind][$version] ?? null;
        abort_unless(is_array($entry), 404);
        $raw = is_file($entry['path']) ? file_get_contents($entry['path']) : false;
        // Keep hashes stable across Windows CRLF and Unix LF checkouts.
        if (is_string($raw)) {
            $raw = str_replace("\r\n", "\n", $raw);
        }
        abort_unless(is_string($raw) && hash_equals($entry['sha256'], hash('sha256', $raw)),
            503, 'Policy content is unavailable or its integrity check failed.');
        $policy = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        abort_unless(($policy['version'] ?? null) === $version, 503, 'Policy version mismatch.');

        $document = ['kind' => $kind, 'hash' => $entry['sha256'], 'content' => $policy, 'snapshot' => $raw];
        $document['institutionally_approved'] = $this->institutionallyApproved($document);
        $release = $this->developerRelease();
        // Publication metadata never changes the exact bytes in the policy or its receipt.
        $document['acknowledgement_release'] = $release && $this->releaseMatches($release, $kind, $document) ? $release : null;

        return $document;
    }

    /** One publication decision for invitation setup and web/API acknowledgement. */
    public function invitationPolicyState(array $terms, array $privacy): array
    {
        $requirement = $terms['content']['requirement_version'] ?? null;
        $validRequirement = is_string($requirement) && trim($requirement) !== '' && strlen($requirement) <= 80;
        if ($validRequirement && $this->institutionallyApproved($terms) && $this->institutionallyApproved($privacy)) {
            return ['can_accept' => true, 'mode' => 'institutional', 'release' => null, 'message' => null];
        }
        $release = $this->developerRelease();
        $ready = $validRequirement && $release && $this->releaseMatches($release, 'terms', $terms) && $this->releaseMatches($release, 'privacy', $privacy);

        return [
            'can_accept' => (bool) $ready,
            'mode' => $ready ? 'developer-managed' : null,
            'release' => $ready ? $release : null,
            'message' => $ready ? null : 'Account setup is not available yet. The project administrator must publish the current Terms and Privacy Notice for application use. Your invitation has not been used; return after publication, or request a replacement if it expires.',
        ];
    }

    public function acceptanceAvailable(): bool
    {
        $terms = $this->document('terms');
        $privacy = $this->document('privacy');

        return $this->invitationPolicyState($terms, $privacy)['can_accept'];
    }

    private function institutionallyApproved(array $document): bool
    {
        $policy = $document['content'];

        return in_array($policy['status'] ?? '', ['approved', 'published'], true)
            && is_string($policy['approved_by'] ?? null) && trim($policy['approved_by']) !== ''
            && $this->effectiveDates($policy['approved_at'] ?? null, $policy['effective_at'] ?? null);
    }

    private function developerRelease(): ?array
    {
        $id = config('legal.acknowledgement_release');
        $release = is_string($id) ? config('legal.acknowledgement_releases', [])[$id] ?? null : null;
        if (! is_array($release) || ($release['status'] ?? '') !== 'published'
            || ($release['authority'] ?? '') !== 'project-developers'
            || ! is_string($release['published_by'] ?? null) || trim($release['published_by']) === ''
            || ! $this->effectiveDates($release['published_at'] ?? null, $release['effective_at'] ?? null)) {
            return null;
        }

        return ['id' => $id] + $release;
    }

    private function releaseMatches(array $release, string $kind, array $document): bool
    {
        $entry = $release['documents'][$kind] ?? null;
        $version = $document['content']['version'] ?? null;
        $hash = $document['hash'] ?? null;

        return is_array($entry) && is_string($entry['sha256'] ?? null) && is_string($hash)
            && preg_match('/\A[0-9a-f]{64}\z/D', $entry['sha256'])
            && ($entry['version'] ?? null) === $version
            && config("legal.current_{$kind}") === $version
            && in_array($document['content']['status'] ?? '', ['draft', 'approved', 'published'], true)
            && hash_equals($entry['sha256'], $hash);
    }

    private function effectiveDates(mixed $issued, mixed $effective): bool
    {
        if (! is_string($issued) || trim($issued) === '' || ! is_string($effective) || trim($effective) === '') {
            return false;
        }
        try {
            $issuedAt = Carbon::parse($issued);
            $effectiveAt = Carbon::parse($effective);

            return $issuedAt->lte($effectiveAt) && $issuedAt->lte(now()) && $effectiveAt->lte(now());
        } catch (\Throwable) {
            return false;
        }
    }

    public function enforcementActive(): bool
    {
        return (bool) config('legal.enforce_terms');
    }

    public function accepted(User $user): bool
    {
        abort_unless($this->acceptanceAvailable(), 503, 'The applicable Terms are unavailable.');
        abort_unless(Schema::hasTable('terms_acceptances'), 503, 'Terms storage is not installed.');
        $document = $this->document('terms');
        $receipt = DB::table('terms_acceptances')->where('user_id', $user->getKey())
            ->where('policy_version', $document['content']['version'])
            ->where('requirement_version', $document['content']['requirement_version'])->first();
        if (! $receipt) {
            return false;
        }
        $this->receiptDocument($receipt);

        return hash_equals($document['hash'], $receipt->content_hash);
    }

    public function status(?User $user): array
    {
        $terms = $this->document('terms');
        $publication = $this->invitationPolicyState($terms, $this->document('privacy'));
        $available = $this->acceptanceAvailable();
        $accepted = $user && $available ? $this->accepted($user) : false;

        return [
            'version' => $terms['content']['version'],
            'requirement_version' => $terms['content']['requirement_version'] ?? null,
            'policy_status' => $terms['content']['status'],
            'acknowledgement_mode' => $publication['mode'],
            'acknowledgement_release' => $publication['release']['id'] ?? null,
            'content_hash' => $terms['hash'],
            'acceptance_available' => $available,
            'accepted' => $accepted,
            'enforcement_active' => $this->enforcementActive(),
            'acceptance_required' => $this->enforcementActive() && ! $accepted,
            'terms_url' => route('legal.terms'),
            'privacy_url' => route('legal.privacy'),
            'review_url' => route('legal.review'),
        ];
    }

    public function loginStatus(User $user): array
    {
        try {
            return $this->status($user);
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() !== 503) {
                throw $exception;
            }

            // Authentication/logout remain available during a policy storage/integrity fault.
            // Protected routes still fail closed when enforcement is configured.
            return ['policy_status' => 'unavailable', 'acceptance_required' => null,
                'enforcement_active' => null, 'acceptance_available' => false,
                'status_url' => route('api.v1.legal.review')];
        }
    }

    public function accept(User $user, string $version, string $hash): object
    {
        abort_unless($this->acceptanceAvailable(), 409, 'The applicable Terms are unavailable.');
        abort_unless(Schema::hasTable('terms_acceptances'), 503, 'Terms storage is not installed.');
        $document = $this->document('terms');
        $policy = $document['content'];
        abort_unless($version === $policy['version'] && hash_equals($document['hash'], $hash),
            409, 'The Terms changed. Review the current version before accepting.');

        return DB::transaction(function () use ($user, $document, $policy): object {
            // Serialize same-account submissions; unique constraint also protects concurrent writes.
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $query = DB::table('terms_acceptances')->where('user_id', $user->getKey())
                ->where('policy_version', $policy['version']);
            $existing = $query->first();
            if ($existing) {
                $this->receiptDocument($existing);
                abort_unless(hash_equals($existing->content_hash, $document['hash']), 409,
                    'A published version was changed. Restore it and publish a new version.');

                return $existing;
            }
            $id = DB::table('terms_acceptances')->insertGetId([
                'user_id' => $user->getKey(),
                'policy_version' => $policy['version'],
                'requirement_version' => $policy['requirement_version'],
                'content_hash' => $document['hash'],
                'policy_snapshot' => $document['snapshot'],
                'accepted_at' => now(),
            ]);

            return DB::table('terms_acceptances')->where('id', $id)->first();
        });
    }

    public function receiptDocument(object $receipt): array
    {
        $message = 'This acceptance receipt could not be verified. Contact your system administrator.';
        abort_unless(is_string($receipt->policy_snapshot ?? null)
            && is_string($receipt->content_hash ?? null)
            && preg_match('/\A[0-9a-f]{64}\z/i', $receipt->content_hash), 503, $message);
        // Creation stores the policy's LF-normalized source bytes. Never reserialize JSON:
        // whitespace, key order and escaping are part of the accepted historical document.
        $snapshot = str_replace("\r\n", "\n", $receipt->policy_snapshot);
        abort_unless(hash_equals($receipt->content_hash, hash('sha256', $snapshot)), 503, $message);
        try {
            $policy = json_decode($snapshot, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            abort(503, $message);
        }
        abort_unless(is_array($policy)
            && ($policy['version'] ?? null) === $receipt->policy_version
            && ($policy['requirement_version'] ?? null) === $receipt->requirement_version, 503, $message);
        foreach (['title', 'version', 'status', 'change_summary'] as $field) {
            abort_unless(is_string($policy[$field] ?? null), 503, $message);
        }
        foreach (['effective_at', 'approved_by', 'approved_at'] as $field) {
            abort_unless(! isset($policy[$field]) || is_string($policy[$field]), 503, $message);
        }
        abort_unless(is_array($policy['sections'] ?? null) && is_array($policy['references'] ?? null), 503, $message);
        foreach ($policy['sections'] as $section) {
            abort_unless(is_array($section) && is_string($section['heading'] ?? null)
                && is_array($section['paragraphs'] ?? null), 503, $message);
            foreach ($section['paragraphs'] as $paragraph) {
                abort_unless(is_string($paragraph), 503, $message);
            }
        }
        foreach ($policy['references'] as $reference) {
            abort_unless(is_array($reference) && is_string($reference['label'] ?? null)
                && is_string($reference['url'] ?? null)
                && in_array(parse_url($reference['url'], PHP_URL_SCHEME), ['https', 'http'], true), 503, $message);
        }

        $document = ['kind' => 'terms', 'hash' => $receipt->content_hash, 'content' => $policy];
        $document['institutionally_approved'] = $this->institutionallyApproved($document);

        return $document;
    }

    public function receipts(User $user): array
    {
        if (! Schema::hasTable('terms_acceptances')) {
            return [];
        }

        return DB::table('terms_acceptances')->where('user_id', $user->getKey())
            ->orderByDesc('accepted_at')->get(['id', 'policy_version', 'content_hash', 'accepted_at'])->all();
    }
}
