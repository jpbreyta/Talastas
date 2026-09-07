<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();

            // Institution identity
            $table->string('name', 255);
            $table->enum('type', [
                'School',
                'Barangay Hall',
                'Local Government Office',
            ])->default('School');

            $table->foreignId('barangay_id')
                ->constrained('barangays')
                ->cascadeOnDelete();

            // Geographic information
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->jsonb('location_geojson')->nullable();
            $table->jsonb('location_bounding_box')->nullable();
            $table->unsignedBigInteger('nominatim_place_id')->nullable();
            $table->string('osm_type', 20)->nullable();
            $table->unsignedBigInteger('osm_id')->nullable();

            // Population / users served
            $table->unsignedInteger('student_population')->nullable();
            $table->unsignedInteger('staff_population')->nullable();

            // Connectivity information
            $table->string('isp_provider', 255)->nullable();
            $table->decimal('internet_speed', 10, 2)->nullable();

            // Kept for compatibility with the current application while the
            // dedicated api_keys table is reviewed during the controller refactor.
            $table->string('api_key', 255)->nullable();

            // Derived inventory summary fields.
            // These should be synchronized from ict_inventory, not entered twice.
            $table->boolean('has_computer_lab')->default(false);
            $table->unsignedInteger('number_of_computers')->default(0);
            $table->boolean('has_projected_whiteboard')->default(false);
            $table->boolean('has_smart_tv')->default(false);
            $table->boolean('has_public_address_system')->default(false);
            $table->boolean('needs_maintenance')->default(false);

            // Data quality / audit state
            $table->decimal('data_completeness_score', 5, 2)->default(0);
            $table->boolean('requires_audit')->default(true);
            $table->timestamp('last_audited_at')->nullable();

            $table->foreignId('audited_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('audit_notes')->nullable();

            // General state
            $table->text('notes')->nullable();
            $table->boolean('is_pilot_site')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index('nominatim_place_id');
            $table->index(['osm_type', 'osm_id']);
            $table->index(['barangay_id', 'type']);
            $table->index(['is_active', 'is_pilot_site']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
