<?php

namespace Webkul\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Employee\Models\Skill;
use Webkul\Employee\Models\SkillLevel;
use Webkul\Employee\Models\SkillType;
use Webkul\Security\Models\User;

/**
 * Candidate Skill Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $candidate_id
 * @property int $skill_id
 * @property int $skill_level_id
 * @property int $skill_type_id
 * @property int $creator_id
 * @property int $user_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $candidate
 * @property-read \Illuminate\Database\Eloquent\Model|null $skill
 * @property-read \Illuminate\Database\Eloquent\Model|null $skillLevel
 * @property-read \Illuminate\Database\Eloquent\Model|null $skillType
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 *
 */
class CandidateSkill extends Model
{
    protected $table = 'recruitments_candidate_skills';

    protected $fillable = [
        'candidate_id',
        'skill_id',
        'skill_level_id',
        'skill_type_id',
        'creator_id',
        'user_id',
    ];

    /**
     * Candidate
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
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
     * Creator
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
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
