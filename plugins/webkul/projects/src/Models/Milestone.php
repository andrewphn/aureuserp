<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Project\Database\Factories\MilestoneFactory;
use Webkul\Security\Models\User;

/**
 * Milestone Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $name
 * @property \Carbon\Carbon|null $deadline
 * @property bool $is_completed
 * @property \Carbon\Carbon|null $completed_at
 * @property int $project_id
 * @property int $creator_id
 * @property string|null $production_stage
 * @property bool $is_critical
 * @property string|null $description
 * @property int $sort_order
 * @property-read \Illuminate\Database\Eloquent\Model|null $project
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class Milestone extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'projects_milestones';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'deadline',
        'is_completed',
        'completed_at',
        'project_id',
        'creator_id',
        'production_stage',
        'is_critical',
        'description',
        'sort_order',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'is_completed' => 'boolean',
        'is_critical'  => 'boolean',
        'deadline'     => 'datetime',
        'completed_at' => 'datetime',
        'sort_order'   => 'integer',
    ];

    /**
     * Project
     *
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tasks associated with this milestone
     *
     * @return HasMany
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Scope to get only critical milestones
     */
    /**
     * Scope query to Critical
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    /**
     * Scope to filter milestones by production stage
     */
    /**
     * Scope query to By Stage
     *
     * @param mixed $query The search query
     * @param string $stage
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStage($query, string $stage)
    {
        return $query->where('production_stage', $stage);
    }

    /**
     * Scope to get overdue milestones
     */
    /**
     * Scope query to Overdue
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverdue($query)
    {
        return $query->where('is_completed', false)
            ->where('deadline', '<', now());
    }

    /**
     * Scope to get upcoming milestones (not completed, deadline in future)
     */
    /**
     * Scope query to Upcoming
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpcoming($query)
    {
        return $query->where('is_completed', false)
            ->where('deadline', '>=', now())
            ->orderBy('deadline', 'asc');
    }

    /**
     * Check if milestone is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return !$this->is_completed && $this->deadline && $this->deadline->isPast();
    }

    /**
     * Get stage color for visual display
     */
    public function getStageColorAttribute(): string
    {
        return match ($this->production_stage) {
            'discovery' => '#8b5cf6',  // Purple
            'design' => '#3b82f6',     // Blue
            'sourcing' => '#f59e0b',   // Amber
            'production' => '#10b981', // Green
            'delivery' => '#6366f1',   // Indigo
            default => '#6b7280',      // Gray
        };
    }

    /**
     * Get stage icon for visual display
     */
    public function getStageIconAttribute(): string
    {
        return match ($this->production_stage) {
            'discovery' => 'heroicon-o-light-bulb',
            'design' => 'heroicon-o-pencil-square',
            'sourcing' => 'heroicon-o-shopping-cart',
            'production' => 'heroicon-o-wrench-screwdriver',
            'delivery' => 'heroicon-o-truck',
            default => 'heroicon-o-flag',
        };
    }

    /**
     * New Factory
     *
     * @return MilestoneFactory
     */
    protected static function newFactory(): MilestoneFactory
    {
        return MilestoneFactory::new();
    }
}
