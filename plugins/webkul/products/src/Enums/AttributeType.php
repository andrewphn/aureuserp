<?php

namespace Webkul\Product\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Attribute Type enumeration
 *
 */
enum AttributeType: string implements HasLabel
{
    case RADIO = 'radio';

    case SELECT = 'select';

    case COLOR = 'color';

    case NUMBER = 'number';

    case DIMENSION = 'dimension';

    public function getLabel(): string
    {
        return match ($this) {
            self::RADIO     => __('products::enums/attribute-type.radio'),
            self::SELECT    => __('products::enums/attribute-type.select'),
            self::COLOR     => __('products::enums/attribute-type.color'),
            self::NUMBER    => __('products::enums/attribute-type.number'),
            self::DIMENSION => __('products::enums/attribute-type.dimension'),
        };
    }

    /**
     * Check if this attribute type stores numeric values
     */
    public function isNumeric(): bool
    {
        return in_array($this, [self::NUMBER, self::DIMENSION]);
    }

    /**
     * Check if this attribute type requires predefined options
     */
    public function requiresOptions(): bool
    {
        return in_array($this, [self::RADIO, self::SELECT, self::COLOR]);
    }
}
