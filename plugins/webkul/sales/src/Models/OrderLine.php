<?php

namespace Webkul\Sale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Account\Models\MoveLine;
use Webkul\Account\Models\Tax;
use Webkul\Inventory\Models\Move as InventoryMove;
use Webkul\Inventory\Models\Route;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Partner\Models\Partner;
use Webkul\Product\Models\Packaging;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Enums\QtyDeliveredMethod;
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
 * @property string|null $sort
 * @property int $order_id
 * @property int $company_id
 * @property int $currency_id
 * @property int $order_partner_id
 * @property int $salesman_id
 * @property int $product_id
 * @property int $product_uom_id
 * @property int $linked_sale_order_sale_id
 * @property int $creator_id
 * @property string|null $state
 * @property string|null $display_type
 * @property int $virtual_id
 * @property int $linked_virtual_id
 * @property mixed $qty_delivered_method
 * @property string|null $invoice_status
 * @property string|null $analytic_distribution
 * @property string|null $name
 * @property string|null $product_uom_qty
 * @property float $price_unit
 * @property float $discount
 * @property float $price_subtotal
 * @property float $price_total
 * @property float $price_reduce_taxexcl
 * @property float $price_reduce_taxinc
 * @property string|null $qty_delivered
 * @property string|null $qty_invoiced
 * @property string|null $qty_to_invoice
 * @property float $untaxed_amount_invoiced
 * @property float $untaxed_amount_to_invoice
 * @property bool $is_downpayment
 * @property bool $is_expense
 * @property \Carbon\Carbon|null $create_date
 * @property \Carbon\Carbon|null $write_date
 * @property float $technical_price_unit
 * @property float $price_tax
 * @property string|null $product_qty
 * @property string|null $product_packaging_qty
 * @property int $product_packaging_id
 * @property string|null $customer_lead
 * @property float $purchase_price
 * @property string|null $margin
 * @property string|null $margin_percent
 * @property int $warehouse_id
 * @property-read \Illuminate\Database\Eloquent\Collection $inventoryMoves
 * @property-read \Illuminate\Database\Eloquent\Model|null $order
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $currency
 * @property-read \Illuminate\Database\Eloquent\Model|null $orderPartner
 * @property-read \Illuminate\Database\Eloquent\Model|null $salesman
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $uom
 * @property-read \Illuminate\Database\Eloquent\Model|null $productPackaging
 * @property-read \Illuminate\Database\Eloquent\Model|null $linkedSaleOrderSale
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $warehouse
 * @property-read \Illuminate\Database\Eloquent\Model|null $route
 * @property-read \Illuminate\Database\Eloquent\Collection $taxes
 * @property-read \Illuminate\Database\Eloquent\Collection $accountMoveLines
 *
 */
class OrderLine extends Model implements Sortable
{
    use SortableTrait;

    protected $table = 'sales_order_lines';

    protected $fillable = [
        'sort',
        'order_id',
        'company_id',
        'currency_id',
        'order_partner_id',
        'salesman_id',
        'product_id',
        'product_uom_id',
        'linked_sale_order_sale_id',
        'creator_id',
        'state',
        'display_type',
        'virtual_id',
        'linked_virtual_id',
        'qty_delivered_method',
        'invoice_status',
        'analytic_distribution',
        'name',
        'product_uom_qty',
        'price_unit',
        'discount',
        'price_subtotal',
        'price_total',
        'price_reduce_taxexcl',
        'price_reduce_taxinc',
        'qty_delivered',
        'qty_invoiced',
        'qty_to_invoice',
        'untaxed_amount_invoiced',
        'untaxed_amount_to_invoice',
        'is_downpayment',
        'is_expense',
        'create_date',
        'write_date',
        'technical_price_unit',
        'price_tax',
        'product_qty',
        'product_packaging_qty',
        'product_packaging_id',
        'customer_lead',
        'purchase_price',
        'margin',
        'margin_percent',
        'warehouse_id',
    ];

    protected $casts = [
        'cast'                 => OrderState::class,
        'qty_delivered_method' => QtyDeliveredMethod::class,
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
        return $this->belongsTo(Order::class);
    }

    /**
     * Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Currency
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Order Partner
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function orderPartner()
    {
        return $this->belongsTo(Partner::class, 'order_partner_id');
    }

    /**
     * Salesman
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function salesman()
    {
        return $this->belongsTo(User::class, 'salesman_id');
    }

    /**
     * Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * Uom
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uom()
    {
        return $this->belongsTo(UOM::class, 'product_uom_id');
    }

    /**
     * Taxes
     *
     * @return BelongsToMany
     */
    public function taxes(): BelongsToMany
    {
        return $this->belongsToMany(Tax::class, 'sales_order_line_taxes', 'order_line_id', 'tax_id');
    }

    /**
     * Account Move Lines
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function accountMoveLines()
    {
        return $this->belongsToMany(MoveLine::class, 'sales_order_line_invoices', 'order_line_id', 'invoice_line_id');
    }

    /**
     * Inventory Moves
     *
     * @return HasMany
     */
    public function inventoryMoves(): HasMany
    {
        return $this->hasMany(InventoryMove::class, 'sale_order_line_id');
    }

    /**
     * Product Packaging
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function productPackaging()
    {
        return $this->belongsTo(Packaging::class);
    }

    /**
     * Linked Sale Order Sale
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function linkedSaleOrderSale()
    {
        return $this->belongsTo(self::class, 'linked_sale_order_sale_id');
    }

    /**
     * Created By
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Warehouse
     *
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Route
     *
     * @return BelongsTo
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id');
    }
}
