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
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        // Google Cloud project ID
        'project_id' => env('GOOGLE_PROJECT_ID'),

        // API key for Document AI and Vision API
        'vision_api_key' => env('GOOGLE_VISION_API_KEY'),

        // Document AI processor ID (create in Google Cloud Console)
        'document_ai_processor_id' => env('GOOGLE_DOCUMENT_AI_PROCESSOR_ID'),

        // Location: 'us' (default) or 'eu'
        'location' => env('GOOGLE_LOCATION', 'us'),
    ],

];
