<?php

namespace Webkul\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Employee\Models\Employee;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Candidate Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $message_bounced
 * @property int $company_id
 * @property int $partner_id
 * @property int $degree_id
 * @property int $manager_id
 * @property int $employee_id
 * @property int $creator_id
 * @property string|null $email_cc
 * @property string|null $name
 * @property string|null $email_from
 * @property string|null $priority
 * @property string|null $phone
 * @property string|null $linkedin_profile
 * @property \Carbon\Carbon|null $availability_date
 * @property array $candidate_properties
 * @property bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection $skills
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $degree
 * @property-read \Illuminate\Database\Eloquent\Model|null $manager
 * @property-read \Illuminate\Database\Eloquent\Model|null $employee
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection $categories
 *
 */
class Candidate extends Model
{
    use HasChatter, HasLogActivity, SoftDeletes;

    protected $table = 'recruitments_candidates';

    protected $fillable = [
        'message_bounced',
        'company_id',
        'partner_id',
        'degree_id',
        'manager_id',
        'employee_id',
        'creator_id',
        'email_cc',
        'name',
        'email_from',
        'priority',
        'phone',
        'linkedin_profile',
        'availability_date',
        'candidate_properties',
        'is_active',
    ];

    protected array $logAttributes = [
        'company.name'     => 'Company',
        'partner.name'     => 'Contact',
        'degree.name'      => 'Degree',
        'user.name'        => 'Manager',
        'employee.name'    => 'Employee',
        'creator.name'     => 'Created By',
        'phone_sanitized'  => 'Phone',
        'email_normalized' => 'Email',
        'email_cc'         => 'Email CC',
        'name'             => 'Candidate Name',
        'email_from'       => 'Email From',
        'phone',
        'linkedin_profile',
        'availability_date',
        'is_active' => 'Status',
    ];

    protected $casts = [
        'candidate_properties' => 'array',
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

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * Degree
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function degree()
    {
        return $this->belongsTo(Degree::class, 'degree_id');
    }

    /**
     * Manager
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
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
     * Categories
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany(ApplicantCategory::class, 'recruitments_candidate_applicant_categories', 'candidate_id', 'category_id');
    }

    /**
     * Skills
     *
     * @return HasMany
     */
    public function skills(): HasMany
    {
        return $this->hasMany(CandidateSkill::class, 'candidate_id');
    }

    /**
     * Create Employee
     *
     */
    public function createEmployee()
    {
        $employee = $this->employee()->create([
            'name'          => $this->name,
            'user_id'       => $this->user_id,
            'department_id' => $this->department_id,
            'company_id'    => $this->company_id,
            'partner_id'    => $this->partner_id,
            'company_id'    => $this->company_id,
            'work_email'    => $this->email_from,
            'mobile_phone'  => $this->phone,
            'is_active'     => true,
        ]);

        $this->update([
            'employee_id' => $employee->id,
        ]);

        return $employee;
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

        static::saved(function (self $candidate) {
            if (! $candidate->partner_id) {
                $candidate->handlePartnerCreation($candidate);
            } else {
                $candidate->handlePartnerUpdation($candidate);
            }
        });
    }

    /**
     * Handle the creation of a partner.
     */
    /**
     * Handle Partner Creation
     *
     * @param self $candidate
     */
    private function handlePartnerCreation(self $candidate)
    {
        $partner = $candidate->partner()->create([
            'creator_id' => Auth::user()->id ?? $candidate->id,
            'sub_type'   => 'partner',
            'company_id' => $candidate->company_id,
            'phone'      => $candidate->phone,
            'email'      => $candidate->email_from,
            'name'       => $candidate->name,
        ]);

        $candidate->partner_id = $partner->id;
        $candidate->save();
    }

    /**
     * Handle the updation of a partner.
     */
    /**
     * Handle Partner Updation
     *
     * @param self $candidate
     */
    private function handlePartnerUpdation(self $candidate)
    {
        $partner = Partner::updateOrCreate(
            ['id' => $candidate->partner_id],
            [
                'creator_id' => Auth::user()->id ?? $candidate->id,
                'sub_type'   => 'partner',
                'company_id' => $candidate->company_id,
                'phone'      => $candidate->phone,
                'email'      => $candidate->email_from,
                'name'       => $candidate->name,
            ]
        );

        if ($candidate->partner_id !== $partner->id) {
            $candidate->partner_id = $partner->id;
            $candidate->save();
        }
    }
}
