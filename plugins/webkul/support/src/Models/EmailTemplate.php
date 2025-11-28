<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Email Template Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $code
 * @property string|null $name
 * @property string|null $subject
 * @property string|null $content
 * @property string|null $description
 * @property bool $is_active
 * @property string|null $sender_name
 *
 */
class EmailTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'subject',
        'content',
        'description',
        'is_active',
        'sender_name',

    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];
}
