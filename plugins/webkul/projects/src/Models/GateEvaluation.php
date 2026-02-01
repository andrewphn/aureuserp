<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Project\Database\Factories\GateEvaluationFactory;
use Webkul\Security\Models\User;

/**
 * Gate Evaluation Eloquent model
 *
 * Provides an audit log of gate evaluations for projects.
 * Enables "why is this blocked?" queries and compliance auditing.
 *
 * @property int $id
 * @property int $project_id
 * @property int $gate_id
 * @property bool $passed
 * @property \Carbon\Carbon $evaluated_at
 * @property int|null $evaluated_by
 * @property array|null $requirement_results
 * @property array|null $failure_reasons
 * @property array|null $context
 * @property string $evaluation_type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Project $project
 * @property-read Gate $gate
 * @property-read User|null $evaluator
 * @property-read StageTransition|null $transition
 */
class GateEvaluation extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'projects_gate_evaluations';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): GateEvaluationFactory
    {
        return GateEvaluationFactory::new();
    }

    /**
     * Evaluation type constants.
     */
    public const TYPE_MANUAL = 'manual';
    public const TYPE_AUTOMATIC = 'automatic';
    public const TYPE_SCHEDULED = 'scheduled';

    /**
     * Fillable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'project_id',
        'gate_id',
        'passed',
        'evaluated_at',
        'evaluated_by',
        'requirement_results',
        'failure_reasons',
        'context',
        'evaluation_type',
    ];

    /**
     * Attribute casting.
     *
     * @var array
     */
    protected $casts = [
        'passed' => 'boolean',
        'evaluated_at' => 'datetime',
        'requirement_results' => 'array',
        'failure_reasons' => 'array',
        'context' => 'array',
    ];

    /**
     * Get the project this evaluation is for.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the gate that was evaluated.
     */
    public function gate(): BelongsTo
    {
        return $this->belongsTo(Gate::class, 'gate_id');
    }

    /**
     * Get the user who triggered the evaluation.
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    /**
     * Get the stage transition that resulted from this evaluation (if any).
     */
    public function transition(): HasOne
    {
        return $this->hasOne(StageTransition::class, 'gate_evaluation_id');
    }

    /**
     * Scope to get only passed evaluations.
     */
    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    /**
     * Scope to get only failed evaluations.
     */
    public function scopeFailed($query)
    {
        return $query->where('passed', false);
    }

    /**
     * Scope to get evaluations for a specific project.
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to get evaluations for a specific gate.
     */
    public function scopeForGate($query, int $gateId)
    {
        return $query->where('gate_id', $gateId);
    }

    /**
     * Scope to order by evaluation time descending (most recent first).
     */
    public function scopeRecent($query)
    {
        return $query->orderByDesc('evaluated_at');
    }

    /**
     * Get the count of failed requirements.
     */
    public function getFailedCount(): int
    {
        return count($this->failure_reasons ?? []);
    }

    /**
     * Get the count of passed requirements.
     */
    public function getPassedCount(): int
    {
        if (!$this->requirement_results) {
            return 0;
        }

        return collect($this->requirement_results)
            ->filter(fn($result) => $result['passed'] ?? false)
            ->count();
    }

    /**
     * Get total requirement count.
     */
    public function getTotalRequirementCount(): int
    {
        return count($this->requirement_results ?? []);
    }

    /**
     * Get a specific requirement result by requirement ID.
     */
    public function getRequirementResult(int $requirementId): ?array
    {
        return $this->requirement_results[$requirementId] ?? null;
    }

    /**
     * Check if evaluation led to a stage transition.
     */
    public function ledToTransition(): bool
    {
        return $this->transition()->exists();
    }

    /**
     * Create a new evaluation record.
     */
    public static function record(
        Project $project,
        Gate $gate,
        bool $passed,
        array $requirementResults,
        array $failureReasons,
        array $context = [],
        string $evaluationType = self::TYPE_MANUAL
    ): self {
        return static::create([
            'project_id' => $project->id,
            'gate_id' => $gate->id,
            'passed' => $passed,
            'evaluated_at' => now(),
            'evaluated_by' => auth()->id(),
            'requirement_results' => $requirementResults,
            'failure_reasons' => $failureReasons,
            'context' => $context,
            'evaluation_type' => $evaluationType,
        ]);
    }
}
