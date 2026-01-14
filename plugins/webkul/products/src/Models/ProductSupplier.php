<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

/**
 * Product Supplier Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $sort
 * @property string|null $delay
 * @property string|null $product_name
 * @property string|null $product_code
 * @property \Carbon\Carbon|null $starts_at
 * @property \Carbon\Carbon|null $ends_at
 * @property string|null $min_qty
 * @property float $price
 * @property float $discount
 * @property int $product_id
 * @property int $partner_id
 * @property int $currency_id
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $currency
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 *
 */
class ProductSupplier extends Model implements Sortable
{
    use SortableTrait;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'products_product_suppliers';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'sort',
        'delay',
        'product_name',
        'product_code',
        'starts_at',
        'ends_at',
        'min_qty',
        'price',
        'discount',
        'product_id',
        'partner_id',
        'currency_id',
        'company_id',
        'creator_id',
        'ai_created',
        'ai_source_document',
        'ai_created_at',
    ];

    protected $casts = [
        'starts_at'     => 'date',
        'ends_at'       => 'date',
        'ai_created'    => 'boolean',
        'ai_created_at' => 'datetime',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
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
     * Partner
     *
     * @return BelongsTo
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
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
     * Creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
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
}
