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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    'mistral' => [
        'api_key' => env('MISTRAL_API_KEY'),
        'model' => env('MISTRAL_MODEL', 'mistral-small-latest'),
        'base_url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1'),
    ],

    'minimax' => [
        'api_key' => env('MINIMAX_API_KEY'),
        'model' => env('MINIMAX_MODEL', 'MiniMax-M3'),
        'base_url' => env('MINIMAX_BASE_URL', 'https://api.minimax.io/anthropic'),
    ],

    'glm' => [
        'api_key' => env('GLM_API_KEY'),
        'model' => env('GLM_MODEL', 'glm-4.5-air'),
        'base_url' => env('GLM_BASE_URL', 'https://api.z.ai/api/paas/v4'),
    ],

    'jina' => [
        'api_key' => env('JINA_API_KEY'),
        'base_url' => env('JINA_BASE_URL', 'https://r.jina.ai'),
        'timeout' => (int) env('JINA_TIMEOUT', 60),
    ],

    'stripe' => [
        'model' => env('STRIPE_MODEL'),
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
    ],

];
