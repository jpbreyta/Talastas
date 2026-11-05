<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InfrastructureComponentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HardwareInfrastructureComponentSeeder::class,
            SoftwareInfrastructureComponentSeeder::class,
            NetworkInfrastructureComponentSeeder::class,
            ConnectivityInfrastructureComponentSeeder::class,
        ]);

        $this->command?->info(
            'Standard ICT infrastructure component catalog seeded successfully.'
        );
    }
}
