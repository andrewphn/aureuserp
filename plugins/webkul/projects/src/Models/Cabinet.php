<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Product\Models\Product;
use Webkul\Project\Services\TcsPricingService;
use Webkul\Sale\Models\OrderLine;
use Webkul\Security\Models\User;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Support\Traits\HasComplexityScore;
use Webkul\Support\Traits\HasFormattedDimensions;
use Webkul\Project\Traits\HasEntityLock;

/**
 * Cabinet Eloquent model
 *
 * Represents a cabinet unit within a cabinet run in the project hierarchy:
 * Project → Room → Room Location → Cabinet Run → Cabinet → Section → Components
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $order_line_id
 * @property int $project_id
 * @property int $room_id
 * @property int $cabinet_run_id
 * @property int $product_variant_id
 * @property string|null $cabinet_number
 * @property string|null $full_code
 * @property int $position_in_run
 * @property float $wall_position_start_inches
 * @property float $length_inches
 * @property float $width_inches
 * @property float $depth_inches
 * @property float $height_inches
 * @property float $linear_feet
 * @property int $quantity
 * @property float $unit_price_per_lf
 * @property float $total_price
 * @property string|null $cabinet_level
 * @property string|null $material_category
 * @property string|null $finish_option
 * @property string|null $hardware_notes
 * @property string|null $custom_modifications
 * @property string|null $shop_notes
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $orderLine
 * @property-read \Illuminate\Database\Eloquent\Model|null $project
 * @property-read \Illuminate\Database\Eloquent\Model|null $room
 * @property-read \Illuminate\Database\Eloquent\Model|null $cabinetRun
 * @property-read \Illuminate\Database\Eloquent\Model|null $productVariant
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection $sections
 *
 */
class Cabinet extends Model
{
    use SoftDeletes, HasChatter, HasLogActivity, HasComplexityScore, HasFormattedDimensions, HasEntityLock;

    protected $table = 'projects_cabinets';

    /**
     * TCS Standard Dimensions (Bryan Patton, Jan 2025)
     *
     * "Cabinets for kitchens are normally 34, 3 quarter tall,
     * because countertops are typically an inch of quarter"
     */
    public const STANDARD_HEIGHTS = [
        'base' => 34.75,      // 34 3/4" (countertop makes 36")
        'wall_30' => 30,
        'wall_36' => 36,
        'wall_42' => 42,
        'tall' => 84,
        'tall_96' => 96,
    ];

    public const STANDARD_COUNTERTOP_HEIGHT = 1.25; // 1 1/4"

    /**
     * TCS Standard Toe Kick (Bryan)
     * "Toe kick standard is 4.5 inches tall"
     * "3 inches from the face" (recess)
     */
    public const STANDARD_TOE_KICK_HEIGHT = 4.5;
    public const STANDARD_TOE_KICK_RECESS = 3.0;

    /**
     * TCS Standard Stretcher (Bryan)
     * "3 inch stretchers"
     */
    public const STANDARD_STRETCHER_HEIGHT = 3.0;

    /**
     * TCS Standard Materials (Bryan)
     * "Our cabinets are built out of 3 quarter prefinished
     * maple plywood, including the backs"
     */
    public const DEFAULT_BOX_MATERIAL = '3/4 prefinished maple plywood';
    public const DEFAULT_BOX_THICKNESS = 0.75;
    public const DEFAULT_BACK_THICKNESS = 0.75;

    /**
     * TCS Standard Face Frame (Bryan)
     * "Face frame... typically is an inch and a half or inch of 3 quarter,
     * then you have an 8th inch gap to your door"
     */
    public const STANDARD_FACE_FRAME_WIDTH = 1.5;
    public const STANDARD_FACE_FRAME_DOOR_GAP = 0.125;

    /**
     * TCS Sink Cabinet (Bryan)
     * "At sink locations... sides will come up an additional 3/4 of an inch"
     */
    public const SINK_SIDE_EXTENSION = 0.75;

    /**
     * TCS Finished End Panel (Bryan, Jan 2026)
     * "Edge cabinet gets a face, 1/4 gap, extended 1/2 in toward wall"
     */
    public const FINISHED_END_GAP = 0.25;           // 1/4" gap between panel and cabinet
    public const FINISHED_END_WALL_EXTENSION = 0.5; // 1/2" extension toward wall for scribe

