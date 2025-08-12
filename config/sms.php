<?php

use App\Services\InfoBipService;

return [
    'sender-name' => 'OddJobber',
    'driver' => env('SMS_DRIVER', 'infobip'),
    'providers' => [
        'infobip' => InfoBipService::class,
    ],
    'auth' => [
        'multitexter' => [
            'username' => env('EBULK_USERNAME'),
            'password' => env('EBULK_PASSWORD'),
        ],
        'infobip' => [
            'base_url' => env('INFOBIP_BASE_URL'),
            'otp_base_url' => env('OTP_INFOBIP_BASE_URL'),
            'api_url' => env('INFOBIZZ_SMS_API'),
            'api_key' => env('INFOBIP_API_KEY'),
            'sender' => env('INFOBIP_SENDER'),
            'application_id' => env('INFOBIP_REG_APP_ID'),
            'message_id' => env('INFOBIP_REG_MSG_ID'),
        ],

    ]
];
