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
use Webkul\Project\Traits\HasOpeningPosition;

/**
 * False Front Model
 *
 * Represents a decorative panel that looks like a drawer but doesn't open.
 * Hierarchy: Project -> Room -> Location -> Cabinet Run -> Cabinet -> Section -> FalseFront
 *
 * Common uses:
 * - Sink base cabinets (panel above sink where plumbing prevents real drawer)
 * - Appliance openings (decorative panels to match surrounding fronts)
 * - Tilt-out trays (false fronts that hinge at bottom for sponge/brush storage)
 *
 * Key construction element: BACKING RAIL - support strip behind false front
 * that provides mounting surface and structural support.
 *
 * Cut List Note: False fronts generate TWO parts:
 * 1. False Front Panel - the visible decorative front
 * 2. Backing Rail - the support strip (if has_backing_rail = true)
 *
 * @property int $id
 * @property int $cabinet_id
 * @property int|null $section_id
 * @property int $false_front_number
 * @property string|null $false_front_name
 * @property string|null $full_code Hierarchical code (e.g., TCS-0554-K1-SW-B1-FF1)
 * @property int $sort_order
 * @property string $false_front_type 'fixed' or 'tilt_out'
 * @property float|null $width_inches Panel width
 * @property float|null $height_inches Panel height
 * @property float $thickness_inches Panel thickness (default 3/4")
 * @property bool $has_backing_rail Whether to include backing rail
 * @property float $backing_rail_width_inches Backing rail width (default 3-1/2")
 * @property float|null $backing_rail_height_inches Backing rail height
 * @property float $backing_rail_thickness_inches Backing rail thickness (default 3/4")
 * @property string $backing_rail_material Backing rail material
 * @property string $backing_rail_position Position: top, center, bottom
 *
 * @property-read Cabinet $cabinet
 * @property-read CabinetSection|null $section
 * @property-read Product|null $product
 */
class FalseFront extends Model implements CabinetComponentInterface
{
    use HasFactory, SoftDeletes, HasFullCode, HasComplexityScore, HasFormattedDimensions, HasEntityLock, HasOpeningPosition;

    protected $table = 'projects_false_fronts';

    /**
     * False front types
     */
    public const TYPE_FIXED = 'fixed';
    public const TYPE_TILT_OUT = 'tilt_out';

    /**
     * Type labels for UI
     */
    public const TYPES = [
        self::TYPE_FIXED => 'Fixed Panel',
        self::TYPE_TILT_OUT => 'Tilt-Out Tray',
    ];

    /**
     * Backing rail position options
     */
    public const BACKING_RAIL_POSITIONS = [
        'top' => 'Top of Panel',
        'center' => 'Center of Panel',
        'bottom' => 'Bottom of Panel',
    ];

    /**
     * Standard backing rail dimensions (in inches)
     */
    public const STANDARD_BACKING_RAIL_WIDTH_INCHES = 3.5;      // 3-1/2"
    public const STANDARD_BACKING_RAIL_THICKNESS_INCHES = 0.75; // 3/4"
    public const STANDARD_PANEL_THICKNESS_INCHES = 0.75;        // 3/4"

    /**
     * Common tilt hardware options
     */
    public const TILT_HARDWARE_OPTIONS = [
        'rev_a_shelf_6572' => 'Rev-A-Shelf 6572 (Standard)',
        'blum_tandembox' => 'Blum TANDEMBOX Tip-On',
        'hinged_euro' => 'Euro-Style Hinges (Bottom)',
    ];

