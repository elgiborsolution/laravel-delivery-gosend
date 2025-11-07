<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | "staging" or "production".
    | This will determine which base URL and credentials are used.
    |
    */
    'environment' => env('GOSEND_ENV', 'staging'),

    /*
    |--------------------------------------------------------------------------
    | Base URLs
    |--------------------------------------------------------------------------
    |
    | Main GoSend endpoints.
    |
    */
    'base_urls' => [
        'staging' => env('GOSEND_STAGING_URL', 'https://integration-kilat-api.gojekapi.com'),
        'production' => env('GOSEND_PRODUCTION_URL', 'https://kilat-api.gojekapi.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    |
    | Client ID and Pass Key per environment.
    |
    */
    'credentials' => [
        'staging' => [
            'client_id' => env('GOSEND_STAGING_CLIENT_ID'),
            'pass_key'  => env('GOSEND_STAGING_PASS_KEY'),
        ],
        'production' => [
            'client_id' => env('GOSEND_PRODUCTION_CLIENT_ID'),
            'pass_key'  => env('GOSEND_PRODUCTION_PASS_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout' => env('GOSEND_HTTP_TIMEOUT', 10),
        'retries' => env('GOSEND_HTTP_RETRIES', 2),
        'retry_sleep_ms' => env('GOSEND_HTTP_RETRY_SLEEP_MS', 250),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled' => env('GOSEND_ROUTES_ENABLED', true),
        'prefix' => env('GOSEND_ROUTE_PREFIX', 'gosend'),
        'middleware' => [
            'api',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    |
    | Token will be compared against the incoming header from GoSend.
    |
    */
    'webhook' => [
        'token_header' => env('GOSEND_WEBHOOK_TOKEN_HEADER', 'X-Callback-Token'),
        'token' => env('GOSEND_WEBHOOK_TOKEN'),
        'route_name' => 'gosend.webhook',
    ],
];
