<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Job Position Skill Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $job_position_id
 * @property int $skill_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $jobPosition
 * @property-read \Illuminate\Database\Eloquent\Model|null $skill
 *
 */
class JobPositionSkill extends Model
{
    protected $table = 'job_position_skills';

    protected $fillable = ['job_position_id', 'skill_id'];

    public $timestamps = false;

    /**
     * Job Position
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function jobPosition()
    {
        return $this->belongsTo(EmployeeJobPosition::class, 'job_position_id');
    }

    /**
     * Skill
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function skill()
    {
        return $this->belongsTo(EmployeeSkill::class, 'skill_id');
    }
}
