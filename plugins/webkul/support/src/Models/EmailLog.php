<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Email Log Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $recipient_email
 * @property string|null $recipient_name
 * @property string|null $subject
 * @property string|null $status
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $sent_at
 *
 */
class EmailLog extends Model
{
    protected $fillable = [
        'recipient_email',
        'recipient_name',
        'subject',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
