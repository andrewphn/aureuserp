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
 * Drawer Model
 *
 * Represents a cabinet drawer component.
 * Hierarchy: Project -> Room -> Location -> Cabinet Run -> Cabinet -> Section -> Drawer
 *
 * @property string|null $full_code Hierarchical code (e.g., TCS-0554-15WSANKATY-K1-SW-B1-A-DRW1)
 * @property float|null $complexity_score Calculated complexity score
 * @property array|null $complexity_breakdown JSON breakdown of complexity factors
 */
class Drawer extends Model implements CabinetComponentInterface
{
    use HasFactory, SoftDeletes, HasFullCode, HasComplexityScore, HasFormattedDimensions, HasEntityLock, HasOpeningPosition;

    protected $table = 'projects_drawers';

    protected $fillable = [
        'product_id',
        'cabinet_id',
        'section_id',
        'drawer_number',
        'drawer_name',
        'full_code',
        'drawer_position',
        'sort_order',
        'front_width_inches',
        'front_height_inches',
        'top_rail_width_inches',
        'bottom_rail_width_inches',
        'stile_width_inches',
        'profile_type',
        'fabrication_method',
        'front_thickness_inches',
        'box_width_inches',
        'box_depth_inches',
        'box_height_inches',
        'box_material',
        'box_thickness',
        'joinery_method',
        'slide_type',
        'slide_model',
        'slide_length_inches',
        'slide_quantity',
        'soft_close',
        'finish_type',
        'paint_color',
        'stain_color',
        'has_decorative_hardware',
        'decorative_hardware_model',
        'slide_product_id',
        'decorative_hardware_product_id',
        'cnc_cut_at',
        'manually_cut_at',
        'edge_banded_at',
        'box_assembled_at',
        'front_attached_at',
        'sanded_at',
        'finished_at',
        'slides_installed_at',
        'installed_in_cabinet_at',
        'qc_passed',
        'qc_notes',
        'qc_inspected_at',
        'qc_inspector_id',
        'notes',
        // Opening position fields
        'position_in_opening_inches',
        'consumed_height_inches',
        'position_from_left_inches',
        'consumed_width_inches',
    ];

    protected function casts(): array
    {
        return [
            'drawer_position' => 'integer',
            'sort_order' => 'integer',
            'front_width_inches' => 'float',
            'front_height_inches' => 'float',
            'top_rail_width_inches' => 'float',
            'bottom_rail_width_inches' => 'float',
            'stile_width_inches' => 'float',
            'front_thickness_inches' => 'float',
            'box_width_inches' => 'float',
            'box_depth_inches' => 'float',
            'box_height_inches' => 'float',
            'box_thickness' => 'float',
            'slide_length_inches' => 'float',
            'slide_quantity' => 'integer',
            'soft_close' => 'boolean',
            'has_decorative_hardware' => 'boolean',
            'cnc_cut_at' => 'datetime',
            'manually_cut_at' => 'datetime',
            'edge_banded_at' => 'datetime',
            'box_assembled_at' => 'datetime',
            'front_attached_at' => 'datetime',
            'sanded_at' => 'datetime',
            'finished_at' => 'datetime',
            'slides_installed_at' => 'datetime',
            'installed_in_cabinet_at' => 'datetime',
            'qc_passed' => 'boolean',
            'qc_inspected_at' => 'datetime',
            // Opening position casts
            'position_in_opening_inches' => 'float',
            'consumed_height_inches' => 'float',
            'position_from_left_inches' => 'float',
            'consumed_width_inches' => 'float',
        ];
    }

    /**
     * Override width field for HasFormattedDimensions trait.
     * Drawer uses front_width_inches for display width.
     */
    protected function getWidthField(): string
    {
        return 'front_width_inches';
    }

    /**
     * Override height field for HasFormattedDimensions trait.
     * Drawer uses front_height_inches for display height.
     */
    protected function getHeightField(): string
    {
        return 'front_height_inches';
    }

    /**
     * Drawer front has no depth, only the box does.
     */
    protected function hasDepthField(): bool
    {
        return false;
    }

    /**
     * Get formatted box dimensions separately.
     */
    public function getFormattedBoxDimensionsAttribute(): string
    {
        return $this->getMeasurementFormatter()->formatDimensions(
            $this->box_width_inches,
            $this->box_height_inches,
            $this->box_depth_inches
        );
    }

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

