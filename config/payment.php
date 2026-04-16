<?php

return [
    'gateway' => env('PAYMENT_GATEWAY', 'palmpesa'),
    'fallback_gateway' => env('PAYMENT_FALLBACK_GATEWAY', 'snippe'),

    'palmpesa' => [
        'enabled' => env('PALMPESA_ENABLED', true),
        'api_token' => env('PALMPESA_API_TOKEN', ''),
        'user_id' => env('PALMPESA_USER_ID', ''),
        'vendor' => env('PALMPESA_VENDOR', ''),
        'environment' => env('PALMPESA_ENVIRONMENT', 'production'),
        'base_url' => env('PALMPESA_BASE_URL', 'https://palmpesa.drmlelwa.co.tz'),
        'callback_url' => env('PALMPESA_CALLBACK_URL', '/api/v1/webhooks/palmpesa'),
        'webhook_secret' => env('PALMPESA_WEBHOOK_SECRET', ''),
        'currency' => env('PALMPESA_CURRENCY', 'TZS'),
        'initiate_path' => env('PALMPESA_INITIATE_PATH', '/api/pay-via-mobile'),
        'status_path' => env('PALMPESA_STATUS_PATH', '/api/order-status'),
    ],

    'snippe' => [
        'enabled' => env('SNIPPE_ENABLED', true),
        'api_key' => env('SNIPPE_API_KEY', ''),
        'environment' => env('SNIPPE_ENVIRONMENT', 'production'),
        'base_url' => env('SNIPPE_BASE_URL', 'https://api.snippe.sh'),
        'webhook_url' => env('SNIPPE_WEBHOOK_URL', '/api/v1/webhooks/snippe'),
        'webhook_secret' => env('SNIPPE_WEBHOOK_SECRET', ''),
        'currency' => env('SNIPPE_CURRENCY', 'TZS'),
        'initiate_path' => env('SNIPPE_INITIATE_PATH', '/api/v1/charges/mobile-money'),
        'status_path' => env('SNIPPE_STATUS_PATH', '/api/v1/charges'),
    ],
];
