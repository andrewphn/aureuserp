<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Activity Type Suggestion Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $activity_type_id
 * @property int $suggested_activity_type_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $activityType
 * @property-read \Illuminate\Database\Eloquent\Model|null $suggestedActivityType
 *
 */
class ActivityTypeSuggestion extends Model
{
    protected $table = 'activity_type_suggestions';

    public $timestamps = false;

    protected $fillable = [
        'activity_type_id',
        'suggested_activity_type_id',
    ];

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
     * Suggested Activity Type
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function suggestedActivityType()
    {
        return $this->belongsTo(ActivityType::class, 'suggested_activity_type_id');
    }
}
