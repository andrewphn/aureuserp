<?php

namespace Webkul\Purchase\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderState: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';

    public static function options(): array
    {
        return [
            self::DRAFT->value     => __('inventories::enums/operation-state.draft'),
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT        => __('inventories::enums/operation-state.draft'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT     => 'gray',
        };
    }
}
