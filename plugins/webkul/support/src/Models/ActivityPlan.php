<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Security\Models\User;

/**
 * Activity Plan Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $company_id
 * @property string|null $plugin
 * @property int $creator_id
 * @property string|null $name
 * @property bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection $activityTypes
 * @property-read \Illuminate\Database\Eloquent\Collection $activityPlanTemplates
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class ActivityPlan extends Model
{
    use HasCustomFields, HasFactory, SoftDeletes;

    protected $table = 'activity_plans';

    protected $fillable = [
        'company_id',
        'plugin',
        'creator_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Company
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
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
     * Activity Types
     *
     * @return HasMany
     */
    public function activityTypes(): HasMany
    {
        return $this->hasMany(ActivityType::class, 'activity_plan_id');
    }

    /**
     * Activity Plan Templates
     *
     * @return HasMany
     */
    public function activityPlanTemplates(): HasMany
    {
        return $this->hasMany(ActivityPlanTemplate::class, 'plan_id');
    }
}
