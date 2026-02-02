<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * AI Task Suggestion model
 *
 * Stores AI-generated task suggestions for milestone templates with review workflow.
 *
 * @property int $id
 * @property int $milestone_template_id
 * @property int|null $created_by
 * @property int|null $reviewed_by
 * @property array $suggested_tasks
 * @property string $status
 * @property float|null $confidence_score
 * @property string|null $ai_reasoning
 * @property array|null $reviewer_corrections
 * @property string|null $reviewer_notes
 * @property \Carbon\Carbon|null $reviewed_at
 * @property string|null $prompt_context
 * @property string|null $model_used
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AiTaskSuggestion extends Model
{
    protected $table = 'projects_ai_task_suggestions';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PARTIAL = 'partial'; // Some tasks approved, some rejected

    // Confidence thresholds
    public const THRESHOLD_HIGH = 80;    // Auto-approve eligible
    public const THRESHOLD_MEDIUM = 50;  // Requires review
    // Below 50 = flagged for careful review

    protected $fillable = [
        'milestone_template_id',
        'created_by',
        'reviewed_by',
        'suggested_tasks',
        'status',
        'confidence_score',
        'ai_reasoning',
        'reviewer_corrections',
        'reviewer_notes',
        'reviewed_at',
        'prompt_context',
        'model_used',
    ];

    protected $casts = [
        'suggested_tasks' => 'array',
        'reviewer_corrections' => 'array',
        'confidence_score' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get all available status options.
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_PARTIAL => 'Partially Approved',
        ];
    }

    /**
     * Get the milestone template this suggestion belongs to.
     */
    public function milestoneTemplate(): BelongsTo
    {
        return $this->belongsTo(MilestoneTemplate::class, 'milestone_template_id');
    }

    /**
     * Get the user who created this suggestion.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who reviewed this suggestion.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    /**
     * Check if this suggestion is pending review.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if this suggestion has been approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if this suggestion has been rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Get confidence level label.
     */
    public function getConfidenceLevelAttribute(): string
    {
        if ($this->confidence_score === null) {
            return 'unknown';
        }

        if ($this->confidence_score >= self::THRESHOLD_HIGH) {
            return 'high';
        }

        if ($this->confidence_score >= self::THRESHOLD_MEDIUM) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get confidence color for UI display.
     */
    public function getConfidenceColorAttribute(): string
    {
        return match ($this->confidence_level) {
            'high' => 'success',
            'medium' => 'warning',
            'low' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get merged suggestions with reviewer corrections applied.
     */
    public function getMergedSuggestionsAttribute(): array
    {
        $suggestions = $this->suggested_tasks ?? [];
        $corrections = $this->reviewer_corrections ?? [];

        if (empty($corrections)) {
            return $suggestions;
        }

        // Apply corrections to suggestions
        foreach ($suggestions as $index => &$task) {
            if (isset($corrections[$index])) {
                $task = array_merge($task, $corrections[$index]);
            }
        }

        return $suggestions;
    }

    /**
     * Get the count of suggested tasks.
     */
    public function getSuggestedTaskCountAttribute(): int
    {
        return count($this->suggested_tasks ?? []);
    }

    /**
     * Approve all suggestions and create task templates.
     */
    public function approve(int $reviewerId, ?array $corrections = null, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewerId,
            'reviewer_corrections' => $corrections,
            'reviewer_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Reject the suggestion.
     */
    public function reject(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewerId,
            'reviewer_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Partially approve - some tasks approved, some rejected.
     */
    public function partialApprove(int $reviewerId, array $selectedTaskIndexes, ?array $corrections = null, ?string $notes = null): Collection
    {
        $this->update([
            'status' => self::STATUS_PARTIAL,
            'reviewed_by' => $reviewerId,
            'reviewer_corrections' => $corrections,
            'reviewer_notes' => $notes,
            'reviewed_at' => now(),
        ]);

        return $this->applyToTemplate($selectedTaskIndexes);
    }

    /**
     * Apply approved suggestions to create actual task templates.
     *
     * @param array|null $selectedIndexes If provided, only create tasks at these indexes
     * @return \Illuminate\Support\Collection Created MilestoneTemplateTask records
     */
    public function applyToTemplate(?array $selectedIndexes = null): \Illuminate\Support\Collection
    {
        $suggestions = $this->merged_suggestions;
        $createdTasks = collect();

        $maxSortOrder = $this->milestoneTemplate->taskTemplates()->max('sort_order') ?? 0;

        foreach ($suggestions as $index => $taskData) {
            // Skip if not in selected indexes (when doing partial approval)
            if ($selectedIndexes !== null && !in_array($index, $selectedIndexes)) {
                continue;
            }

            $sortOrder = ++$maxSortOrder;

            // Create the main task
            $task = $this->createTaskFromSuggestion($taskData, $sortOrder);
            $createdTasks->push($task);

            // Create subtasks if present
            if (!empty($taskData['subtasks'])) {
                $subtaskSortOrder = 0;
                foreach ($taskData['subtasks'] as $subtaskData) {
                    $subtaskSortOrder++;
                    $subtask = $this->createTaskFromSuggestion(
                        $subtaskData,
                        $subtaskSortOrder,
                        $task->id
                    );
                    $createdTasks->push($subtask);
                }
            }
        }

        return $createdTasks;
    }

    /**
     * Create a single task template from suggestion data.
     */
    protected function createTaskFromSuggestion(array $data, int $sortOrder, ?int $parentId = null): MilestoneTemplateTask
    {
        return MilestoneTemplateTask::create([
            'milestone_template_id' => $this->milestone_template_id,
            'ai_suggestion_id' => $this->id,
            'parent_id' => $parentId,
            'title' => $data['title'] ?? 'Untitled Task',
            'description' => $data['description'] ?? null,
            'allocated_hours' => $data['allocated_hours'] ?? 0,
            'relative_days' => $data['relative_days'] ?? 0,
            'duration_type' => $data['duration_type'] ?? 'fixed',
            'duration_days' => $data['duration_days'] ?? 1,
            'duration_rate_key' => $data['duration_rate_key'] ?? null,
            'duration_unit_type' => $data['duration_unit_type'] ?? 'linear_feet',
            'duration_unit_size' => $data['duration_unit_size'] ?? null,
            'duration_min_days' => $data['duration_min_days'] ?? null,
            'duration_max_days' => $data['duration_max_days'] ?? null,
            'priority' => $data['priority'] ?? false,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    /**
     * Scope to get pending suggestions.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get suggestions by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get suggestions for a specific milestone template.
     */
    public function scopeForTemplate($query, int $templateId)
    {
        return $query->where('milestone_template_id', $templateId);
    }
}
