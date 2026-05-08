<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_gap_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            $table->decimal('gap_score', 5, 2); // 0-100 percentage score
            $table->text('infrastructure_narrative');
            $table->text('policy_recommendation');
            $table->json('recommendations_detail')->nullable();
            $table->timestamp('analysis_date');
            $table->timestamps();

            $table->unique('institution_id');
            $table->index('gap_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_gap_analytics');
    }
};
