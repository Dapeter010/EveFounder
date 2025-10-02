<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

// IMPORTANT: Update with your production domains
    'allowed_origins' => [
        'https://evefound.com',
        'https://www.evefound.com',
        'https://server.evefound.com',
        'https://www.server.evefound.com',
        'http://localhost:3000', // Keep for local development
        'http://localhost:*',
        'http://127.0.0.1:*',
        'http://10.0.2.2:*',  // Android emulator
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