    protected $fillable = [
        'cabinet_id',
        'section_id',
        'false_front_number',
        'false_front_name',
        'full_code',
        'sort_order',
        'false_front_type',
        // Panel dimensions
        'width_inches',
        'height_inches',
        'thickness_inches',
        // Backing rail
        'has_backing_rail',
        'backing_rail_width_inches',
        'backing_rail_height_inches',
        'backing_rail_thickness_inches',
        'backing_rail_material',
        'backing_rail_position',
        // Style
        'profile_type',
        'rail_width_inches',
        'stile_width_inches',
        'finish_type',
        'paint_color',
        'stain_color',
        // Tilt hardware
        'has_tilt_hardware',
        'tilt_hardware_model',
        'tilt_hardware_product_id',
        // Decorative hardware
        'has_decorative_hardware',
        'decorative_hardware_model',
        'decorative_hardware_product_id',
        // Mounting
        'mounting_screw_length_inches',
        'mounting_screw_count',
        // Opening position (HasOpeningPosition trait)
        'position_in_opening_inches',
        'consumed_height_inches',
        'position_from_left_inches',
        'consumed_width_inches',
        // Production tracking
        'panel_cnc_cut_at',
        'panel_edge_banded_at',
        'panel_sanded_at',
        'panel_finished_at',
        'backing_rail_cut_at',
        'backing_rail_installed_at',
        'installed_at',
        // QC
        'qc_passed',
        'qc_notes',
        // Product link
        'product_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'false_front_number' => 'integer',
            'sort_order' => 'integer',
            // Panel dimensions
            'width_inches' => 'float',
            'height_inches' => 'float',
            'thickness_inches' => 'float',
            // Backing rail
            'has_backing_rail' => 'boolean',
            'backing_rail_width_inches' => 'float',
            'backing_rail_height_inches' => 'float',
            'backing_rail_thickness_inches' => 'float',
            // Style
            'rail_width_inches' => 'float',
            'stile_width_inches' => 'float',
            // Hardware
            'has_tilt_hardware' => 'boolean',
            'has_decorative_hardware' => 'boolean',
            'mounting_screw_length_inches' => 'float',
            'mounting_screw_count' => 'integer',
            // Opening position
            'position_in_opening_inches' => 'float',
            'consumed_height_inches' => 'float',
            'position_from_left_inches' => 'float',
            'consumed_width_inches' => 'float',
            // Production timestamps
            'panel_cnc_cut_at' => 'datetime',
            'panel_edge_banded_at' => 'datetime',
            'panel_sanded_at' => 'datetime',
            'panel_finished_at' => 'datetime',
            'backing_rail_cut_at' => 'datetime',
            'backing_rail_installed_at' => 'datetime',
            'installed_at' => 'datetime',
            // QC
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
     * Override height field for HasFormattedDimensions trait.
     */
    protected function getHeightField(): string
    {
        return 'height_inches';
    }

    /**
     * False front panel has no depth field (only thickness).
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

    public function tiltHardwareProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'tilt_hardware_product_id');
    }

    public function decorativeHardwareProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'decorative_hardware_product_id');
    }

    /**
     * Hardware requirements for this false front.
     */
    public function hardwareRequirements(): HasMany
    {
        return $this->hasMany(HardwareRequirement::class, 'false_front_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeFixed($query)
    {
        return $query->where('false_front_type', self::TYPE_FIXED);
    }

    public function scopeTiltOut($query)
    {
        return $query->where('false_front_type', self::TYPE_TILT_OUT);
    }

    // ========================================
    // CABINET COMPONENT INTERFACE
    // ========================================

    /**
     * Get the component code for this false front.
     * Format: FF1, FF2, etc.
     */
    public function getComponentCode(): string
    {
        return 'FF' . ($this->false_front_number ?? 1);
    }

    /**
     * Get the component's name.
     */
    public function getComponentName(): ?string
    {
        return $this->false_front_name;
    }

    /**
     * Get the component's number.
     */
    public function getComponentNumber(): ?int
    {
        return $this->false_front_number;
    }

    /**
     * Get the component type identifier.
     */
    public static function getComponentType(): string
    {
        return 'false_front';
    }

    // ========================================
    // COMPUTED ATTRIBUTES
    // ========================================

    /**
     * Get formatted dimensions for display.
     */
    public function getFormattedDimensionsDisplayAttribute(): string
    {
        return $this->getMeasurementFormatter()->formatDimensions(
            $this->width_inches,
            $this->height_inches,
            $this->thickness_inches
        );
    }

    /**
     * Get type label for display.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->false_front_type] ?? $this->false_front_type;
    }

    /**
     * Check if this is a fixed type false front.
     */
    public function isFixed(): bool
    {
        return $this->false_front_type === self::TYPE_FIXED;
    }

    /**
     * Check if this is a tilt-out type false front.
     */
    public function isTiltOut(): bool
    {
        return $this->false_front_type === self::TYPE_TILT_OUT;
    }

    // ========================================
    // CUT LIST HELPERS
    // ========================================

    /**
     * Round a dimension to the nearest 1/16 inch (shop standard).
     */
    public static function roundToSixteenth(float $inches): float
    {
        return round($inches * 16) / 16;
    }

    /**
     * Get cut list data for this false front.
     * Returns array with panel and optionally backing rail.
     */
    public function getCutListDataAttribute(): array
    {
        $parts = [];

        // 1. False Front Panel
        $parts[] = [
            'part' => 'False Front Panel',
            'code' => $this->full_code ?? $this->getComponentCode(),
            'qty' => 1,
            'width' => self::roundToSixteenth($this->width_inches ?? 0),
            'height' => self::roundToSixteenth($this->height_inches ?? 0),
            'thickness' => $this->thickness_inches ?? self::STANDARD_PANEL_THICKNESS_INCHES,
            'material' => $this->profile_type ?? 'panel',
            'finish' => $this->finish_type,
            'type' => $this->false_front_type,
        ];

        // 2. Backing Rail (if enabled)
        if ($this->has_backing_rail) {
            $parts[] = [
                'part' => 'Backing Rail',
                'code' => ($this->full_code ?? $this->getComponentCode()) . '-BR',
                'qty' => 1,
                'width' => self::roundToSixteenth($this->width_inches ?? 0), // Same width as panel
                'height' => self::roundToSixteenth($this->backing_rail_width_inches ?? self::STANDARD_BACKING_RAIL_WIDTH_INCHES),
                'thickness' => $this->backing_rail_thickness_inches ?? self::STANDARD_BACKING_RAIL_THICKNESS_INCHES,
                'material' => $this->backing_rail_material ?? 'plywood',
                'finish' => null, // Usually unfinished
                'position' => $this->backing_rail_position ?? 'center',
            ];
        }

        return $parts;
    }

    /**
     * Get total part count for cut list (panel + optional backing rail).
     */
    public function getCutListPartCountAttribute(): int
    {
        return $this->has_backing_rail ? 2 : 1;
    }

    // ========================================
    // BOOT
    // ========================================

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($falseFront) {
            // Set default backing rail height to match panel height if not set
            if ($falseFront->has_backing_rail && empty($falseFront->backing_rail_height_inches)) {
                $falseFront->backing_rail_height_inches = $falseFront->height_inches;
            }

            // Generate full code if not set
            if (empty($falseFront->full_code)) {
                $falseFront->full_code = $falseFront->generateFullCode();
            }
        });
    }

    /**
     * Generate the complete hierarchical code for this false front.
     * Format: TCS-0554-K1-SW-B1-FF1
     */
    public function generateFullCode(): string
    {
        $cabinet = $this->cabinet;
        if (!$cabinet) {
            return 'FF' . ($this->false_front_number ?? 1);
        }

        $cabinetCode = $cabinet->generateFullCode();
        return $cabinetCode ? "{$cabinetCode}-{$this->getComponentCode()}" : $this->getComponentCode();
    }
}
