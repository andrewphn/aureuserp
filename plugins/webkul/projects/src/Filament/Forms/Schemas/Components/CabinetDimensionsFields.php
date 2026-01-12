<?php

namespace Webkul\Project\Filament\Forms\Schemas\Components;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Webkul\Support\Services\MeasurementFormatter;

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
            static::getDimensionInput('length_inches', 'Width', 24),
            static::getDimensionInput('height_inches', 'Height', 34.5),
            static::getDimensionInput('depth_inches', 'Depth', 24),
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
            static::getDimensionInput('length_inches', 'Width (in)', 24, true, 6, 96),
            static::getDimensionInput('height_inches', 'Height (in)', 34.5, true, 6, 96),
            TextInput::make('quantity')
                ->label('Quantity')
                ->numeric()
                ->default(1)
                ->minValue(1)
                ->maxValue(50),
        ]);
    }

    /**
     * Get dimension input field with fractional support
     * Allows entering "41 5/16", "41-5/16", "41.3125", etc. and converts to decimal
     * 
     * @param string $name Field name
     * @param string $label Field label
     * @param float|null $default Default value
     * @param bool $required Whether field is required
     * @param float|null $minValue Minimum value
     * @param float|null $maxValue Maximum value
     * @return TextInput
     */
    public static function getDimensionInput(
        string $name,
        string $label,
        ?float $default = null,
        bool $required = false,
        ?float $minValue = null,
        ?float $maxValue = null
    ): TextInput {
        $field = TextInput::make($name)
            ->label($label)
            ->placeholder('e.g. 41 5/16 or 41-5/16')
            ->suffix('in')
            ->helperText('Enter as decimal (41.3125) or fraction (41 5/16)')
            ->live(debounce: 500);

        if ($default !== null) {
            $field->default($default);
        }

        if ($required) {
            $field->required();
        }

        // Parse fractional input and convert to decimal
        $field->afterStateUpdated(function ($state, callable $set) use ($name) {
            if ($state !== null && $state !== '') {
                $decimal = MeasurementFormatter::parse($state);
                if ($decimal !== null) {
                    // Round to 4 decimal places for precision
                    $rounded = round($decimal, 4);
                    // Only update if the parsed value differs from what was entered
                    // This allows users to see their fraction while we store the decimal
                    if (abs($rounded - (float) $state) > 0.0001) {
                        $set($name, $rounded);
                    }
                }
            }
        });

        return $field;
    }
}
