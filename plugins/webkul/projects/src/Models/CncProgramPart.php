<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * CNC Program Part Model
 *
 * Links CNC programs to individual cabinet components (doors, drawers, panels, etc.)
 * Enables tracking which parts are cut in which program/sheet.
 *
 * @property int $id
 * @property int $cnc_program_id
 * @property string $component_type
 * @property int $component_id
 * @property string|null $part_label
 * @property int $quantity
 * @property int|null $sheet_number
 * @property array|null $position_data
 */
class CncProgramPart extends Model
{
    protected $table = 'projects_cnc_program_parts';

    protected $fillable = [
        'cnc_program_id',
        'component_type',
        'component_id',
        'part_label',
        'quantity',
        'sheet_number',
        'position_data',
    ];

    protected $casts = [
        'position_data' => 'array',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function cncProgram(): BelongsTo
    {
        return $this->belongsTo(CncProgram::class, 'cnc_program_id');
    }

    /**
     * Get the component (door, drawer, panel, etc.)
     */
    public function component(): MorphTo
    {
        return $this->morphTo('component', 'component_type', 'component_id');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

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
            default => ucfirst($this->component_type),
        };
    }
}
