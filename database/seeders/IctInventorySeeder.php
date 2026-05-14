<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IctInventorySeeder extends Seeder
{
    public function run(): void
    {
        $inventory = [
            // BatStateU Lipa Campus (institution_id: 1)
            [
                'institution_id' => 1,
                'category' => 'Networking',
                'condition' => 'Excellent',
                'acquisition_year' => 2024,
                'status' => 'Active',
            ],
            [
                'institution_id' => 1,
                'category' => 'Server Equipment',
                'condition' => 'Good',
                'acquisition_year' => 2023,
                'status' => 'Active',
            ],
            [
                'institution_id' => 1,
                'category' => 'Desktop Computers',
                'condition' => 'Needs Maintenance',
                'acquisition_year' => 2021,
                'status' => 'Under Repair',
            ],

            // Kolehiyo ng Lungsod ng Lipa (institution_id: 2)
            [
                'institution_id' => 2,
                'category' => 'Projected Whiteboard',
                'condition' => 'Good',
                'acquisition_year' => 2023,
                'status' => 'Active',
            ],
            [
                'institution_id' => 2,
                'category' => 'Smart TV',
                'condition' => 'Excellent',
                'acquisition_year' => 2025,
                'status' => 'Active',
            ],
            [
                'institution_id' => 2,
                'category' => 'Desktop Computers',
                'condition' => 'Fair',
                'acquisition_year' => 2022,
                'status' => 'Active',
            ],

            // Lipa City Hall (institution_id: 3)
            [
                'institution_id' => 3,
                'category' => 'Public Address System',
                'condition' => 'Excellent',
                'acquisition_year' => 2024,
                'status' => 'Active',
            ],
            [
                'institution_id' => 3,
                'category' => 'Information Kiosk',
                'condition' => 'Needs Maintenance',
                'acquisition_year' => 2022,
                'status' => 'Active',
            ],
            [
                'institution_id' => 3,
                'category' => 'Office Laptops',
                'condition' => 'Good',
                'acquisition_year' => 2023,
                'status' => 'Active',
            ],
        ];

        DB::table('ict_inventory')->insert($inventory);

        $this->command->info('ICT Inventory seeded successfully.');
    }
}