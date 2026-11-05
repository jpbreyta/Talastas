<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (!Schema::hasColumn('institutions', 'location_geometry')) {
            DB::statement(
                'ALTER TABLE institutions
                 ADD COLUMN location_geometry geometry(Geometry, 4326)'
            );
        }

        DB::statement(
            "UPDATE institutions
             SET location_geometry =
                ST_SetSRID(
                    ST_GeomFromGeoJSON(location_geojson::text),
                    4326
                )
             WHERE location_geojson IS NOT NULL
               AND location_geojson->>'type'
                    IN ('Polygon', 'MultiPolygon')
               AND location_geometry IS NULL"
        );

        DB::statement(
            'CREATE INDEX IF NOT EXISTS
                institutions_location_geometry_gix
             ON institutions
             USING GIST (location_geometry)'
        );
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            'DROP INDEX IF EXISTS
                institutions_location_geometry_gix'
        );

        if (Schema::hasColumn('institutions', 'location_geometry')) {
            DB::statement(
                'ALTER TABLE institutions
                 DROP COLUMN location_geometry'
            );
        }
    }
};
