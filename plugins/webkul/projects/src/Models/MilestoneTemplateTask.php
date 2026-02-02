<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Milestone Template Task model
 *
 * Defines task templates that are created when a milestone is generated.
 * Supports parent-child relationships for subtasks.
 *
 * @property int $id
 * @property int $milestone_template_id
 * @property int|null $parent_id
 * @property string $title
 * @property string|null $description
 * @property float $allocated_hours
 * @property bool $priority
 * @property int $sort_order
 * @property bool $is_active
 * @property int $relative_days
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MilestoneTemplateTask extends Model
{
    protected $table = 'projects_milestone_template_tasks';

    protected $fillable = [
        'milestone_template_id',
        'ai_suggestion_id',
        'parent_id',
        'title',
        'description',
        'allocated_hours',
        'priority',
        'sort_order',
        'is_active',
        'relative_days',
        'duration_days',
        'duration_type',
        'duration_per_unit',
        'duration_unit_size',
        'duration_unit_type',
        'duration_min_days',
        'duration_max_days',
        'duration_rate_key',
    ];

    protected $casts = [
        'allocated_hours' => 'float',
        'priority' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'relative_days' => 'integer',
        'duration_days' => 'integer',
        'duration_per_unit' => 'float',
        'duration_unit_size' => 'float',
        'duration_min_days' => 'integer',
        'duration_max_days' => 'integer',
    ];

    /**
     * Duration unit types available for formula calculations.
     */
    public const DURATION_UNIT_TYPES = [
        'linear_feet' => 'Linear Feet',
        'cabinets' => 'Cabinets',
        'rooms' => 'Rooms',
        'doors' => 'Doors',
        'drawers' => 'Drawers',
    ];

    /**
     * Company rate keys - maps to columns in companies table.
     * Format: 'key' => 'Human Label'
     */
    public const COMPANY_RATE_KEYS = [
        'design_concepts_lf_per_day' => 'Design Concepts (LF/day)',
        'design_revisions_lf_per_day' => 'Design Revisions (LF/day)',
        'shop_drawings_lf_per_day' => 'Shop Drawings (LF/day)',
        'cut_list_bom_lf_per_day' => 'Cut List & BOM (LF/day)',
        'rough_mill_lf_per_day' => 'Rough Mill (LF/day)',
        'cabinet_assembly_lf_per_day' => 'Cabinet Assembly (LF/day)',
        'doors_drawers_lf_per_day' => 'Doors & Drawers (LF/day)',
        'sanding_prep_lf_per_day' => 'Sanding & Prep (LF/day)',
        'finishing_lf_per_day' => 'Finishing (LF/day)',
        'hardware_install_lf_per_day' => 'Hardware Install (LF/day)',
        'installation_lf_per_day' => 'Installation (LF/day)',
        'shop_capacity_per_day' => 'Shop Capacity (LF/day)',
    ];

    /**
     * Calculate duration based on project data and company rates.
     *
     * @param float|null $linearFeet Project's estimated linear feet
     * @param int|null $cabinetCount Number of cabinets
     * @param int|null $roomCount Number of rooms
     * @param int|null $doorCount Number of doors
     * @param int|null $drawerCount Number of drawers
     * @param object|null $company Company model with production rates
     * @return int Duration in days
     */
    public function calculateDuration(
        ?float $linearFeet = null,
        ?int $cabinetCount = null,
        ?int $roomCount = null,
        ?int $doorCount = null,
        ?int $drawerCount = null,
        ?object $company = null
    ): int {
        // If fixed duration, return it directly
        if ($this->duration_type !== 'formula') {
            return $this->duration_days ?? 1;
        }

        // Get the rate - either from company or custom formula
        $lfPerDay = $this->getEffectiveRate($company);

        if (!$lfPerDay || $lfPerDay <= 0) {
            return $this->duration_days ?? 1;
        }

        // Get the appropriate value based on unit type
        $unitValue = match ($this->duration_unit_type) {
            'linear_feet' => $linearFeet,
            'cabinets' => $cabinetCount,
            'rooms' => $roomCount,
            'doors' => $doorCount,
            'drawers' => $drawerCount,
            default => $linearFeet, // Default to linear feet
        };

        // If no value available, fall back to fixed duration
        if ($unitValue === null || $unitValue <= 0) {
            return $this->duration_days ?? 1;
        }

        // Calculate: project_size / rate = days
        // Example: 150 LF / 15 LF per day = 10 days
        $calculatedDays = ceil($unitValue / $lfPerDay);

        // Apply min/max bounds
        if ($this->duration_min_days !== null && $calculatedDays < $this->duration_min_days) {
            $calculatedDays = $this->duration_min_days;
        }
        if ($this->duration_max_days !== null && $calculatedDays > $this->duration_max_days) {
            $calculatedDays = $this->duration_max_days;
        }

        return (int) $calculatedDays;
    }

    /**
     * Get the effective production rate (LF per day).
     * Uses company rate if duration_rate_key is set, otherwise uses custom formula.
     */
    public function getEffectiveRate(?object $company = null): ?float
    {
        // If using company rate
        if ($this->duration_rate_key && $company) {
            $rateKey = $this->duration_rate_key;
            if (isset($company->$rateKey) && $company->$rateKey > 0) {
                return (float) $company->$rateKey;
            }
        }

        // Fall back to custom formula (duration_unit_size is LF per day equivalent)
        if ($this->duration_unit_size && $this->duration_unit_size > 0) {
            return (float) $this->duration_unit_size;
        }

        return null;
    }

    /**
     * Get a human-readable description of the duration formula.
     */
    public function getDurationFormulaDescriptionAttribute(): ?string
    {
        if ($this->duration_type !== 'formula') {
            return null;
        }

        // If using company rate
        if ($this->duration_rate_key) {
            $rateLabel = self::COMPANY_RATE_KEYS[$this->duration_rate_key] ?? $this->duration_rate_key;
            return "Uses company rate: {$rateLabel}";
        }

        // Custom formula
        if ($this->duration_unit_size) {
            $unitLabel = self::DURATION_UNIT_TYPES[$this->duration_unit_type] ?? $this->duration_unit_type;
            return "{$this->duration_unit_size} {$unitLabel} per day";
        }

        return null;
    }

    /**
     * Get the milestone template this task belongs to.
     */
    public function milestoneTemplate(): BelongsTo
    {
        return $this->belongsTo(MilestoneTemplate::class, 'milestone_template_id');
    }

    /**
     * Get the AI suggestion that created this task (if any).
     */
    public function aiSuggestion(): BelongsTo
    {
        return $this->belongsTo(AiTaskSuggestion::class, 'ai_suggestion_id');
    }

    /**
     * Check if this task was created from AI suggestion.
     */
    public function isFromAiSuggestion(): bool
    {
        return $this->ai_suggestion_id !== null;
    }

    /**
     * Get the parent task template (for subtasks).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get child task templates (subtasks).
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Alias for children - subtasks.
     */
    public function subtasks(): HasMany
    {
        return $this->children();
    }

    /**
     * Scope to get only active task templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only root tasks (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Check if this is a subtask.
     */
    public function isSubtask(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Get the full hierarchy depth.
     */
    public function getDepthAttribute(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }
}
