<?php

namespace Webkul\TimeOff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Employee\Models\Department;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\TimeOff\Enums\AllocationType;

/**
 * Leave Allocation Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $holiday_status_id
 * @property int $employee_id
 * @property int $employee_company_id
 * @property int $manager_id
 * @property int $approver_id
 * @property int $second_approver_id
 * @property int $department_id
 * @property int $accrual_plan_id
 * @property int $creator_id
 * @property string|null $name
 * @property string|null $state
 * @property mixed $allocation_type
 * @property string|null $date_from
 * @property string|null $date_to
 * @property \Carbon\Carbon|null $last_executed_carryover_date
 * @property string|null $last_called
 * @property string|null $actual_last_called
 * @property string|null $next_call
 * @property \Carbon\Carbon|null $carried_over_days_expiration_date
 * @property string|null $notes
 * @property string|null $already_accrued
 * @property string|null $number_of_days
 * @property float $number_of_hours_display
 * @property float $yearly_accrued_amount
 * @property string|null $expiring_carryover_days
 * @property-read \Illuminate\Database\Eloquent\Model|null $employee
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $employeeCompany
 * @property-read \Illuminate\Database\Eloquent\Model|null $manager
 * @property-read \Illuminate\Database\Eloquent\Model|null $approver
 * @property-read \Illuminate\Database\Eloquent\Model|null $secondApprover
 * @property-read \Illuminate\Database\Eloquent\Model|null $department
 * @property-read \Illuminate\Database\Eloquent\Model|null $accrualPlan
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $holidayStatus
 *
 */
class LeaveAllocation extends Model
{
    use HasChatter, HasFactory, HasLogActivity;

    protected $table = 'time_off_leave_allocations';

    protected $fillable = [
        'holiday_status_id',
        'employee_id',
        'employee_company_id',
        'manager_id',
        'approver_id',
        'second_approver_id',
        'department_id',
        'accrual_plan_id',
        'creator_id',
        'name',
        'state',
        'allocation_type',
        'date_from',
        'date_to',
        'last_executed_carryover_date',
        'last_called',
        'actual_last_called',
        'next_call',
        'carried_over_days_expiration_date',
        'notes',
        'already_accrued',
        'number_of_days',
        'number_of_hours_display',
        'yearly_accrued_amount',
        'expiring_carryover_days',
    ];

    protected array $logAttributes = [
        'holidayStatus.name'                => 'Time Off Type',
        'employee.name'                     => 'Employee',
        'employeeCompany.name'              => 'Employee Company',
        'approver.name'                     => 'Approver',
        'secondApprover.name'               => 'Second Approver',
        'department.name'                   => 'Department',
        'accrualPlan.name'                  => 'Accrual Plan',
        'createdBy.name'                    => 'Created By',
        'name'                              => 'Name',
        'state'                             => 'State',
        'allocation_type'                   => 'Allocation Type',
        'date_from'                         => 'Date From',
        'date_to'                           => 'Date To',
        'last_executed_carryover_date'      => 'Last Executed Carryover Date',
        'last_called'                       => 'Last Called',
        'actual_last_called'                => 'Actual Last Called',
        'next_call'                         => 'Next Call',
        'carried_over_days_expiration_date' => 'Carried Over Days Expiration Date',
        'notes'                             => 'Notes',
        'already_accrued'                   => 'Already Accrued',
        'number_of_days'                    => 'Number Of Days',
        'number_of_hours_display'           => 'Number Of Hours Display',
        'yearly_accrued_amount'             => 'Yearly Accrued Amount',
        'expiring_carryover_days'           => 'Expiring Carryover Days',
        'created_at'                        => 'Created At',
        'updated_at'                        => 'Updated At',
    ];

    protected $casts = [
        'allocation_type' => AllocationType::class,
    ];

    /**
     * Employee
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'employee_company_id');
    }

    /**
     * Employee Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employeeCompany()
    {
        return $this->belongsTo(Company::class, 'employee_company_id');
    }

    /**
     * Manager
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Approver
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    /**
     * Second Approver
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function secondApprover()
    {
        return $this->belongsTo(Employee::class, 'second_approver_id');
    }

    /**
     * Department
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

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
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Holiday Status
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function holidayStatus()
    {
        return $this->belongsTo(LeaveType::class, 'holiday_status_id');
    }
}
