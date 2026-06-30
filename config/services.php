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

    'ai_service' => [
        'base_url' => env('AI_SERVICE_URL', 'http://127.0.0.1:8001'),
        'timeout' => env('AI_SERVICE_TIMEOUT', 300),
        'matching_timeout' => env('AI_MATCHING_TIMEOUT', 70),
        'parse_timeout' => env('AI_PARSE_TIMEOUT', 60),
        'generation_timeout' => env('AI_GENERATION_TIMEOUT', 300),
        'chat_timeout' => env('AI_CHAT_TIMEOUT', 300),
        'stream_timeout' => env('AI_STREAM_TIMEOUT', 360),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'momo' => [
        'base_url' => env('MOMO_BASE_URL', 'https://test-payment.momo.vn'),
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'partner_name' => env('MOMO_PARTNER_NAME', env('APP_NAME', 'KhanhMai')),
        'store_id' => env('MOMO_STORE_ID', 'KhanhMaiStore'),
        'access_key' => env('MOMO_ACCESS_KEY'),
        'secret_key' => env('MOMO_SECRET_KEY'),
        'request_type' => env('MOMO_REQUEST_TYPE', 'captureWallet'),
        'lang' => env('MOMO_LANG', 'vi'),
        'timeout' => env('MOMO_TIMEOUT', 30),
        'redirect_url' => env('MOMO_REDIRECT_URL'),
        'ipn_url' => env('MOMO_IPN_URL'),
        'min_amount' => env('MOMO_MIN_AMOUNT', 1000),
        'pending_expire_minutes' => env('MOMO_PENDING_EXPIRE_MINUTES', 15),
        'auto_complete_return_local' => env('MOMO_AUTO_COMPLETE_RETURN_LOCAL', env('APP_ENV') === 'local'),
    ],

    'vnpay' => [
        'base_url' => env('VNPAY_BASE_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'tmn_code' => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
        'return_url' => env('VNPAY_RETURN_URL'),
        'ipn_url' => env('VNPAY_IPN_URL'),
        'locale' => env('VNPAY_LOCALE', 'vn'),
        'order_type' => env('VNPAY_ORDER_TYPE', 'other'),
        'min_amount' => env('VNPAY_MIN_AMOUNT', 1000),
        'pending_expire_minutes' => env('VNPAY_PENDING_EXPIRE_MINUTES', 15),
        'auto_complete_return_local' => env('VNPAY_AUTO_COMPLETE_RETURN_LOCAL', env('APP_ENV') === 'local'),
    ],

];
