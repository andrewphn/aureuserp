<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Security\Models\User;

/**
 * CNC Cut Part Model
 *
 * Represents an individual cabinet part cut on a CNC sheet.
 * Part labels like "BS-1", "DR-2", "SH-3" identify specific cabinet components.
 * Enables QA tracking for each individual part - pass/fail/recut.
 *
 * @property int $id
 * @property int $cnc_program_part_id
 * @property string $part_label
 * @property string|null $part_type
 * @property string|null $description
 * @property string|null $component_type
 * @property int|null $component_id
 * @property float|null $x_position
 * @property float|null $y_position
 * @property float|null $width
 * @property float|null $height
 * @property float $rotation
 * @property float|null $part_width
 * @property float|null $part_height
 * @property float|null $part_thickness
 * @property string $status
 * @property string|null $failure_reason
 * @property string|null $notes
 * @property bool $inspected
 * @property \Carbon\Carbon|null $inspected_at
 * @property int|null $inspected_by
 * @property bool $is_recut
 * @property int|null $original_part_id
 * @property int $recut_count
 */
class CncCutPart extends Model
{
    use HasChatter;

    protected $table = 'projects_cnc_cut_parts';

    /**
     * Status constants for QA workflow
     */
    public const STATUS_PENDING = 'pending';      // Not yet cut
    public const STATUS_CUT = 'cut';              // Cut, awaiting inspection
    public const STATUS_PASSED = 'passed';        // QA passed
    public const STATUS_FAILED = 'failed';        // QA failed
    public const STATUS_RECUT_NEEDED = 'recut_needed';  // Needs to be recut
    public const STATUS_SCRAPPED = 'scrapped';    // Cannot be salvaged

    /**
     * Common failure reasons
     */
    public const FAILURE_CHIP_OUT = 'chip_out';
    public const FAILURE_WRONG_SIZE = 'wrong_size';
    public const FAILURE_WRONG_MATERIAL = 'wrong_material';
    public const FAILURE_MACHINE_ERROR = 'machine_error';
    public const FAILURE_MATERIAL_DEFECT = 'material_defect';
    public const FAILURE_OPERATOR_ERROR = 'operator_error';

    protected $fillable = [
        'cnc_program_part_id',
        'part_label',
        'part_type',
        'description',
        'component_type',
        'component_id',
        'x_position',
        'y_position',
        'width',
        'height',
        'rotation',
        'part_width',
        'part_height',
        'part_thickness',
        'status',
        'failure_reason',
        'notes',
        'inspected',
        'inspected_at',
        'inspected_by',
        'is_recut',
        'original_part_id',
        'recut_count',
    ];

