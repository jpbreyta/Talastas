<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'institution_analytics',
            function (Blueprint $table): void {
                $table->string(
                    'processing_stage',
                    50
                )
                    ->nullable()
                    ->after('processing_status');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'institution_analytics',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'processing_stage'
                );
            }
        );
    }
};
