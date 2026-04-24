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

    'paystack' => [
        'secret'   => env('PAYSTACK_SECRET_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    ],

    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
        'model'   => env('CLAUDE_MODEL', 'claude-haiku-4-5-20251001'),
        'url'     => env('CLAUDE_API_URL', 'https://api.anthropic.com/v1/messages'),
    ],

    'lettreci' => [
        'price'    => (int) env('LETTRECI_PRICE', 5000),
        'currency' => env('LETTRECI_CURRENCY', 'XOF'),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),

        // modèles séparés (comme ton .env)
        'boc_model'    => env('OPENAI_BOC_MODEL', 'gpt-4.1-mini'),
        'market_model' => env('OPENAI_MARKET_MODEL', 'gpt-4.1-mini'),
        'bubble_model' => env('OPENAI_BUBBLE_MODEL', 'gpt-4.1-mini'),
    ],

    'tawk' => [
    'widget_id' => env('TAWK_TO_WIDGET_ID'),
],

'cloudflare_stream' => [
    'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
    'token' => env('CLOUDFLARE_STREAM_TOKEN'),
    'customer_subdomain' => env('CLOUDFLARE_STREAM_CUSTOMER_SUBDOMAIN'),
    'signed_exp' => (int) env('CLOUDFLARE_STREAM_SIGNED_EXP', 3600),
],

'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],



    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

];
