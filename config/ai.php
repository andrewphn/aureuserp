<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Document Scanner Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control the behavior of the AI-powered document scanner
    | used for processing invoices, packing slips, and product labels.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Confidence Thresholds
    |--------------------------------------------------------------------------
    |
    | scan_confidence_threshold: Minimum confidence score (0-1) required for
    | a scan result to be considered acceptable. Results below this threshold
    | will be flagged for manual review.
    |
    | scan_auto_apply_threshold: Minimum confidence score (0-1) required for
    | scan results to be automatically applied without user confirmation.
    | Should be higher than the confidence threshold.
    |
    */

    'scan_confidence_threshold' => env('AI_SCAN_CONFIDENCE_THRESHOLD', 0.70),

    'scan_auto_apply_threshold' => env('AI_SCAN_AUTO_APPLY_THRESHOLD', 0.95),

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | scan_logging_enabled: Whether to log all scan attempts to the database.
    | Recommended to keep enabled for audit purposes.
    |
    | scan_log_retention_days: Number of days to keep scan logs before
    | automatic cleanup (0 = keep forever).
    |
    */

    'scan_logging_enabled' => env('AI_SCAN_LOGGING_ENABLED', true),

    'scan_log_retention_days' => env('AI_SCAN_LOG_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | File Storage
    |--------------------------------------------------------------------------
    |
    | scan_file_storage_disk: The disk to use for storing scanned documents.
    |
    | scan_file_storage_path: The path within the disk for storing files.
    |
    */

    'scan_file_storage_disk' => env('AI_SCAN_STORAGE_DISK', 'local'),

    'scan_file_storage_path' => env('AI_SCAN_STORAGE_PATH', 'document-scans'),

    /*
    |--------------------------------------------------------------------------
    | Gemini AI Configuration
    |--------------------------------------------------------------------------
    |
    | These settings are for the Google Gemini AI service integration.
    |
    */

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-pro-vision'),
        'max_tokens' => env('GEMINI_MAX_TOKENS', 4096),
        'temperature' => env('GEMINI_TEMPERATURE', 0.1),
    ],

];
