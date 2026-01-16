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
 * TCS CONSTRUCTION RULE (Bryan Patton, Jan 2025):
 * ALL false fronts have a backing. The backing serves DUAL PURPOSE:
 * 1. Backing for the false front panel (provides mounting surface)
 * 2. Functions AS the stretcher (eliminates need for separate stretcher piece)
 *
 * Example: 6" false front face with 7" backing = backing extends 1" past face
 * to function as the stretcher for the drawer below.
 *
 * Cut List Note: False fronts generate TWO parts:
 * 1. False Front Panel - the visible decorative front
 * 2. Backing - the support piece that ALSO serves as stretcher
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
 * @property bool $has_backing Whether to include backing (TCS: always true)
 * @property float $backing_height_inches Backing height (extends past face to serve as stretcher)
 * @property float $backing_thickness_inches Backing thickness (default 3/4")
 * @property string $backing_material Backing material
 * @property bool $backing_is_stretcher Whether backing doubles as stretcher (TCS: always true)
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
     * Standard backing dimensions (in inches)
     *
     * TCS Rule: Backing extends past false front face to serve as stretcher.
     * Backing height = false front height + overhang to reach stretcher position.
     */
    public const STANDARD_BACKING_THICKNESS_INCHES = 0.75; // 3/4"
    public const STANDARD_PANEL_THICKNESS_INCHES = 0.75;   // 3/4"
    public const DEFAULT_BACKING_OVERHANG_INCHES = 1.0;    // 1" typical overhang

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
        // Backing (TCS: all false fronts have backing that doubles as stretcher)
        'has_backing',
        'backing_height_inches',
        'backing_thickness_inches',
        'backing_material',
        'backing_is_stretcher',
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
        'backing_cut_at',
        'backing_installed_at',
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
            // Backing (doubles as stretcher)
            'has_backing' => 'boolean',
            'backing_height_inches' => 'float',
            'backing_thickness_inches' => 'float',
            'backing_is_stretcher' => 'boolean',
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
            'backing_cut_at' => 'datetime',
            'backing_installed_at' => 'datetime',
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
     *
     * TCS Rule: All false fronts have backing that doubles as stretcher.
     * Returns array with panel and backing (which serves as stretcher).
     */
    public function getCutListDataAttribute(): array
    {
        $parts = [];

        // 1. False Front Panel (decorative face)
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

        // 2. Backing (TCS: always present, doubles as stretcher)
        // Backing height extends past face to reach stretcher position
        $backingHeight = $this->backing_height_inches
            ?? (($this->height_inches ?? 0) + self::DEFAULT_BACKING_OVERHANG_INCHES);

        $parts[] = [
            'part' => 'Backing/Stretcher',
            'code' => ($this->full_code ?? $this->getComponentCode()) . '-BK',
            'qty' => 1,
            'width' => self::roundToSixteenth($this->width_inches ?? 0), // Same width as panel
            'height' => self::roundToSixteenth($backingHeight),
            'thickness' => $this->backing_thickness_inches ?? self::STANDARD_BACKING_THICKNESS_INCHES,
            'material' => $this->backing_material ?? 'plywood',
            'finish' => null, // Unfinished - hidden behind panel
            'is_stretcher' => $this->backing_is_stretcher ?? true,
            'note' => 'Backing serves dual purpose: false front backing AND stretcher',
        ];

        return $parts;
    }

    /**
     * Get total part count for cut list (panel + backing).
     * TCS: All false fronts have backing, so always 2 parts.
     */
    public function getCutListPartCountAttribute(): int
    {
        return 2; // Panel + Backing (backing is always present per TCS rule)
    }

    /**
     * Calculate backing height based on false front height and stretcher position.
     *
     * TCS Rule: Backing extends past false front face to reach stretcher.
     *
     * @param float|null $stretcherPositionFromTop Position of stretcher from top of box
     * @return float Backing height in inches
     */
    public function calculateBackingHeight(?float $stretcherPositionFromTop = null): float
    {
        $faceHeight = $this->height_inches ?? 6;

        if ($stretcherPositionFromTop !== null) {
            // Backing height = distance from top to stretcher
            return $stretcherPositionFromTop;
        }

        // Default: face height + standard overhang
        return $faceHeight + self::DEFAULT_BACKING_OVERHANG_INCHES;
    }

    // ========================================
    // BOOT
    // ========================================

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($falseFront) {
            // TCS Rule: All false fronts have backing that doubles as stretcher
            $falseFront->has_backing = true;
            $falseFront->backing_is_stretcher = true;

            // Set default backing height if not set (face height + overhang)
            if (empty($falseFront->backing_height_inches) && $falseFront->height_inches) {
                $falseFront->backing_height_inches = $falseFront->height_inches + self::DEFAULT_BACKING_OVERHANG_INCHES;
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
