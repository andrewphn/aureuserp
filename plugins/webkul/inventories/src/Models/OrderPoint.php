<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Inventory\Database\Factories\OrderPointFactory;
use Webkul\Inventory\Enums\OrderPointTrigger;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Order Point Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $name
 * @property mixed $trigger
 * @property string|null $snoozed_until
 * @property string|null $product_min_qty
 * @property string|null $product_max_qty
 * @property string|null $qty_multiple
 * @property string|null $qty_to_order_manual
 * @property int $product_id
 * @property int $product_category_id
 * @property int $warehouse_id
 * @property int $location_id
 * @property int $route_id
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $productCategory
 * @property-read \Illuminate\Database\Eloquent\Model|null $warehouse
 * @property-read \Illuminate\Database\Eloquent\Model|null $route
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class OrderPoint extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_order_points';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'trigger',
        'snoozed_until',
        'product_min_qty',
        'product_max_qty',
        'qty_multiple',
        'qty_to_order_manual',
        'product_id',
        'product_category_id',
        'warehouse_id',
        'location_id',
        'route_id',
        'company_id',
        'creator_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'trigger' => OrderPointTrigger::class,
    ];

    /**
     * Product
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Product Category
     *
     * @return BelongsTo
     */
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'product_category_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Route
     *
     * @return BelongsTo
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * Company
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * New Factory
     *
     * @return OrderPointFactory
     */
    protected static function newFactory(): OrderPointFactory
    {
        return OrderPointFactory::new();
    }
}
