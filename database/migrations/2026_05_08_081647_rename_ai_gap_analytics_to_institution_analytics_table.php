<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('ai_gap_analytics', 'institution_analytics');
        Schema::table('institution_analytics', function (Blueprint $table) {
            $table->integer('data_completeness_score')->nullable()->after('gap_score');
            $table->float('asset_to_user_ratio')->nullable()->after('data_completeness_score');
            $table->string('risk_level')->nullable()->after('asset_to_user_ratio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_analytics', function (Blueprint $table) {
            $table->dropColumn(['data_completeness_score', 'asset_to_user_ratio', 'risk_level']);
        });
        Schema::rename('institution_analytics', 'ai_gap_analytics');
    }
};
