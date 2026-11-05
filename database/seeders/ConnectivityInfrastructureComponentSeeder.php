<?php

namespace Database\Seeders;

use App\Models\IctComponent;
use App\Services\ConnectivityInventory;
use Illuminate\Database\Seeder;

class ConnectivityInfrastructureComponentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ConnectivityInventory::COMPONENTS as $key => $definition) {
            IctComponent::updateOrCreate(['key' => $key], [
                'name' => $definition['name'],
                'component_type' => IctComponent::TYPE_CONNECTIVITY,
                'subsection' => 'Connectivity Capacity',
                'is_standard' => true,
                'counts_toward_infrastructure' => false,
                'is_computing_device' => false,
                'is_active' => true,
            ]);
        }
    }
}
