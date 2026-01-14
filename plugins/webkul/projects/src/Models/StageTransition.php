<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

/**
 * Stage Transition Eloquent model
 *
 * Provides a complete audit trail of all project stage changes.
 * Enables timeline reconstruction, compliance reporting, and debugging.
 *
 * @property int $id
 * @property int $project_id
 * @property int|null $from_stage_id
 * @property int $to_stage_id
 * @property int|null $gate_id
 * @property string $transition_type
 * @property \Carbon\Carbon $transitioned_at
 * @property int|null $transitioned_by
 * @property string|null $reason
 * @property int|null $gate_evaluation_id
 * @property array|null $metadata
 * @property int|null $duration_minutes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Project $project
 * @property-read ProjectStage|null $fromStage
 * @property-read ProjectStage $toStage
 * @property-read Gate|null $gate
 * @property-read User|null $transitioner
 * @property-read GateEvaluation|null $evaluation
 */
class StageTransition extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'projects_stage_transitions';

    /**
     * Transition type constants.
     */
    public const TYPE_ADVANCE = 'advance';
    public const TYPE_ROLLBACK = 'rollback';
    public const TYPE_FORCE = 'force';
    public const TYPE_SYSTEM = 'system';

    /**
     * Fillable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'project_id',
        'from_stage_id',
        'to_stage_id',
        'gate_id',
        'transition_type',
        'transitioned_at',
        'transitioned_by',
        'reason',
        'gate_evaluation_id',
        'metadata',
        'duration_minutes',
    ];

    /**
     * Attribute casting.
     *
     * @var array
     */
    protected $casts = [
        'transitioned_at' => 'datetime',
        'metadata' => 'array',
        'duration_minutes' => 'integer',
    ];

    /**
     * Get the project this transition belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the stage the project transitioned from.
     */
    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(ProjectStage::class, 'from_stage_id');
    }

    /**
     * Get the stage the project transitioned to.
     */
    public function toStage(): BelongsTo
    {
        return $this->belongsTo(ProjectStage::class, 'to_stage_id');
    }

    /**
     * Get the gate that was passed for this transition.
     */
    public function gate(): BelongsTo
    {
        return $this->belongsTo(Gate::class, 'gate_id');
    }

    /**
     * Get the user who triggered the transition.
     */
    public function transitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transitioned_by');
    }

    /**
     * Get the gate evaluation associated with this transition.
     */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(GateEvaluation::class, 'gate_evaluation_id');
    }

    /**
     * Scope to get transitions for a specific project.
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to get transitions of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('transition_type', $type);
    }

    /**
     * Scope to order by transition time (most recent first).
     */
    public function scopeRecent($query)
    {
        return $query->orderByDesc('transitioned_at');
    }

    /**
     * Scope to order by transition time (oldest first).
     */
    public function scopeChronological($query)
    {
        return $query->orderBy('transitioned_at');
    }

    /**
     * Check if this was a forced transition.
     */
    public function isForced(): bool
    {
        return $this->transition_type === self::TYPE_FORCE;
    }

    /**
     * Check if this was a rollback.
     */
    public function isRollback(): bool
    {
        return $this->transition_type === self::TYPE_ROLLBACK;
    }

    /**
     * Check if this was a normal advance.
     */
    public function isAdvance(): bool
    {
        return $this->transition_type === self::TYPE_ADVANCE;
    }

    /**
     * Get human-readable duration.
     */
    public function getDurationForHumans(): ?string
    {
        if (!$this->duration_minutes) {
            return null;
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        $days = floor($hours / 24);
        $hours = $hours % 24;

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' ' . ($days === 1 ? 'day' : 'days');
        }
        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours === 1 ? 'hour' : 'hours');
        }
        if ($minutes > 0 && $days === 0) {
            $parts[] = $minutes . ' ' . ($minutes === 1 ? 'minute' : 'minutes');
        }

        return implode(', ', $parts) ?: '< 1 minute';
    }

    /**
     * Record a new stage transition.
     */
    public static function record(
        Project $project,
        ?ProjectStage $fromStage,
        ProjectStage $toStage,
        string $type,
        ?Gate $gate = null,
        ?GateEvaluation $evaluation = null,
        ?string $reason = null,
        array $metadata = []
    ): self {
        // Calculate duration in previous stage
        $durationMinutes = null;
        if ($project->stage_entered_at) {
            $durationMinutes = (int) now()->diffInMinutes($project->stage_entered_at);
        }

        return static::create([
            'project_id' => $project->id,
            'from_stage_id' => $fromStage?->id,
            'to_stage_id' => $toStage->id,
            'gate_id' => $gate?->id,
            'transition_type' => $type,
            'transitioned_at' => now(),
            'transitioned_by' => auth()->id(),
            'reason' => $reason,
            'gate_evaluation_id' => $evaluation?->id,
            'metadata' => $metadata,
            'duration_minutes' => $durationMinutes,
        ]);
    }

    /**
     * Get the transition label for display.
     */
    public function getTransitionLabel(): string
    {
        $from = $this->fromStage?->name ?? 'None';
        $to = $this->toStage->name;

        return "{$from} → {$to}";
    }
}
