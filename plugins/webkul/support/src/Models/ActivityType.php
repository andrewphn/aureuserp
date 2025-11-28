<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Security\Models\User;

/**
 * Activity Type Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $sort
 * @property float $delay_count
 * @property string|null $delay_unit
 * @property string|null $delay_from
 * @property string|null $icon
 * @property string|null $decoration_type
 * @property string|null $chaining_type
 * @property string|null $plugin
 * @property string|null $category
 * @property string|null $name
 * @property string|null $summary
 * @property string|null $default_note
 * @property bool $is_active
 * @property bool $keep_done
 * @property int $creator_id
 * @property int $default_user_id
 * @property int $activity_plan_id
 * @property int $triggered_next_type_id
 * @property-read \Illuminate\Database\Eloquent\Collection $activityTypes
 * @property-read \Illuminate\Database\Eloquent\Model|null $activityPlan
 * @property-read \Illuminate\Database\Eloquent\Model|null $triggeredNextType
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $defaultUser
 * @property-read \Illuminate\Database\Eloquent\Collection $suggestedActivityTypes
 *
 */
class ActivityType extends Model implements Sortable
{
    use HasFactory, SoftDeletes, SortableTrait;

    protected $table = 'activity_types';

    protected $fillable = [
        'sort',
        'delay_count',
        'delay_unit',
        'delay_from',
        'icon',
        'decoration_type',
        'chaining_type',
        'plugin',
        'category',
        'name',
        'summary',
        'default_note',
        'is_active',
        'keep_done',
        'creator_id',
        'default_user_id',
        'activity_plan_id',
        'triggered_next_type_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'keep_done' => 'boolean',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Activity Plan
     *
     * @return BelongsTo
     */
    public function activityPlan(): BelongsTo
    {
        return $this->belongsTo(ActivityPlan::class, 'activity_plan_id');
    }

    /**
     * Triggered Next Type
     *
     * @return BelongsTo
     */
    public function triggeredNextType(): BelongsTo
    {
        return $this->belongsTo(self::class, 'triggered_next_type_id');
    }

    /**
     * Activity Types
     *
     * @return HasMany
     */
    public function activityTypes(): HasMany
    {
        return $this->hasMany(self::class, 'triggered_next_type_id');
    }

    /**
     * Suggested Activity Types
     *
     * @return BelongsToMany
     */
    public function suggestedActivityTypes(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'activity_type_suggestions', 'activity_type_id', 'suggested_activity_type_id');
    }

    /**
     * Created By
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Default User
     *
     * @return BelongsTo
     */
    public function defaultUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_user_id');
    }
}
