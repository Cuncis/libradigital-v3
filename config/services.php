<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mayar' => [
        'api_key' => env('MAYAR_API_KEY'),
        'webhook_token' => env('MAYAR_WEBHOOK_TOKEN'),
        'is_production' => env('MAYAR_IS_PRODUCTION', false),
        // Override only if Mayar's sandbox/production hosts change; otherwise
        // this is derived from is_production (api.mayar.id vs api.mayar.club).
        'base_url' => env('MAYAR_API_BASE'),
        // The single Mayar membership product covering all local Plan tiers.
        'product_id' => env('MAYAR_PRODUCT_ID'),
    ],

];
