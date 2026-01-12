<?php

namespace Webkul\Project\Filament\Forms\Schemas\Components;

use Filament\Forms\Components\Select;

/**
 * Cabinet Type Select Atom Component
 * 
 * Reusable Select field for cabinet types following atomic design principles
 */
class CabinetTypeSelect
{
    /**
     * Get cabinet type options
     */
    public static function getOptions(): array
    {
        return [
            'base' => 'Base',
            'wall' => 'Wall',
            'tall' => 'Tall',
            'vanity' => 'Vanity',
            'specialty' => 'Specialty',
        ];
    }

    /**
     * Get cabinet type Select field
     */
    public static function getCabinetTypeSelect(): Select
    {
        return Select::make('cabinet_type')
            ->label('Cabinet Type')
            ->options(static::getOptions())
            ->required()
            ->default('base')
            ->searchable();
    }
}
