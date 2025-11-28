<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Security\Models\User;

/**
 * Activity Plan Template Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $sort
 * @property int $plan_id
 * @property int $activity_type_id
 * @property int $responsible_id
 * @property int $creator_id
 * @property float $delay_count
 * @property string|null $delay_unit
 * @property string|null $delay_from
 * @property string|null $summary
 * @property string|null $responsible_type
 * @property string|null $note
 * @property-read \Illuminate\Database\Eloquent\Model|null $activityPlan
 * @property-read \Illuminate\Database\Eloquent\Model|null $activityType
 * @property-read \Illuminate\Database\Eloquent\Model|null $responsible
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $assignedUser
 *
 */
class ActivityPlanTemplate extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    protected $table = 'activity_plan_templates';

    protected $fillable = [
        'sort',
        'plan_id',
        'activity_type_id',
        'responsible_id',
        'creator_id',
        'delay_count',
        'delay_unit',
        'delay_from',
        'summary',
        'responsible_type',
        'note',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Relationships
     */
    public function activityPlan(): BelongsTo
    {
        return $this->belongsTo(ActivityPlan::class, 'plan_id');
    }

    /**
     * Activity Type
     *
     * @return BelongsTo
     */
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    /**
     * Responsible
     *
     * @return BelongsTo
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    /**
     * Created By
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Assigned User
     *
     * @return BelongsTo
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
