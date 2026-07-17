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
        'base_url' => env(
            'NOMINATIM_BASE_URL',
            'https://nominatim.openstreetmap.org/search'
        ),

        'output_format' => env(
            'NOMINATIM_OUTPUT_FORMAT',
            'json'
        ),

        'country_code' => env(
            'NOMINATIM_COUNTRY_CODE',
            'ph'
        ),

        'app_email' => env(
            'NOMINATIM_APP_EMAIL'
        ),

        'user_agent' => env(
            'NOMINATIM_USER_AGENT',
            'LipaGisMapping/1.0 ('
                .env(
                    'NOMINATIM_APP_EMAIL',
                    'jpreytapogi@gmail.com'
                )
                .')'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Python Analytics Service
    |--------------------------------------------------------------------------
    |
    | Configuration for the FastAPI service responsible for calculating
    | institution ICT gap scores and recommendations.
    |
    */
    'python_analytics' => [
        'url' => env(
            'PYTHON_ANALYTICS_URL',
            'http://127.0.0.1:8002'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | RDBMA API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for RDBMA integration.
    |
    */
    'rdbma' => [
        'api_key' => env('RDBMA_API_KEY'),
    ],

];