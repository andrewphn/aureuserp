<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\Product;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;

/**
 * Cabinet Materials Bom Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $cabinet_specification_id
 * @property int $cabinet_run_id
 * @property int $product_id
 * @property string|null $component_name
 * @property float $quantity_required
 * @property string|null $unit_of_measure
 * @property float $waste_factor_percentage
 * @property float $quantity_with_waste
 * @property float $component_width_inches
 * @property float $component_height_inches
 * @property int $quantity_of_components
 * @property float $sqft_per_component
 * @property float $total_sqft_required
 * @property float $linear_feet_per_component
 * @property float $total_linear_feet
 * @property float $board_feet_required
 * @property float $unit_cost
 * @property float $total_material_cost
 * @property string|null $grain_direction
 * @property bool $requires_edge_banding
 * @property string|null $edge_banding_sides
 * @property float $edge_banding_lf
 * @property string|null $cnc_notes
 * @property string|null $machining_operations
 * @property bool $material_allocated
 * @property \Carbon\Carbon|null $material_allocated_at
 * @property bool $material_issued
 * @property \Carbon\Carbon|null $material_issued_at
 * @property int $substituted_product_id
 * @property string|null $substitution_notes
 * @property-read \Illuminate\Database\Eloquent\Model|null $cabinetSpecification
 * @property-read \Illuminate\Database\Eloquent\Model|null $cabinetRun
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $substitutedProduct
 *
 */
class CabinetMaterialsBom extends Model
{
    use SoftDeletes, HasChatter, HasLogActivity;

    protected $table = 'projects_bom';

    protected $fillable = [
        'cabinet_specification_id',
        'cabinet_run_id',
        'product_id',
        'component_name',
        'quantity_required',
        'unit_of_measure',
        'waste_factor_percentage',
        'quantity_with_waste',
        'component_width_inches',
        'component_height_inches',
        'quantity_of_components',
        'sqft_per_component',
        'total_sqft_required',
        'linear_feet_per_component',
        'total_linear_feet',
        'board_feet_required',
        'unit_cost',
        'total_material_cost',
        'grain_direction',
        'requires_edge_banding',
        'edge_banding_sides',
        'edge_banding_lf',
        'cnc_notes',
        'machining_operations',
        'material_allocated',
        'material_allocated_at',
        'material_issued',
        'material_issued_at',
        'substituted_product_id',
        'substitution_notes',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:2',
        'waste_factor_percentage' => 'decimal:2',
        'quantity_with_waste' => 'decimal:2',
        'component_width_inches' => 'decimal:3',
        'component_height_inches' => 'decimal:3',
        'quantity_of_components' => 'integer',
        'sqft_per_component' => 'decimal:2',
        'total_sqft_required' => 'decimal:2',
        'linear_feet_per_component' => 'decimal:2',
        'total_linear_feet' => 'decimal:2',
        'board_feet_required' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_material_cost' => 'decimal:2',
        'requires_edge_banding' => 'boolean',
        'edge_banding_lf' => 'decimal:2',
        'material_allocated' => 'boolean',
        'material_allocated_at' => 'datetime',
        'material_issued' => 'boolean',
        'material_issued_at' => 'datetime',
    ];

    /**
     * Attributes to log for Chatter activity tracking
     */
    protected $logAttributes = [
        'product.name' => 'Material',
        'component_name' => 'Component',
        'quantity_required' => 'Quantity Required',
        'quantity_with_waste' => 'Quantity with Waste',
        'total_material_cost' => 'Material Cost',
        'material_allocated' => 'Allocated',
        'material_issued' => 'Issued',
    ];

    /**
     * Relationships
     */
    public function cabinetSpecification(): BelongsTo
    {
        return $this->belongsTo(CabinetSpecification::class, 'cabinet_specification_id');
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
     * Product
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Substituted Product
     *
     * @return BelongsTo
     */
    public function substitutedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'substituted_product_id');
    }

    /**
     * Scopes
     */
    /**
     * Scope query to Allocated
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAllocated($query)
    {
        return $query->where('material_allocated', true);
    }

    /**
     * Scope query to Issued
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIssued($query)
    {
        return $query->where('material_issued', true);
    }

    /**
     * Scope query to Pending
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('material_allocated', false);
    }

    /**
     * Scope query to By Component
     *
     * @param mixed $query The search query
     * @param string $component
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByComponent($query, string $component)
    {
        return $query->where('component_name', $component);
    }

    /**
     * Auto-calculate fields before saving
     */
    /**
     * Boot
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($bom) {
            // Auto-calculate quantity with waste
            if ($bom->quantity_required && $bom->waste_factor_percentage) {
                $bom->quantity_with_waste = round(
                    $bom->quantity_required * (1 + ($bom->waste_factor_percentage / 100)),
                    2
                );
            }

            // Auto-calculate total material cost
            if ($bom->quantity_with_waste && $bom->unit_cost) {
                $bom->total_material_cost = round(
                    $bom->quantity_with_waste * $bom->unit_cost,
                    2
                );
            }

            // Auto-calculate square footage for components
            if ($bom->component_width_inches && $bom->component_height_inches && $bom->quantity_of_components) {
                $bom->sqft_per_component = round(
                    ($bom->component_width_inches * $bom->component_height_inches) / 144,
                    2
                );
                $bom->total_sqft_required = round(
                    $bom->sqft_per_component * $bom->quantity_of_components * (1 + ($bom->waste_factor_percentage / 100)),
                    2
                );
            }
        });
    }
}
