<?php

namespace Webkul\Product\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Product Type enumeration
 *
 */
enum ProductType: string implements HasLabel
{
    case GOODS = 'goods';

    case SERVICE = 'service';

    case CONSUMABLE = 'consumable';

    public function getLabel(): string
    {
        return match ($this) {
            self::GOODS      => __('products::enums/product-type.goods'),
            self::SERVICE    => __('products::enums/product-type.service'),
            self::CONSUMABLE => __('products::enums/product-type.consumable'),
        };
    }
}
