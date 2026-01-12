<?php

namespace Webkul\Project\Filament\Forms\Schemas\Components;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Webkul\Support\Filament\Forms\Components\MeasurementInput;
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
     * Uses the MeasurementInput component which handles fractional input and display
     * 
     * @param string $name Field name
     * @param string $label Field label
     * @param float|null $default Default value
     * @param bool $required Whether field is required
     * @param float|null $minValue Minimum value
     * @param float|null $maxValue Maximum value
     * @return MeasurementInput
     */
    public static function getDimensionInput(
        string $name,
        string $label,
        ?float $default = null,
        bool $required = false,
        ?float $minValue = null,
        ?float $maxValue = null
    ): MeasurementInput {
        $field = MeasurementInput::make($name)
            ->label($label);

        if ($default !== null) {
            $field->default($default);
        }

        if ($required) {
            $field->required();
        }

        if ($minValue !== null) {
            $field->minValue($minValue);
        }

        if ($maxValue !== null) {
            $field->maxValue($maxValue);
        }

        return $field;
    }
}
