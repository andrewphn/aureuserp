<?php

namespace Webkul\Support\Filament\Forms\Components;

use Filament\Forms\Components\TextInput;
use Webkul\Support\Services\MeasurementFormatter;

/**
 * Measurement Input Component
 *
 * A Filament form component for entering measurements in inches.
 * Supports fractional input (e.g., "41 5/16", "41-5/16") and stores as decimal.
 * Defaults to inches.
 *
 * Usage:
 *   MeasurementInput::make('width_inches')
 *       ->label('Width')
 *       ->default(24)
 *       ->required()
 *
 * The component will:
 * - Accept fractional input like "41 5/16" or "41-5/16"
 * - Allow the user to see and edit the fractional value in the input field
 * - Parse and convert to decimal (41.3125) for storage in the database
 * - Display fractional values when loading existing data
 */
class MeasurementInput extends TextInput
{
    protected string $unit = 'inches';

    protected bool $showUnitSymbol = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Set default placeholder
        $this->placeholder('e.g. 41 5/16 or 41-5/16');

        // Set default helper text
        $this->helperText('Enter as decimal (41.3125) or fraction (41 5/16). Defaults to inches.');

        // Enable live updates with debounce
        $this->live(debounce: 500);

        // Format state for display - convert decimal to fraction when loading existing data
        $this->formatStateUsing(function ($state) {
            if ($state === null || $state === '') {
                return null;
            }

            // If state is already a fraction string (user is typing), return it as-is
            if (is_string($state) && (
                str_contains($state, '/') || 
                preg_match('/\d+\s+\d+\/\d+/', $state) ||
                preg_match('/\d+-\d+\/\d+/', $state)
            )) {
                return $state;
            }

            // If it's a decimal number, convert to fraction for display
            if (is_numeric($state)) {
                $formatter = new MeasurementFormatter();
                $fraction = $formatter->formatFraction((float) $state, false); // false = no symbol
                return $fraction;
            }

            return $state;
        });

        // Dehydrate state (convert fraction to decimal for storage)
        // This is what gets saved to the database
        $this->dehydrateStateUsing(function ($state) {
            if ($state === null || $state === '') {
                return null;
            }

            // If already a number, return it
            if (is_numeric($state)) {
                return round((float) $state, 4);
            }

            // Parse fractional input and convert to decimal
            $decimal = MeasurementFormatter::parse($state);
            
            return $decimal !== null ? round($decimal, 4) : null;
        });

        // After state updated - normalize the display value
        // This ensures the field shows the fraction while storing the decimal
        $this->afterStateUpdated(function ($state, callable $set, callable $get) {
            if ($state !== null && $state !== '') {
                // Check if input looks like a fraction
                $isFractionInput = is_string($state) && (
                    str_contains($state, '/') || 
                    preg_match('/\d+\s+\d+\/\d+/', $state) ||
                    preg_match('/\d+-\d+\/\d+/', $state)
                );

                if ($isFractionInput) {
                    // User typed a fraction - keep it as-is in the field for display/editing
                    // The dehydrateStateUsing will convert it to decimal for storage
                    return; // Don't change the state, let it remain as the fraction string
                } else {
                    // User typed a decimal - convert to fraction for display
                    if (is_numeric($state)) {
                        $formatter = new MeasurementFormatter();
                        $fraction = $formatter->formatFraction((float) $state, false);
                        $set($this->getName(), $fraction);
                    }
                }
            }
        });
    }

    /**
     * Set the unit (default: 'inches')
     */
    public function unit(string $unit): static
    {
        $this->unit = $unit;
        return $this;
    }

    /**
     * Show/hide unit symbol in display
     */
    public function showUnitSymbol(bool $show = true): static
    {
        $this->showUnitSymbol = $show;
        return $this;
    }

    /**
     * Get the unit
     */
    public function getUnit(): string
    {
        return $this->unit;
    }

    /**
     * Get whether to show unit symbol
     */
    public function getShowUnitSymbol(): bool
    {
        return $this->showUnitSymbol;
    }
}
