<?php

namespace Webkul\Chatter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Attachment Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $company_id
 * @property int $creator_id
 * @property int $message_id
 * @property string|null $file_size
 * @property string|null $name
 * @property string|null $messageable
 * @property string|null $file_path
 * @property string|null $original_file_name
 * @property string|null $mime_type
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $message
 * @property-read \Illuminate\Database\Eloquent\Model|null $messageable
 *
 */
class Attachment extends Model
{
    protected $table = 'chatter_attachments';

    protected $fillable = [
        'company_id',
        'creator_id',
        'message_id',
        'file_size',
        'name',
        'messageable',
        'file_path',
        'original_file_name',
        'mime_type',
    ];

    protected $appends = ['url'];

    /**
     * Messageable
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function messageable()
    {
        return $this->morphTo();
    }

    /**
     * Created By
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Message
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function message()
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    public static function boot()
    {
        parent::boot();

        static::deleted(function ($attachment) {
            $filePath = $attachment->file_path;

            if (
                $filePath
                && Storage::disk('public')->exists($filePath)
            ) {
                Storage::disk('public')->delete($filePath);
            }
        });
    }
}
