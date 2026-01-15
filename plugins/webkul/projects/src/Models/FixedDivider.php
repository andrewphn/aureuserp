<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Product\Models\Product;
use Webkul\Support\Traits\HasFormattedDimensions;

/**
 * Fixed Divider Model
 *
 * Represents a full-depth fixed divider within a cabinet.
 * Used for:
 * - Section division (between drawer section and hanging section)
 * - Smell isolation (trash cabinets)
 * - Structural support
 *
 * Based on TCS shop practices (Bryan Patton, Jan 2025):
 * "A full depth fixed divider if it needed a division between a drawer
 * section and a hanging section... or if it was a trash cabinet and
 * you didn't want the smells to come through"
 *
 * Hierarchy: Project → Room → Room Location → Cabinet Run → Cabinet → Fixed Divider
 *
 * @property int $id
 * @property int $cabinet_id
 * @property float|null $position_from_left_inches
 * @property float|null $position_from_bottom_inches
 * @property string $orientation
 * @property string $purpose
 * @property float|null $width_inches
 * @property float|null $height_inches
 * @property float|null $depth_inches
 * @property float $thickness_inches
 * @property string $material
 * @property int|null $material_product_id
 * @property string|null $notes
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class FixedDivider extends Model
{
    use HasFactory, SoftDeletes, HasFormattedDimensions;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'projects_fixed_dividers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cabinet_id',
        'position_from_left_inches',
        'position_from_bottom_inches',
        'orientation',
        'purpose',
        'width_inches',
        'height_inches',
        'depth_inches',
        'thickness_inches',
        'material',
        'material_product_id',
        'notes',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position_from_left_inches' => 'float',
            'position_from_bottom_inches' => 'float',
            'width_inches' => 'float',
            'height_inches' => 'float',
            'depth_inches' => 'float',
            'thickness_inches' => 'float',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Divider orientations
     */
    public const ORIENTATIONS = [
        'vertical' => 'Vertical (Side-to-Side)',
        'horizontal' => 'Horizontal (Top-to-Bottom)',
    ];

    /**
     * Divider purposes
     */
    public const PURPOSES = [
        'section_division' => 'Section Division',
        'smell_isolation' => 'Smell Isolation (Trash)',
        'structural' => 'Structural Support',
        'drawer_hanging_division' => 'Drawer/Hanging Division',
    ];

    /**
     * TCS Standard Material (from Bryan)
     */
    public const DEFAULT_MATERIAL = '3/4 maple plywood';
    public const DEFAULT_THICKNESS = 0.75;

    /**
     * Get the cabinet this divider belongs to.
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_id');
    }

    /**
     * Get the material product for this divider.
     */
    public function materialProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'material_product_id');
    }

    /**
     * Check if this is a vertical divider.
     */
    public function isVertical(): bool
    {
        return $this->orientation === 'vertical';
    }

    /**
     * Check if this is a horizontal divider.
     */
    public function isHorizontal(): bool
    {
        return $this->orientation === 'horizontal';
    }

    /**
     * Check if this is for smell isolation (trash cabinet).
     */
    public function isSmellIsolation(): bool
    {
        return $this->purpose === 'smell_isolation';
    }

    /**
     * Get the formatted purpose label.
     */
    public function getPurposeLabelAttribute(): string
    {
        return self::PURPOSES[$this->purpose] ?? ucfirst(str_replace('_', ' ', $this->purpose));
    }

    /**
     * Get the formatted orientation label.
     */
    public function getOrientationLabelAttribute(): string
    {
        return self::ORIENTATIONS[$this->orientation] ?? ucfirst($this->orientation);
    }

    /**
     * Scope to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope to filter by purpose.
     */
    public function scopeForPurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    /**
     * Scope to filter by orientation.
     */
    public function scopeVertical($query)
    {
        return $query->where('orientation', 'vertical');
    }

    /**
     * Scope to filter by orientation.
     */
    public function scopeHorizontal($query)
    {
        return $query->where('orientation', 'horizontal');
    }

    /**
     * Calculate square footage of this divider for BOM.
     */
    public function getSquareFeetAttribute(): float
    {
        $width = $this->width_inches ?? 0;
        $height = $this->height_inches ?? 0;

        return round(($width * $height) / 144, 2); // Convert sq in to sq ft
    }

    /**
     * Auto-set dimensions from cabinet if not specified.
     */
    public function inferDimensionsFromCabinet(): void
    {
        if (!$this->cabinet) {
            $this->load('cabinet');
        }

        $cabinet = $this->cabinet;
        if (!$cabinet) {
            return;
        }

        // For vertical dividers, height = cabinet interior height, depth = cabinet depth
        if ($this->isVertical()) {
            if (!$this->height_inches) {
                // Cabinet height minus toe kick (4.5") minus stretcher height (3")
                $toeKick = $cabinet->toe_kick_height ?? 4.5;
                $stretcher = $cabinet->stretcher_height_inches ?? 3.0;
                $this->height_inches = ($cabinet->height_inches ?? 30) - $toeKick - $stretcher;
            }
            if (!$this->depth_inches) {
                $this->depth_inches = $cabinet->depth_inches ?? 24;
            }
        }

        // For horizontal dividers, width = cabinet interior width, depth = cabinet depth
        if ($this->isHorizontal()) {
            if (!$this->width_inches) {
                // Cabinet width minus 2 × side thickness (0.75" each)
                $sideThickness = self::DEFAULT_THICKNESS * 2;
                $this->width_inches = ($cabinet->length_inches ?? 36) - $sideThickness;
            }
            if (!$this->depth_inches) {
                $this->depth_inches = $cabinet->depth_inches ?? 24;
            }
        }
    }
}
