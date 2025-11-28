<?php

namespace Webkul\Purchase\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Account\Models\Tax;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Move as InventoryMove;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Partner\Models\Partner;
use Webkul\Product\Models\Packaging;
use Webkul\Purchase\Database\Factories\OrderLineFactory;
use Webkul\Purchase\Enums\QtyReceivedMethod;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;
use Webkul\Support\Models\UOM;

/**
 * Order Line Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $name
 * @property string|null $state
 * @property string|null $sort
 * @property mixed $qty_received_method
 * @property string|null $display_type
 * @property string|null $product_qty
 * @property string|null $product_uom_qty
 * @property string|null $product_packaging_qty
 * @property float $price_tax
 * @property float $discount
 * @property float $price_unit
 * @property float $price_subtotal
 * @property float $price_total
 * @property string|null $qty_invoiced
 * @property string|null $qty_received
 * @property string|null $qty_received_manual
 * @property string|null $qty_to_invoice
 * @property bool $is_downpayment
 * @property \Carbon\Carbon|null $planned_at
 * @property string|null $product_description_variants
 * @property bool $propagate_cancel
 * @property float $price_total_cc
 * @property int $uom_id
 * @property int $product_id
 * @property int $product_packaging_id
 * @property int $order_id
 * @property int $partner_id
 * @property int $currency_id
 * @property int $company_id
 * @property int $creator_id
 * @property int $final_location_id
 * @property int $order_point_id
 * @property-read \Illuminate\Database\Eloquent\Collection $accountMoveLines
 * @property-read \Illuminate\Database\Eloquent\Collection $inventoryMoves
 * @property-read \Illuminate\Database\Eloquent\Model|null $order
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $productPackaging
 * @property-read \Illuminate\Database\Eloquent\Model|null $uom
 * @property-read \Illuminate\Database\Eloquent\Model|null $currency
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Model|null $finalLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $orderPoint
 * @property-read \Illuminate\Database\Eloquent\Collection $taxes
 *
 */
class OrderLine extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'purchases_order_lines';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'state',
        'sort',
        'qty_received_method',
        'display_type',
        'product_qty',
        'product_uom_qty',
        'product_packaging_qty',
        'price_tax',
        'discount',
        'price_unit',
        'price_subtotal',
        'price_total',
        'qty_invoiced',
        'qty_received',
        'qty_received_manual',
        'qty_to_invoice',
        'is_downpayment',
        'planned_at',
        'product_description_variants',
        'propagate_cancel',
        'price_total_cc',
        'uom_id',
        'product_id',
        'product_packaging_id',
        'order_id',
        'partner_id',
        'currency_id',
        'company_id',
        'creator_id',
        'final_location_id',
        'order_point_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'qty_received_method' => QtyReceivedMethod::class,
        'planned_at'          => 'datetime',
        'is_downpayment'      => 'boolean',
        'propagate_cancel'    => 'boolean',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Order
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Partner
     *
     * @return BelongsTo
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

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
     * Product Packaging
     *
     * @return BelongsTo
     */
    public function productPackaging(): BelongsTo
    {
        return $this->belongsTo(Packaging::class);
    }

    /**
     * Uom
     *
     * @return BelongsTo
     */
    public function uom(): BelongsTo
    {
        return $this->belongsTo(UOM::class);
    }

    /**
     * Taxes
     *
     * @return BelongsToMany
     */
    public function taxes(): BelongsToMany
    {
        return $this->belongsToMany(Tax::class, 'purchases_order_line_taxes', 'order_line_id', 'tax_id');
    }

    /**
     * Currency
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * Account Move Lines
     *
     * @return HasMany
     */
    public function accountMoveLines(): HasMany
    {
        return $this->hasMany(AccountMoveLine::class, 'purchase_order_line_id');
    }

    /**
     * Inventory Moves
     *
     * @return HasMany
     */
    public function inventoryMoves(): HasMany
    {
        return $this->hasMany(InventoryMove::class, 'purchase_order_line_id');
    }

    /**
     * Final Location
     *
     * @return BelongsTo
     */
    public function finalLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'final_location_id');
    }

    /**
     * Order Point
     *
     * @return BelongsTo
     */
    public function orderPoint(): BelongsTo
    {
        return $this->belongsTo(OrderPoint::class, 'order_point_id');
    }

    protected static function newFactory(): OrderLineFactory
    {
        return OrderLineFactory::new();
    }
}