    public function slideProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'slide_product_id');
    }

    public function decorativeHardwareProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'decorative_hardware_product_id');
    }

    /**
     * Additional hardware/products for this drawer
     */
    public function hardwareRequirements(): HasMany
    {
        return $this->hasMany(HardwareRequirement::class, 'drawer_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getFormattedFrontDimensionsAttribute(): string
    {
        return ($this->front_width_inches ?? '?') . '"W x ' . ($this->front_height_inches ?? '?') . '"H';
    }

    /**
     * Get the component code for this drawer
     * Format: DRW1, DRW2, etc.
     */
    public function getComponentCode(): string
    {
        return 'DRW' . ($this->drawer_number ?? 1);
    }

    /**
     * Get the component's name.
     */
    public function getComponentName(): ?string
    {
        return $this->drawer_name;
    }

    /**
     * Get the component's number.
     */
    public function getComponentNumber(): ?int
    {
        return $this->drawer_number;
    }

    /**
     * Get the component type identifier.
     */
    public static function getComponentType(): string
    {
        return 'drawer';
    }

    /**
     * Blum TANDEM 563H clearance constants (1/2" drawer sides)
     */
    public const BLUM_SIDE_DEDUCTION = 0.625;    // 5/8" total
    public const BLUM_HEIGHT_DEDUCTION = 0.8125; // 13/16" total
    public const SIDE_THICKNESS = 0.5;           // 1/2" plywood sides
    public const BOTTOM_THICKNESS = 0.25;        // 1/4" plywood bottom

    /**
     * Get cut list data for this drawer box
     *
     * Returns all pieces needed to fabricate the drawer box:
     * - 2x sides (1/2" plywood)
     * - 2x front/back (1/2" plywood)
     * - 1x bottom (1/4" plywood, in dado)
     *
     * @return array Cut list with all drawer box pieces
     */
    public function getCutListDataAttribute(): array
    {
        // Get box dimensions from stored values or calculate from front
        $boxWidth = $this->box_width_inches;
        $boxHeight = $this->box_height_inches;
        $boxDepth = $this->box_depth_inches ?? $this->slide_length_inches ?? 18;

        // If box dimensions not stored, calculate from front dimensions
        if (!$boxWidth && $this->front_width_inches) {
            // Box width = front width - side deduction (Blum standard)
            $boxWidth = $this->front_width_inches - self::BLUM_SIDE_DEDUCTION;
        }

        if (!$boxHeight && $this->front_height_inches) {
            // Box height = front height - height deduction, rounded down to 1/2"
            $exactHeight = $this->front_height_inches - self::BLUM_HEIGHT_DEDUCTION;
            $boxHeight = floor($exactHeight * 2) / 2; // Round down to nearest 1/2"
        }

        // Shop depth adds 1/4" for bottom dado
        $shopDepth = $boxDepth + 0.25;

        // Front/back width = box width - 2× side thickness
        $frontBackWidth = $boxWidth - (2 * self::SIDE_THICKNESS);

        // Bottom dimensions (in dado groove, 3/8" inset on each side)
        $bottomWidth = $boxWidth - (2 * 0.375);
        $bottomLength = $boxDepth - (2 * 0.375);

        return [
            'drawer_number' => $this->drawer_number,
            'drawer_name' => $this->drawer_name,
            'box_dimensions' => [
                'width' => $boxWidth,
                'height' => $boxHeight,
                'depth' => $boxDepth,
                'depth_shop' => $shopDepth,
            ],
            'pieces' => [
                [
                    'part' => 'Sides',
                    'qty' => 2,
                    'width' => $boxHeight,
                    'length' => $shopDepth,
                    'thickness' => self::SIDE_THICKNESS,
                    'material' => '1/2" Plywood',
                    'notes' => 'Left and right sides',
                ],
                [
                    'part' => 'Front/Back',
                    'qty' => 2,
                    'width' => $boxHeight,
                    'length' => $frontBackWidth,
                    'thickness' => self::SIDE_THICKNESS,
                    'material' => '1/2" Plywood',
                    'notes' => 'Sub-front and back pieces',
                ],
                [
                    'part' => 'Bottom',
                    'qty' => 1,
                    'width' => $bottomWidth,
                    'length' => $bottomLength,
                    'thickness' => self::BOTTOM_THICKNESS,
                    'material' => '1/4" Plywood',
                    'notes' => 'In dado groove',
                ],
            ],
            'hardware' => [
                'slides' => [
                    'type' => $this->slide_type ?? 'Blum TANDEM',
                    'model' => $this->slide_model,
                    'length' => $this->slide_length_inches ?? 18,
                    'qty' => $this->slide_quantity ?? 2,
                    'soft_close' => $this->soft_close ?? true,
                ],
            ],
        ];
    }
}
