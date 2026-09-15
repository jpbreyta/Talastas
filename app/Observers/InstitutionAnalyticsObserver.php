<?php

namespace App\Observers;

use App\Models\InstitutionAnalytics;
use App\Models\User;
use App\Services\NotificationService;

class InstitutionAnalyticsObserver
{
    public function saved(InstitutionAnalytics $analytics): void
    {
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
            actionUrl: '/analytics',
        );
    }
}
