<?php

declare(strict_types=1);

return [
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

    // SmartLocal — SMS gateway adapter selection.
    'sms' => [
        'driver' => env('SMS_DRIVER', 'fake'), // fake | kavenegar
        'kavenegar_key' => env('KAVENEGAR_API_KEY', ''),
    ],

    // SmartLocal — payment gateway adapter selection.
    'payment' => [
        'driver' => env('PAYMENT_DRIVER', 'fake'), // fake | zarinpal
        'zarinpal_merchant' => env('ZARINPAL_MERCHANT_ID', ''),
        'callback_url' => env('PAYMENT_CALLBACK_URL', 'http://localhost:8080/api/v1/payment/callback'),
    ],
];
