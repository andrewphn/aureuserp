<?php

namespace Webkul\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Applicant Interviewer Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $applicant_id
 * @property int $interviewer_id
 *
 */
class ApplicantInterviewer extends Model
{
    protected $table = 'recruitments_applicant_interviewers';

    public $timestamps = false;

    protected $fillable = [
        'applicant_id',
        'interviewer_id',
    ];
}