    /**
     * TCS Back Wall Gap (Bryan, Jan 2026)
     * "We go 1/4 off the back wall for safety"
     * This is subtracted from available depth when calculating cabinet depth
     */
    public const BACK_WALL_GAP = 0.25; // 1/4" gap from back wall

    /**
     * Top construction types
     */
    public const TOP_CONSTRUCTION_TYPES = [
        'stretchers' => 'Stretchers (Base Cabinets)',
        'full_top' => 'Full Top (Wall Cabinets)',
        'none' => 'No Top',
    ];

    protected $fillable = [
        'order_line_id',
        'project_id',
        'room_id',
        'cabinet_run_id',
        'product_variant_id',
        'cabinet_number',
        'full_code',
        'position_in_run',
        'wall_position_start_inches',
        'length_inches',
        'width_inches',
        'depth_inches',
        'height_inches',
        'linear_feet',
        'quantity',
        'unit_price_per_lf',
        'total_price',
        // Line item adjustments
        'adjustment_type',
        'adjustment_value',
        'adjustment_reason',
        'adjustment_amount',
        'final_price',
        'cabinet_level',
        'material_category',
        'finish_option',
        'hardware_notes',
        'custom_modifications',
        'shop_notes',
        'creator_id',
        // Door/Drawer Configuration
        'door_style',
        'door_mounting',
        'door_count',
        'drawer_count',
        // Hardware from Products
        'hinge_product_id',
        'hinge_quantity',
        'hinge_model',
        'slide_product_id',
        'slide_quantity',
        'slide_model',
        'product_id',
        // Production tracking timestamps
        'face_frame_cut_at',
        'door_fronts_cut_at',
        'edge_banded_at',
        'hardware_installed_at',
        'pocket_holes_at',
        'doweled_at',
        // Hardware products
        'pullout_product_id',
        'lazy_susan_product_id',
        // Cabinet configurator fields
        'construction_type',
        'section_layout_type',
        'face_frame_stile_width_inches',
        'face_frame_rail_width_inches',
        'face_frame_mid_stile_count',
        // Construction details (Bryan Patton, Jan 2025)
        'top_construction_type',
        'stretcher_height_inches',
        'sink_requires_extended_sides',
        'sink_side_extension_inches',
        'face_frame_door_gap_inches',
        // Material product links
        'box_material_product_id',
        'face_frame_material_product_id',
        'edge_banding_product_id',
        // Construction template
        'construction_template_id',
    ];

    protected $casts = [
        'length_inches' => 'decimal:2',
        'width_inches' => 'decimal:2',
        'depth_inches' => 'decimal:2',
        'height_inches' => 'decimal:2',
        'linear_feet' => 'decimal:2',
        'quantity' => 'integer',
        'unit_price_per_lf' => 'decimal:2',
        'total_price' => 'decimal:2',
        'adjustment_value' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'position_in_run' => 'integer',
        'wall_position_start_inches' => 'decimal:2',
        // Production tracking timestamps
        'face_frame_cut_at' => 'datetime',
        'door_fronts_cut_at' => 'datetime',
        'edge_banded_at' => 'datetime',
        'hardware_installed_at' => 'datetime',
        'pocket_holes_at' => 'datetime',
        'doweled_at' => 'datetime',
        // Cabinet configurator casts
        'face_frame_stile_width_inches' => 'decimal:3',
        'face_frame_rail_width_inches' => 'decimal:3',
        'face_frame_mid_stile_count' => 'integer',
        // Construction details casts
        'stretcher_height_inches' => 'decimal:3',
        'sink_requires_extended_sides' => 'boolean',
        'sink_side_extension_inches' => 'decimal:3',
        'face_frame_door_gap_inches' => 'decimal:3',
    ];

    /**
     * Override width field for HasFormattedDimensions trait.
     * Cabinet uses length_inches for cabinet width.
     */
    protected function getWidthField(): string
    {
        return 'length_inches';
    }

