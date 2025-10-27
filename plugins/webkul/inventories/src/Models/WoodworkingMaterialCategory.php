<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Product\Models\Product;

class WoodworkingMaterialCategory extends Model
{
    protected $table = 'woodworking_material_categories';

    protected $fillable = [
        'name',
        'code',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Relationships
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'material_category_id');
    }

    /**
     * Scope: Get categories by type prefix
     * Example: 'Sheet Goods', 'Solid Wood', 'Hardware', 'Finishes', 'Accessories'
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('name', 'LIKE', "{$type}%");
    }

    /**
     * Scope: Get sheet goods categories
     */
    public function scopeSheetGoods($query)
    {
        return $query->where('name', 'LIKE', 'Sheet Goods%');
    }

    /**
     * Scope: Get solid wood categories
     */
    public function scopeSolidWood($query)
    {
        return $query->where('name', 'LIKE', 'Solid Wood%');
    }

    /**
     * Scope: Get hardware categories
     */
    public function scopeHardware($query)
    {
        return $query->where('name', 'LIKE', 'Hardware%');
    }

    /**
     * Scope: Get finish categories
     */
    public function scopeFinishes($query)
    {
        return $query->where('name', 'LIKE', 'Finishes%');
    }

    /**
     * Scope: Get accessory categories
     */
    public function scopeAccessories($query)
    {
        return $query->where('name', 'LIKE', 'Accessories%');
    }

    /**
     * Scope: Ordered by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get count of products in this category
     */
    public function getProductCountAttribute(): int
    {
        return $this->products()->count();
    }
}
