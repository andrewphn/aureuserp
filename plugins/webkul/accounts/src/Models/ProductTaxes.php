<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\Product;

/**
 * Product Taxes Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $product_id
 * @property int $tax_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $tax
 *
 */
class ProductTaxes extends Model
{
    protected $table = 'accounts_product_taxes';

    protected $fillable = [
        'product_id',
        'tax_id',
    ];

    public $timestamps = false;

    /**
     * Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
}
