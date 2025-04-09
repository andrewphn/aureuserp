<?php

namespace Webkul\Purchase\Models;

use Webkul\Invoice\Models\Product as BaseProduct;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends BaseProduct
{
    /**
     * Create a new Eloquent model instance.
     *
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->mergeFillable([
        ]);

        $this->mergeCasts([
        ]);

        parent::__construct($attributes);
    }

    public function supplierInformation(): HasMany
    {
        if ($this->is_configurable) {
            return $this->hasMany(ProductSupplier::class)
                ->orWhereIn('product_id', $this->variants()->pluck('id'));
        } else {
            return $this->hasMany(ProductSupplier::class);
        }
    }
}
