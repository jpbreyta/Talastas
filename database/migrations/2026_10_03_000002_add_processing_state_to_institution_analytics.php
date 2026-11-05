<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_analytics', function (Blueprint $table): void {
            $table->uuid('processing_token')
                ->nullable()
                ->after('analysis_date');

            $table->string('processing_status', 30)
                ->nullable()
                ->after('processing_token');

            $table->timestamp('processing_started_at')
                ->nullable()
                ->after('processing_status');

            $table->timestamp('processing_completed_at')
                ->nullable()
                ->after('processing_started_at');

            $table->text('processing_error')
                ->nullable()
                ->after('processing_completed_at');

            $table->index('processing_token');
            $table->index('processing_status');
        });
    }

    public function down(): void
    {
        Schema::table('institution_analytics', function (Blueprint $table): void {
            $table->dropIndex(['processing_token']);
            $table->dropIndex(['processing_status']);

            $table->dropColumn([
                'processing_token',
                'processing_status',
                'processing_started_at',
                'processing_completed_at',
                'processing_error',
            ]);
        });
    }
};
