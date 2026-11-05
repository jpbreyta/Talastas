<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('q10_number_of_connections')->nullable();
        });
        Schema::create('institution_internet_connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->unsignedTinyInteger('connection_number');
            foreach (['isp', 'plan', 'allocation', 'validity', 'context'] as $field) {
                $table->string($field, 500)->nullable();
            }
            foreach (['download_speed_mbps', 'upload_speed_mbps', 'latency_ms', 'throughput_mbps'] as $field) {
                $table->decimal($field, 14, 6)->nullable();
            }
            $table->timestamps();
            $table->unique(['institution_id', 'connection_number'], 'institution_connection_number_unique');
        });
    }

    public function down(): void
    {
        // Retain questionnaire responses on rollback to prevent data loss.
    }
};
