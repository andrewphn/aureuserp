<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

/**
 * Product Attribute Value Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property float $extra_price
 * @property int $product_id
 * @property int $attribute_id
 * @property int $product_attribute_id
 * @property int $attribute_option_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $attribute
 * @property-read \Illuminate\Database\Eloquent\Model|null $productAttribute
 * @property-read \Illuminate\Database\Eloquent\Model|null $attributeOption
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class ProductAttributeValue extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'products_product_attribute_values';

    /**
     * Timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'extra_price',
        'product_id',
        'attribute_id',
        'product_attribute_id',
        'attribute_option_id',
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
     * Attribute
     *
     * @return BelongsTo
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Product Attribute
     *
     * @return BelongsTo
     */
    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class);
    }

    /**
     * Attribute Option
     *
     * @return BelongsTo
     */
    public function attributeOption(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class);
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
}
