<?php

namespace Database\Seeders;

use App\Models\IctComponent;
use Illuminate\Database\Seeder;

class SoftwareInfrastructureComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['windows_os', 'Windows', 'Operating Systems'],
            ['linux_os', 'Linux', 'Operating Systems'],
            ['mac_os', 'Mac', 'Operating Systems'],

            ['productivity_suite', 'Productivity Suite', 'Licensed Applications'],
            ['collaboration_account', 'Collaboration Account', 'Licensed Applications'],
            ['enterprise_suite', 'Enterprise Suite', 'Licensed Applications'],
            ['gis_software_license', 'GIS Software License', 'Licensed Applications'],
            ['database_management_license', 'Database Mgt License', 'Licensed Applications'],
            ['software_development_tool', 'Software Development Tool', 'Licensed Applications'],

            ['anti_virus', 'Anti-virus', 'Specialized Tools'],
            ['utilities', 'Utilities', 'Specialized Tools'],
            ['network_monitoring_tool', 'Network Monitoring Tool', 'Specialized Tools'],

            ['inventory_system', 'Inventory System', 'In-House Developed Systems'],
            ['appointment_system', 'Appointment System', 'In-House Developed Systems'],
            ['billing_system', 'Billing System', 'In-House Developed Systems'],
            ['payment_processing_system', 'Payment Processing', 'In-House Developed Systems'],
            ['queue_system', 'Queue System', 'In-House Developed Systems'],
        ];

        foreach ($components as [$key, $name, $subsection]) {
            IctComponent::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $name,
                    'component_type' => IctComponent::TYPE_SOFTWARE,
                    'subsection' => $subsection,
                    'is_standard' => true,
                    'counts_toward_infrastructure' => false,
                    'is_computing_device' => false,
                    'is_active' => true,
                ]
            );

        }
    }
}
