<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->jsonb('location_geojson')
                ->nullable()
                ->after('longitude');

            $table->jsonb('location_bounding_box')
                ->nullable()
                ->after('location_geojson');

            $table->unsignedBigInteger('nominatim_place_id')
                ->nullable()
                ->after('location_bounding_box');

            $table->string('osm_type', 20)
                ->nullable()
                ->after('nominatim_place_id');

            $table->unsignedBigInteger('osm_id')
                ->nullable()
                ->after('osm_type');

            $table->index('nominatim_place_id');
            $table->index(['osm_type', 'osm_id']);
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropIndex(['nominatim_place_id']);
            $table->dropIndex(['osm_type', 'osm_id']);

            $table->dropColumn([
                'location_geojson',
                'location_bounding_box',
                'nominatim_place_id',
                'osm_type',
                'osm_id',
            ]);
        });
    }
};
