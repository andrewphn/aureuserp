<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\Product;

/**
 * TCS Material to Inventory Product Mapping
 *
 * Maps TCS pricing material categories (Paint Grade, Stain Grade, Premium, Custom)
 * to actual inventory products with specific wood species and usage multipliers
 * for Bill of Materials (BOM) generation.
 */
class TcsMaterialInventoryMapping extends Model
{
    protected $table = 'tcs_material_inventory_mappings';

    protected $fillable = [
        'tcs_material_slug',
        'wood_species',
        'inventory_product_id',
        'material_category_id',
        'board_feet_per_lf',
        'sheet_sqft_per_lf',
        'is_box_material',
        'is_face_frame_material',
        'is_door_material',
        'priority',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'board_feet_per_lf' => 'decimal:4',
        'sheet_sqft_per_lf' => 'decimal:4',
        'is_box_material' => 'boolean',
        'is_face_frame_material' => 'boolean',
        'is_door_material' => 'boolean',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */

    /**
     * Get the inventory product this mapping refers to
     */
    public function inventoryProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'inventory_product_id');
    }

    /**
     * Get the woodworking material category
     */
    public function materialCategory(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Inventories\Models\WoodworkingMaterialCategory::class, 'material_category_id');
    }

    /**
     * Scopes
     */

    /**
     * Scope: Filter by TCS material category
     */
    public function scopeForMaterial($query, string $materialSlug)
    {
        return $query->where('tcs_material_slug', $materialSlug);
    }

    /**
     * Scope: Filter by active materials
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Order by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority');
    }

    /**
     * Scope: Filter by usage type
     */
    public function scopeForUsage($query, string $usageType)
    {
        return match ($usageType) {
            'box' => $query->where('is_box_material', true),
            'face_frame' => $query->where('is_face_frame_material', true),
            'door' => $query->where('is_door_material', true),
            default => $query,
        };
    }

    /**
     * Scope: Solid wood materials only
     */
    public function scopeSolidWood($query)
    {
        return $query->where('board_feet_per_lf', '>', 0);
    }

    /**
     * Scope: Sheet goods materials only
     */
    public function scopeSheetGoods($query)
    {
        return $query->where('sheet_sqft_per_lf', '>', 0);
    }

    /**
     * Helpers
     */

    /**
     * Get display name for TCS material category
     */
    public function getTcsMaterialDisplayAttribute(): string
    {
        return match ($this->tcs_material_slug) {
            'paint_grade' => 'Paint Grade (Hard Maple/Poplar)',
            'stain_grade' => 'Stain Grade (Oak/Maple)',
            'premium' => 'Premium (Rifted White Oak/Black Walnut)',
            'custom_exotic' => 'Custom/Exotic Wood',
            default => ucfirst(str_replace('_', ' ', $this->tcs_material_slug)),
        };
    }

    /**
     * Get material type (solid wood or sheet goods)
     */
    public function getMaterialTypeAttribute(): string
    {
        if ($this->board_feet_per_lf > 0) {
            return 'Solid Wood';
        } elseif ($this->sheet_sqft_per_lf > 0) {
            return 'Sheet Goods';
        }

        return 'Unknown';
    }

    /**
     * Get formatted usage description
     */
    public function getUsageDescriptionAttribute(): string
    {
        $usage = [];

        if ($this->is_box_material) {
            $usage[] = 'Cabinet Boxes';
        }
        if ($this->is_face_frame_material) {
            $usage[] = 'Face Frames';
        }
        if ($this->is_door_material) {
            $usage[] = 'Doors/Drawers';
        }

        return implode(', ', $usage) ?: 'None specified';
    }

    /**
     * Calculate material requirement for given linear footage
     *
     * @param float $linearFeet
     * @param bool $includeWaste Add 10% waste factor
     * @return array ['quantity' => float, 'unit' => string]
     */
    public function calculateRequirement(float $linearFeet, bool $includeWaste = true): array
    {
        $wasteFactor = $includeWaste ? 1.10 : 1.0;

        if ($this->board_feet_per_lf > 0) {
            return [
                'quantity' => round($linearFeet * $this->board_feet_per_lf * $wasteFactor, 2),
                'unit' => 'board_feet',
                'unit_display' => 'Board Feet',
            ];
        } elseif ($this->sheet_sqft_per_lf > 0) {
            return [
                'quantity' => round($linearFeet * $this->sheet_sqft_per_lf * $wasteFactor, 2),
                'unit' => 'square_feet',
                'unit_display' => 'Square Feet',
            ];
        }

        return [
            'quantity' => 0,
            'unit' => 'unknown',
            'unit_display' => 'Unknown',
        ];
    }

    /**
     * Check if this material is preferred (lower priority)
     */
    public function isPreferred(): bool
    {
        // Get other materials in same category
        $lowestPriority = self::forMaterial($this->tcs_material_slug)
            ->active()
            ->min('priority');

        return $this->priority === $lowestPriority;
    }

    /**
     * Get estimated cost per linear foot
     *
     * Based on inventory product cost and usage multiplier
     */
    public function getCostPerLinearFootAttribute(): ?float
    {
        if (!$this->inventoryProduct || !$this->inventoryProduct->cost_price) {
            return null;
        }

        $costPerUnit = $this->inventoryProduct->cost_price;

        if ($this->board_feet_per_lf > 0) {
            return round($costPerUnit * $this->board_feet_per_lf * 1.10, 2); // Include 10% waste
        } elseif ($this->sheet_sqft_per_lf > 0) {
            return round($costPerUnit * $this->sheet_sqft_per_lf * 1.10, 2);
        }

        return null;
    }
}
