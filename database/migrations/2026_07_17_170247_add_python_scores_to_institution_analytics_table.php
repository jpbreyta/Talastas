<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_analytics', function (Blueprint $table) {
            $table->decimal(
                'infrastructure_deficit_score',
                5,
                2
            )->nullable()->after('gap_score');

            $table->decimal(
                'connectivity_divide_score',
                5,
                2
            )->nullable()->after('infrastructure_deficit_score');

            $table->decimal(
                'technological_obsolescence_score',
                5,
                2
            )->nullable()->after('connectivity_divide_score');
        });

        DB::statement(
            'ALTER TABLE institution_analytics
             ALTER COLUMN gap_score DROP NOT NULL'
        );

        DB::statement(
            'ALTER TABLE institution_analytics
             ALTER COLUMN data_completeness_score
             TYPE NUMERIC(5,2)
             USING data_completeness_score::NUMERIC(5,2)'
        );
    }

    public function down(): void
    {
        Schema::table('institution_analytics', function (Blueprint $table) {
            $table->dropColumn([
                'infrastructure_deficit_score',
                'connectivity_divide_score',
                'technological_obsolescence_score',
            ]);
        });

        DB::statement(
            'ALTER TABLE institution_analytics
             ALTER COLUMN data_completeness_score
             TYPE INTEGER
             USING ROUND(data_completeness_score)::INTEGER'
        );

        DB::statement(
            'ALTER TABLE institution_analytics
             ALTER COLUMN gap_score SET NOT NULL'
        );
    }
};