    /**
     * Attributes to log for Chatter activity tracking
     */
    protected $logAttributes = [
        'cabinetRun.roomLocation.room.name' => 'Room',
        'cabinetRun.name' => 'Cabinet Run',
        'cabinet_number' => 'Cabinet Number',
        'position_in_run' => 'Position in Run',
        'length_inches' => 'Length (inches)',
        'width_inches' => 'Width (inches)',
        'depth_inches' => 'Depth (inches)',
        'height_inches' => 'Height (inches)',
        'linear_feet' => 'Linear Feet',
        'quantity' => 'Quantity',
        'unit_price_per_lf' => 'Unit Price (per LF)',
        'total_price' => 'Total Price',
        'hardware_notes' => 'Hardware Notes',
        'custom_modifications' => 'Custom Modifications',
        'shop_notes' => 'Shop Notes',
    ];

    /**
     * Relationships
     */
    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class, 'order_line_id');
    }

    /**
     * Project
     *
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Room
     *
     * @return BelongsTo
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * Cabinet Run
     *
     * @return BelongsTo
     */
    public function cabinetRun(): BelongsTo
    {
        return $this->belongsTo(CabinetRun::class, 'cabinet_run_id');
    }

    /**
     * Product Variant
     *
     * @return BelongsTo
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_variant_id');
    }

    /**
     * Creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Cabinet Sections
     *
     * @return HasMany
     */
    public function sections(): HasMany
    {
        return $this->hasMany(CabinetSection::class, 'cabinet_id');
    }

    /**
     * Product (main cabinet product/SKU)
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Hinge product for this cabinet
     *
     * @return BelongsTo
     */
    public function hingeProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'hinge_product_id');
    }

    /**
     * Slide product for this cabinet
     *
     * @return BelongsTo
     */
    public function slideProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'slide_product_id');
    }

    /**
     * Pullout product for this cabinet
     *
     * @return BelongsTo
     */
    public function pulloutProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'pullout_product_id');
    }

    /**
     * Lazy susan product for this cabinet
     *
     * @return BelongsTo
     */
    public function lazySusanProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'lazy_susan_product_id');
    }

    /**
     * Box material product (sheet goods/plywood)
     *
     * @return BelongsTo
     */
    public function boxMaterialProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'box_material_product_id');
    }

    /**
     * Face frame material product (lumber)
     *
     * @return BelongsTo
     */
    public function faceFrameMaterialProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'face_frame_material_product_id');
    }

    /**
     * Edge banding product
     *
     * @return BelongsTo
     */
    public function edgeBandingProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'edge_banding_product_id');
    }

    /**
     * Construction template for this cabinet.
     *
     * If null, inherits from room -> project -> global default.
     *
     * @return BelongsTo
     */
    public function constructionTemplate(): BelongsTo
    {
        return $this->belongsTo(ConstructionTemplate::class, 'construction_template_id');
    }

    /**
     * Fixed dividers for this cabinet
     *
     * Full-depth dividers used for:
     * - Section division (drawer vs hanging)
     * - Smell isolation (trash cabinets)
     *
     * @return HasMany
     */
    public function fixedDividers(): HasMany
    {
        return $this->hasMany(FixedDivider::class, 'cabinet_id');
    }

    /**
     * Hardware requirements for this cabinet
     *
     * @return HasMany
     */
    public function hardwareRequirements(): HasMany
    {
        return $this->hasMany(HardwareRequirement::class, 'cabinet_id');
    }

    /**
     * Stretchers for this cabinet
     *
     * Stretchers are horizontal rails at the top of base cabinets that:
     * - Hold the cabinet square and stable
     * - Provide a surface to attach the countertop
     * - Give drawer slides something to mount to
     *
     * Rule: Number of stretchers = 2 (front + back) + drawer_count
     *
     * @return HasMany
     */
    public function stretchers(): HasMany
    {
        return $this->hasMany(Stretcher::class, 'cabinet_id');
    }

    /**
     * Determine if this cabinet needs stretchers
     *
     * Stretchers are required for:
     * - Base cabinets with face frame construction
     * - Cabinets with drawers (for slide mounting)
     * - Vanity cabinets
     * - Island cabinets
     *
     * Stretchers are NOT required for:
     * - Wall cabinets
     * - Frameless cabinets with full top construction
     *
     * @return bool
     */
    public function needsStretchers(): bool
    {
        // Get cabinet type from product or attributes
        $cabinetType = $this->cabinet_type
            ?? $this->productVariant?->category_name
            ?? $this->product?->category_name
            ?? 'base';

        $cabinetType = strtolower($cabinetType);

        // Wall cabinets don't need stretchers
        $wallTypes = ['wall', 'wall_diagonal_corner', 'wall_blind_corner'];
        if (in_array($cabinetType, $wallTypes)) {
            return false;
        }

        // Check construction style if available
        $constructionStyle = $this->construction_style ?? 'face_frame';
        $topConstruction = $this->top_construction ?? 'stretchers';

        // Frameless cabinets with full top don't need stretchers
        if ($constructionStyle === 'frameless' && $topConstruction === 'full_top') {
            return false;
        }

        // Face frame cabinets typically use stretchers
        if ($constructionStyle === 'face_frame') {
            return true;
        }

        // Base/tall cabinets with drawers need stretchers for slide mounting
        if (($this->drawer_count ?? 0) > 0) {
            return true;
        }

        // Check if cabinet type is in the stretcher-requiring list
        $stretcherTypes = [
            'base', 'sink_base', 'drawer_base', 'blind_base_corner', 'lazy_susan',
            'vanity', 'vanity_drawer', 'vanity_sink', 'island', 'tall', 'tall_pantry',
        ];

        return in_array($cabinetType, $stretcherTypes);
    }

    /**
     * Get the required stretcher count for this cabinet
     *
     * Rule: 2 structural (front + back) + drawer count
     *
     * @return int
     */
    public function getRequiredStretcherCountAttribute(): int
    {
        if (!$this->needsStretchers()) {
            return 0;
        }

        return 2 + ($this->drawer_count ?? 0);
    }

    /**
     * Auto-create stretchers for this cabinet using StretcherCalculator
     *
     * @param bool $force If true, delete existing stretchers first
     * @return \Illuminate\Support\Collection Collection of created stretchers
     */
    public function createStretchers(bool $force = false): \Illuminate\Support\Collection
    {
        $calculator = app(\App\Services\StretcherCalculator::class);
        return $calculator->createStretchersForCabinet($this, $force);
    }

    /**
     * Calculated Attributes
     */
    protected $appends = ['size_range', 'common_size_match'];

    /**
     * Get linear feet from length (auto-calculated)
     */
    public function getCalculatedLinearFeetAttribute(): float
    {
        return round($this->length_inches / 12, 2);
    }

    /**
     * Get total price (auto-calculated)
     */
    public function getCalculatedTotalPriceAttribute(): float
    {
        return round(
            $this->unit_price_per_lf * $this->linear_feet * $this->quantity,
            2
        );
    }

    /**
     * Get size range category for analytics/templates
     *
     * Returns: 'small', 'medium', 'large', 'extra-large'
     */
    public function getSizeRangeAttribute(): string
    {
        $linearFeet = $this->linear_feet;

        return match (true) {
            $linearFeet <= 1.5 => 'small',      // 12-18"
            $linearFeet <= 3.0 => 'medium',     // 18-36"
            $linearFeet <= 4.0 => 'large',      // 36-48"
            default => 'extra-large',           // 48"+
        };
    }

    /**
     * Check if dimensions match a common/template size
     *
     * Returns common size name if match found, null otherwise
     */
    public function getCommonSizeMatchAttribute(): ?string
    {
        $commonSizes = [
            '12" Base' => ['length' => 12, 'depth' => 24, 'height' => 30],
            '15" Base' => ['length' => 15, 'depth' => 24, 'height' => 30],
            '18" Base' => ['length' => 18, 'depth' => 24, 'height' => 30],
            '24" Base' => ['length' => 24, 'depth' => 24, 'height' => 30],
            '30" Base' => ['length' => 30, 'depth' => 24, 'height' => 30],
            '36" Base' => ['length' => 36, 'depth' => 24, 'height' => 30],
            '12" Wall' => ['length' => 12, 'depth' => 12, 'height' => 30],
            '15" Wall' => ['length' => 15, 'depth' => 12, 'height' => 30],
            '18" Wall' => ['length' => 18, 'depth' => 12, 'height' => 30],
            '24" Wall' => ['length' => 24, 'depth' => 12, 'height' => 30],
            '30" Wall' => ['length' => 30, 'depth' => 12, 'height' => 30],
            '36" Wall' => ['length' => 36, 'depth' => 12, 'height' => 30],
            '18" Tall Pantry' => ['length' => 18, 'depth' => 24, 'height' => 84],
            '24" Tall Pantry' => ['length' => 24, 'depth' => 24, 'height' => 84],
            '30" Tall Pantry' => ['length' => 30, 'depth' => 24, 'height' => 96],
        ];

        foreach ($commonSizes as $name => $dimensions) {
            if (
                abs($this->length_inches - $dimensions['length']) <= 0.5 &&
                abs($this->depth_inches - $dimensions['depth']) <= 0.5 &&
                abs($this->height_inches - $dimensions['height']) <= 0.5
            ) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Scope: Get cabinets by size range for template generation
     */
    public function scopeBySizeRange($query, string $range)
    {
        return match ($range) {
            'small' => $query->whereBetween('linear_feet', [0, 1.5]),
            'medium' => $query->whereBetween('linear_feet', [1.5, 3.0]),
            'large' => $query->whereBetween('linear_feet', [3.0, 4.0]),
            'extra-large' => $query->where('linear_feet', '>', 4.0),
            default => $query,
        };
    }

    /**
     * Scope: Get most common dimensions for template suggestions
     */
    public function scopeMostCommon($query, int $limit = 10)
    {
        return $query
            ->selectRaw('
                length_inches,
                width_inches,
                depth_inches,
                height_inches,
                COUNT(*) as usage_count
            ')
            ->groupBy(['length_inches', 'width_inches', 'depth_inches', 'height_inches'])
            ->orderByDesc('usage_count')
            ->limit($limit);
    }

    /**
     * Auto-calculate fields before saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($cabinet) {
            // Auto-calculate linear feet from length
            if ($cabinet->length_inches && !$cabinet->linear_feet) {
                $cabinet->linear_feet = round($cabinet->length_inches / 12, 2);
            }

            // Auto-calculate unit_price_per_lf from TcsPricingService if pricing attributes are set
            // This dynamically pulls pricing from products_attribute_options
            if ($cabinet->shouldCalculatePrice()) {
                $pricingService = new TcsPricingService();
                $effectivePricing = $pricingService->resolveEffectivePricing($cabinet);

                $cabinet->unit_price_per_lf = $pricingService->calculateUnitPrice(
                    $effectivePricing['cabinet_level'],
                    $effectivePricing['material_category'],
                    $effectivePricing['finish_option']
                );
            }

            // Auto-calculate total price from unit price, LF, and quantity
            if ($cabinet->unit_price_per_lf && $cabinet->linear_feet && $cabinet->quantity) {
                $cabinet->total_price = round(
                    $cabinet->unit_price_per_lf * $cabinet->linear_feet * $cabinet->quantity,
                    2
                );
            }

            // Always regenerate full_code
            $cabinet->full_code = $cabinet->generateFullCode();
        });
    }

    /**
     * Generate the complete hierarchical code for this cabinet
     * Format: TCS-0554-15WSANKATY-K1-SW-U1
     */
    public function generateFullCode(): string
    {
        $parts = [];

        // Explicitly load relationships to ensure they're available
        // This is necessary because during boot/saving, relationships may not be loaded
        if ($this->cabinet_run_id && !$this->relationLoaded('cabinetRun')) {
            $this->load('cabinetRun.roomLocation.room.project');
        }

        // Walk up the hierarchy
        $run = $this->cabinetRun;
        $location = $run?->roomLocation;
        $room = $location?->room ?? $this->room;
        $project = $room?->project ?? $this->project;

        // Build code from project down to cabinet run
        if ($project?->project_number) {
            $parts[] = $project->project_number;
        }

        if ($room?->room_code) {
            $parts[] = $room->room_code;
        }

        if ($location?->location_code) {
            $parts[] = $location->location_code;
        }

        if ($run?->run_code) {
            $parts[] = $run->run_code;
        }

        return implode('-', array_filter($parts));
    }

    /**
     * Determine if pricing should be calculated from TcsPricingService
     *
     * Pricing is calculated when:
     * - unit_price_per_lf is not manually set (null or 0)
     * - Cabinet has pricing attributes (level, material, or finish)
     *
     * @return bool
     */
    public function shouldCalculatePrice(): bool
    {
        // Skip if unit_price_per_lf is already explicitly set
        if ($this->unit_price_per_lf && $this->unit_price_per_lf > 0) {
            // Check if pricing attributes changed - if so, recalculate
            $dirty = $this->getDirty();
            if (!isset($dirty['cabinet_level']) &&
                !isset($dirty['material_category']) &&
                !isset($dirty['finish_option'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the calculated unit price from TcsPricingService
     *
     * This method can be used to get pricing without saving the cabinet.
     * Uses the inheritance chain: Cabinet → Run → Location → Room
     *
     * @return float The calculated unit price per linear foot
     */
    public function getCalculatedUnitPriceAttribute(): float
    {
        $pricingService = new TcsPricingService();
        $effectivePricing = $pricingService->resolveEffectivePricing($this);

        return $pricingService->calculateUnitPrice(
            $effectivePricing['cabinet_level'],
            $effectivePricing['material_category'],
            $effectivePricing['finish_option']
        );
    }

    /**
     * Get detailed price breakdown from TcsPricingService
     *
     * Returns price components: base level price + material price + finish price
     *
     * @return array Price breakdown with labels and values
     */
    public function getPriceBreakdownAttribute(): array
    {
        $pricingService = new TcsPricingService();
        $effectivePricing = $pricingService->resolveEffectivePricing($this);

        return $pricingService->getPriceBreakdown(
            $effectivePricing['cabinet_level'],
            $effectivePricing['material_category'],
            $effectivePricing['finish_option']
        );
    }

    /**
     * Generate template data from common sizes
     *
     * Returns: Collection of template data for UI presets
     */
    public static function generateTemplates()
    {
        return self::mostCommon(20)
            ->get()
            ->map(function ($cabinet) {
                return [
                    'name' => "{$cabinet->length_inches}\" × {$cabinet->depth_inches}\" × {$cabinet->height_inches}\"",
                    'length' => $cabinet->length_inches,
                    'width' => $cabinet->width_inches,
                    'depth' => $cabinet->depth_inches,
                    'height' => $cabinet->height_inches,
                    'usage_count' => $cabinet->usage_count,
                    'size_range' => self::determineSizeRange($cabinet->length_inches / 12),
                ];
            });
    }

    /**
     * Helper to determine size range from linear feet
     */
    private static function determineSizeRange(float $linearFeet): string
    {
        return match (true) {
            $linearFeet <= 1.5 => 'small',
            $linearFeet <= 3.0 => 'medium',
            $linearFeet <= 4.0 => 'large',
            default => 'extra-large',
        };
    }

    /**
     * Material BOM Methods
     */

    /**
     * Generate Bill of Materials for this cabinet
     *
     * @return \Illuminate\Support\Collection
     */
    public function generateBom(): \Illuminate\Support\Collection
    {
        $bomService = new \Webkul\Project\Services\MaterialBomService();
        return $bomService->generateBomForCabinet($this);
    }

    /**
     * Get formatted BOM with product details
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFormattedBom(): \Illuminate\Support\Collection
    {
        $bomService = new \Webkul\Project\Services\MaterialBomService();
        $bom = $bomService->generateBomForCabinet($this);
        return $bomService->formatBom($bom, true);
    }

    /**
     * Get material cost estimate for this cabinet
     *
     * @return float
     */
    public function estimateMaterialCost(): float
    {
        $bomService = new \Webkul\Project\Services\MaterialBomService();
        $bom = $bomService->generateBomForCabinet($this);
        return $bomService->estimateMaterialCost($bom);
    }

    /**
     * Check if materials are available in inventory
     *
     * @return array
     */
    public function checkMaterialAvailability(): array
    {
        $bomService = new \Webkul\Project\Services\MaterialBomService();
        $bom = $bomService->generateBomForCabinet($this);
        return $bomService->checkMaterialAvailability($bom);
    }

    /**
     * Get material recommendations for this cabinet
     *
     * @param string $usageType box|face_frame|door
     * @return \Illuminate\Support\Collection
     */
    public function getMaterialRecommendations(string $usageType = 'box'): \Illuminate\Support\Collection
    {
        $bomService = new \Webkul\Project\Services\MaterialBomService();
        return $bomService->getMaterialRecommendations($this, $usageType);
    }

    /**
     * Check if this cabinet has material category assigned
     *
     * @return bool
     */
    public function hasMaterialCategory(): bool
    {
        return !empty($this->material_category);
    }

    /**
     * Get the inherited material category from parent entities
     *
     * Cascades: CabinetRun → RoomLocation → Room
     *
     * @return string|null
     */
    public function getInheritedMaterialCategory(): ?string
    {
        // Check own material category first
        if ($this->material_category) {
            return $this->material_category;
        }

        // Check cabinet run
        if ($this->cabinetRun && $this->cabinetRun->material_category) {
            return $this->cabinetRun->material_category;
        }

        // Check room location
        if ($this->cabinetRun && $this->cabinetRun->roomLocation && $this->cabinetRun->roomLocation->material_category) {
            return $this->cabinetRun->roomLocation->material_category;
        }

        // Check room
        if ($this->room && $this->room->material_category) {
            return $this->room->material_category;
        }

        return null;
    }

    /**
     * Get the effective material category (with inheritance)
     *
     * @return string|null
     */
    public function getEffectiveMaterialCategoryAttribute(): ?string
    {
        return $this->getInheritedMaterialCategory();
    }

    /**
     * Get cabinet box cut list data (3/4" plywood pieces)
     *
     * Returns all cabinet box pieces needed for fabrication:
     * - 2x side panels
     * - 1x bottom panel
     * - 1x back panel (TCS uses full 3/4" backs)
     *
     * @return array Cut list with all cabinet box pieces
     */
    public function getBoxCutListAttribute(): array
    {
        // Get dimensions
        $cabinetWidth = $this->length_inches ?? $this->width_inches ?? 24;
        $cabinetHeight = $this->height_inches ?? 30;
        $cabinetDepth = $this->depth_inches ?? 24;
        $toeKickHeight = $this->toe_kick_height_inches ?? self::STANDARD_TOE_KICK_HEIGHT;

        // Calculate box dimensions
        $sidePanelThickness = self::DEFAULT_BOX_THICKNESS;
        $backPanelThickness = self::DEFAULT_BACK_THICKNESS;

        // Box height = cabinet height - toe kick
        $boxHeight = $cabinetHeight - $toeKickHeight;

        // Inside width = cabinet width - (2 × side thickness)
        $insideWidth = $cabinetWidth - (2 * $sidePanelThickness);

        // Inside depth = cabinet depth - back thickness
        $insideDepth = $cabinetDepth - $backPanelThickness;

        return [
            'cabinet_number' => $this->cabinet_number,
            'cabinet_code' => $this->full_code,
            'material' => '3/4" Plywood',
            'calculated_dimensions' => [
                'box_height' => $boxHeight,
                'inside_width' => $insideWidth,
                'inside_depth' => $insideDepth,
            ],
            'pieces' => [
                [
                    'part' => 'Left Side',
                    'qty' => 1,
                    'width' => $insideDepth,
                    'length' => $boxHeight,
                    'thickness' => $sidePanelThickness,
                    'notes' => 'Depth × Box Height',
                ],
                [
                    'part' => 'Right Side',
                    'qty' => 1,
                    'width' => $insideDepth,
                    'length' => $boxHeight,
                    'thickness' => $sidePanelThickness,
                    'notes' => 'Depth × Box Height',
                ],
                [
                    'part' => 'Bottom',
                    'qty' => 1,
                    'width' => $insideWidth,
                    'length' => $insideDepth,
                    'thickness' => $sidePanelThickness,
                    'notes' => 'Inside Width × Depth',
                ],
                [
                    'part' => 'Back',
                    'qty' => 1,
                    'width' => $insideWidth,
                    'length' => $boxHeight,
                    'thickness' => $backPanelThickness,
                    'notes' => 'Inside Width × Box Height (TCS full 3/4" back)',
                ],
            ],
        ];
    }
}
