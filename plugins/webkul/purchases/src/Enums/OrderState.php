<?php

namespace Webkul\Purchase\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Order State enumeration
 *
 */
enum OrderState: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';

    case SENT = 'sent';

    case PURCHASE = 'purchase';

    case ON_HOLD = 'on_hold';

    case DONE = 'done';

    case CANCELED = 'canceled';

    /**
     * Options
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::DRAFT->value    => __('purchases::enums/order-state.draft'),
            self::SENT->value     => __('purchases::enums/order-state.sent'),
            self::PURCHASE->value => __('purchases::enums/order-state.purchase'),
            self::ON_HOLD->value  => __('purchases::enums/order-state.on_hold'),
            self::DONE->value     => __('purchases::enums/order-state.done'),
            self::CANCELED->value => __('purchases::enums/order-state.canceled'),
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT    => __('purchases::enums/order-state.draft'),
            self::SENT     => __('purchases::enums/order-state.sent'),
            self::PURCHASE => __('purchases::enums/order-state.purchase'),
            self::ON_HOLD  => __('purchases::enums/order-state.on_hold'),
            self::DONE     => __('purchases::enums/order-state.done'),
            self::CANCELED => __('purchases::enums/order-state.canceled'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT    => 'gray',
            self::SENT     => 'blue',
            self::PURCHASE => 'success',
            self::ON_HOLD  => 'warning',
            self::DONE     => 'success',
            self::CANCELED => 'danger',
        };
    }
}
