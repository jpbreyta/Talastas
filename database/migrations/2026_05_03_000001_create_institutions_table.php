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
            $table->string('name', 255);
            $table->enum('type', ['School', 'Hospital', 'Government', 'Library', 'Community Center', 'Other']);
            $table->string('barangay_name', 255);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('student_population')->nullable();
            $table->integer('staff_population')->nullable();
            $table->string('isp_provider', 255)->nullable();
            $table->decimal('internet_speed', 8, 2)->nullable(); // Mbps
            $table->boolean('has_computer_lab')->default(false);
            $table->integer('number_of_computers')->nullable();
            $table->boolean('has_projected_whiteboard')->default(false);
            $table->boolean('has_smart_tv')->default(false);
            $table->boolean('has_public_address_system')->default(false);
            $table->boolean('needs_maintenance')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index('barangay_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
