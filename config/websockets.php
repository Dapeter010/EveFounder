<?php
// config/websockets.php (Laravel Backend)
// Update for production deployment

return [
    'dashboard' => [
        'port' => env('LARAVEL_WEBSOCKETS_PORT', 6001),
    ],

    'apps' => [
        [
            'id' => env('PUSHER_APP_ID'),
            'name' => env('APP_NAME'),
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'path' => env('PUSHER_APP_PATH'),
            'capacity' => null,
            'enable_client_messages' => false,
            'enable_statistics' => true,
        ],
    ],

    'app_provider' => BeyondCode\LaravelWebSockets\Apps\ConfigAppProvider::class,

    // IMPORTANT: Add your production domains
    'allowed_origins' => [
        'https://evefound.com',
        'https://www.evefound.com',
        'https://server.evefound.com',
        'http://localhost:3000', // Keep for local development
    ],

    'max_request_size_in_kb' => 250,

    'path' => 'laravel-websockets',

    'middleware' => [
        'web',
    ],

    'statistics' => [
        'model' => \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry::class,
        'logger' => BeyondCode\LaravelWebSockets\Statistics\Logger\HttpStatisticsLogger::class,
        'interval_in_seconds' => 60,
        'delete_statistics_older_than_days' => 60,
        'perform_dns_lookup' => false,
    ],

    // SSL Configuration for production
    'ssl' => [
        'local_cert' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT', null),
        'local_pk' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_PK', null),
        'passphrase' => env('LARAVEL_WEBSOCKETS_SSL_PASSPHRASE', null),
    ],

    'channel_manager' => \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManagers\ArrayChannelManager::class,
];
