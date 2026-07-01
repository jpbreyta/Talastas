<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nominatim Geocoding Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Nominatim OpenStreetMap geocoding service.
    | Used for automatic location geocoding based on institution name/barangay.
    |
    */
    'nominatim' => [
        'base_url' => env('NOMINATIM_BASE_URL', 'https://nominatim.openstreetmap.org/search'),
        'output_format' => env('NOMINATIM_OUTPUT_FORMAT', 'json'),
        'country_code' => env('NOMINATIM_COUNTRY_CODE', 'ph'),
        'app_email' => env('NOMINATIM_APP_EMAIL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | RDBMA API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for RDBMA (Research/Database Management API) integration.
    | Used for external system connectivity for institutions.
    |
    */
    'rdbma' => [
        'api_key' => env('RDBMA_API_KEY'),
    ],

];