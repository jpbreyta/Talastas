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

            // GeoJSON copy used by Laravel/Leaflet for direct client-side rendering.
            $table->jsonb('geometry')->nullable();

            $table->unsignedInteger('population')->nullable();
            $table->unsignedInteger('total_institutions')->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        // PostGIS geometry used for spatial queries such as ST_Contains/ST_Intersects.
        // Ensure the PostGIS extension is enabled in Supabase before running migrations.
        DB::statement(
            'ALTER TABLE barangays
             ADD COLUMN geom geometry(MultiPolygon, 4326)'
        );

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
