<?php

namespace Webkul\TimeOff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Security\Models\User;

/**
 * Leave Accrual Level Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $sort
 * @property int $accrual_plan_id
 * @property float $start_count
 * @property string|null $first_day
 * @property string|null $second_day
 * @property string|null $first_month_day
 * @property string|null $second_month_day
 * @property string|null $yearly_day
 * @property string|null $postpone_max_days
 * @property float $accrual_validity_count
 * @property int $creator_id
 * @property string|null $start_type
 * @property string|null $added_value_type
 * @property string|null $frequency
 * @property string|null $week_day
 * @property string|null $first_month
 * @property string|null $second_month
 * @property string|null $yearly_month
 * @property string|null $action_with_unused_accruals
 * @property string|null $accrual_validity_type
 * @property string|null $added_value
 * @property string|null $maximum_leave
 * @property string|null $maximum_leave_yearly
 * @property string|null $cap_accrued_time
 * @property string|null $cap_accrued_time_yearly
 * @property string|null $accrual_validity
 * @property-read \Illuminate\Database\Eloquent\Model|null $accrualPlan
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class LeaveAccrualLevel extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    protected $table = 'time_off_leave_accrual_levels';

    protected $fillable = [
        'sort',
        'accrual_plan_id',
        'start_count',
        'first_day',
        'second_day',
        'first_month_day',
        'second_month_day',
        'yearly_day',
        'postpone_max_days',
        'accrual_validity_count',
        'creator_id',
        'start_type',
        'added_value_type',
        'frequency',
        'week_day',
        'first_month',
        'second_month',
        'yearly_month',
        'action_with_unused_accruals',
        'accrual_validity_type',
        'added_value',
        'maximum_leave',
        'maximum_leave_yearly',
        'cap_accrued_time',
        'cap_accrued_time_yearly',
        'accrual_validity',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Accrual Plan
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accrualPlan()
    {
        return $this->belongsTo(LeaveAccrualPlan::class, 'accrual_plan_id');
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
}
