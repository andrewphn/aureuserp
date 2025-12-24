<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Gemini API Key
    |--------------------------------------------------------------------------
    |
    | Here you may specify your Gemini API Key and organization. This will be
    | used to authenticate with the Gemini API - you can find your API key
    | on Google AI Studio, at https://aistudio.google.com/app/apikey.
    */

    'api_key' => env('GEMINI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Gemini Base URL
    |--------------------------------------------------------------------------
    |
    | If you need a specific base URL for the Gemini API, you can provide it here.
    | Otherwise, leave empty to use the default value.
    */
    'base_url' => env('GEMINI_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout may be used to specify the maximum number of seconds to wait
    | for a response. By default, the client will time out after 30 seconds.
    */

    'request_timeout' => env('GEMINI_REQUEST_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Default Model
    |--------------------------------------------------------------------------
    |
    | The default Gemini model to use for AI operations.
    */
    'default_model' => env('GEMINI_DEFAULT_MODEL', 'gemini-1.5-flash'),

    /*
    |--------------------------------------------------------------------------
    | Generation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AI text generation including token limits and creativity.
    */
    'max_tokens' => env('GEMINI_MAX_TOKENS', 2048),
    'temperature' => env('GEMINI_TEMPERATURE', 0.7),

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings for optimizing API performance and caching.
    */
    'enable_response_caching' => env('GEMINI_ENABLE_CACHING', true),
    'cache_ttl' => env('GEMINI_CACHE_TTL', 3600), // 1 hour
    'rate_limit_per_minute' => env('GEMINI_RATE_LIMIT', 60),
];
