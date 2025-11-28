<?php

namespace Webkul\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;

/**
 * Job Position Interviewer Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $job_position_id
 * @property int $user_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $jobPosition
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 *
 */
class JobPositionInterviewer extends Model
{
    protected $table = 'recruitments_job_position_interviewers';

    protected $fillable = ['job_position_id', 'user_id'];

    public $timestamps = false;

    /**
     * Job Position
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function jobPosition()
    {
        return $this->belongsTo(JobPosition::class, 'job_position_id');
    }

    /**
     * User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
