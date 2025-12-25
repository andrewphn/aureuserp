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

    'nutrient' => [
        'license_key'     => env('NUTRIENT_LICENSE_KEY'),
        'cloud_api_key'   => env('NUTRIENT_CLOUD_API_KEY'),
    ],

    'google' => [
        'places_api_key' => env('GOOGLE_PLACES_API_KEY'),
    ],

    'google_drive' => [
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_DRIVE_REDIRECT_URI'),
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
        'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
    ],

    'hubspot' => [
        'api_key' => env('HUBSPOT_API_KEY'),
        'access_token' => env('HUBSPOT_ACCESS_TOKEN'),
        'owner_id' => env('HUBSPOT_OWNER_ID'),
        'questionnaire_workflow_id' => env('HUBSPOT_QUESTIONNAIRE_WORKFLOW_ID'),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'leads' => [
        'notification_email' => env('LEAD_NOTIFICATION_EMAIL', 'info@tcswoodwork.com'),
        'api_key' => env('LEADS_API_KEY'),
    ],

    'scrapeops' => [
        'api_key' => env('SCRAPEOPS_API_KEY'),
    ],

];
