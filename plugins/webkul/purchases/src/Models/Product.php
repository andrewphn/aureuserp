<?php

namespace Webkul\Purchase\Models;

use Webkul\Invoice\Models\Product as BaseProduct;

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
}
