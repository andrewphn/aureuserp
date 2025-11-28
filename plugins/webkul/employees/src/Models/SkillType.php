<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Employee\Database\Factories\SkillTypeFactory;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Security\Models\User;

/**
 * Skill Type Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $name
 * @property string|null $color
 * @property int $creator_id
 * @property bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection $skillLevels
 * @property-read \Illuminate\Database\Eloquent\Collection $skills
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class SkillType extends Model
{
    use HasCustomFields, HasFactory, SoftDeletes;

    protected $table = 'employees_skill_types';

    protected $fillable = [
        'name',
        'color',
        'creator_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Skill Levels
     *
     * @return HasMany
     */
    public function skillLevels(): HasMany
    {
        return $this->hasMany(SkillLevel::class, 'skill_type_id');
    }

    /**
     * Skills
     *
     * @return HasMany
     */
    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class, 'skill_type_id');
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

    /**
     * Get the factory instance for the model.
     */
    /**
     * New Factory
     *
     * @return SkillTypeFactory
     */
    protected static function newFactory(): SkillTypeFactory
    {
        return SkillTypeFactory::new();
    }
}
