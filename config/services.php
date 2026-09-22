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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'payment_links' => [
            'wedding' => [
                env('MIDTRANS_WEDDING_4_HOURS_PAYMENT_LINK', env('MIDTRANS_WEDDING_PAYMENT_LINK')),
                env('MIDTRANS_WEDDING_6_HOURS_PAYMENT_LINK', env('MIDTRANS_WEDDING_PAYMENT_LINK')),
                env('MIDTRANS_WEDDING_8_HOURS_PAYMENT_LINK', env('MIDTRANS_WEDDING_PAYMENT_LINK')),
            ],
            'reservation' => [
                env('MIDTRANS_RESERVATION_3_HOURS_PAYMENT_LINK'),
                env('MIDTRANS_RESERVATION_4_HOURS_PAYMENT_LINK'),
                env('MIDTRANS_RESERVATION_4_PLUS_1_HOURS_PAYMENT_LINK'),
            ],
            'unlimited' => [
                env('MIDTRANS_UNLIMITED_2_HOURS_PAYMENT_LINK'),
                env('MIDTRANS_UNLIMITED_3_HOURS_PAYMENT_LINK'),
                env('MIDTRANS_UNLIMITED_4_HOURS_PAYMENT_LINK'),
            ],
        ],
    ],

];
