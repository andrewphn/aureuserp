<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Employee\Database\Factories\EmployeeFactory;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Partner\Models\BankAccount;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Country;
use Webkul\Support\Models\State;

/**
 * Employee Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $company_id
 * @property int $user_id
 * @property int $creator_id
 * @property int $calendar_id
 * @property int $department_id
 * @property int $job_id
 * @property int $attendance_manager_id
 * @property int $partner_id
 * @property int $work_location_id
 * @property int $parent_id
 * @property int $coach_id
 * @property int $country_id
 * @property int $state_id
 * @property float $country_of_birth
 * @property int $bank_account_id
 * @property int $departure_reason_id
 * @property string|null $name
 * @property string|null $job_title
 * @property string|null $work_phone
 * @property string|null $mobile_phone
 * @property string|null $color
 * @property string|null $work_email
 * @property string|null $children
 * @property string|null $distance_home_work
 * @property string|null $km_home_work
 * @property string|null $distance_home_work_unit
 * @property string|null $private_phone
 * @property string|null $private_email
 * @property string|null $private_street1
 * @property string|null $private_street2
 * @property string|null $private_city
 * @property string|null $private_zip
 * @property int $private_state_id
 * @property int $private_country_id
 * @property string|null $private_car_plate
 * @property string|null $lang
 * @property string|null $gender
 * @property string|null $birthday
 * @property string|null $marital
 * @property string|null $spouse_complete_name
 * @property string|null $spouse_birthdate
 * @property string|null $place_of_birth
 * @property string|null $ssnid
 * @property string|null $sinid
 * @property int $identification_id
 * @property int $passport_id
 * @property string|null $permit_no
 * @property string|null $visa_no
 * @property string|null $certificate
 * @property string|null $study_field
 * @property string|null $study_school
 * @property string|null $emergency_contact
 * @property string|null $emergency_phone
 * @property string|null $employee_type
 * @property string|null $barcode
 * @property string|null $pin
 * @property int $address_id
 * @property string|null $time_zone
 * @property string|null $work_permit
 * @property int $leave_manager_id
 * @property string|null $visa_expire
 * @property \Carbon\Carbon|null $work_permit_expiration_date
 * @property \Carbon\Carbon|null $departure_date
 * @property string|null $departure_description
 * @property string|null $additional_note
 * @property string|null $notes
 * @property bool $is_active
 * @property bool $is_flexible
 * @property bool $is_fully_flexible
 * @property bool $work_permit_scheduled_activity
 * @property-read \Illuminate\Database\Eloquent\Collection $skills
 * @property-read \Illuminate\Database\Eloquent\Collection $resumes
 * @property-read \Illuminate\Database\Eloquent\Model|null $privateState
 * @property-read \Illuminate\Database\Eloquent\Model|null $privateCountry
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Model|null $calendar
 * @property-read \Illuminate\Database\Eloquent\Model|null $department
 * @property-read \Illuminate\Database\Eloquent\Model|null $job
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $workLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $parent
 * @property-read \Illuminate\Database\Eloquent\Model|null $coach
 * @property-read \Illuminate\Database\Eloquent\Model|null $country
 * @property-read \Illuminate\Database\Eloquent\Model|null $state
 * @property-read \Illuminate\Database\Eloquent\Model|null $countryOfBirth
 * @property-read \Illuminate\Database\Eloquent\Model|null $bankAccount
 * @property-read \Illuminate\Database\Eloquent\Model|null $departureReason
 * @property-read \Illuminate\Database\Eloquent\Model|null $employmentType
 * @property-read \Illuminate\Database\Eloquent\Model|null $leaveManager
 * @property-read \Illuminate\Database\Eloquent\Model|null $attendanceManager
 * @property-read \Illuminate\Database\Eloquent\Model|null $companyAddress
 * @property-read \Illuminate\Database\Eloquent\Collection $categories
 *
 */
class Employee extends Model
{
    use HasChatter, HasCustomFields, HasFactory, HasLogActivity, SoftDeletes;

