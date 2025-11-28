<?php

namespace Webkul\Sale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\Product;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;

/**
 * Sales Order Line Item Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $sales_order_id
 * @property int $room_id
 * @property int $room_location_id
 * @property int $cabinet_run_id
 * @property int $cabinet_specification_id
 * @property int $product_id
 * @property string|null $line_item_type
 * @property string|null $description
 * @property float $quantity
 * @property float $linear_feet
 * @property float $base_rate_per_lf
 * @property float $material_rate_per_lf
 * @property float $combined_rate_per_lf
 * @property float $unit_price
 * @property float $subtotal
 * @property float $discount_percentage
 * @property float $discount_amount
 * @property float $line_total
 * @property int $sequence
 * @property-read \Illuminate\Database\Eloquent\Model|null $salesOrder
 * @property-read \Illuminate\Database\Eloquent\Model|null $room
 * @property-read \Illuminate\Database\Eloquent\Model|null $roomLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $cabinetRun
 * @property-read \Illuminate\Database\Eloquent\Model|null $cabinetSpecification
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 *
 */
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

    /**
     * Room
     *
     * @return BelongsTo
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Project\Models\Room::class, 'room_id');
    }

    /**
     * Room Location
     *
     * @return BelongsTo
     */
    public function roomLocation(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Project\Models\RoomLocation::class, 'room_location_id');
    }

    /**
     * Cabinet Run
     *
     * @return BelongsTo
     */
    public function cabinetRun(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Project\Models\CabinetRun::class, 'cabinet_run_id');
    }

    /**
     * Cabinet Specification
     *
     * @return BelongsTo
     */
    public function cabinetSpecification(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Project\Models\CabinetSpecification::class, 'cabinet_specification_id');
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
     * Scopes
     */
    /**
     * Scope query to Cabinets
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCabinets($query)
    {
        return $query->where('line_item_type', 'cabinet');
    }

    /**
     * Scope query to Countertops
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCountertops($query)
    {
        return $query->where('line_item_type', 'countertop');
    }

    /**
     * Scope query to Additional
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAdditional($query)
    {
        return $query->where('line_item_type', 'additional');
    }

    /**
     * Scope query to Discount
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDiscount($query)
    {
        return $query->where('line_item_type', 'discount');
    }

    /**
     * Scope query to Ordered
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence');
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
