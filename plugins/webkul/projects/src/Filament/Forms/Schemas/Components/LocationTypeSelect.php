<?php

namespace Webkul\Project\Filament\Forms\Schemas\Components;

use Filament\Forms\Components\Select;

/**
 * Location Type Select Atom Component
 * 
 * Reusable Select field for location types following atomic design principles
 */
class LocationTypeSelect
{
    /**
     * Get location type options
     */
    public static function getOptions(): array
    {
        return [
            'wall' => 'Wall',
            'island' => 'Island',
            'peninsula' => 'Peninsula',
            'corner' => 'Corner',
            'alcove' => 'Alcove',
            'sink_wall' => 'Sink Wall',
            'range_wall' => 'Range Wall',
            'refrigerator_wall' => 'Refrigerator Wall',
        ];
    }

    /**
     * Get location type Select field
     */
    public static function getLocationTypeSelect(): Select
    {
        return Select::make('location_type')
            ->label('Location Type')
            ->options(static::getOptions())
            ->required()
            ->default('wall')
            ->searchable();
    }
}
