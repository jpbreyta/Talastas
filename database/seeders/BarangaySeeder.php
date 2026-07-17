<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class BarangaySeeder extends Seeder
{
    public function run(): void
    {
        $geojsonPath = public_path('data/lipa-city-barangays.geojson');

        if (!File::exists($geojsonPath)) {
            throw new RuntimeException(
                "Barangay GeoJSON file was not found: {$geojsonPath}"
            );
        }

        $geojson = json_decode(
            File::get($geojsonPath),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($geojson['features'] ?? [] as $feature) {
            $properties = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;

            if (
                empty($properties['CODE']) ||
                empty($properties['NAME']) ||
                empty($geometry)
            ) {
                continue;
            }

            $barangay = Barangay::updateOrCreate(
                [
                    'code' => $properties['CODE'],
                ],
                [
                    'name' => $properties['NAME'],
                    'district' => $properties['DISTRICT'] ?? null,
                    'geometry' => $geometry,
                ]
            );
            
            DB::statement(
                'UPDATE barangays
                 SET geom = ST_SetSRID(
                     ST_GeomFromGeoJSON(?),
                     4326
                 )
                 WHERE id = ?',
                [
                    json_encode($geometry, JSON_THROW_ON_ERROR),
                    $barangay->id,
                ]
            );
        }
    }
}