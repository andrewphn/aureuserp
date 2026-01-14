<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GmailReceiptImport Model
 *
 * Tracks Gmail receipt import processing and deduplication.
 */
class GmailReceiptImport extends Model
{
    protected $table = 'gmail_receipt_imports';

    protected $fillable = [
        'message_id',
        'thread_id',
        'attachment_id',
        'attachment_filename',
        'received_at',
        'status',
        'scan_log_id',
        'error_message',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function scanLog(): BelongsTo
    {
        return $this->belongsTo(DocumentScanLog::class, 'scan_log_id');
    }
}
