<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Product\Models\Product;
use Webkul\Support\Traits\HasFullCode;

/**
 * Drawer Model
 *
 * Represents a cabinet drawer component.
 * Hierarchy: Project -> Room -> Location -> Cabinet Run -> Cabinet -> Section -> Drawer
 *
 * @property string|null $full_code Hierarchical code (e.g., TCS-0554-15WSANKATY-K1-SW-B1-A-DRW1)
 */
class Drawer extends Model
{
    use HasFactory, SoftDeletes, HasFullCode;

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
        'style_width_inches',
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
            'style_width_inches' => 'float',
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
        ];
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
}
