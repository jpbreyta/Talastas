<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ICT Inventory Categories
    |--------------------------------------------------------------------------
    |
    | Keep the database category as a stable snake_case key. The label is what
    | the UI displays. Specification definitions are intentionally stored in
    | config so the controller and Blade forms can use the same source of truth.
    |
    */
    'categories' => [
        'computer' => [
            'label' => 'Computer (Unspecified)',
            'specifications' => [
                'processor' => [
                    'label' => 'Processor',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:150',
                ],
                'ram_gb' => [
                    'label' => 'RAM (GB)',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:1|max:1024',
                ],
                'storage_type' => [
                    'label' => 'Storage Type',
                    'type' => 'select',
                    'options' => [
                        'hdd' => 'HDD',
                        'ssd' => 'SSD',
                        'nvme' => 'NVMe SSD',
                        'hybrid' => 'Hybrid',
                        'other' => 'Other',
                    ],
                    'rules' => 'nullable|in:hdd,ssd,nvme,hybrid,other',
                ],
                'storage_gb' => [
                    'label' => 'Storage Capacity (GB)',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:1|max:100000',
                ],
                'operating_system' => [
                    'label' => 'Operating System',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:150',
                ],
                'architecture' => [
                    'label' => 'Architecture',
                    'type' => 'select',
                    'options' => [
                        '32_bit' => '32-bit',
                        '64_bit' => '64-bit',
                    ],
                    'rules' => 'nullable|in:32_bit,64_bit',
                ],
                'network_capable' => [
                    'label' => 'Network Capable',
                    'type' => 'boolean',
                    'rules' => 'nullable|boolean',
                ],
            ],
        ],

        'desktop_computer' => [
            'label' => 'Desktop Computer',
            'specifications' => [
                'processor' => [
                    'label' => 'Processor',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:150',
                ],
                'ram_gb' => [
                    'label' => 'RAM (GB)',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:1|max:1024',
                ],
                'storage_type' => [
                    'label' => 'Storage Type',
                    'type' => 'select',
                    'options' => [
                        'hdd' => 'HDD',
                        'ssd' => 'SSD',
                        'nvme' => 'NVMe SSD',
                        'hybrid' => 'Hybrid',
                        'other' => 'Other',
                    ],
                    'rules' => 'nullable|in:hdd,ssd,nvme,hybrid,other',
                ],
                'storage_gb' => [
                    'label' => 'Storage Capacity (GB)',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:1|max:100000',
                ],
                'operating_system' => [
                    'label' => 'Operating System',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:150',
                ],
                'architecture' => [
                    'label' => 'Architecture',
                    'type' => 'select',
                    'options' => [
                        '32_bit' => '32-bit',
                        '64_bit' => '64-bit',
                    ],
                    'rules' => 'nullable|in:32_bit,64_bit',
                ],
                'network_capable' => [
                    'label' => 'Network Capable',
                    'type' => 'boolean',
                    'rules' => 'nullable|boolean',
                ],
            ],
        ],

        'laptop' => [
            'label' => 'Laptop',
            'specifications' => [
                'processor' => [
                    'label' => 'Processor',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:150',
                ],
                'ram_gb' => [
                    'label' => 'RAM (GB)',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:1|max:1024',
                ],
                'storage_type' => [
                    'label' => 'Storage Type',
                    'type' => 'select',
                    'options' => [
                        'hdd' => 'HDD',
                        'ssd' => 'SSD',
                        'nvme' => 'NVMe SSD',
                        'hybrid' => 'Hybrid',
                        'other' => 'Other',
                    ],
                    'rules' => 'nullable|in:hdd,ssd,nvme,hybrid,other',
                ],
                'storage_gb' => [
                    'label' => 'Storage Capacity (GB)',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:1|max:100000',
                ],
                'operating_system' => [
                    'label' => 'Operating System',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:150',
                ],
                'architecture' => [
                    'label' => 'Architecture',
                    'type' => 'select',
                    'options' => [
                        '32_bit' => '32-bit',
                        '64_bit' => '64-bit',
                    ],
                    'rules' => 'nullable|in:32_bit,64_bit',
                ],
                'network_capable' => [
                    'label' => 'Network Capable',
                    'type' => 'boolean',
                    'rules' => 'nullable|boolean',
                ],
            ],
        ],

        'tablet' => [
            'label' => 'Tablet',
            'specifications' => [
                'processor' => [
                    'label' => 'Processor',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:150',
                ],
                'ram_gb' => [
                    'label' => 'RAM (GB)',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:1|max:256',
                ],
                'storage_gb' => [
                    'label' => 'Storage Capacity (GB)',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:1|max:10000',
                ],
                'operating_system' => [
                    'label' => 'Operating System',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:150',
                ],
                'wifi_standard' => [
                    'label' => 'Wi-Fi Standard',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:100',
                ],
            ],
        ],

        'printer' => [
            'label' => 'Printer',
            'specifications' => [
                'printer_type' => [
                    'label' => 'Printer Type',
                    'type' => 'select',
                    'options' => [
                        'inkjet' => 'Inkjet',
                        'laser' => 'Laser',
                        'dot_matrix' => 'Dot Matrix',
                        'thermal' => 'Thermal',
                        'other' => 'Other',
                    ],
                    'rules' => 'nullable|in:inkjet,laser,dot_matrix,thermal,other',
                ],
                'color_support' => [
                    'label' => 'Color Printing',
                    'type' => 'boolean',
                    'rules' => 'nullable|boolean',
                ],
                'network_capable' => [
                    'label' => 'Network Capable',
                    'type' => 'boolean',
                    'rules' => 'nullable|boolean',
                ],
                'duplex' => [
                    'label' => 'Automatic Duplex',
                    'type' => 'boolean',
                    'rules' => 'nullable|boolean',
                ],
            ],
        ],

        'router' => [
            'label' => 'Router',
            'specifications' => [
                'wifi_standard' => [
                    'label' => 'Wi-Fi Standard',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:100',
                ],
                'frequency_band' => [
                    'label' => 'Frequency Band',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:100',
                ],
                'max_speed_mbps' => [
                    'label' => 'Maximum Speed (Mbps)',
                    'type' => 'number',
                    'rules' => 'nullable|numeric|min:0|max:1000000',
                ],
                'lan_ports' => [
                    'label' => 'LAN Ports',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:0|max:1000',
                ],
                'wan_ports' => [
                    'label' => 'WAN Ports',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:0|max:100',
                ],
            ],
        ],

        'network_switch' => [
            'label' => 'Network Switch',
            'specifications' => [
                'port_count' => [
                    'label' => 'Port Count',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:1|max:1000',
                ],
                'max_speed_mbps' => [
                    'label' => 'Maximum Port Speed (Mbps)',
                    'type' => 'number',
                    'rules' => 'nullable|numeric|min:0|max:1000000',
                ],
                'managed' => [
                    'label' => 'Managed Switch',
                    'type' => 'boolean',
                    'rules' => 'nullable|boolean',
                ],
                'poe_support' => [
                    'label' => 'PoE Support',
                    'type' => 'boolean',
                    'rules' => 'nullable|boolean',
                ],
            ],
        ],

        'access_point' => [
            'label' => 'Wireless Access Point',
            'specifications' => [
                'wifi_standard' => [
                    'label' => 'Wi-Fi Standard',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:100',
                ],
                'frequency_band' => [
                    'label' => 'Frequency Band',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:100',
                ],
                'max_speed_mbps' => [
                    'label' => 'Maximum Speed (Mbps)',
                    'type' => 'number',
                    'rules' => 'nullable|numeric|min:0|max:1000000',
                ],
            ],
        ],

        'smart_tv' => [
            'label' => 'Smart TV',
            'specifications' => [
                'screen_size_inches' => [
                    'label' => 'Screen Size (inches)',
                    'type' => 'number',
                    'rules' => 'nullable|numeric|min:1|max:500',
                ],
                'resolution' => [
                    'label' => 'Resolution',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:100',
                ],
                'smart_os' => [
                    'label' => 'Smart TV OS / Platform',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:100',
                ],
            ],
        ],

        'projector' => [
            'label' => 'Projector',
            'specifications' => [
                'resolution' => [
                    'label' => 'Resolution',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:100',
                ],
                'brightness_lumens' => [
                    'label' => 'Brightness (lumens)',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:0|max:100000',
                ],
                'display_technology' => [
                    'label' => 'Display Technology',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:100',
                ],
                'hdmi_support' => [
                    'label' => 'HDMI Support',
                    'type' => 'boolean',
                    'rules' => 'nullable|boolean',
                ],
                'vga_support' => [
                    'label' => 'VGA Support',
                    'type' => 'boolean',
                    'rules' => 'nullable|boolean',
                ],
            ],
        ],

        'projected_whiteboard' => [
            'label' => 'Projected / Interactive Whiteboard',
            'specifications' => [
                'technology' => [
                    'label' => 'Technology',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:100',
                ],
                'display_size_inches' => [
                    'label' => 'Display Size (inches)',
                    'type' => 'number',
                    'rules' => 'nullable|numeric|min:1|max:500',
                ],
                'resolution' => [
                    'label' => 'Resolution',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:100',
                ],
                'touch_support' => [
                    'label' => 'Touch Support',
                    'type' => 'boolean',
                    'rules' => 'nullable|boolean',
                ],
            ],
        ],

        'server' => [
            'label' => 'Server',
            'specifications' => [
                'processor' => [
                    'label' => 'Processor',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:150',
                ],
                'ram_gb' => [
                    'label' => 'RAM (GB)',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:1|max:8192',
                ],
                'storage_type' => [
                    'label' => 'Storage Type',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:100',
                ],
                'storage_gb' => [
                    'label' => 'Storage Capacity (GB)',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:1|max:10000000',
                ],
                'operating_system' => [
                    'label' => 'Operating System',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:150',
                ],
                'raid_support' => [
                    'label' => 'RAID Support',
                    'type' => 'boolean',
                    'rules' => 'nullable|boolean',
                ],
            ],
        ],

        'ups' => [
            'label' => 'UPS',
            'specifications' => [
                'capacity_va' => [
                    'label' => 'Capacity (VA)',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:1|max:10000000',
                ],
                'battery_runtime_minutes' => [
                    'label' => 'Estimated Runtime (minutes)',
                    'type' => 'number',
                    'rules' => 'nullable|integer|min:0|max:100000',
                ],
            ],
        ],

        'pa_system' => [
            'label' => 'Public Address System',
            'specifications' => [
                'system_type' => [
                    'label' => 'System Type',
                    'type' => 'text',
                    'rules' => 'nullable|string|max:100',
                ],
                'power_watts' => [
                    'label' => 'Power (watts)',
                    'type' => 'number',
                    'rules' => 'nullable|numeric|min:0|max:1000000',
                ],
                'wireless_support' => [
                    'label' => 'Wireless Support',
                    'type' => 'boolean',
                    'rules' => 'nullable|boolean',
                ],
            ],
        ],

        'other' => [
            'label' => 'Other ICT Equipment',
            'specifications' => [],
        ],
    ],

    'conditions' => [
        'new' => 'New',
        'excellent' => 'Excellent',
        'good' => 'Good',
        'fair' => 'Fair',
        'poor' => 'Poor',
        'needs_repair' => 'Needs Repair',
    ],

    'statuses' => [
        'active' => 'Active',
        'under_repair' => 'Under Repair',
        'inactive' => 'Inactive',
        'retired' => 'Retired',
        'disposed' => 'Disposed',
    ],
];
