<?php

namespace Database\Seeders;

use App\Helpers\GapScoreCalculator;
use App\Models\Institution;
use Illuminate\Database\Seeder;

class BackfillInstitutionScopeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Institution::all() as $institution) {
            $completeness = GapScoreCalculator::computeDataCompleteness($institution);

            $institution->update([
                'sector_type' => $institution->sector_type ?: 'School',
                'is_pilot_site' => true, // All existing records are pilot sites
                'requires_audit' => true,
                'data_completeness_score' => $completeness,
                'is_active' => true,
            ]);
        }

        $this->command->info('Institution scope fields backfilled successfully.');
    }
}
