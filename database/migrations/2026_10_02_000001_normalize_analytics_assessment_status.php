<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('institution_analytics')
            ->whereNotNull('gap_score')
            ->update(['risk_level' => 'assessed']);

        DB::table('institution_analytics')
            ->whereNull('gap_score')
            ->update(['risk_level' => 'insufficient_data']);
    }

    public function down(): void
    {
        /*
         * Previous Critical/Warning/Adequate labels were based on unsourced
         * cutoffs. They are intentionally not reconstructed on rollback.
         */
    }
};
