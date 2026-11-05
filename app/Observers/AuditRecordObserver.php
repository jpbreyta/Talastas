<?php

namespace App\Observers;

use App\Models\AuditRecord;
use App\Models\InstitutionAnalytics;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class AuditRecordObserver implements ShouldHandleEventsAfterCommit
{
    public function created(AuditRecord $audit): void
    {
        $this->invalidateAnalytics((int) $audit->institution_id);
    }

    public function updated(AuditRecord $audit): void
    {
        if (! $audit->wasChanged(['validation_status', 'notes', 'audit_date'])) {
            return;
        }

        $this->invalidateAnalytics((int) $audit->institution_id);
    }

    private function invalidateAnalytics(int $institutionId): void
    {
        InstitutionAnalytics::query()
            ->where('institution_id', $institutionId)
            ->update([
                'gap_score' => null,
                'infrastructure_deficit_score' => null,
                'connectivity_divide_score' => null,
                'technological_obsolescence_score' => null,
                'risk_level' => 'insufficient_data',
                'infrastructure_narrative' => null,
                'policy_recommendation' => null,
                'recommendations_detail' => null,
                'analysis_date' => null,
                'updated_at' => now(),
            ]);
    }
}