    protected $casts = [
        'x_position' => 'decimal:3',
        'y_position' => 'decimal:3',
        'width' => 'decimal:3',
        'height' => 'decimal:3',
        'rotation' => 'decimal:3',
        'part_width' => 'decimal:3',
        'part_height' => 'decimal:3',
        'part_thickness' => 'decimal:3',
        'inspected' => 'boolean',
        'inspected_at' => 'datetime',
        'is_recut' => 'boolean',
        'recut_count' => 'integer',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * The sheet this part was cut on
     */
    public function sheet(): BelongsTo
    {
        return $this->belongsTo(CncProgramPart::class, 'cnc_program_part_id');
    }

    /**
     * Alias for sheet() - the parent CNC program part (sheet file)
     */
    public function cncProgramPart(): BelongsTo
    {
        return $this->belongsTo(CncProgramPart::class, 'cnc_program_part_id');
    }

    /**
     * The inspector who checked this part
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    /**
     * The linked cabinet component (door, drawer, panel, etc.)
     */
    public function component(): MorphTo
    {
        return $this->morphTo('component', 'component_type', 'component_id');
    }

    /**
     * Original part if this is a recut
     */
    public function originalPart(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_part_id');
    }

    /**
     * Recuts of this part
     */
    public function recuts(): HasMany
    {
        return $this->hasMany(self::class, 'original_part_id');
    }

    // =========================================================================
    // QA Workflow Methods
    // =========================================================================

    /**
     * Mark part as cut (ready for inspection)
     */
    public function markCut(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_CUT;
        return $this->save();
    }

    /**
     * Pass QA inspection
     */
    public function passInspection(?int $inspectorId = null, ?string $notes = null): bool
    {
        $this->status = self::STATUS_PASSED;
        $this->inspected = true;
        $this->inspected_at = now();
        $this->inspected_by = $inspectorId ?? auth()->id();

        if ($notes) {
            $this->notes = $notes;
        }

        $result = $this->save();

        // Log to chatter
        if ($result) {
            $this->addMessage([
                'type' => 'note',
                'body' => "Part passed QA inspection" . ($notes ? ": {$notes}" : ""),
                'is_internal' => true,
            ]);
        }

        return $result;
    }

    /**
     * Fail QA inspection
     */
    public function failInspection(string $reason, ?int $inspectorId = null, ?string $notes = null): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->failure_reason = $reason;
        $this->inspected = true;
        $this->inspected_at = now();
        $this->inspected_by = $inspectorId ?? auth()->id();

        if ($notes) {
            $this->notes = $notes;
        }

        $result = $this->save();

        // Log to chatter
        if ($result) {
            $reasonLabel = self::getFailureReasons()[$reason] ?? $reason;
            $this->addMessage([
                'type' => 'note',
                'body' => "Part failed QA: {$reasonLabel}" . ($notes ? " - {$notes}" : ""),
                'is_internal' => true,
            ]);
        }

        return $result;
    }

    /**
     * Mark as needing recut
     */
    public function markForRecut(?string $notes = null): bool
    {
        $this->status = self::STATUS_RECUT_NEEDED;

        if ($notes) {
            $this->notes = ($this->notes ? $this->notes . "\n" : "") . "Recut needed: {$notes}";
        }

        $result = $this->save();

        if ($result) {
            $this->addMessage([
                'type' => 'note',
                'body' => "Part marked for recut" . ($notes ? ": {$notes}" : ""),
                'is_internal' => true,
            ]);
        }

        return $result;
    }

