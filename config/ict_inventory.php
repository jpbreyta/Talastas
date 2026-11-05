<?php

/*
|--------------------------------------------------------------------------
| ICT inventory legacy value labels
|--------------------------------------------------------------------------
|
| Standard infrastructure components are no longer defined in configuration.
| They are stored in the ict_components database table and populated by the
| InfrastructureComponentSeeder family. This file only retains legacy labels
| used by older data/API compatibility paths.
|
*/

return [
    'conditions' => [
        'good' => 'Good',
        'poor' => 'Poor',
        'needs_repair' => 'Needs Repair',
    ],

    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'retired' => 'Retired',
        'disposed' => 'Disposed',
    ],
];
