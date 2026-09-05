<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_analytics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('institution_id')
                ->unique()
                ->constrained('institutions')
                ->cascadeOnDelete();

            // Overall and component gap scores (0-100).
            $table->decimal('gap_score', 5, 2)->nullable();
            $table->decimal('infrastructure_deficit_score', 5, 2)->nullable();
            $table->decimal('connectivity_divide_score', 5, 2)->nullable();
            $table->decimal('technological_obsolescence_score', 5, 2)->nullable();

            // Supporting analytical indicators.
            $table->decimal('data_completeness_score', 5, 2)->nullable();
            $table->double('asset_to_user_ratio')->nullable();
            $table->string('risk_level', 30)->nullable();

            // AI / decision-support outputs.
            $table->text('infrastructure_narrative')->nullable();
            $table->text('policy_recommendation')->nullable();
            $table->jsonb('recommendations_detail')->nullable();

            $table->timestamp('analysis_date')->nullable();
            $table->timestamps();

            $table->index('gap_score');
            $table->index('risk_level');
            $table->index('analysis_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_analytics');
    }
};
