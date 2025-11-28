<?php

namespace Webkul\TimeOff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\TimeOff\Enums\AccruedGainTime;
use Webkul\TimeOff\Enums\CarryoverDate;
use Webkul\TimeOff\Enums\CarryoverDay;
use Webkul\TimeOff\Enums\CarryoverMonth;

/**
 * Leave Accrual Plan Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $time_off_type_id
 * @property int $company_id
 * @property mixed $carryover_day
 * @property int $creator_id
 * @property string|null $name
 * @property string|null $transition_mode
 * @property mixed $accrued_gain_time
 * @property mixed $carryover_date
 * @property mixed $carryover_month
 * @property string|null $added_value_type
 * @property bool $is_active
 * @property bool $is_based_on_worked_time
 * @property-read \Illuminate\Database\Eloquent\Collection $leaveAccrualLevels
 * @property-read \Illuminate\Database\Eloquent\Model|null $timeOffType
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class LeaveAccrualPlan extends Model
{
    use HasFactory;

    protected $table = 'time_off_leave_accrual_plans';

    protected $fillable = [
        'time_off_type_id',
        'company_id',
        'carryover_day',
        'creator_id',
        'name',
        'transition_mode',
        'accrued_gain_time',
        'carryover_date',
        'carryover_month',
        'added_value_type',
        'is_active',
        'is_based_on_worked_time',
    ];

    protected $casts = [
        'accrued_gain_time' => AccruedGainTime::class,
        'carryover_day'     => CarryoverDay::class,
        'carryover_month'   => CarryoverMonth::class,
        'carryover_date'    => CarryoverDate::class,
    ];

    /**
     * Time Off Type
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function timeOffType()
    {
        return $this->belongsTo(LeaveType::class, 'time_off_type_id');
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
     * Created By
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Leave Accrual Levels
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function leaveAccrualLevels()
    {
        return $this->hasMany(LeaveAccrualLevel::class, 'accrual_plan_id');
    }
}
