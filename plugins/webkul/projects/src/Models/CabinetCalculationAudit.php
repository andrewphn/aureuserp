<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

/**
 * Cabinet Calculation Audit Model
 *
 * Tracks calculation history and discrepancies between:
 * - Cabinet stored values vs calculated values
 * - Cabinet values vs ConstructionTemplate standards
 * - Material-specific thickness values
 *
 * @property int $id
 * @property int $cabinet_id
 * @property int|null $construction_template_id
 * @property int|null $audited_by_user_id
 * @property string $audit_type
 * @property string $audit_status
 * @property array|null $stored_values
 * @property array|null $calculated_values
 * @property array|null $template_values
 * @property array|null $discrepancies
 * @property int $discrepancy_count
 * @property float|null $max_discrepancy_inches
 * @property string|null $max_discrepancy_field
 * @property bool $is_overridden
 * @property int|null $override_by_user_id
 * @property \Carbon\Carbon|null $override_at
 * @property string|null $override_reason
 * @property string|null $notes
 * @property string|null $trigger_source
 */
class CabinetCalculationAudit extends Model
{
    use HasFactory;

    protected $table = 'projects_cabinet_calculation_audits';

    // Audit types
    public const TYPE_INITIAL = 'initial_calculation';
    public const TYPE_RECALC = 'recalculation';
    public const TYPE_TEMPLATE_CHANGE = 'template_change';
    public const TYPE_MATERIAL_CHANGE = 'material_change';
    public const TYPE_DIMENSION_CHANGE = 'dimension_change';
    public const TYPE_VALIDATION = 'validation';

    // Audit statuses
    public const STATUS_PASSED = 'passed';
    public const STATUS_WARNING = 'warning';
    public const STATUS_FAILED = 'failed';
    public const STATUS_OVERRIDE = 'override';

    // Tolerance for discrepancies (in inches)
    public const TOLERANCE_WARNING = 0.0625;  // 1/16" - minor
    public const TOLERANCE_FAILURE = 0.125;   // 1/8" - major

    protected $fillable = [
        'cabinet_id',
        'construction_template_id',
        'audited_by_user_id',
        'audit_type',
        'audit_status',
        'stored_values',
        'calculated_values',
        'template_values',
        'discrepancies',
        'discrepancy_count',
        'max_discrepancy_inches',
        'max_discrepancy_field',
        'is_overridden',
        'override_by_user_id',
        'override_at',
        'override_reason',
        'notes',
        'trigger_source',
    ];

    protected function casts(): array
    {
        return [
            'stored_values' => 'array',
            'calculated_values' => 'array',
            'template_values' => 'array',
            'discrepancies' => 'array',
            'discrepancy_count' => 'integer',
            'max_discrepancy_inches' => 'float',
            'is_overridden' => 'boolean',
            'override_at' => 'datetime',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_id');
    }

    public function constructionTemplate(): BelongsTo
    {
        return $this->belongsTo(ConstructionTemplate::class, 'construction_template_id');
    }

    public function auditedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'audited_by_user_id');
    }

    public function overrideBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'override_by_user_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopePassed($query)
    {
        return $query->where('audit_status', self::STATUS_PASSED);
    }

    public function scopeFailed($query)
    {
        return $query->where('audit_status', self::STATUS_FAILED);
    }

    public function scopeWarning($query)
    {
        return $query->where('audit_status', self::STATUS_WARNING);
    }

    public function scopeNeedsAttention($query)
    {
        return $query->whereIn('audit_status', [self::STATUS_FAILED, self::STATUS_WARNING])
            ->where('is_overridden', false);
    }

    public function scopeForCabinet($query, int $cabinetId)
    {
        return $query->where('cabinet_id', $cabinetId);
    }

    // ========================================
    // STATUS HELPERS
    // ========================================

    public function isPassed(): bool
    {
        return $this->audit_status === self::STATUS_PASSED;
    }

    public function isFailed(): bool
    {
        return $this->audit_status === self::STATUS_FAILED;
    }

    public function isWarning(): bool
    {
        return $this->audit_status === self::STATUS_WARNING;
    }

    public function needsAttention(): bool
    {
        return in_array($this->audit_status, [self::STATUS_FAILED, self::STATUS_WARNING])
            && !$this->is_overridden;
    }

    // ========================================
    // OVERRIDE METHODS
    // ========================================

    /**
     * Override the audit discrepancy.
     */
    public function override(User $user, string $reason): self
    {
        $this->update([
            'is_overridden' => true,
            'override_by_user_id' => $user->id,
            'override_at' => now(),
            'override_reason' => $reason,
            'audit_status' => self::STATUS_OVERRIDE,
        ]);

        return $this;
    }

    // ========================================
    // DISPLAY HELPERS
    // ========================================

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->audit_status) {
            self::STATUS_PASSED => 'success',
            self::STATUS_WARNING => 'warning',
            self::STATUS_FAILED => 'danger',
            self::STATUS_OVERRIDE => 'info',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->audit_status) {
            self::STATUS_PASSED => 'Passed',
            self::STATUS_WARNING => 'Warning',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_OVERRIDE => 'Overridden',
            default => 'Unknown',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->audit_type) {
            self::TYPE_INITIAL => 'Initial Calculation',
            self::TYPE_RECALC => 'Recalculation',
            self::TYPE_TEMPLATE_CHANGE => 'Template Changed',
            self::TYPE_MATERIAL_CHANGE => 'Material Changed',
            self::TYPE_DIMENSION_CHANGE => 'Dimension Changed',
            self::TYPE_VALIDATION => 'Pre-Production Validation',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted discrepancy summary.
     */
    public function getDiscrepancySummaryAttribute(): string
    {
        if ($this->discrepancy_count === 0) {
            return 'No discrepancies';
        }

        $max = $this->max_discrepancy_inches;
        $field = $this->max_discrepancy_field;

        return sprintf(
            '%d discrepanc%s (max: %.4f" in %s)',
            $this->discrepancy_count,
            $this->discrepancy_count === 1 ? 'y' : 'ies',
            $max,
            $field ?? 'unknown field'
        );
    }

    // ========================================
    // STATIC HELPERS
    // ========================================

    public static function auditTypeOptions(): array
    {
        return [
            self::TYPE_INITIAL => 'Initial Calculation',
            self::TYPE_RECALC => 'Recalculation',
            self::TYPE_TEMPLATE_CHANGE => 'Template Changed',
            self::TYPE_MATERIAL_CHANGE => 'Material Changed',
            self::TYPE_DIMENSION_CHANGE => 'Dimension Changed',
            self::TYPE_VALIDATION => 'Pre-Production Validation',
        ];
    }

    public static function auditStatusOptions(): array
    {
        return [
            self::STATUS_PASSED => 'Passed',
            self::STATUS_WARNING => 'Warning',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_OVERRIDE => 'Overridden',
        ];
    }
}
