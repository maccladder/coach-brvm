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

    'did' => [
        'api_key'           => env('DID_API_KEY'),
        'base_url'          => env('DID_BASE_URL', 'https://api.d-id.com'),
        'avatar_source_url' => env('AVATAR_SOURCE_URL'),
        'voice_provider'    => env('DID_VOICE_PROVIDER', 'microsoft'),
        'voice_id'          => env('DID_VOICE_ID', 'fr-FR-HenriNeural'),
    ],

    'paystack' => [
        'secret'   => env('PAYSTACK_SECRET_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    ],

    'n8n' => [
        'api_key' => env('N8N_API_KEY'),
    ],

    'comparateur' => [
        'cle' => env('COMPARATEUR_N8N_CLE'),
    ],

    'bet' => [
        'token' => env('BET_API_TOKEN'),
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
        'tts_model'       => env('OPENAI_TTS_MODEL', 'gpt-4o-mini-tts'),
        'tts_voice'       => env('OPENAI_TTS_VOICE', 'alloy'),
        'dividende_model' => env('OPENAI_DIVIDENDE_MODEL', 'gpt-4.1-mini'),
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



    'google_analytics' => [
        'credentials_path' => env('GA_CREDENTIALS_PATH'),
        'property_id'      => env('GA_PROPERTY_ID'),
    ],

    'twilio' => [
        'account_sid'        => env('TWILIO_ACCOUNT_SID'),
        'auth_token'         => env('TWILIO_AUTH_TOKEN'),
        'verify_service_sid' => env('TWILIO_VERIFY_SERVICE_SID'),
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

    'whatsapp' => [
        'support_number' => env('WHATSAPP_SUPPORT_NUMBER'),
    ],

];
