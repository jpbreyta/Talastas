<?php

namespace App\Jobs;

use App\Models\Institution;
use App\Models\InstitutionAnalytics;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProcessInstitutionAnalytics implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $institutionId
    ) {
    }

    public function handle(): void
    {
        $institution = Institution::query()
            ->with('ictInventory')
            ->find($this->institutionId);

        if (! $institution) {
            Log::warning('Institution analytics skipped because the institution was not found.', [
                'institution_id' => $this->institutionId,
            ]);

            return;
        }

        $inventory = $institution->ictInventory;

        $payload = $this->buildPayload(
            $institution,
            $inventory
        );

        $apiUrl = rtrim(
            config(
                'services.python_analytics.url',
                'http://127.0.0.1:8002'
            ),
            '/'
        );

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->connectTimeout(10)
                ->timeout(45)
                ->retry(
                    2,
                    1000,
                    function (Throwable $exception): bool {
                        return $exception instanceof ConnectionException;
                    }
                )
                ->post($apiUrl.'/analyze', $payload);
        } catch (Throwable $exception) {
            Log::error('Unable to connect to the Python analytics service.', [
                'institution_id' => $institution->id,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        if (! $response->successful()) {
            Log::error('Python analytics service returned an unsuccessful response.', [
                'institution_id' => $institution->id,
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);

            throw new RuntimeException(
                'Python analytics service returned HTTP '.$response->status().'.'
            );
        }

        $data = $response->json();

        $this->saveAnalytics(
            $institution,
            $data
        );
    }

    private function buildPayload(
        Institution $institution,
        Collection $inventory
    ): array {
        $totalInventoryCount = $inventory->count();

        $activeComputersCount = $inventory
            ->filter(fn ($asset) => $this->isComputer($asset->category))
            ->filter(fn ($asset) => $this->isActive($asset->status))
            ->count();

        if ($totalInventoryCount === 0) {
            $activeComputersCount = $institution->number_of_computers;
        }

        $smartTvsCount = $inventory
            ->filter(fn ($asset) => $this->isSmartTv($asset->category))
            ->filter(fn ($asset) => $this->isActive($asset->status))
            ->count();

        $projectorsCount = $inventory
            ->filter(fn ($asset) => $this->isProjector($asset->category))
            ->filter(fn ($asset) => $this->isActive($asset->status))
            ->count();

        $paSystemsCount = $inventory
            ->filter(fn ($asset) => $this->isPaSystem($asset->category))
            ->filter(fn ($asset) => $this->isActive($asset->status))
            ->count();

        $oldEquipmentCount = $inventory
            ->filter(fn ($asset) => $this->isOldEquipment($asset->acquisition_year))
            ->count();

        $poorConditionCount = $inventory
            ->filter(fn ($asset) => $this->isPoorCondition($asset->condition))
            ->count();

        $needsRepairCount = $inventory
            ->filter(fn ($asset) => $this->needsRepair($asset->status))
            ->count();

        $retiredEquipmentCount = $inventory
            ->filter(fn ($asset) => $this->isRetired($asset->status))
            ->count();

        return [
            'id' => $institution->id,
            'name' => $institution->name,
            'institution_type' => $institution->type,

            'internet_speed' => $this->nullableFloat(
                $institution->internet_speed
            ),

            'student_population' => $institution->student_population,
            'staff_population' => $institution->staff_population,

            'needs_maintenance' => (bool) $institution->needs_maintenance,

            'has_computer_lab' => $this->nullableBoolean(
                $institution->has_computer_lab
            ),

            'has_smart_tv' => $this->nullableBoolean(
                $institution->has_smart_tv
            ),

            'has_projected_whiteboard' => $this->nullableBoolean(
                $institution->has_projected_whiteboard
            ),

            'has_public_address_system' => $this->nullableBoolean(
                $institution->has_public_address_system
            ),

            'active_computers_count' => $activeComputersCount,
            'smart_tvs_count' => $smartTvsCount,
            'projectors_count' => $projectorsCount,
            'pa_systems_count' => $paSystemsCount,

            'total_inventory_count' => $totalInventoryCount,
            'old_equipment_count' => $oldEquipmentCount,
            'poor_condition_count' => $poorConditionCount,
            'needs_repair_count' => $needsRepairCount,
            'retired_equipment_count' => $retiredEquipmentCount,

            'average_equipment_age' => $this->averageEquipmentAge(
                $inventory
            ),
        ];
    }

    private function saveAnalytics(
        Institution $institution,
        array $data
    ): void {
        InstitutionAnalytics::query()->updateOrCreate(
            [
                'institution_id' => $institution->id,
            ],
            [
                'gap_score' => $data['gap_score'] ?? null,
                'risk_level' => $data['risk_level'] ?? 'insufficient_data',

                'data_completeness_score' => $data[
                    'data_completeness_score'
                ] ?? 0,

                'infrastructure_deficit_score' => $data[
                    'infrastructure_deficit_score'
                ] ?? null,

                'connectivity_divide_score' => $data[
                    'connectivity_divide_score'
                ] ?? null,

                'technological_obsolescence_score' => $data[
                    'technological_obsolescence_score'
                ] ?? null,

                'infrastructure_narrative' => $data[
                    'infrastructure_narrative'
                ] ?? null,

                'policy_recommendation' => $data[
                    'policy_recommendation'
                ] ?? null,

                'analysis_date' => now(),
            ]
        );

        $institution->updateQuietly([
            'data_completeness_score' => $data[
                'data_completeness_score'
            ] ?? 0,

            'requires_audit' => (
                ($data['risk_level'] ?? null) === 'insufficient_data'
            ),
        ]);
    }

    private function normalize(?string $value): string
    {
        return strtolower(trim($value ?? ''));
    }

    private function containsAny(
        ?string $value,
        array $keywords
    ): bool {
        $normalized = $this->normalize($value);

        foreach ($keywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function isComputer(?string $category): bool
    {
        return $this->containsAny($category, [
            'computer',
            'desktop',
            'laptop',
        ]);
    }

    private function isSmartTv(?string $category): bool
    {
        return $this->containsAny($category, [
            'smart tv',
            'smart television',
        ]);
    }

    private function isProjector(?string $category): bool
    {
        return $this->containsAny($category, [
            'projector',
            'projected whiteboard',
        ]);
    }

    private function isPaSystem(?string $category): bool
    {
        return $this->containsAny($category, [
            'public address',
            'pa system',
            'sound system',
        ]);
    }

    private function isActive(?string $status): bool
    {
        return $this->containsAny($status, [
            'active',
            'operational',
            'working',
            'in use',
        ]);
    }

    private function isPoorCondition(?string $condition): bool
    {
        return $this->containsAny($condition, [
            'poor',
            'damaged',
            'defective',
            'non-functional',
            'non functional',
        ]);
    }

    private function needsRepair(?string $status): bool
    {
        return $this->containsAny($status, [
            'repair',
            'maintenance',
            'damaged',
        ]);
    }

    private function isRetired(?string $status): bool
    {
        return $this->containsAny($status, [
            'retired',
            'disposed',
            'decommissioned',
            'obsolete',
        ]);
    }

    private function isOldEquipment(?int $acquisitionYear): bool
    {
        if (! $acquisitionYear) {
            return false;
        }

        return $acquisitionYear <= now()->year - 5;
    }

    private function averageEquipmentAge(
        Collection $inventory
    ): ?float {
        $years = $inventory
            ->pluck('acquisition_year')
            ->filter(
                fn ($year) => is_numeric($year)
                    && (int) $year > 0
                    && (int) $year <= now()->year
            );

        if ($years->isEmpty()) {
            return null;
        }

        $averageAge = $years
            ->map(fn ($year) => now()->year - (int) $year)
            ->average();

        return round((float) $averageAge, 2);
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null
            ? null
            : (float) $value;
    }

    private function nullableBoolean(mixed $value): ?bool
    {
        return $value === null
            ? null
            : (bool) $value;
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Institution analytics job failed permanently.', [
            'institution_id' => $this->institutionId,
            'message' => $exception->getMessage(),
        ]);
    }
}