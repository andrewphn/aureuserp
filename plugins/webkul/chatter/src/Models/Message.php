<?php

namespace Webkul\Chatter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Webkul\Security\Models\User;
use Webkul\Support\Models\ActivityType;
use Webkul\Support\Models\Company;

/**
 * Message Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $company_id
 * @property int $activity_type_id
 * @property string|null $messageable_type
 * @property int $messageable_id
 * @property string|null $type
 * @property string|null $name
 * @property string|null $subject
 * @property string|null $body
 * @property string|null $summary
 * @property bool $is_internal
 * @property \Carbon\Carbon|null $date_deadline
 * @property \Carbon\Carbon|null $pinned_at
 * @property string|null $log_name
 * @property string|null $event
 * @property string|null $assigned_to
 * @property string|null $causer_type
 * @property int $causer_id
 * @property array $properties
 * @property-read \Illuminate\Database\Eloquent\Collection $attachments
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $activityType
 * @property-read \Illuminate\Database\Eloquent\Model|null $assignedTo
 * @property-read \Illuminate\Database\Eloquent\Model|null $messageable
 * @property-read \Illuminate\Database\Eloquent\Model|null $causer
 *
 */
class Message extends Model
{
    protected $table = 'chatter_messages';

    protected $fillable = [
        'company_id',
        'activity_type_id',
        'messageable_type',
        'messageable_id',
        'type',
        'name',
        'subject',
        'body',
        'summary',
        'is_internal',
        'date_deadline',
        'pinned_at',
        'log_name',
        'event',
        'assigned_to',
        'causer_type',
        'causer_id',
        'properties',
    ];

    protected $casts = [
        'properties'    => 'array',
        'date_deadline' => 'date',
    ];

    /**
     * Messageable
     *
     * @return MorphTo
     */
    public function messageable(): MorphTo
    {
        return $this->morphTo();
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

    /**
     * Activity Type
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function activityType()
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    /**
     * Causer
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function causer()
    {
        return $this->morphTo();
    }

    /**
     * Assigned To
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Set the Properties attribute
     *
     * @param mixed $value The value to set
     * @return void
     */
    public function setPropertiesAttribute($value)
    {
        $this->attributes['properties'] = json_encode($value);
    }

    /**
     * Boot
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        $user = filament()->auth()->user();

        if ($user) {
            static::creating(function ($data) use ($user) {
                DB::transaction(function () use ($data, $user) {
                    $data->causer_type = $user->getMorphClass();
                    $data->causer_id = $user->id;
                });
            });

            static::updating(function ($data) use ($user) {
                $data->causer_type = $user->getMorphClass();
                $data->causer_id = $user->id;
            });
        }
    }

    /**
     * Attachments
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'message_id');
    }
}
