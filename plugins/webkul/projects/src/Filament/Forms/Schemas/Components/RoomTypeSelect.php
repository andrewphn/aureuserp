<?php

namespace Webkul\Project\Filament\Forms\Schemas\Components;

use Filament\Forms\Components\Select;

/**
 * Room Type Select Atom Component
 * 
 * Reusable Select field for room types following atomic design principles
 */
class RoomTypeSelect
{
    /**
     * Get room type options
     */
    public static function getOptions(): array
    {
        return [
            'kitchen' => 'Kitchen',
            'bathroom' => 'Bathroom',
            'laundry' => 'Laundry',
            'pantry' => 'Pantry',
            'closet' => 'Closet',
            'mudroom' => 'Mudroom',
            'office' => 'Office',
            'bedroom' => 'Bedroom',
            'living_room' => 'Living Room',
            'dining_room' => 'Dining Room',
            'garage' => 'Garage',
            'basement' => 'Basement',
            'other' => 'Other',
        ];
    }

    /**
     * Get room type Select field
     */
    public static function getRoomTypeSelect(): Select
    {
        return Select::make('room_type')
            ->label('Room Type')
            ->options(static::getOptions())
            ->required()
            ->default('kitchen')
            ->searchable();
    }
}
