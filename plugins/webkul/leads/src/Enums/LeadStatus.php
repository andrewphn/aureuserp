<?php

namespace Webkul\Lead\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum LeadStatus: string implements HasColor, HasIcon, HasLabel
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case QUALIFIED = 'qualified';
    case DISQUALIFIED = 'disqualified';
    case CONVERTED = 'converted';

    public function getLabel(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::CONTACTED => 'Contacted',
            self::QUALIFIED => 'Qualified',
            self::DISQUALIFIED => 'Disqualified',
            self::CONVERTED => 'Converted',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NEW => 'info',
            self::CONTACTED => 'warning',
            self::QUALIFIED => 'success',
            self::DISQUALIFIED => 'danger',
            self::CONVERTED => 'primary',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::NEW => 'heroicon-o-inbox',
            self::CONTACTED => 'heroicon-o-phone',
            self::QUALIFIED => 'heroicon-o-check-circle',
            self::DISQUALIFIED => 'heroicon-o-x-circle',
            self::CONVERTED => 'heroicon-o-arrow-right-circle',
        };
    }

    /**
     * Get all values as array for select options
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
