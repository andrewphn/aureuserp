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
 * Door Model
 *
 * Represents a cabinet door component.
 * Hierarchy: Project -> Room -> Location -> Cabinet Run -> Cabinet -> Section -> Door
 *
 * @property string|null $full_code Hierarchical code (e.g., TCS-0554-15WSANKATY-K1-SW-U1-A-DOOR1)
 * @property float|null $complexity_score Calculated complexity score
 * @property array|null $complexity_breakdown JSON breakdown of complexity factors
 */
class Door extends Model implements CabinetComponentInterface
{
    use HasFactory, SoftDeletes, HasFullCode, HasComplexityScore, HasFormattedDimensions, HasEntityLock, HasOpeningPosition;

    protected $table = 'projects_doors';

    protected $fillable = [
        'product_id',
        'cabinet_id',
        'section_id',
        'door_number',
        'door_name',
        'full_code',
        'sort_order',
        'width_inches',
        'height_inches',
        'rail_width_inches',
        'style_width_inches',
        'has_check_rail',
        'check_rail_width_inches',
        'profile_type',
        'fabrication_method',
        'thickness_inches',
        'hinge_type',
        'hinge_model',
        'hinge_quantity',
        'hinge_side',
        'has_glass',
        'glass_type',
        'finish_type',
        'paint_color',
        'stain_color',
        'has_decorative_hardware',
        'decorative_hardware_model',
        'hinge_product_id',
        'decorative_hardware_product_id',
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
        // Opening position fields
        'position_in_opening_inches',
        'consumed_height_inches',
        'position_from_left_inches',
        'consumed_width_inches',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'width_inches' => 'float',
            'height_inches' => 'float',
            'rail_width_inches' => 'float',
            'style_width_inches' => 'float',
            'has_check_rail' => 'boolean',
            'check_rail_width_inches' => 'float',
            'thickness_inches' => 'float',
            'hinge_quantity' => 'integer',
            'has_glass' => 'boolean',
            'has_decorative_hardware' => 'boolean',
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
            // Opening position casts
            'position_in_opening_inches' => 'float',
            'consumed_height_inches' => 'float',
            'position_from_left_inches' => 'float',
            'consumed_width_inches' => 'float',
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

    public function hingeProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'hinge_product_id');
    }

    public function decorativeHardwareProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'decorative_hardware_product_id');
    }

    /**
     * Additional hardware/products for this door
     */
    public function hardwareRequirements(): HasMany
    {
        return $this->hasMany(HardwareRequirement::class, 'door_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getFormattedDimensionsAttribute(): string
    {
        return ($this->width_inches ?? '?') . '"W x ' . ($this->height_inches ?? '?') . '"H';
    }

    /**
     * Get the component code for this door
     * Format: DOOR1, DOOR2, etc.
     */
    public function getComponentCode(): string
    {
        return 'DOOR' . ($this->door_number ?? 1);
    }

    /**
     * Get the component's name.
     */
    public function getComponentName(): ?string
    {
        return $this->door_name;
    }

    /**
     * Get the component's number.
     */
    public function getComponentNumber(): ?int
    {
        return $this->door_number;
    }

    /**
     * Get the component type identifier.
     */
    public static function getComponentType(): string
    {
        return 'door';
    }
}
