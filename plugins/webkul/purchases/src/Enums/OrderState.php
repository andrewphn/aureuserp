<?php

namespace Webkul\Purchase\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderState: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';

    case SENT = 'sent';

    case PURCHASE = 'purchase';

    public static function options(): array
    {
        return [
            self::DRAFT->value => __('purchases::enums/operation-state.draft'),
            self::SENT->value => __('purchases::enums/operation-state.sent'),
            self::PURCHASE->value => __('purchases::enums/operation-state.purchase'),
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => __('purchases::enums/operation-state.draft'),
            self::SENT => __('purchases::enums/operation-state.sent'),
            self::PURCHASE => __('purchases::enums/operation-state.purchase'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'gray',
            self::PURCHASE => 'gray',
        };
    }
}
