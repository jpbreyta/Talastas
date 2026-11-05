<?php

return [
    // Provisioning credentials belong only in the environment or deployment secret store.
    'admin' => [
        'email' => env('ADMIN_SEED_EMAIL'),
        'password' => env('ADMIN_SEED_PASSWORD'),
    ],
];
