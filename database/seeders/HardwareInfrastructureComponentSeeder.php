<?php

namespace Database\Seeders;

use App\Models\IctComponent;
use Illuminate\Database\Seeder;

class HardwareInfrastructureComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['desktop_computer', 'Desktop', 'Computing Devices', true],
            ['laptop', 'Laptop', 'Computing Devices', true],
            ['tablet', 'Tablet', 'Computing Devices', true],
            ['smart_phone', 'Smart Phone', 'Computing Devices', true],
            ['workstation', 'Workstation', 'Computing Devices', true],
            ['pda', 'PDA', 'Computing Devices', true],

            ['application_server', 'Application Server', 'Server and Data Processing Equipment', false],
            ['database_server', 'Database Server', 'Server and Data Processing Equipment', false],
            ['file_server', 'File Server', 'Server and Data Processing Equipment', false],
            ['web_server', 'Web Server', 'Server and Data Processing Equipment', false],
            ['mainframe', 'Mainframe', 'Server and Data Processing Equipment', false],
            ['virtual_server', 'Virtual Server', 'Server and Data Processing Equipment', false],

            ['external_hard_drive', 'External Hard Drive', 'Storage Devices', false],
            ['flash_drive', 'Flash Drive', 'Storage Devices', false],

            ['lan_capable_printer', 'LAN-capable Printer', 'Peripheral Devices', false],
            ['pos_set_with_printer', 'POS Set with Printer', 'Peripheral Devices', false],
            ['inkjet_printer', 'Inkjet Printer', 'Peripheral Devices', false],
            ['laser_printer', 'Laser Printer', 'Peripheral Devices', false],
            ['copier', 'Copier', 'Peripheral Devices', false],
            ['scanner', 'Scanner', 'Peripheral Devices', false],
            ['projector', 'Projector', 'Peripheral Devices', false],
            ['web_camera', 'Web Camera', 'Peripheral Devices', false],
            ['biometric_device', 'Biometric Devices', 'Peripheral Devices', false],

            ['ups', 'UPS', 'Power and Environmental Support', false],
            ['voltage_regulator', 'Voltage Regulator', 'Power and Environmental Support', false],
            ['server_rack', 'Server Rack', 'Power and Environmental Support', false],
            ['backup_generator', 'Backup Generator', 'Power and Environmental Support', false],
        ];

        foreach ($components as [$key, $name, $subsection, $computing]) {
            IctComponent::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $name,
                    'component_type' => IctComponent::TYPE_HARDWARE,
                    'subsection' => $subsection,
                    'is_standard' => true,
                    'counts_toward_infrastructure' => true,
                    'is_computing_device' => $computing,
                    'is_active' => true,
                ]
            );

        }
    }
}
