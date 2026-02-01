<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Webkul\Security\Models\User;

/**
 * CNC Program Part Model
 *
 * Represents individual NC files or operations within a CNC program.
 * Tracks cutting operations, their status, and operator assignments.
 *
 * @property int $id
 * @property int $cnc_program_id
 * @property string $file_name
 * @property string|null $file_path
 * @property int|null $sheet_number
 * @property string|null $operation_type
 * @property string|null $tool
 * @property int|null $file_size
 * @property string $status
 * @property string|null $material_status
 * @property \Carbon\Carbon|null $run_at
 * @property \Carbon\Carbon|null $completed_at
 * @property int|null $operator_id
 * @property string|null $notes
 * @property string|null $component_type
 * @property int|null $component_id
 * @property string|null $part_label
 * @property int $quantity
 * @property array|null $position_data
 */
class CncProgramPart extends Model
{
    protected $table = 'projects_cnc_program_parts';

    /**
     * Status constants for part workflow
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_ERROR = 'error';

    /**
     * Material status constants
     */
    public const MATERIAL_READY = 'ready';
    public const MATERIAL_PENDING = 'pending_material';
    public const MATERIAL_ORDERED = 'ordered';
    public const MATERIAL_RECEIVED = 'received';

    protected $fillable = [
        'cnc_program_id',
        'file_name',
        'file_path',
        'sheet_number',
        'operation_type',
        'tool',
        'file_size',
        'status',
        'material_status',
        'run_at',
        'completed_at',
        'operator_id',
        'notes',
        'component_type',
        'component_id',
        'part_label',
        'quantity',
        'position_data',
    ];

    protected $casts = [
        'position_data' => 'array',
        'run_at' => 'datetime',
        'completed_at' => 'datetime',
        'file_size' => 'integer',
        'quantity' => 'integer',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function cncProgram(): BelongsTo
    {
        return $this->belongsTo(CncProgram::class, 'cnc_program_id');
    }

    /**
     * Get the operator who ran this part
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Get the component (door, drawer, panel, etc.)
     */
    public function component(): MorphTo
    {
        return $this->morphTo('component', 'component_type', 'component_id');
    }

    // =========================================================================
    // Workflow Methods
    // =========================================================================

    /**
     * Start running this part
     */
    public function startRunning(?int $operatorId = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_RUNNING;
        $this->run_at = now();
        $this->operator_id = $operatorId ?? auth()->id();

        return $this->save();
    }

    /**
     * Mark this part as complete
     */
    public function markComplete(): bool
    {
        if ($this->status !== self::STATUS_RUNNING) {
            return false;
        }

        $this->status = self::STATUS_COMPLETE;
        $this->completed_at = now();
        $result = $this->save();

        // Check if all parts in the program are complete
        if ($result) {
            $this->cncProgram->checkProgramCompletion();
        }

        return $result;
    }

    /**
     * Mark this part as error
     */
    public function markError(?string $notes = null): bool
    {
        $this->status = self::STATUS_ERROR;
        if ($notes) {
            $this->notes = $notes;
        }

        return $this->save();
    }

    /**
     * Reset part to pending
     */
    public function resetToPending(): bool
    {
        $this->status = self::STATUS_PENDING;
        $this->run_at = null;
        $this->completed_at = null;

        return $this->save();
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Get the run duration in minutes
     */
    public function getRunDurationMinutesAttribute(): ?int
    {
        if (!$this->run_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();

        return $this->run_at->diffInMinutes($endTime);
    }

    /**
     * Get component type display name
     */
    public function getComponentTypeNameAttribute(): string
    {
        return match ($this->component_type) {
            'door' => 'Door',
            'drawer' => 'Drawer Front',
            'panel' => 'Panel',
            'shelf' => 'Shelf',
            'back' => 'Back Panel',
            'side' => 'Side Panel',
            'top' => 'Top Panel',
            'bottom' => 'Bottom Panel',
            default => ucfirst($this->component_type ?? 'Unknown'),
        };
    }

    /**
     * Get operation type display name
     */
    public function getOperationTypeNameAttribute(): string
    {
        return match ($this->operation_type) {
            'profile' => 'Edge Profile',
            'drilling' => 'Drilling',
            'pocket' => 'Pocket',
            'groove' => 'Groove',
            'shelf_pins' => 'Shelf Pins',
            'slide_holes' => 'Slide Holes',
            default => ucfirst($this->operation_type ?? 'Unknown'),
        };
    }

    /**
     * Get material status display name
     */
    public function getMaterialStatusNameAttribute(): string
    {
        return match ($this->material_status) {
            self::MATERIAL_READY => 'Ready',
            self::MATERIAL_PENDING => 'Pending Material',
            self::MATERIAL_ORDERED => 'Ordered',
            self::MATERIAL_RECEIVED => 'Received',
            default => 'Unknown',
        };
    }

    /**
     * Check if part is ready to run (has material)
     */
    public function isReadyToRun(): bool
    {
        return $this->status === self::STATUS_PENDING
            && in_array($this->material_status, [self::MATERIAL_READY, self::MATERIAL_RECEIVED, null]);
    }

    /**
     * Get available status options
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_RUNNING => 'Running',
            self::STATUS_COMPLETE => 'Complete',
            self::STATUS_ERROR => 'Error',
        ];
    }

    /**
     * Get available material status options
     */
    public static function getMaterialStatusOptions(): array
    {
        return [
            self::MATERIAL_READY => 'Ready',
            self::MATERIAL_PENDING => 'Pending Material',
            self::MATERIAL_ORDERED => 'Ordered',
            self::MATERIAL_RECEIVED => 'Received',
        ];
    }

    /**
     * Get available operation types
     */
    public static function getOperationTypes(): array
    {
        return [
            'profile' => 'Edge Profile',
            'drilling' => 'Drilling',
            'pocket' => 'Pocket',
            'groove' => 'Groove',
            'shelf_pins' => 'Shelf Pins',
            'slide_holes' => 'Slide Holes',
        ];
    }
}
