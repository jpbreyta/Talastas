<?php

namespace App\Observers;

use App\Models\InstitutionAnalytics;
use App\Models\User;
use App\Services\NotificationService;

class InstitutionAnalyticsObserver
{
    public function saved(InstitutionAnalytics $analytics): void
    {
        $completedNow =
            $analytics->processing_status === 'completed'
            && (
                $analytics->wasChanged('processing_status')
                || $analytics->wasChanged('analysis_date')
            );

        if (
            !$completedNow
            || $analytics->analysis_date === null
        ) {
            return;
        }

        $institution = $analytics->institution()->first();

        if (! $institution) {
            return;
        }

        $recipients = User::query()
            ->where('role', 'admin')
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        app(NotificationService::class)->analysisCompleted(
            recipients: $recipients,
            institutionName: $institution->name,
            institutionId: $institution->id,
            analysisId: $analytics->getKey(),
            actionUrl: "/institutions/{$institution->id}",
        );
    }
}
