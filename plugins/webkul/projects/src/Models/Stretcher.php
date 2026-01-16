<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Product\Models\Product;
use Webkul\Project\Contracts\CabinetComponentInterface;
use Webkul\Support\Traits\HasComplexityScore;
use Webkul\Support\Traits\HasFormattedDimensions;
use Webkul\Support\Traits\HasFullCode;
use Webkul\Project\Traits\HasEntityLock;

/**
 * Stretcher Model
 *
 * Represents horizontal support rails in base cabinets.
 * Hierarchy: Project -> Room -> Location -> Cabinet Run -> Cabinet -> Section -> Stretcher
 *
 * Stretchers are positioned at the top of base cabinets to:
 * - Hold the cabinet square and stable
 * - Provide a surface to attach the countertop
 * - Give drawer slides something to mount to
 *
 * Rule: Number of stretchers = 2 (front + back) + drawer_count (one per drawer)
 *
 * @property int $id
 * @property int $cabinet_id
 * @property int|null $section_id
 * @property string $position Position: front, back, drawer_support
 * @property int $stretcher_number Stretcher number within cabinet
 * @property string|null $full_code Hierarchical code (e.g., TCS-0554-K1-SW-B1-STR1)
 * @property float $width_inches Stretcher width (= cabinet inside width)
 * @property float $depth_inches Stretcher depth (typically 3-4")
 * @property float $thickness_inches Stretcher thickness (standard 3/4")
 * @property float|null $position_from_front_inches Distance from cabinet front
 * @property float|null $position_from_top_inches Distance from cabinet top (to top of stretcher)
 * @property float|null $position_from_bottom_inches Distance from cabinet bottom (to top of stretcher)
 * @property float|null $position_override_inches Manual override position from CAD
 * @property string|null $position_source Source of position: calculated, cad_override, manual
 * @property string $material Material type: plywood, solid_wood, mdf
 * @property int|null $product_id Linked inventory product
 * @property bool $supports_drawer Whether this stretcher supports a drawer
 * @property int|null $drawer_id Linked drawer for drawer_support position
 * @property float|null $cut_width_inches Exact cut width for CNC
 * @property float|null $cut_width_shop_inches Shop-rounded width (to 1/16")
 * @property float|null $cut_depth_inches Exact cut depth for CNC
 * @property float|null $cut_depth_shop_inches Shop-rounded depth (to 1/16")
 *
 * @property-read Cabinet $cabinet
 * @property-read CabinetSection|null $section
 * @property-read Drawer|null $drawer
 * @property-read Product|null $product
 */
class Stretcher extends Model implements CabinetComponentInterface
{
    use HasFactory, SoftDeletes, HasFullCode, HasComplexityScore, HasFormattedDimensions, HasEntityLock;

    protected $table = 'projects_stretchers';

    /**
     * Position constants
     */
    public const POSITION_FRONT = 'front';
    public const POSITION_BACK = 'back';
    public const POSITION_DRAWER_SUPPORT = 'drawer_support';

    /**
     * Position labels for UI
     */
    public const POSITIONS = [
        self::POSITION_FRONT => 'Front Stretcher',
        self::POSITION_BACK => 'Back Stretcher',
        self::POSITION_DRAWER_SUPPORT => 'Drawer Support Stretcher',
    ];

    /**
     * Standard dimensions (in inches)
     *
     * TCS Standard (Bryan Patton, Jan 2025): "3 inch stretchers"
     */
    public const STANDARD_DEPTH_INCHES = 3.0;       // 3" (TCS standard)
    public const STANDARD_THICKNESS_INCHES = 0.75;  // 3/4"
    public const MINIMUM_DEPTH_INCHES = 2.5;        // 2-1/2"
    public const MAXIMUM_DEPTH_INCHES = 4.0;        // 4"

