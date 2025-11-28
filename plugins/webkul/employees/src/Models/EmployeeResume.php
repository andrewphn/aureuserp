<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;

/**
 * Employee Resume Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $employee_id
 * @property int $employee_resume_line_type_id
 * @property int $creator_id
 * @property int $user_id
 * @property string|null $display_type
 * @property \Carbon\Carbon|null $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property string|null $name
 * @property string|null $description
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $employee
 * @property-read \Illuminate\Database\Eloquent\Model|null $resumeType
 *
 */
class EmployeeResume extends Model
{
    protected $table = 'employees_employee_resumes';

    protected $fillable = [
        'employee_id',
        'employee_resume_line_type_id',
        'creator_id',
        'user_id',
        'display_type',
        'start_date',
        'end_date',
        'name',
        'description',
    ];

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
     * Employee
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Resume Type
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function resumeType()
    {
        return $this->belongsTo(EmployeeResumeLineType::class, 'employee_resume_line_type_id');
    }
}
