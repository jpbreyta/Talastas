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
        'lookup_url' => env(
            'NOMINATIM_LOOKUP_URL',
            'https://nominatim.openstreetmap.org/lookup'
        ),
        'reverse_url' => env(
            'NOMINATIM_REVERSE_URL',
            'https://nominatim.openstreetmap.org/reverse'
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
            'LipaGisMapping/1.0'
                . (env('NOMINATIM_APP_EMAIL') ? ' (' . env('NOMINATIM_APP_EMAIL') . ')' : '')
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Python Analytics Service
    |--------------------------------------------------------------------------
    |
    | The Python service remains responsible for deterministic ICT scores.
    | Generative AI will be used for narratives and recommendations.
    |
    */
    'python_analytics' => [
        'url' => env(
            'PYTHON_ANALYTICS_URL',
            'http://127.0.0.1:8002'
        ),

        'timeout' => (int) env(
            'PYTHON_ANALYTICS_TIMEOUT',
            15
        ),

        'health_check_timeout' => (int) env(
            'PYTHON_ANALYTICS_HEALTH_TIMEOUT',
            3
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Geometry Complexity Limits
    |--------------------------------------------------------------------------
    |
    | Maximum coordinate count accepted for institution boundary geometry.
    | Prevents PostGIS denial of service from maliciously large polygons.
    |
    */
    'geometry' => [
        'max_coordinates' => (int) env(
            'GEOMETRY_MAX_COORDINATES',
            10000
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Provider Order
    |--------------------------------------------------------------------------
    |
    | Gemini is used first. Groq is called only when Gemini is unavailable,
    | rate-limited, times out, or returns an unusable response.
    |
    */
    'ai' => [
        'primary_provider' => env(
            'AI_PRIMARY_PROVIDER',
            'gemini'
        ),

        'fallback_provider' => env(
            'AI_FALLBACK_PROVIDER',
            'groq'
        ),

        'connect_timeout' => (int) env(
            'AI_CONNECT_TIMEOUT',
            5
        ),

        'request_timeout' => (int) env(
            'AI_REQUEST_TIMEOUT',
            20
        ),

        'retry_times' => (int) env(
            'AI_RETRY_TIMES',
            1
        ),

        'retry_delay_ms' => (int) env(
            'AI_RETRY_DELAY_MS',
            300
        ),

        'max_output_tokens' => (int) env(
            'AI_MAX_OUTPUT_TOKENS',
            700
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Gemini API
    |--------------------------------------------------------------------------
    |
    | Gemini 2.5 Flash-Lite is the default because it is a stable,
    | lightweight model suitable for structured analytics narratives.
    |
    */
    'gemini' => [
        'api_key' => env(
            'GEMINI_API_KEY'
        ),

        'model' => env(
            'GEMINI_MODEL',
            'gemini-2.5-flash-lite'
        ),

        'base_url' => env(
            'GEMINI_BASE_URL',
            'https://generativelanguage.googleapis.com/v1beta/models'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Groq API Fallback
    |--------------------------------------------------------------------------
    |
    | Groq is the secondary provider. It uses an OpenAI-compatible endpoint
    | and is contacted only after the primary Gemini request fails.
    |
    */
    'groq' => [
        'api_key' => env(
            'GROQ_API_KEY'
        ),

        'model' => env(
            'GROQ_MODEL',
            'openai/gpt-oss-20b'
        ),

        // Separate from Gemini's output budget: GPT-OSS also spends tokens on reasoning.
        'max_completion_tokens' => (int) env('GROQ_MAX_COMPLETION_TOKENS', 2400),

        'base_url' => env(
            'GROQ_BASE_URL',
            'https://api.groq.com/openai/v1/chat/completions'
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
        'api_key' => env(
            'RDBMA_API_KEY'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resend Email API
    |--------------------------------------------------------------------------
    |
    | Resend is used for all outbound account-security email delivery.
    | SMTP credentials are no longer used.
    |
    */
    'resend' => [
        'key' => env('RESEND_API_KEY'),
        'endpoint' => env('RESEND_API_ENDPOINT', 'https://api.resend.com/emails'),
    ],

];