    protected $fillable = [
        'cabinet_id',
        'section_id',
        'position',
        'stretcher_number',
        'full_code',
        'width_inches',
        'depth_inches',
        'thickness_inches',
        'position_from_front_inches',
        'position_from_top_inches',
        'position_from_bottom_inches',
        'position_override_inches',
        'position_source',
        'material',
        'product_id',
        'supports_drawer',
        'drawer_id',
        'cut_width_inches',
        'cut_width_shop_inches',
        'cut_depth_inches',
        'cut_depth_shop_inches',
        'cut_at',
        'edge_banded_at',
        'installed_at',
        'qc_passed',
        'qc_notes',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'stretcher_number' => 'integer',
            'width_inches' => 'float',
            'depth_inches' => 'float',
            'thickness_inches' => 'float',
            'position_from_front_inches' => 'float',
            'position_from_top_inches' => 'float',
            'position_from_bottom_inches' => 'float',
            'position_override_inches' => 'float',
            'supports_drawer' => 'boolean',
            'cut_width_inches' => 'float',
            'cut_width_shop_inches' => 'float',
            'cut_depth_inches' => 'float',
            'cut_depth_shop_inches' => 'float',
            'cut_at' => 'datetime',
            'edge_banded_at' => 'datetime',
            'installed_at' => 'datetime',
            'qc_passed' => 'boolean',
        ];
    }

    /**
     * Override width field for HasFormattedDimensions trait.
     */
    protected function getWidthField(): string
    {
        return 'width_inches';
    }

    /**
     * Override height field - stretchers have depth, not height.
     */
    protected function getHeightField(): string
    {
        return 'depth_inches';
    }

    /**
     * Stretchers have thickness instead of depth.
     */
    protected function hasDepthField(): bool
    {
        return false;
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CabinetSection::class, 'section_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function drawer(): BelongsTo
    {
        return $this->belongsTo(Drawer::class, 'drawer_id');
    }

    /**
     * Hardware requirements for this stretcher
     */
    public function hardwareRequirements(): HasMany
    {
        return $this->hasMany(HardwareRequirement::class, 'stretcher_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeOrdered($query)
    {
        return $query->orderBy('stretcher_number');
    }

    public function scopeFront($query)
    {
        return $query->where('position', self::POSITION_FRONT);
    }

    public function scopeBack($query)
    {
        return $query->where('position', self::POSITION_BACK);
    }

    public function scopeDrawerSupport($query)
    {
        return $query->where('position', self::POSITION_DRAWER_SUPPORT);
    }

    public function scopeStructural($query)
    {
        return $query->whereIn('position', [self::POSITION_FRONT, self::POSITION_BACK]);
    }

    // ========================================
    // CABINET COMPONENT INTERFACE
    // ========================================

    /**
     * Get the component code for this stretcher
     * Format: STR1, STR2, etc.
     */
    public function getComponentCode(): string
    {
        return 'STR' . ($this->stretcher_number ?? 1);
    }

    /**
     * Get the component's name.
     */
    public function getComponentName(): ?string
    {
        return self::POSITIONS[$this->position] ?? 'Stretcher';
    }

    /**
     * Get the component's number.
     */
    public function getComponentNumber(): ?int
    {
        return $this->stretcher_number;
    }

    /**
     * Get the component type identifier.
     */
    public static function getComponentType(): string
    {
        return 'stretcher';
    }

    // ========================================
    // COMPUTED ATTRIBUTES
    // ========================================

    /**
     * Get formatted dimensions for display
     */
    public function getFormattedDimensionsDisplayAttribute(): string
    {
        return $this->getMeasurementFormatter()->formatDimensions(
            $this->width_inches,
            $this->depth_inches,
            $this->thickness_inches
        );
    }

    /**
     * Get position label for display
     */
    public function getPositionLabelAttribute(): string
    {
        return self::POSITIONS[$this->position] ?? $this->position;
    }

    /**
     * Check if this is a structural stretcher (front or back)
     */
    public function getIsStructuralAttribute(): bool
    {
        return in_array($this->position, [self::POSITION_FRONT, self::POSITION_BACK]);
    }

    /**
     * Check if this is a drawer support stretcher
     */
    public function getIsDrawerSupportAttribute(): bool
    {
        return $this->position === self::POSITION_DRAWER_SUPPORT;
    }

    /**
     * Get the effective position from top (uses override if set).
     *
     * TCS Rule (Bryan Patton, Jan 2025):
     * - Stretcher splits the gap between drawer faces
     * - Override allows CAD-specified positions to take precedence
     */
    public function getEffectivePositionFromTopAttribute(): ?float
    {
        // Override takes precedence
        if ($this->position_override_inches !== null) {
            return $this->position_override_inches;
        }

        return $this->position_from_top_inches;
    }

    /**
     * Get the effective position from bottom.
     *
     * Calculated from position_from_top if not directly set.
     */
    public function getEffectivePositionFromBottomAttribute(): ?float
    {
        if ($this->position_from_bottom_inches !== null) {
            return $this->position_from_bottom_inches;
        }

        // Calculate from position_from_top if cabinet available
        if ($this->position_from_top_inches !== null && $this->cabinet) {
            $boxHeight = ($this->cabinet->height_inches ?? 30) - ($this->cabinet->toe_kick_height_inches ?? 4);
            return $boxHeight - $this->position_from_top_inches;
        }

        return null;
    }

    // ========================================
    // CUT LIST HELPERS
    // ========================================

    /**
     * Round a dimension to the nearest 1/16 inch (shop standard)
     */
    public static function roundToSixteenth(float $inches): float
    {
        return round($inches * 16) / 16;
    }

    /**
     * Calculate and set shop-rounded dimensions
     */
    public function calculateCutDimensions(): void
    {
        $this->cut_width_inches = $this->width_inches;
        $this->cut_width_shop_inches = self::roundToSixteenth($this->width_inches);
        $this->cut_depth_inches = $this->depth_inches;
        $this->cut_depth_shop_inches = self::roundToSixteenth($this->depth_inches);
    }

    /**
     * Get cut list data for this stretcher
     */
    public function getCutListDataAttribute(): array
    {
        return [
            'part' => $this->getComponentName(),
            'code' => $this->full_code ?? $this->getComponentCode(),
            'qty' => 1,
            'width' => $this->cut_width_shop_inches ?? self::roundToSixteenth($this->width_inches),
            'depth' => $this->cut_depth_shop_inches ?? self::roundToSixteenth($this->depth_inches),
            'thickness' => $this->thickness_inches,
            'material' => $this->material,
            'position' => $this->position,
            'drawer_id' => $this->drawer_id,
        ];
    }

    // ========================================
    // BOOT
    // ========================================

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($stretcher) {
            // Auto-calculate cut dimensions if width/depth are set
            if ($stretcher->width_inches && $stretcher->depth_inches) {
                $stretcher->calculateCutDimensions();
            }

            // Generate full code
            if (!$stretcher->full_code) {
                $stretcher->full_code = $stretcher->generateFullCode();
            }
        });
    }

    /**
     * Generate the complete hierarchical code for this stretcher
     * Format: TCS-0554-K1-SW-B1-STR1
     */
    public function generateFullCode(): string
    {
        $cabinet = $this->cabinet;
        if (!$cabinet) {
            return 'STR' . ($this->stretcher_number ?? 1);
        }

        $cabinetCode = $cabinet->generateFullCode();
        return $cabinetCode ? "{$cabinetCode}-{$this->getComponentCode()}" : $this->getComponentCode();
    }
}
