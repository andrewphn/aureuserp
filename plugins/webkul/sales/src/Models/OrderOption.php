<?php

namespace Webkul\Sale\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Security\Models\User;
use Webkul\Support\Models\UOM;

/**
 * Order Option Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $sort
 * @property int $order_id
 * @property int $product_id
 * @property int $line_id
 * @property int $uom_id
 * @property int $creator_id
 * @property string|null $name
 * @property float $quantity
 * @property float $price_unit
 * @property float $discount
 * @property-read \Illuminate\Database\Eloquent\Model|null $order
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $line
 * @property-read \Illuminate\Database\Eloquent\Model|null $uom
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class OrderOption extends Model implements Sortable
{
    use SortableTrait;

    protected $table = 'sales_order_options';

    protected $fillable = [
        'sort',
        'order_id',
        'product_id',
        'line_id',
        'uom_id',
        'creator_id',
        'name',
        'quantity',
        'price_unit',
        'discount',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Order
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Line
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function line()
    {
        return $this->belongsTo(OrderLine::class, 'line_id');
    }

    /**
     * UOM (Unit of Measure)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }

    /**
     * Creator
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
