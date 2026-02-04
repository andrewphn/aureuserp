<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Milestone Template Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $name
 * @property string|null $production_stage
 * @property bool $is_critical
 * @property string|null $description
 * @property int $relative_days
 * @property int $sort_order
 * @property bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection|MilestoneTemplateTask[] $taskTemplates
 * @property-read \Illuminate\Database\Eloquent\Collection|MilestoneRequirementTemplate[] $requirements
 * @property-read \Illuminate\Database\Eloquent\Collection|Tag[] $tags
 *
 */
class MilestoneTemplate extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'projects_milestone_templates';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'production_stage',
        'is_critical',
        'description',
        'relative_days',
        'sort_order',
        'is_active',
    ];

    /**
     * Casts.
     *
     * @var array
     */
    protected $casts = [
        'is_critical' => 'boolean',
        'is_active'   => 'boolean',
        'relative_days' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Scope to get only active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter templates by production stage
     */
    public function scopeByStage($query, string $stage)
    {
        return $query->where('production_stage', $stage);
    }

    /**
     * Scope to get critical templates
     */
    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    /**
     * Get task templates for this milestone template.
     */
    public function taskTemplates(): HasMany
    {
        return $this->hasMany(MilestoneTemplateTask::class, 'milestone_template_id')->orderBy('sort_order');
    }

    /**
     * Get only root-level task templates (no parent).
     */
    public function rootTaskTemplates(): HasMany
    {
        return $this->taskTemplates()->whereNull('parent_id');
    }

    /**
     * Get requirement templates for this milestone template.
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(MilestoneRequirementTemplate::class, 'milestone_template_id')->orderBy('sort_order');
    }

    /**
     * Get the total allocated hours from all task templates.
     */
    public function getTotalAllocatedHoursAttribute(): float
    {
        return $this->taskTemplates()->sum('allocated_hours');
    }

    /**
     * Get the count of task templates.
     */
    public function getTaskCountAttribute(): int
    {
        return $this->taskTemplates()->count();
    }

    /**
     * Get tags for this milestone template.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            'projects_milestone_template_tag',
            'milestone_template_id',
            'tag_id'
        )->withTimestamps();
    }

    /**
     * Scope to filter by tag.
     */
    public function scopeWithTag($query, $tagId)
    {
        return $query->whereHas('tags', function ($q) use ($tagId) {
            $q->where('projects_tags.id', $tagId);
        });
    }

    /**
     * Scope to filter by tag name.
     */
    public function scopeWithTagName($query, string $tagName)
    {
        return $query->whereHas('tags', function ($q) use ($tagName) {
            $q->where('projects_tags.name', $tagName);
        });
    }

    /**
     * Scope to filter by any of the given tags.
     */
    public function scopeWithAnyTags($query, array $tagIds)
    {
        return $query->whereHas('tags', function ($q) use ($tagIds) {
            $q->whereIn('projects_tags.id', $tagIds);
        });
    }
}
