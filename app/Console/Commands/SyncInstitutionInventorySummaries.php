<?php

namespace App\Console\Commands;

use App\Models\Institution;
use App\Services\InstitutionInventorySummaryService;
use Illuminate\Console\Command;

class SyncInstitutionInventorySummaries extends Command
{
    protected $signature = 'inventory:sync-summaries
                            {--institution= : Sync one institution ID}';

    protected $description =
        'Recalculate institution inventory summaries from active grouped ICT inventory records.';

    public function handle(
        InstitutionInventorySummaryService $summaryService
    ): int {
        $institutionId = $this->option('institution');

        if ($institutionId !== null) {
            if (
                !is_numeric($institutionId)
                || (int) $institutionId <= 0
            ) {
                $this->error(
                    'The --institution option must be a valid institution ID.'
                );

                return self::FAILURE;
            }

            $institution = Institution::find(
                (int) $institutionId
            );

            if (!$institution) {
                $this->error('The institution was not found.');

                return self::FAILURE;
            }

            $summaryService->sync($institution);

            $this->info(
                "Synchronized inventory summary for {$institution->name}."
            );

            return self::SUCCESS;
        }

        $syncedCount = 0;

        Institution::query()
            ->select('id')
            ->orderBy('id')
            ->chunkById(
                200,
                function ($institutions) use (
                    $summaryService,
                    &$syncedCount
                ): void {
                    foreach ($institutions as $institution) {
                        $summaryService->sync(
                            (int) $institution->id
                        );

                        $syncedCount++;
                    }
                }
            );

        $this->info(
            "Synchronized {$syncedCount} institution inventory summaries."
        );

        return self::SUCCESS;
    }
}
