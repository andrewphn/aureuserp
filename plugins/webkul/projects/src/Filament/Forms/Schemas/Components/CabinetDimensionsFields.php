<?php

namespace Webkul\Project\Filament\Forms\Schemas\Components;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

/**
 * Cabinet Dimensions Fields Molecule Component
 * 
 * Reusable cabinet dimension fields following atomic design principles
 */
class CabinetDimensionsFields
{
    /**
     * Get cabinet dimensions grid
     * 
     * @param int $columns Number of columns in grid (default: 5 for code, type, width, height, qty)
     * @return Grid
     */
    public static function getCabinetDimensionsGrid(int $columns = 5): Grid
    {
        return Grid::make($columns)->schema([
            TextInput::make('length_inches')
                ->label('Width')
                ->numeric()
                ->required()
                ->default(24)
                ->suffix('in'),
            TextInput::make('height_inches')
                ->label('Height')
                ->numeric()
                ->required()
                ->default(34.5)
                ->suffix('in'),
            TextInput::make('depth_inches')
                ->label('Depth')
                ->numeric()
                ->default(24)
                ->suffix('in'),
            TextInput::make('quantity')
                ->label('Qty')
                ->numeric()
                ->default(1)
                ->minValue(1),
        ]);
    }

    /**
     * Get simplified dimensions grid (width, height, quantity only)
     * 
     * @return Grid
     */
    public static function getSimplifiedDimensionsGrid(): Grid
    {
        return Grid::make(3)->schema([
            TextInput::make('length_inches')
                ->label('Width (in)')
                ->numeric()
                ->required()
                ->default(24)
                ->minValue(6)
                ->maxValue(96),
            TextInput::make('height_inches')
                ->label('Height (in)')
                ->numeric()
                ->required()
                ->default(34.5)
                ->minValue(6)
                ->maxValue(96),
            TextInput::make('quantity')
                ->label('Quantity')
                ->numeric()
                ->default(1)
                ->minValue(1)
                ->maxValue(50),
        ]);
    }
}