    protected $table = 'employees_employees';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'creator_id',
        'calendar_id',
        'department_id',
        'job_id',
        'attendance_manager_id',
        'partner_id',
        'work_location_id',
        'parent_id',
        'coach_id',
        'country_id',
        'state_id',
        'country_of_birth',
        'bank_account_id',
        'departure_reason_id',
        'name',
        'job_title',
        'work_phone',
        'mobile_phone',
        'color',
        'work_email',
        'children',
        'distance_home_work',
        'km_home_work',
        'distance_home_work_unit',
        'private_phone',
        'private_email',
        'private_street1',
        'private_street2',
        'private_city',
        'private_zip',
        'private_state_id',
        'private_country_id',
        'private_car_plate',
        'lang',
        'gender',
        'birthday',
        'marital',
        'spouse_complete_name',
        'spouse_birthdate',
        'place_of_birth',
        'ssnid',
        'sinid',
        'identification_id',
        'passport_id',
        'permit_no',
        'visa_no',
        'certificate',
        'study_field',
        'study_school',
        'emergency_contact',
        'emergency_phone',
        'employee_type',
        'barcode',
        'pin',
        'address_id',
        'time_zone',
        'work_permit',
        'leave_manager_id',
        'visa_expire',
        'work_permit_expiration_date',
        'departure_date',
        'departure_description',
        'additional_note',
        'notes',
        'is_active',
        'is_flexible',
        'is_fully_flexible',
        'work_permit_scheduled_activity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active'                      => 'boolean',
        'is_flexible'                    => 'boolean',
        'is_fully_flexible'              => 'boolean',
        'work_permit_scheduled_activity' => 'boolean',
    ];

    /**
     * Private State
     *
     * @return BelongsTo
     */
    public function privateState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'private_state_id');
    }

    /**
     * Private Country
     *
     * @return BelongsTo
     */
    public function privateCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'private_country_id');
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
     * User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
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
     * Department
     *
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Job
     *
     * @return BelongsTo
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(EmployeeJobPosition::class, 'job_id');
    }

    /**
     * Partner
     *
     * @return BelongsTo
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * Work Location
     *
     * @return BelongsTo
     */
    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'work_location_id');
    }

    /**
     * Parent
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Coach
     *
     * @return BelongsTo
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(self::class, 'coach_id');
    }

    /**
     * Country
     *
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * State
     *
     * @return BelongsTo
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    /**
     * Country Of Birth
     *
     * @return BelongsTo
     */
    public function countryOfBirth(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_of_birth');
    }

    /**
     * Bank Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    /**
     * Departure Reason
     *
     * @return BelongsTo
     */
    public function departureReason(): BelongsTo
    {
        return $this->belongsTo(DepartureReason::class, 'departure_reason_id');
    }

    /**
     * Employment Type
     *
     * @return BelongsTo
     */
    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class, 'employee_type');
    }

    /**
     * Categories
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany(EmployeeCategory::class, 'employees_employee_categories', 'employee_id', 'category_id');
    }

    /**
     * Skills
     *
     * @return HasMany
     */
    public function skills(): HasMany
    {
        return $this->hasMany(EmployeeSkill::class, 'employee_id');
    }

    /**
     * Resumes
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function resumes()
    {
        return $this->hasMany(EmployeeResume::class, 'employee_id');
    }

    /**
     * Get the factory instance for the model.
     */
    /**
     * New Factory
     *
     * @return EmployeeFactory
     */
    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }

    /**
     * Leave Manager
     *
     * @return BelongsTo
     */
    public function leaveManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leave_manager_id');
    }

    /**
     * Attendance Manager
     *
     * @return BelongsTo
     */
    public function attendanceManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendance_manager_id');
    }

    /**
     * Company Address
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function companyAddress()
    {
        return $this->belongsTo(Partner::class, 'address_id');
    }

    /**
     * Bootstrap the model and its traits.
     */
    /**
     * Boot
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function (self $employee) {
            if (! $employee->partner_id) {
                $employee->handlePartnerCreation($employee);
            } else {
                $employee->handlePartnerUpdation($employee);
            }
        });
    }

    /**
     * Handle the creation of the partner.
     */
    /**
     * Handle Partner Creation
     *
     * @param self $employee
     * @return void
     */
    private function handlePartnerCreation(self $employee): void
    {
        // Get parent employee's partner_id if parent exists
        $parentPartnerId = null;
        if ($employee->parent_id) {
            $parentEmployee = self::find($employee->parent_id);
            $parentPartnerId = $parentEmployee?->partner_id;
        }

        $partner = $employee->partner()->create([
            'account_type' => 'individual',
            'sub_type'     => 'employee',
            'creator_id'   => Auth::id(),
            'name'         => $employee?->name,
            'email'        => $employee?->work_email ?? $employee?->private_email,
            'job_title'    => $employee?->job_title,
            'phone'        => $employee?->work_phone,
            'mobile'       => $employee?->mobile_phone,
            'color'        => $employee?->color,
            'parent_id'    => $parentPartnerId,
            'company_id'   => $employee?->company_id,
            'user_id'      => $employee?->user_id,
        ]);

        $employee->partner_id = $partner->id;
        $employee->save();
    }

    /**
     * Handle the updation of the partner.
     */
    /**
     * Handle Partner Updation
     *
     * @param self $employee
     * @return void
     */
    private function handlePartnerUpdation(self $employee): void
    {
        $partner = Partner::updateOrCreate(
            ['id' => $employee->partner_id],
            [
                'account_type' => 'individual',
                'sub_type'     => 'employee',
                'creator_id'   => Auth::id(),
                'name'         => $employee?->name,
                'email'        => $employee?->work_email ?? $employee?->private_email,
                'job_title'    => $employee?->job_title,
                'phone'        => $employee?->work_phone,
                'mobile'       => $employee?->mobile_phone,
                'color'        => $employee?->color,
                'parent_id'    => $employee?->parent_id,
                'company_id'   => $employee?->company_id,
                'user_id'      => $employee?->user_id,
            ]
        );

        if ($employee->partner_id !== $partner->id) {
            $employee->partner_id = $partner->id;
            $employee->save();
        }
    }
}
