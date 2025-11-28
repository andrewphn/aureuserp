<?php

namespace Webkul\Sale\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Qty Delivered Method enumeration
 *
 */
enum QtyDeliveredMethod: string implements HasLabel
{
    case MANUAL = 'manual';

    case STOCK_MOVE = 'stock_move';

    /**
     * Get the human-readable label for this delivery method
     *
     * @return string Translated label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::MANUAL     => __('sales::enums/qty-delivered-method.manual'),
            self::STOCK_MOVE => __('sales::enums/qty-delivered-method.stock-move'),
        };
    }
}
