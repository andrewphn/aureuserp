<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\Product;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;

class CabinetMaterialsBom extends Model
{
    use SoftDeletes, HasChatter, HasLogActivity;

    protected $table = 'cabinet_materials_bom';

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

    public function cabinetRun(): BelongsTo
    {
        return $this->belongsTo(CabinetRun::class, 'cabinet_run_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function substitutedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'substituted_product_id');
    }

    /**
     * Scopes
     */
    public function scopeAllocated($query)
    {
        return $query->where('material_allocated', true);
    }

    public function scopeIssued($query)
    {
        return $query->where('material_issued', true);
    }

    public function scopePending($query)
    {
        return $query->where('material_allocated', false);
    }

    public function scopeByComponent($query, string $component)
    {
        return $query->where('component_name', $component);
    }

    /**
     * Auto-calculate fields before saving
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
