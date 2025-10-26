<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Footer Version
    |--------------------------------------------------------------------------
    |
    | Control which version of the global footer is displayed.
    | 'v1' = Original Blade-only implementation
    | 'v2' = New FilamentPHP v4 compliant widget
    |
    | For staged rollout:
    | - Start with 'v1' (safe fallback)
    | - Test 'v2' on staging
    | - Gradually roll out 'v2' to production
    | - Remove 'v1' after successful migration
    |
    */
    'version' => env('FOOTER_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Enabled Context Types
    |--------------------------------------------------------------------------
    |
    | Define which context types are enabled for the global footer.
    | Plugins can add their own context types via service provider.
    |
    */
    'enabled_contexts' => [
        'project',
        'sale',
        'inventory',
        'production',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Minimized State
    |--------------------------------------------------------------------------
    |
    | Whether the footer should be minimized by default on page load.
    |
    */
    'default_minimized' => true,

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching for context data to improve performance.
    |
    */
    'cache' => [
        'enabled' => env('FOOTER_CACHE_ENABLED', true),
        'ttl' => env('FOOTER_CACHE_TTL', 300), // 5 minutes
        'prefix' => 'footer_context',
    ],

    /*
    |--------------------------------------------------------------------------
    | Context Persistence
    |--------------------------------------------------------------------------
    |
    | How long should context be persisted in sessionStorage (in milliseconds).
    | Default: 86400000 (24 hours)
    |
    */
    'context_ttl' => 86400000,

    /*
    |--------------------------------------------------------------------------
    | User Preferences
    |--------------------------------------------------------------------------
    |
    | Settings for user customization of footer fields.
    |
    */
    'preferences' => [
        'enabled' => true,
        'allow_field_reordering' => true,
        'allow_field_hiding' => true,
        'use_persona_templates' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugin Extensibility
    |--------------------------------------------------------------------------
    |
    | Allow plugins to register their own context providers.
    |
    */
    'allow_plugin_contexts' => true,

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | Configuration for API endpoints used by the footer.
    |
    */
    'api' => [
        'base_path' => '/api',
        'rate_limit' => 60, // requests per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable/disable specific features.
    |
    */
    'features' => [
        'tags' => true,
        'timeline_alerts' => true,
        'estimates' => true,
        'real_time_updates' => true,
        'save_button' => true,
    ],
];
