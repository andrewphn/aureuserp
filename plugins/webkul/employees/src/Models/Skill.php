<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Employee\Database\Factories\SkillFactory;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Security\Models\User;

/**
 * Skill Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $sort
 * @property string|null $name
 * @property int $skill_type_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $skillLevels
 * @property-read \Illuminate\Database\Eloquent\Collection $employeeSkills
 * @property-read \Illuminate\Database\Eloquent\Model|null $skillType
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class Skill extends Model implements Sortable
{
    use HasCustomFields, HasFactory, SoftDeletes, SortableTrait;

    protected $table = 'employees_skills';

    protected $fillable = [
        'sort',
        'name',
        'skill_type_id',
        'creator_id',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
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
     * Skill Levels
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function skillLevels()
    {
        return $this->hasMany(SkillLevel::class);
    }

    /**
     * Employee Skills
     *
     * @return HasMany
     */
    public function employeeSkills(): HasMany
    {
        return $this->hasMany(EmployeeSkill::class, 'skill_id');
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
     * @return SkillFactory
     */
    protected static function newFactory(): SkillFactory
    {
        return SkillFactory::new();
    }
}
