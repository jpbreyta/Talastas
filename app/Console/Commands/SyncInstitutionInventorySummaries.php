<?php

namespace App\Console\Commands;

use App\Models\Institution;
use App\Services\InstitutionInventorySummaryService;
use Illuminate\Console\Command;

class SyncInstitutionInventorySummaries extends Command
{
    protected $signature = 'inventory:sync-summaries
                            {--institution= : Sync one institution ID}';

    protected $description = 'Recalculate institution summaries from active inventory records.';

    public function handle(
        InstitutionInventorySummaryService $summaryService
    ): int {
        $institutionId = $this->option('institution');

        if ($institutionId !== null) {
            $institution = Institution::find($institutionId);

            if (!$institution) {
                $this->error('The institution was not found.');

                return self::FAILURE;
            }

            $summaryService->sync($institution);

            $this->info('The institution summary was synchronized.');

            return self::SUCCESS;
        }

        $syncedCount = 0;

        Institution::query()
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($institutions) use (
                $summaryService,
                &$syncedCount
            ): void {
                foreach ($institutions as $institution) {
                    $summaryService->sync($institution);
                    $syncedCount++;
                }
            });

        $this->info("Synchronized {$syncedCount} institution summaries.");

        return self::SUCCESS;
    }
}
