<?php

namespace Webkul\TimeOff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Employee\Models\Calendar;
use Webkul\Employee\Models\Department;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\TimeOff\Enums\RequestDateFromPeriod;
use Webkul\TimeOff\Enums\State;

/**
 * Leave Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $user_id
 * @property int $manager_id
 * @property int $holiday_status_id
 * @property int $employee_id
 * @property int $employee_company_id
 * @property int $company_id
 * @property int $department_id
 * @property int $calendar_id
 * @property int $meeting_id
 * @property int $first_approver_id
 * @property int $second_approver_id
 * @property int $creator_id
 * @property string|null $private_name
 * @property string|null $attachment
 * @property mixed $state
 * @property string|null $duration_display
 * @property mixed $request_date_from_period
 * @property string|null $request_date_from
 * @property string|null $request_date_to
 * @property string|null $notes
 * @property string|null $request_unit_half
 * @property float $request_unit_hours
 * @property string|null $date_from
 * @property string|null $date_to
 * @property string|null $number_of_days
 * @property float $number_of_hours
 * @property string|null $request_hour_from
 * @property string|null $request_hour_to
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $manager
 * @property-read \Illuminate\Database\Eloquent\Model|null $holidayStatus
 * @property-read \Illuminate\Database\Eloquent\Model|null $employee
 * @property-read \Illuminate\Database\Eloquent\Model|null $employeeCompany
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $department
 * @property-read \Illuminate\Database\Eloquent\Model|null $calendar
 * @property-read \Illuminate\Database\Eloquent\Model|null $firstApprover
 * @property-read \Illuminate\Database\Eloquent\Model|null $secondApprover
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class Leave extends Model
{
    use HasChatter, HasFactory, HasLogActivity;

    protected $table = 'time_off_leaves';

    protected $fillable = [
        'user_id',
        'manager_id',
        'holiday_status_id',
        'employee_id',
        'employee_company_id',
        'company_id',
        'department_id',
        'calendar_id',
        'meeting_id',
        'first_approver_id',
        'second_approver_id',
        'creator_id',
        'private_name',
        'attachment',
        'state',
        'duration_display',
        'request_date_from_period',
        'request_date_from',
        'request_date_to',
        'notes',
        'request_unit_half',
        'request_unit_hours',
        'date_from',
        'date_to',
        'number_of_days',
        'number_of_hours',
        'request_hour_from',
        'request_hour_to',
    ];

    protected array $logAttributes = [
        'user.name'                => 'User',
        'manger.name'              => 'Manager',
        'holidayStatus.name'       => 'Holiday Status',
        'employee.name'            => 'Employee',
        'employeeCompany.name'     => 'Employee Company',
        'department.name'          => 'Department',
        'calendar.name'            => 'Calendar',
        'firstApprover.name'       => 'First Approver',
        'lastApprover.name'        => 'Last Approver',
        'private_name'             => 'Description',
        'state'                    => 'State',
        'duration_display'         => 'Duration Display',
        'request_date_from_period' => 'Request Date From Period',
        'request_date_from'        => 'Request Date From',
        'request_date_to'          => 'Request Date To',
        'notes'                    => 'Notes',
        'request_unit_half'        => 'Request Unit Half',
        'request_unit_hours'       => 'Request Unit Hours',
        'date_from'                => 'Date From',
        'date_to'                  => 'Date To',
        'number_of_days'           => 'Number Of Days',
        'number_of_hours'          => 'Number Of Hours',
        'request_hour_from'        => 'Request Hour From',
        'request_hour_to'          => 'Request Hour To',
    ];

    protected $casts = [
        'state'                    => State::class,
        'request_date_from_period' => RequestDateFromPeriod::class,
    ];

    /**
     * User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Manager
     *
     * @return BelongsTo
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Holiday Status
     *
     * @return BelongsTo
     */
    public function holidayStatus(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'holiday_status_id');
    }

    /**
     * Employee
     *
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Employee Company
     *
     * @return BelongsTo
     */
    public function employeeCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'employee_company_id');
    }

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
     * Department
     *
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Calendar
     *
     * @return BelongsTo
     */
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class, 'calendar_id');
    }

    /**
     * First Approver
     *
     * @return BelongsTo
     */
    public function firstApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'first_approver_id');
    }

    /**
     * Second Approver
     *
     * @return BelongsTo
     */
    public function secondApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'second_approver_id');
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
}
