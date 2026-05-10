<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionsSeeder extends Seeder
{
    public function run(): void
    {
        $institutions = [
            [
                'name' => 'Lipa City National High School',
                'sector_type' => 'School',
                'barangay_name' => 'Poblacion Barrio 1',
                'latitude' => 13.9411,
                'longitude' => 121.1644,
                'student_population' => 1200,
                'internet_speed' => 50.0,
                'is_pilot_site' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Lipa City Hall',
                'sector_type' => 'City Government Office',
                'barangay_name' => 'Poblacion Barrio 2',
                'latitude' => 13.9420,
                'longitude' => 121.1650,
                'student_population' => null,
                'internet_speed' => 100.0,
                'is_pilot_site' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Lipa Medix Medical Center',
                'sector_type' => 'Health Center',
                'barangay_name' => 'Tambo',
                'latitude' => 13.9500,
                'longitude' => 121.1700,
                'student_population' => null,
                'internet_speed' => 25.0,
                'is_pilot_site' => true,
                'is_active' => true,
            ],
        ];

        foreach ($institutions as $inst) {
            Institution::create($inst);
        }

        $this->command->info('Institutions seeded successfully.');
    }
}
