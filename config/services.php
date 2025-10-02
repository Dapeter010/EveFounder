<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Existing OS service config
    'os' => [
        'api_key' => env('OS_API_KEY'),
        'base_url' => env('OS_BASE_URL'),
        'api_secret' => env('OS_API_SECRET'),
    ],

    // New getAddress.io service config
    'getaddress' => [
        'id_url' => env('GETADDRESS_ID_URL'),
        'api_key' => env('GETADDRESS_API_KEY'),
        'endpoint' => env('ADDRESS_ENDPOINT'),
    ],

    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'anon_key' => env('SUPABASE_ANON_KEY'),
        'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'webhook_secret' => env('SUPABASE_WEBHOOK_SECRET'),
        'allowed_webhook_ips' => explode(',', env('SUPABASE_WEBHOOK_ALLOWED_IPS', '')),
    ],

    'firebase' => [
        'server_key' => env('FIREBASE_SERVER_KEY'),
    ],
];
