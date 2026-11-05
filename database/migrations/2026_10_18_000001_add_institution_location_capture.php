<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('location_source', 20)->nullable();
        });
        Schema::table('user_activity_logs', function (Blueprint $table) {
            $table->string('location_source', 20)->nullable();
            $table->decimal('actor_latitude', 10, 7)->nullable();
            $table->decimal('actor_longitude', 11, 7)->nullable();
            $table->double('gps_accuracy')->nullable();
            $table->boolean('gps_verified')->nullable();
            $table->index(['record_type', 'record_id', 'occurred_at']);
        });
    }
    public function down(): void
    {
        Schema::table('user_activity_logs', function (Blueprint $table) {
            $table->dropIndex(['record_type', 'record_id', 'occurred_at']);
            $table->dropColumn(['location_source', 'actor_latitude', 'actor_longitude', 'gps_accuracy', 'gps_verified']);
        });
        Schema::table('institutions', fn (Blueprint $table) => $table->dropColumn('location_source'));
    }
};
