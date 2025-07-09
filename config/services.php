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

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'embedding_endpoint' => env('GEMINI_EMBEDDING_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/embedding-001:embedContent'),
        'query_endpoint' => env('GEMINI_QUERY_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent'),
        'completion_model' => env('GEMINI_COMPLETION_MODEL', 'models/gemini-1.5-flash'),
    ],

];
