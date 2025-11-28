<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Employee\Database\Factories\EmployeeSkillFactory;
use Webkul\Security\Models\User;

/**
 * Employee Skill Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $employee_id
 * @property int $skill_id
 * @property int $skill_level_id
 * @property int $skill_type_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $employee
 * @property-read \Illuminate\Database\Eloquent\Model|null $skill
 * @property-read \Illuminate\Database\Eloquent\Model|null $skillLevel
 * @property-read \Illuminate\Database\Eloquent\Model|null $skillType
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class EmployeeSkill extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employees_employee_skills';

    protected $fillable = [
        'employee_id',
        'skill_id',
        'skill_level_id',
        'skill_type_id',
        'creator_id',
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
     * Skill
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    /**
     * Skill Level
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function skillLevel()
    {
        return $this->belongsTo(SkillLevel::class);
    }

    /**
     * Skill Type
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function skillType()
    {
        return $this->belongsTo(SkillType::class);
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
     * Get the factory instance for the model.
     */
    /**
     * New Factory
     *
     * @return EmployeeSkillFactory
     */
    protected static function newFactory(): EmployeeSkillFactory
    {
        return EmployeeSkillFactory::new();
    }
}
