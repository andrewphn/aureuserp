<?php

namespace Webkul\Sale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\Product;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;

class SalesOrderLineItem extends Model
{
    use SoftDeletes, HasChatter, HasLogActivity;

    protected $table = 'sales_order_line_items';

    protected $fillable = [
        'sales_order_id',
        'room_id',
        'room_location_id',
        'cabinet_run_id',
        'cabinet_specification_id',
        'product_id',
        'line_item_type',
        'description',
        'quantity',
        'linear_feet',
        'base_rate_per_lf',
        'material_rate_per_lf',
        'combined_rate_per_lf',
        'unit_price',
        'subtotal',
        'discount_percentage',
        'discount_amount',
        'line_total',
        'sequence',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'linear_feet' => 'decimal:2',
        'base_rate_per_lf' => 'decimal:2',
        'material_rate_per_lf' => 'decimal:2',
        'combined_rate_per_lf' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'sequence' => 'integer',
    ];

    /**
     * Attributes to log for Chatter activity tracking
     */
    protected $logAttributes = [
        'salesOrder.name' => 'Sales Order',
        'product.name' => 'Product',
        'line_item_type' => 'Type',
        'quantity' => 'Quantity',
        'linear_feet' => 'Linear Feet',
        'base_rate_per_lf' => 'Base Rate per LF',
        'material_rate_per_lf' => 'Material Rate per LF',
        'combined_rate_per_lf' => 'Combined Rate per LF',
        'unit_price' => 'Unit Price',
        'line_total' => 'Line Total',
    ];

    /**
     * Relationships
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sales_order_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Project\Models\Room::class, 'room_id');
    }

    public function roomLocation(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Project\Models\RoomLocation::class, 'room_location_id');
    }

    public function cabinetRun(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Project\Models\CabinetRun::class, 'cabinet_run_id');
    }

    public function cabinetSpecification(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Project\Models\CabinetSpecification::class, 'cabinet_specification_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Scopes
     */
    public function scopeCabinets($query)
    {
        return $query->where('line_item_type', 'cabinet');
    }

    public function scopeCountertops($query)
    {
        return $query->where('line_item_type', 'countertop');
    }

    public function scopeAdditional($query)
    {
        return $query->where('line_item_type', 'additional');
    }

    public function scopeDiscount($query)
    {
        return $query->where('line_item_type', 'discount');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence');
    }

    /**
     * Auto-calculate fields before saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($lineItem) {
            // Auto-calculate subtotal based on line_item_type
            if ($lineItem->line_item_type === 'cabinet' && $lineItem->linear_feet && $lineItem->combined_rate_per_lf) {
                $lineItem->subtotal = round(
                    $lineItem->linear_feet * $lineItem->combined_rate_per_lf * ($lineItem->quantity ?? 1),
                    2
                );
            } elseif ($lineItem->quantity && $lineItem->unit_price) {
                $lineItem->subtotal = round(
                    $lineItem->quantity * $lineItem->unit_price,
                    2
                );
            }

            // Auto-calculate discount amount if percentage provided
            if ($lineItem->discount_percentage && $lineItem->subtotal) {
                $lineItem->discount_amount = round(
                    $lineItem->subtotal * ($lineItem->discount_percentage / 100),
                    2
                );
            }

            // Auto-calculate line total
            if ($lineItem->subtotal) {
                $lineItem->line_total = round(
                    $lineItem->subtotal - ($lineItem->discount_amount ?? 0),
                    2
                );
            }
        });
    }
}
