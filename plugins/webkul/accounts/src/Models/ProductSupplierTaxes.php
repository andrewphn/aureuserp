<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\Product;

/**
 * Product Supplier Taxes Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $product_id
 * @property int $tax_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $tax
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 *
 */
class ProductSupplierTaxes extends Model
{
    protected $table = 'accounts_product_supplier_taxes';

    protected $fillable = [
        'product_id',
        'tax_id',
    ];

    /**
     * Tax
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id');
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
}
