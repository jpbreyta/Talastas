<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('institutions', 'data_completeness_score')) {
            DB::statement(
                'ALTER TABLE institutions
                 ALTER COLUMN data_completeness_score
                 TYPE NUMERIC(5, 2)
                 USING data_completeness_score::NUMERIC'
            );
        }

        if (
            Schema::hasTable('institution_analytics')
            && Schema::hasColumn(
                'institution_analytics',
                'data_completeness_score'
            )
        ) {
            DB::statement(
                'ALTER TABLE institution_analytics
                 ALTER COLUMN data_completeness_score
                 TYPE NUMERIC(5, 2)
                 USING data_completeness_score::NUMERIC'
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('institutions', 'data_completeness_score')) {
            DB::statement(
                'ALTER TABLE institutions
                 ALTER COLUMN data_completeness_score
                 TYPE INTEGER
                 USING ROUND(data_completeness_score)::INTEGER'
            );
        }

        if (
            Schema::hasTable('institution_analytics')
            && Schema::hasColumn(
                'institution_analytics',
                'data_completeness_score'
            )
        ) {
            DB::statement(
                'ALTER TABLE institution_analytics
                 ALTER COLUMN data_completeness_score
                 TYPE INTEGER
                 USING ROUND(data_completeness_score)::INTEGER'
            );
        }
    }
};
