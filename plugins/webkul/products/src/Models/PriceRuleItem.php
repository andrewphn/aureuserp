<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Database\Factories\PriceRuleItemFactory;
use Webkul\Product\Enums\PriceRuleApplyTo;
use Webkul\Product\Enums\PriceRuleBase;
use Webkul\Product\Enums\PriceRuleType;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

/**
 * Price Rule Item Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property mixed $apply_to
 * @property string|null $display_apply_to
 * @property mixed $base
 * @property mixed $type
 * @property float $min_quantity
 * @property float $fixed_price
 * @property float $price_discount
 * @property float $price_round
 * @property float $price_surcharge
 * @property float $price_markup
 * @property float $price_min_margin
 * @property float $percent_price
 * @property \Carbon\Carbon|null $starts_at
 * @property \Carbon\Carbon|null $ends_at
 * @property int $price_rule_id
 * @property int $base_price_rule_id
 * @property int $currency_id
 * @property int $product_id
 * @property int $category_id
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $priceRule
 * @property-read \Illuminate\Database\Eloquent\Model|null $basePriceRule
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $category
 * @property-read \Illuminate\Database\Eloquent\Model|null $currency
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class PriceRuleItem extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'products_price_rule_items';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'apply_to',
        'display_apply_to',
        'base',
        'type',
        'min_quantity',
        'fixed_price',
        'price_discount',
        'price_round',
        'price_surcharge',
        'price_markup',
        'price_min_margin',
        'percent_price',
        'starts_at',
        'ends_at',
        'price_rule_id',
        'base_price_rule_id',
        'currency_id',
        'product_id',
        'category_id',
        'company_id',
        'creator_id',
    ];

    /**
     * Casts
     *
     * @var string
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'apply_to'  => PriceRuleApplyTo::class,
        'base'      => PriceRuleBase::class,
        'type'      => PriceRuleType::class,
    ];

    /**
     * Price Rule
     *
     * @return BelongsTo
     */
    public function priceRule(): BelongsTo
    {
        return $this->belongsTo(PriceRule::class);
    }

    /**
     * Base Price Rule
     *
     * @return BelongsTo
     */
    public function basePriceRule(): BelongsTo
    {
        return $this->belongsTo(PriceRule::class);
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
     * Category
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
     * @return PriceRuleItemFactory
     */
    protected static function newFactory(): PriceRuleItemFactory
    {
        return PriceRuleItemFactory::new();
    }
}
