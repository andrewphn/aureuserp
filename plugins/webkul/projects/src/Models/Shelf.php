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
 * Shelf Model
 *
 * Represents a cabinet shelf component.
 * Hierarchy: Project -> Room -> Location -> Cabinet Run -> Cabinet -> Section -> Shelf
 *
 * @property string|null $full_code Hierarchical code (e.g., TCS-0554-15WSANKATY-K1-SW-U1-B-SHELF1)
 * @property float|null $complexity_score Calculated complexity score
 * @property array|null $complexity_breakdown JSON breakdown of complexity factors
 */
class Shelf extends Model implements CabinetComponentInterface
{
    use HasFactory, SoftDeletes, HasFullCode, HasComplexityScore, HasFormattedDimensions, HasEntityLock;

    protected $table = 'projects_shelves';

    protected $fillable = [
        'product_id',
        'cabinet_id',
        'section_id',
        'shelf_number',
        'shelf_name',
        'full_code',
        'sort_order',
        'width_inches',
        'depth_inches',
        'thickness_inches',
        'shelf_type',
        'material',
        'edge_treatment',
        'pin_hole_spacing',
        'number_of_positions',
        'slide_type',
        'slide_model',
        'slide_product_id',
        'slide_length_inches',
        'soft_close',
        'weight_capacity_lbs',
        'finish_type',
        'paint_color',
        'stain_color',
        // Opening reference (source dimensions)
        'opening_width_inches',
        'opening_height_inches',
        'opening_depth_inches',
        // Calculated dimensions
        'cut_width_inches',
        'cut_depth_inches',
        // Pin hole specifications
        'pin_setback_front_inches',
        'pin_setback_back_inches',
        'pin_vertical_spacing_inches',
        'pin_hole_diameter_mm',
        'has_center_support',
        // Notch specifications
        'notch_depth_inches',
        'notch_count',
        // Clearances
        'clearance_side_inches',
        'clearance_back_inches',
        // Edge banding
        'edge_band_front',
        'edge_band_back',
        'edge_band_sides',
        'edge_band_length_inches',
        // Hardware
        'shelf_pin_product_id',
        'shelf_pin_quantity',
        // Metadata
        'spec_source',
        'dimensions_calculated_at',
        // Production tracking
        'cnc_cut_at',
        'manually_cut_at',
        'edge_banded_at',
        'assembled_at',
        'sanded_at',
        'finished_at',
        'hardware_installed_at',
        'installed_in_cabinet_at',
        'qc_passed',
        'qc_notes',
        'qc_inspected_at',
        'qc_inspector_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'width_inches' => 'float',
            'depth_inches' => 'float',
            'thickness_inches' => 'float',
            'pin_hole_spacing' => 'float',
            'number_of_positions' => 'integer',
            'slide_length_inches' => 'float',
            'soft_close' => 'boolean',
            'weight_capacity_lbs' => 'float',
            // Opening reference
            'opening_width_inches' => 'float',
            'opening_height_inches' => 'float',
            'opening_depth_inches' => 'float',
            // Calculated dimensions
            'cut_width_inches' => 'float',
            'cut_depth_inches' => 'float',
            // Pin hole specifications
            'pin_setback_front_inches' => 'float',
            'pin_setback_back_inches' => 'float',
            'pin_vertical_spacing_inches' => 'float',
            'pin_hole_diameter_mm' => 'float',
            'has_center_support' => 'boolean',
            // Notch specifications
            'notch_depth_inches' => 'float',
            'notch_count' => 'integer',
            // Clearances
            'clearance_side_inches' => 'float',
            'clearance_back_inches' => 'float',
            // Edge banding
            'edge_band_front' => 'boolean',
            'edge_band_back' => 'boolean',
            'edge_band_sides' => 'boolean',
            'edge_band_length_inches' => 'float',
            // Hardware
            'shelf_pin_quantity' => 'integer',
            // Metadata
            'dimensions_calculated_at' => 'datetime',
            // Production tracking
            'cnc_cut_at' => 'datetime',
            'manually_cut_at' => 'datetime',
            'edge_banded_at' => 'datetime',
            'assembled_at' => 'datetime',
            'sanded_at' => 'datetime',
            'finished_at' => 'datetime',
            'hardware_installed_at' => 'datetime',
            'installed_in_cabinet_at' => 'datetime',
            'qc_passed' => 'boolean',
            'qc_inspected_at' => 'datetime',
        ];
    }

    public const SHELF_TYPES = [
        'fixed' => 'Fixed Shelf',
        'adjustable' => 'Adjustable Shelf',
        'roll_out' => 'Roll-Out Shelf',
        'pull_down' => 'Pull-Down Shelf',
        'corner' => 'Corner Shelf',
        'floating' => 'Floating Shelf',
    ];

    public const MATERIALS = [
        'plywood' => 'Plywood',
        'mdf' => 'MDF',
        'melamine' => 'Melamine',
        'solid_wood' => 'Solid Wood',
        'glass' => 'Glass',
        'wire' => 'Wire',
    ];

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

    /**
     * Shelf pin product (5mm standard)
     */
    public function shelfPinProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'shelf_pin_product_id');
    }

    /**
     * Additional hardware/products for this shelf
     */
    public function hardwareRequirements(): HasMany
    {
        return $this->hasMany(HardwareRequirement::class, 'shelf_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getFormattedDimensionsAttribute(): string
    {
        $w = $this->width_inches ?? '?';
        $d = $this->depth_inches ?? '?';
        return "{$w}\"W x {$d}\"D";
    }

    /**
     * Get the component code for this shelf
     * Format: SHELF1, SHELF2, etc.
     */
    public function getComponentCode(): string
    {
        return 'SHELF' . ($this->shelf_number ?? 1);
    }

    /**
     * Get the component's name.
     */
    public function getComponentName(): ?string
    {
        return $this->shelf_name;
    }

    /**
     * Get the component's number.
     */
    public function getComponentNumber(): ?int
    {
        return $this->shelf_number;
    }

    /**
     * Get the component type identifier.
     */
    public static function getComponentType(): string
    {
        return 'shelf';
    }
}
