<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Employee\Database\Factories\SkillLevelFactory;

/**
 * Skill Level Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $name
 * @property int $skill_type_id
 * @property string|null $level
 * @property string|null $default_level
 * @property-read \Illuminate\Database\Eloquent\Collection $employeeSkills
 * @property-read \Illuminate\Database\Eloquent\Model|null $skillType
 *
 */
class SkillLevel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employees_skill_levels';

    protected $fillable = [
        'name',
        'skill_type_id',
        'level',
        'default_level',
    ];

    /**
     * Skill Type
     *
     * @return BelongsTo
     */
    public function skillType(): BelongsTo
    {
        return $this->belongsTo(SkillType::class, 'skill_type_id');
    }

    /**
     * Employee Skills
     *
     * @return HasMany
     */
    public function employeeSkills(): HasMany
    {
        return $this->hasMany(EmployeeSkill::class, 'skill_level_id');
    }

    /**
     * Get the factory instance for the model.
     */
    /**
     * New Factory
     *
     * @return SkillLevelFactory
     */
    protected static function newFactory(): SkillLevelFactory
    {
        return SkillLevelFactory::new();
    }
}
