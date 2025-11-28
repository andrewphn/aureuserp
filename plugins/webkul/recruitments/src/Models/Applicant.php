<?php

namespace Webkul\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Employee\Models\Department;
use Webkul\Employee\Models\Employee;
use Webkul\Recruitment\Enums\ApplicationStatus;
use Webkul\Recruitment\Traits\HasApplicationStatus;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\UTMMedium;
use Webkul\Support\Models\UTMSource;

/**
 * Applicant Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $source_id
 * @property int $medium_id
 * @property int $candidate_id
 * @property int $stage_id
 * @property int $last_stage_id
 * @property int $company_id
 * @property int $recruiter_id
 * @property int $job_id
 * @property int $department_id
 * @property int $refuse_reason_id
 * @property string|null $state
 * @property int $creator_id
 * @property string|null $email_cc
 * @property string|null $priority
 * @property string|null $salary_proposed_extra
 * @property string|null $salary_expected_extra
 * @property array $applicant_properties
 * @property string|null $applicant_notes
 * @property bool $is_active
 * @property \Carbon\Carbon|null $create_date
 * @property \Carbon\Carbon|null $date_closed
 * @property \Carbon\Carbon|null $date_opened
 * @property \Carbon\Carbon|null $date_last_stage_updated
 * @property \Carbon\Carbon|null $refuse_date
 * @property float $probability
 * @property float $salary_proposed
 * @property float $salary_expected
 * @property float $delay_close
 * @property-read \Illuminate\Database\Eloquent\Model|null $source
 * @property-read \Illuminate\Database\Eloquent\Model|null $medium
 * @property-read \Illuminate\Database\Eloquent\Model|null $candidate
 * @property-read \Illuminate\Database\Eloquent\Model|null $stage
 * @property-read \Illuminate\Database\Eloquent\Model|null $lastStage
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $recruiter
 * @property-read \Illuminate\Database\Eloquent\Model|null $job
 * @property-read \Illuminate\Database\Eloquent\Model|null $department
 * @property-read \Illuminate\Database\Eloquent\Model|null $refuseReason
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection $interviewer
 * @property-read \Illuminate\Database\Eloquent\Collection $categories
 * @property-read \Illuminate\Database\Eloquent\Collection $skills
 *
 */
class Applicant extends Model
{
    use HasApplicationStatus, HasChatter, HasLogActivity, SoftDeletes;

    protected $table = 'recruitments_applicants';

    protected $fillable = [
        'source_id',
        'medium_id',
        'candidate_id',
        'stage_id',
        'last_stage_id',
        'company_id',
        'recruiter_id',
        'job_id',
        'department_id',
        'refuse_reason_id',
        'state',
        'creator_id',
        'email_cc',
        'priority',
        'salary_proposed_extra',
        'salary_expected_extra',
        'applicant_properties',
        'applicant_notes',
        'is_active',
        'create_date',
        'date_closed',
        'date_opened',
        'date_last_stage_updated',
        'refuse_date',
        'probability',
        'salary_proposed',
        'salary_expected',
        'delay_close',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'create_date'             => 'date',
        'date_closed'             => 'date',
        'date_opened'             => 'date',
        'date_last_stage_updated' => 'date',
        'refuse_date'             => 'date',
        'applicant_properties'    => 'json',
        'probability'             => 'double',
        'salary_proposed'         => 'double',
        'salary_expected'         => 'double',
        'delay_close'             => 'double',
    ];

    protected $appends = [
        'application_status',
    ];

    /**
     * Source
     *
     * @return BelongsTo
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(UTMSource::class);
    }

    /**
     * Medium
     *
     * @return BelongsTo
     */
    public function medium(): BelongsTo
    {
        return $this->belongsTo(UTMMedium::class);
    }

    /**
     * Candidate
     *
     * @return BelongsTo
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Skills
     *
     * @return HasManyThrough
     */
    public function skills(): HasManyThrough
    {
        return $this->hasManyThrough(
            CandidateSkill::class,
            Candidate::class,
            'id',
            'candidate_id',
            'candidate_id',
            'id'
        );
    }

    /**
     * Stage
     *
     * @return BelongsTo
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    /**
     * Last Stage
     *
     * @return BelongsTo
     */
    public function lastStage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'last_stage_id');
    }

    /**
     * Company
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    /**
     * Interviewer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function interviewer()
    {
        return $this->belongsToMany(User::class, 'recruitments_applicant_interviewers', 'applicant_id', 'interviewer_id');
    }

    /**
     * Categories
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany(ApplicantCategory::class, 'recruitments_applicant_applicant_categories', 'applicant_id', 'category_id');
    }

    /**
     * Job
     *
     * @return BelongsTo
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class, 'job_id');
    }

    /**
     * Department
     *
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Refuse Reason
     *
     * @return BelongsTo
     */
    public function refuseReason(): BelongsTo
    {
        return $this->belongsTo(RefuseReason::class);
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

    public static function getStatusOptions(): array
    {
        return ApplicationStatus::options();
    }

    public function setAsHired(): bool
    {
        return $this->updateStatus(ApplicationStatus::HIRED->value);
    }

    /**
     * Set As Refused
     *
     * @param int $refuseReasonId
     * @return bool
     */
    public function setAsRefused(int $refuseReasonId): bool
    {
        return $this->updateStatus(ApplicationStatus::REFUSED->value, [
            'refuse_reason_id' => $refuseReasonId,
        ]);
    }

    public function setAsArchived(): bool
    {
        return $this->updateStatus(ApplicationStatus::ARCHIVED->value);
    }

    /**
     * Reopen
     *
     * @return bool
     */
    public function reopen(): bool
    {
        return $this->updateStatus(ApplicationStatus::ONGOING->value);
    }

    /**
     * Update Stage
     *
     * @param array $data The data array
     * @return bool
     */
    public function updateStage(array $data): bool
    {
        return $this->update($data);
    }

    public function getApplicationStatusAttribute(): ?ApplicationStatus
    {
        if ($this->refuse_reason_id) {
            return ApplicationStatus::REFUSED;
        } elseif (! $this->is_active || $this->deleted_at) {
            return ApplicationStatus::ARCHIVED;
        } elseif ($this->date_closed) {
            return ApplicationStatus::HIRED;
        } else {
            return ApplicationStatus::ONGOING;
        }
    }

    /**
     * Create Employee
     *
     * @return ?Employee
     */
    public function createEmployee(): ?Employee
    {
        if (! $this->candidate?->partner_id) {
            return null;
        }

        if ($this->candidate->employee_id) {
            return $this->candidate->employee;
        }

        $employee = Employee::create([
            'name'          => $this->candidate->name,
            'user_id'       => $this->candidate->user_id,
            'job_id'        => $this->job_id,
            'department_id' => $this->department_id,
            'company_id'    => $this->company_id,
            'partner_id'    => $this->candidate->partner_id,
            'company_id'    => $this->candidate->company_id,
            'work_email'    => $this->candidate->email_from,
            'mobile_phone'  => $this->candidate->phone,
            'is_active'     => true,
        ]);

        $this->candidate()->update([
            'employee_id' => $employee->id,
        ]);

        return $employee;
    }
}
