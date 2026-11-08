<?php

namespace App\Observers;

use App\Models\IctInventory;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\InstitutionAnalytics;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class IctInventoryObserver implements ShouldHandleEventsAfterCommit
{
    private static bool $batching = false;
    private static array $pending = [];

    public static function invalidateForInstitution(int $institutionId): void
    {
        (new self)->invalidateAnalytics($institutionId);
    }

    public static function batch(callable $callback): mixed
    {
        if (self::$batching) {
            return $callback();
        }
        self::$batching = true;
        try {
            $result = $callback();
            foreach (array_keys(self::$pending) as $id) {
                (new self)->invalidateNow($id);
            }
            return $result;
        } finally {
            self::$batching = false;
            self::$pending = [];
        }
    }

    private const ANALYTICS_RELEVANT_FIELDS = [
        'institution_id',
        'component_id',
        'component_type',
        'asset_name',
        'total_count',
        'active_count',
        'retired_count',
        'functional_count',
        'defective_count',
        'defective_active_count',
        'functional_count_recorded',
    ];

    public function created(IctInventory $inventory): void
    {
        $this->invalidateAnalytics(
            (int) $inventory->institution_id
        );
    }

    public function updated(IctInventory $inventory): void
    {
        if (
            !$inventory->wasChanged(
                self::ANALYTICS_RELEVANT_FIELDS
            )
        ) {
            return;
        }

        $currentInstitutionId =
            (int) $inventory->institution_id;

        $originalInstitutionId =
            (int) ($inventory->getPrevious()['institution_id'] ?? $inventory->getOriginal('institution_id'));

        $this->invalidateAnalytics(
            $currentInstitutionId
        );

        if (
            $originalInstitutionId > 0
            && $originalInstitutionId
                !== $currentInstitutionId
        ) {
            $this->invalidateAnalytics(
                $originalInstitutionId
            );
        }
    }

    public function deleted(IctInventory $inventory): void
    {
        $this->invalidateAnalytics(
            (int) $inventory->institution_id
        );
    }

    private function invalidateAnalytics(
        int $institutionId
    ): void {
        if (self::$batching) {
            self::$pending[$institutionId] = true;
            return;
        }
        $this->invalidateNow($institutionId);
    }

    private function invalidateNow(int $institutionId): void
    {
        DB::transaction(function () use ($institutionId): void {
            Institution::whereKey($institutionId)->lockForUpdate()->first();
            $analytics = InstitutionAnalytics::query()
                ->where('institution_id', $institutionId)
                ->lockForUpdate()
                ->first();

            if (! $analytics) {
                return;
            }

            $details = $analytics->recommendations_detail ?? [];
            if ($analytics->analysis_date !== null && $analytics->processing_status === 'completed') {
                $details['last_completed_assessment'] = [
                    'completed_at' => $analytics->analysis_date->toIso8601String(),
                    'gap_score' => $analytics->gap_score,
                    'infrastructure_deficit_score' => $analytics->infrastructure_deficit_score,
                    'connectivity_divide_score' => $analytics->connectivity_divide_score,
                    'technological_obsolescence_score' => $analytics->technological_obsolescence_score,
                    'narrative' => $analytics->infrastructure_narrative,
                    'inventory_totals' => data_get($details, 'assessment.inventory_inputs.totals'),
                ];
            }
            $details['source_data_changed_at'] = now()->toIso8601String();

            $analytics->forceFill([
                'gap_score' => null,
                'infrastructure_deficit_score' => null,
                'connectivity_divide_score' => null,
                'technological_obsolescence_score' => null,
                'risk_level' => 'insufficient_data',
                'infrastructure_narrative' => null,
                'policy_recommendation' => null,
                'recommendations_detail' => $details,
                'analysis_date' => null,
                'ai_priorities' => null,
                'processing_token' => (string) Str::uuid(),
                'processing_status' => 'idle',
                'processing_stage' => 'outdated',
                'processing_started_at' => null,
                'processing_completed_at' => null,
                'processing_error' => null,
                'updated_at' => now(),
            ])->saveQuietly();
        });
    }
}
