<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nutrient Web SDK License Key
    |--------------------------------------------------------------------------
    |
    | Your Nutrient (formerly PSPDFKit) license key for production use.
    | Free tier available for businesses with <20 employees and <$1M revenue.
    |
    */

    'license_key' => env('NUTRIENT_LICENSE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default Toolbar Configuration
    |--------------------------------------------------------------------------
    |
    | Default toolbar items to display in the PDF viewer.
    | Can be customized per-viewer instance.
    |
    */

    'default_toolbar' => [
        'sidebar',
        'zoom-in',
        'zoom-out',
        'zoom-mode',
        'spacer',
        'search',
        'print',
        'download',
        'spacer',
        'annotate',
        'text-highlighter',
        'ink',
        'note',
        'arrow',
        'line',
        'rectangle',
        'ellipse',
        'stamp',
    ],

    /*
    |--------------------------------------------------------------------------
    | Annotation Settings
    |--------------------------------------------------------------------------
    |
    | Global annotation configuration
    |
    */

    'enable_annotations' => true,
    'enable_forms' => true,
    'enable_measuring_tools' => false,

    /*
    |--------------------------------------------------------------------------
    | File Upload Restrictions
    |--------------------------------------------------------------------------
    |
    | Maximum file size in bytes and allowed MIME types
    |
    */

    'max_file_size' => 50 * 1024 * 1024, // 50MB

    'allowed_mime_types' => [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.openxmlformats-officedocument.presentationml.presentation', // .pptx
    ],

    'allowed_extensions' => [
        'pdf',
        'docx',
        'xlsx',
        'pptx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Autosave Configuration
    |--------------------------------------------------------------------------
    |
    | Interval in seconds for automatic annotation saving
    |
    */

    'autosave_interval' => 30,

    /*
    |--------------------------------------------------------------------------
    | Viewer Options
    |--------------------------------------------------------------------------
    |
    | Default viewer configuration options
    |
    */

    'viewer_options' => [
        'theme' => 'auto', // 'light', 'dark', or 'auto'
        'initial_view_state' => [
            'zoom' => 'auto',
            'scrollMode' => 'continuous',
            'layoutMode' => 'single',
        ],
        'enable_history' => true,
        'enable_clipboard' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Cache and performance optimization settings
    |
    */

    'enable_web_assembly' => true,
    'enable_service_worker' => false,
    'cache_documents' => true,

];
