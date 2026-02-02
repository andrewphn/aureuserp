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
    | For general tasks, use the flash model. For complex reasoning, use pro.
    */
    'default_model' => env('GEMINI_MODEL', env('GEMINI_DEFAULT_MODEL', 'gemini-2.5-flash')),

    /*
    |--------------------------------------------------------------------------
    | Model for Complex Tasks (Reasoning, Task Planning)
    |--------------------------------------------------------------------------
    |
    | The model to use for complex reasoning tasks like task generation.
    | This should be the most capable model available.
    */
    'reasoning_model' => env('GEMINI_REASONING_MODEL', 'gemini-2.5-pro'),

    /*
    |--------------------------------------------------------------------------
    | Model for Fast/Simple Tasks
    |--------------------------------------------------------------------------
    |
    | The model to use for simple, fast tasks where cost-efficiency matters.
    */
    'fast_model' => env('GEMINI_FAST_MODEL', 'gemini-2.5-flash-lite'),

    /*
    |--------------------------------------------------------------------------
    | Available Models (cached, updated via artisan command)
    |--------------------------------------------------------------------------
    |
    | Known good models - updated periodically.
    | Run `php artisan gemini:update-models` to refresh from API.
    */
    'available_models' => [
        // Gemini 2.5 (Current Best - as of 2025)
        'gemini-2.5-pro' => [
            'name' => 'Gemini 2.5 Pro',
            'description' => 'Most powerful model with adaptive thinking and deep reasoning',
            'use_case' => 'Complex reasoning, task planning, coding',
            'max_tokens' => 65536,
        ],
        'gemini-2.5-flash' => [
            'name' => 'Gemini 2.5 Flash',
            'description' => 'Fast and efficient for most tasks',
            'use_case' => 'General purpose, balanced speed/quality',
            'max_tokens' => 65536,
        ],
        'gemini-2.5-flash-lite' => [
            'name' => 'Gemini 2.5 Flash-Lite',
            'description' => 'Ultra-efficient for high-frequency tasks',
            'use_case' => 'Simple tasks, cost-sensitive applications',
            'max_tokens' => 8192,
        ],
        // Gemini 2.0 (Legacy - retiring March 2026)
        'gemini-2.0-flash' => [
            'name' => 'Gemini 2.0 Flash',
            'description' => 'Multimodal performance (retiring March 2026)',
            'use_case' => 'Legacy support',
            'max_tokens' => 8192,
            'deprecated' => true,
        ],
        // Gemini 1.5 (Deprecated)
        'gemini-1.5-flash' => [
            'name' => 'Gemini 1.5 Flash',
            'description' => 'Deprecated - use 2.5 models instead',
            'use_case' => 'Legacy support only',
            'max_tokens' => 8192,
            'deprecated' => true,
        ],
        'gemini-1.5-pro' => [
            'name' => 'Gemini 1.5 Pro',
            'description' => 'Deprecated - use 2.5 models instead',
            'use_case' => 'Legacy support only',
            'max_tokens' => 8192,
            'deprecated' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Generation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AI text generation including token limits and creativity.
    */
    'max_tokens' => env('GEMINI_MAX_TOKENS', 8192),
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
