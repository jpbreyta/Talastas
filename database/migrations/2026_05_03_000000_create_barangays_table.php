<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangays', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100)->unique();
            $table->string('code', 10)->unique();
            $table->string('district', 20)->nullable();

            // GeoJSON used by Laravel, Leaflet, and Flutter.
            $table->json('geometry')->nullable();

            $table->integer('population')->nullable();
            $table->integer('total_institutions')->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        // Create the PostGIS spatial geometry column.
        DB::statement(
            'ALTER TABLE barangays
             ADD COLUMN geom geometry(MultiPolygon, 4326)'
        );

        // Spatial index for faster ST_Contains and ST_Intersects queries.
        DB::statement(
            'CREATE INDEX barangays_geom_gist_index
             ON barangays
             USING GIST (geom)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('barangays');
    }
};