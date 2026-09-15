<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\TalastasNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationService
{
    public function auditSubmitted(
        User $submitter,
        string $institutionName,
        int|string|null $institutionId = null,
        int|string|null $auditId = null,
        ?string $actionUrl = null,
    ): void {
        $submitter->notify(new TalastasNotification(
            category: 'audit.submitted',
            title: 'Audit submission received',
            message: "Your quarterly ICT audit for {$institutionName} was submitted and is awaiting validation.",
            actionUrl: $actionUrl,
            metadata: array_filter([
                'institution_id' => $institutionId,
                'audit_id' => $auditId,
                'status' => 'pending',
            ], fn ($value) => $value !== null),
        ));

        $this->administrators()
            ->reject(fn (User $user): bool => $user->is($submitter))
            ->each(fn (User $admin) => $admin->notify(
                new TalastasNotification(
                    category: 'audit.review_required',
                    title: 'ICT audit requires validation',
                    message: "A quarterly ICT audit for {$institutionName} is ready for review.",
                    actionUrl: $actionUrl,
                    metadata: array_filter([
                        'institution_id' => $institutionId,
                        'audit_id' => $auditId,
                        'status' => 'pending',
                    ], fn ($value) => $value !== null),
                )
            ));
    }

    public function auditStatusChanged(
        User $submitter,
        string $institutionName,
        string $status,
        int|string|null $institutionId = null,
        int|string|null $auditId = null,
        ?string $actionUrl = null,
    ): void {
        $normalizedStatus = Str::lower(trim($status));

        [$title, $message] = match ($normalizedStatus) {
            'validated', 'approved' => [
                'Audit validated',
                "The ICT audit for {$institutionName} has been validated.",
            ],
            'flagged', 'needs_revision', 'revision_required' => [
                'Audit requires attention',
                "The ICT audit for {$institutionName} was flagged for review or correction.",
            ],
            default => [
                'Audit status updated',
                "The ICT audit for {$institutionName} is now " . Str::headline($normalizedStatus) . '.',
            ],
        };

        $submitter->notify(new TalastasNotification(
            category: 'audit.status_changed',
            title: $title,
            message: $message,
            actionUrl: $actionUrl,
            metadata: array_filter([
                'institution_id' => $institutionId,
                'audit_id' => $auditId,
                'status' => $normalizedStatus,
            ], fn ($value) => $value !== null),
        ));
    }

    public function analysisCompleted(
        iterable $recipients,
        string $institutionName,
        int|string|null $institutionId = null,
        int|string|null $analysisId = null,
        ?string $actionUrl = null,
    ): void {
        collect($recipients)
            ->filter(fn ($user): bool => $user instanceof User)
            ->unique('id')
            ->each(fn (User $user) => $user->notify(
                new TalastasNotification(
                    category: 'analytics.completed',
                    title: 'Gap analysis completed',
                    message: "Updated ICT gap analytics are available for {$institutionName}.",
                    actionUrl: $actionUrl,
                    metadata: array_filter([
                        'institution_id' => $institutionId,
                        'analysis_id' => $analysisId,
                    ], fn ($value) => $value !== null),
                )
            ));
    }

    public function accountSecurityChanged(User $user, string $message): void
    {
        $user->notify(new TalastasNotification(
            category: 'account.security',
            title: 'Account security updated',
            message: $message,
            actionUrl: '/profile',
        ));
    }

    private function administrators(): Collection
    {
        return User::query()
            ->where('role', 'admin')
            ->get();
    }
}
