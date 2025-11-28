<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product Combination Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $product_id
 * @property int $product_attribute_value_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $productAttributeValue
 *
 */
class ProductCombination extends Model
{
    use HasFactory;

    protected $table = 'products_product_combinations';

    protected $fillable = [
        'product_id',
        'product_attribute_value_id',
    ];

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
     * Product Attribute Value
     *
     * @return BelongsTo
     */
    public function productAttributeValue(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeValue::class, 'product_attribute_value_id');
    }
}
