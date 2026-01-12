<?php

namespace Webkul\Project\Filament\Forms\Schemas\Components;

use Filament\Forms\Components\Select;

/**
 * Run Type Select Atom Component
 * 
 * Reusable Select field for cabinet run types following atomic design principles
 */
class RunTypeSelect
{
    /**
     * Get run type options
     */
    public static function getOptions(): array
    {
        return [
            'base' => 'Base Cabinets',
            'wall' => 'Wall Cabinets',
            'tall' => 'Tall Cabinets',
            'island' => 'Island',
        ];
    }

    /**
     * Get run type Select field
     */
    public static function getRunTypeSelect(): Select
    {
        return Select::make('run_type')
            ->label('Run Type')
            ->options(static::getOptions())
            ->required()
            ->default('base')
            ->searchable();
    }
}
