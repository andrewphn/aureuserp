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
 * @property float|null $numeric_value
 * @property int $product_id
 * @property int $attribute_id
 * @property int $product_attribute_id
 * @property int|null $attribute_option_id
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
        'numeric_value',
        'product_id',
        'attribute_id',
        'product_attribute_id',
        'attribute_option_id',
    ];

    /**
     * Attribute casts.
     *
     * @var array
     */
    protected $casts = [
        'extra_price'   => 'decimal:4',
        'numeric_value' => 'decimal:4',
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

    /**
     * Get the effective value (option name for SELECT/RADIO/COLOR, numeric for NUMBER/DIMENSION)
     *
     * @return string|float|null
     */
    public function getValue(): string|float|null
    {
        if ($this->attribute?->isNumeric()) {
            return $this->numeric_value;
        }

        return $this->attributeOption?->name;
    }

    /**
     * Get formatted value with unit (for display)
     *
     * @return string
     */
    public function getFormattedValue(): string
    {
        if ($this->attribute?->isNumeric()) {
            return $this->attribute->formatValue($this->numeric_value);
        }

        return $this->attributeOption?->name ?? '';
    }

    /**
     * Check if this value is for a numeric attribute
     */
    public function isNumeric(): bool
    {
        return $this->attribute?->isNumeric() ?? false;
    }
}