    /**
     * Mark as scrapped (cannot be salvaged)
     */
    public function markScrapped(?string $reason = null): bool
    {
        $this->status = self::STATUS_SCRAPPED;

        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : "") . "Scrapped: {$reason}";
        }

        $result = $this->save();

        if ($result) {
            $this->addMessage([
                'type' => 'note',
                'body' => "Part scrapped" . ($reason ? ": {$reason}" : ""),
                'is_internal' => true,
            ]);
        }

        return $result;
    }

    /**
     * Create a recut part based on this one
     */
    public function createRecut(): self
    {
        $recut = self::create([
            'cnc_program_part_id' => null, // Will be set when nested on new sheet
            'part_label' => $this->part_label,
            'part_type' => $this->part_type,
            'description' => $this->description . ' (RECUT)',
            'component_type' => $this->component_type,
            'component_id' => $this->component_id,
            'part_width' => $this->part_width,
            'part_height' => $this->part_height,
            'part_thickness' => $this->part_thickness,
            'status' => self::STATUS_PENDING,
            'is_recut' => true,
            'original_part_id' => $this->id,
            'recut_count' => $this->recut_count + 1,
        ]);

        // Update original part status
        $this->status = self::STATUS_RECUT_NEEDED;
        $this->save();

        // Log to chatter
        $this->addMessage([
            'type' => 'note',
            'body' => "Recut created: Part #{$recut->id}",
            'is_internal' => true,
        ]);

        return $recut;
    }

    // =========================================================================
    // Accessors & Helpers
    // =========================================================================

    /**
     * Get the CNC program (grandparent)
     */
    public function getCncProgramAttribute(): ?CncProgram
    {
        return $this->sheet?->cncProgram;
    }

    /**
     * Get the project (great-grandparent)
     */
    public function getProjectAttribute(): ?Project
    {
        return $this->cnc_program?->project;
    }

    /**
     * Parse part type from label (e.g., "BS-1" -> "base_side")
     */
    public function getInferredPartTypeAttribute(): ?string
    {
        $label = strtoupper($this->part_label ?? '');

        return match (true) {
            str_starts_with($label, 'BS') => 'base_side',
            str_starts_with($label, 'DR') => 'drawer_front',
            str_starts_with($label, 'DF') => 'drawer_front',
            str_starts_with($label, 'DB') => 'drawer_bottom',
            str_starts_with($label, 'DO') => 'door',
            str_starts_with($label, 'SH') => 'shelf',
            str_starts_with($label, 'BK') => 'back_panel',
            str_starts_with($label, 'TP') => 'top_panel',
            str_starts_with($label, 'BT') => 'bottom_panel',
            str_starts_with($label, 'ST') => 'stretcher',
            str_starts_with($label, 'FK') => 'face_frame',
            str_starts_with($label, 'KK') => 'kickplate',
            default => null,
        };
    }

    /**
     * Get part type display name
     */
    public function getPartTypeNameAttribute(): string
    {
        $type = $this->part_type ?? $this->inferred_part_type;

        return match ($type) {
            'base_side' => 'Base Side',
            'drawer_front' => 'Drawer Front',
            'drawer_bottom' => 'Drawer Bottom',
            'door' => 'Door',
            'shelf' => 'Shelf',
            'back_panel' => 'Back Panel',
            'top_panel' => 'Top Panel',
            'bottom_panel' => 'Bottom Panel',
            'stretcher' => 'Stretcher',
            'face_frame' => 'Face Frame',
            'kickplate' => 'Kickplate',
            default => ucfirst(str_replace('_', ' ', $type ?? 'Unknown')),
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'gray',
            self::STATUS_CUT => 'info',
            self::STATUS_PASSED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_RECUT_NEEDED => 'warning',
            self::STATUS_SCRAPPED => 'danger',
            default => 'gray',
        };
    }

    /**
     * Check if part needs attention (failed or needs recut)
     */
    public function getNeedsAttentionAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_FAILED,
            self::STATUS_RECUT_NEEDED,
        ]);
    }

    /**
     * Get dimensions as string
     */
    public function getDimensionsAttribute(): ?string
    {
        if (!$this->part_width || !$this->part_height) {
            return null;
        }

        $dims = "{$this->part_width}\" x {$this->part_height}\"";

        if ($this->part_thickness) {
            $dims .= " x {$this->part_thickness}\"";
        }

        return $dims;
    }

    // =========================================================================
    // Static Helpers
    // =========================================================================

    /**
     * Get available status options
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CUT => 'Cut',
            self::STATUS_PASSED => 'Passed QA',
            self::STATUS_FAILED => 'Failed QA',
            self::STATUS_RECUT_NEEDED => 'Recut Needed',
            self::STATUS_SCRAPPED => 'Scrapped',
        ];
    }

    /**
     * Get common failure reasons
     */
    public static function getFailureReasons(): array
    {
        return [
            self::FAILURE_CHIP_OUT => 'Chip Out',
            self::FAILURE_WRONG_SIZE => 'Wrong Size',
            self::FAILURE_WRONG_MATERIAL => 'Wrong Material',
            self::FAILURE_MACHINE_ERROR => 'Machine Error',
            self::FAILURE_MATERIAL_DEFECT => 'Material Defect',
            self::FAILURE_OPERATOR_ERROR => 'Operator Error',
        ];
    }

    /**
     * Get part type options
     */
    public static function getPartTypeOptions(): array
    {
        return [
            'base_side' => 'Base Side',
            'drawer_front' => 'Drawer Front',
            'drawer_bottom' => 'Drawer Bottom',
            'door' => 'Door',
            'shelf' => 'Shelf',
            'back_panel' => 'Back Panel',
            'top_panel' => 'Top Panel',
            'bottom_panel' => 'Bottom Panel',
            'stretcher' => 'Stretcher',
            'face_frame' => 'Face Frame',
            'kickplate' => 'Kickplate',
        ];
    }
}
