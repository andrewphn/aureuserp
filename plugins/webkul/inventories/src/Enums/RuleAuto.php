<?php

namespace Webkul\Inventory\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Rule Auto enumeration
 *
 */
enum RuleAuto: string implements HasLabel
{
    case MANUAL = 'manual';

    case TRANSPARENT = 'transparent';

    public function getLabel(): string
    {
        return match ($this) {
            self::MANUAL      => __('inventories::enums/rule-auto.manual'),
            self::TRANSPARENT => __('inventories::enums/rule-auto.transparent'),
        };
    }
}
