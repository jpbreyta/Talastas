<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_analytics', function (Blueprint $table): void {
            $table->jsonb('ai_priorities')
                ->nullable()
                ->after('recommendations_detail');
        });
    }

    public function down(): void
    {
        Schema::table('institution_analytics', function (Blueprint $table): void {
            $table->dropColumn('ai_priorities');
        });
    }
};