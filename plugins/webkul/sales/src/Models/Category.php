<?php

namespace Webkul\Sale\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Invoice\Models\Category as BaseCategory;

/**
 * Category Eloquent model
 *
 */
class Category extends BaseCategory
{
    /**
     * Products
     *
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
