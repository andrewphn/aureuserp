<?php

namespace Webkul\TimeOff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\TimeOff\Enums\LeaveValidationType;

/**
 * Leave Type Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $sort
 * @property string|null $color
 * @property int $company_id
 * @property string|null $max_allowed_negative
 * @property int $creator_id
 * @property mixed $leave_validation_type
 * @property string|null $requires_allocation
 * @property string|null $employee_requests
 * @property string|null $allocation_validation_type
 * @property string|null $time_type
 * @property string|null $request_unit
 * @property string|null $name
 * @property string|null $create_calendar_meeting
 * @property bool $is_active
 * @property string|null $show_on_dashboard
 * @property string|null $unpaid
 * @property string|null $include_public_holidays_in_duration
 * @property string|null $support_document
 * @property string|null $allows_negative
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection $notifiedTimeOffOfficers
 *
 */
class LeaveType extends Model implements Sortable
{
    use HasFactory, SoftDeletes, SortableTrait;

    protected $table = 'time_off_leave_types';

    protected $fillable = [
        'sort',
        'color',
        'company_id',
        'max_allowed_negative',
        'creator_id',
        'leave_validation_type',
        'requires_allocation',
        'employee_requests',
        'allocation_validation_type',
        'time_type',
        'request_unit',
        'name',
        'create_calendar_meeting',
        'is_active',
        'show_on_dashboard',
        'unpaid',
        'include_public_holidays_in_duration',
        'support_document',
        'allows_negative',
    ];

    protected $casts = [
        'leave_validation_type' => LeaveValidationType::class,
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function notifiedTimeOffOfficers()
    {
        return $this->belongsToMany(User::class, 'time_off_user_leave_types', 'leave_type_id', 'user_id');
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
