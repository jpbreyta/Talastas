<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BarangaySeeder extends Seeder
{
    public function run(): void
    {
        $geojsonPath = public_path('data/lipa-city-barangays.geojson');

        if (File::exists($geojsonPath)) {
            $geojson = json_decode(File::get($geojsonPath), true);

            foreach ($geojson['features'] as $feature) {
                Barangay::updateOrCreate(
                    ['code' => $feature['properties']['CODE']],
                    [
                        'name' => $feature['properties']['NAME'],
                        'district' => $feature['properties']['DISTRICT'],
                        'geometry' => json_encode($feature['geometry']),
                    ]
                );
            }
        } else {
            // Fallback for testing
            Barangay::create([
                'name' => 'Test Barangay',
                'code' => 'TEST001',
                'district' => 'NORTH',
                'geometry' => null,
            ]);
        }
    }
}