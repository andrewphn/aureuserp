<?php

return [
    'gmail' => [
        'user_id' => env('RECEIPT_GMAIL_USER_ID', 'me'),
        'senders_allowlist' => array_filter(array_map('trim', explode(',', env('RECEIPT_GMAIL_SENDERS', '')))),
        'subject_keywords' => array_filter(array_map('trim', explode(',', env('RECEIPT_GMAIL_SUBJECT_KEYWORDS', 'receipt,invoice,order,statement')))),
        'max_age_days' => (int) env('RECEIPT_GMAIL_MAX_AGE_DAYS', 30),
        'max_messages' => (int) env('RECEIPT_GMAIL_MAX_MESSAGES', 50),
    ],
    'storage_disk' => env('RECEIPT_STORAGE_DISK'),
    'storage_path' => env('RECEIPT_STORAGE_PATH'),
];
