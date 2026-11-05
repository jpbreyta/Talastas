<?php

namespace Database\Seeders;

use App\Models\IctComponent;
use Illuminate\Database\Seeder;

class NetworkInfrastructureComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['modem', 'Modem', 'Network Service'],
            ['router', 'Router', 'Network Service'],
            ['wireless_access_point', 'Wireless Access Point', 'Network Service'],
            ['network_switch', 'Network Switch', 'Network Service'],
            ['network_hub', 'Network Hub', 'Network Service'],
            ['firewall', 'Firewall', 'Network Service'],
            ['load_balancer', 'Load Balancer', 'Network Service'],
            ['vpn', 'VPN', 'Network Service'],
            ['ids_ips', 'IDS/IPS', 'Network Service'],

            ['lan_cable', 'LAN Cable', 'Network Infrastructure'],
            ['fiber_optic_cable', 'Fiber Optic Cable', 'Network Infrastructure'],
            ['network_patch_panel', 'Network Patch Panels', 'Network Infrastructure'],
            ['network_server_room', 'Network Server Room', 'Network Infrastructure'],
        ];

        foreach ($components as [$key, $name, $subsection]) {
            IctComponent::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $name,
                    'component_type' => IctComponent::TYPE_NETWORK,
                    'subsection' => $subsection,
                    'is_standard' => true,
                    'counts_toward_infrastructure' => true,
                    'is_computing_device' => false,
                    'is_active' => true,
                ]
            );

        }
    }
}
