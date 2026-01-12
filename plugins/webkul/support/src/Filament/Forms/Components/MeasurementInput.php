<?php

namespace Webkul\Support\Filament\Forms\Components;

use Filament\Forms\Components\TextInput;
use Webkul\Support\Services\MeasurementFormatter;

/**
 * Measurement Input Component
 *
 * A Filament form component for entering measurements with unit conversion.
 * Supports fractional input (e.g., "41 5/16", "41-5/16") and stores as decimal inches.
 * Includes unit selector (inches, feet, millimeters) and shows formatted measurement in helper text.
 *
 * Usage:
 *   MeasurementInput::make('width_inches')
 *       ->label('Width')
 *       ->default(24)
 *       ->required()
 *       ->withUnitSelector() // Enable unit selector
 *
 * The component will:
 * - Accept fractional input like "41 5/16" or "41-5/16"
 * - Allow the user to see and edit the fractional value in the input field
 * - Support unit conversion (inches, feet, millimeters)
 * - Parse and convert to decimal inches (41.3125) for storage in the database
 * - Display fractional values when loading existing data
 * - Show formatted measurement in helper text
 */
class MeasurementInput extends TextInput
{
    protected string $view = 'forms.components.measurement-input';
    
    protected string $inputUnit = 'inches'; // Unit for input (default: inches)
    protected bool $showUnitSelector = false; // Whether to show unit selector
    protected string $unitSelectorField = ''; // Field name for unit selector

    protected function setUp(): void
    {
        parent::setUp();

        // Generate unit selector field name if not set
        if (empty($this->unitSelectorField) && $this->showUnitSelector) {
            $this->unitSelectorField = $this->getName() . '_unit';
        }

        // Set default placeholder
        $this->placeholder('e.g. 41 5/16, 41 yd, 41 mm, or 41"');

        // Set helper text to show formatted measurement
        $this->helperText(function (callable $get) {
            $value = $get($this->getName());
            $unit = $this->showUnitSelector ? ($get($this->unitSelectorField) ?? 'inches') : 'inches';
            
            if ($value === null || $value === '') {
                return 'Enter as decimal (41.3125), fraction (41 5/16), or with unit (41 yd, 41 mm, 41 cm, 41 m). Defaults to inches.';
            }

            // Parse the input value (handles fractions, decimals, etc.)
            $parsed = MeasurementFormatter::parse($value);
            
            if ($parsed === null) {
                return 'Invalid format. Enter as decimal (41.3125), fraction (41 5/16), or with unit (41 yd, 41 mm).';
            }

            // Convert input value to inches for formatting
            $inches = $this->convertToInches($parsed, $unit);
            
            if ($inches === null) {
                return 'Error converting unit. Enter as decimal (41.3125), fraction (41 5/16), or with unit (41 yd, 41 mm).';
            }

            // Format the measurement in all formats for display
            $formatter = new MeasurementFormatter();
            $formatted = [];
            
            // Show fraction format
            $fraction = $formatter->formatFraction($inches, true);
            $formatted[] = $fraction;
            
            // Show decimal format
            $decimal = $formatter->formatDecimal($inches, true);
            if ($decimal !== $fraction) {
                $formatted[] = $decimal;
            }
            
            // Show metric format
            $metric = $formatter->formatMetric($inches, true);
            $formatted[] = $metric;
            
            return 'Measurement: ' . implode(' = ', $formatted);
        });

        // Enable live updates with debounce
        $this->live(debounce: 500);

        // Format state for display - convert decimal to fraction when loading existing data
        $this->formatStateUsing(function ($state, callable $get) {
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

        // Dehydrate state (convert to decimal inches for storage)
        // This is what gets saved to the database
        $this->dehydrateStateUsing(function ($state, callable $get) {
            if ($state === null || $state === '') {
                return null;
            }

            // Get the input unit
            $unit = $this->showUnitSelector ? ($get($this->unitSelectorField) ?? 'inches') : 'inches';
            
            // Parse the input value
            $decimal = MeasurementFormatter::parse($state);
            
            if ($decimal === null) {
                return null;
            }

            // Convert to inches for storage
            $inches = $this->convertToInches($decimal, $unit);
            
            return $inches !== null ? round($inches, 4) : null;
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

        // Add suffix if unit selector is not enabled
        if (!$this->showUnitSelector) {
            $this->suffix('in');
        }
    }

    /**
     * Convert a value from the specified unit to inches
     *
     * @param float $value The value to convert
     * @param string $unit The unit of the input value ('inches', 'feet', 'millimeters')
     * @return float|null The value in inches, or null if invalid
     */
    protected function convertToInches(float $value, string $unit): ?float
    {
        return match ($unit) {
            'inches' => $value,
            'feet' => $value * MeasurementFormatter::FEET_TO_INCHES, // 1 foot = 12 inches
            'yards' => $value * MeasurementFormatter::YARDS_TO_INCHES, // 1 yard = 36 inches
            'millimeters' => $value / MeasurementFormatter::INCHES_TO_MM, // Convert mm to inches
            'centimeters' => $value / MeasurementFormatter::INCHES_TO_CM, // Convert cm to inches
            'meters' => $value / MeasurementFormatter::INCHES_TO_M, // Convert m to inches
            default => $value, // Default to inches
        };
    }

    /**
     * Enable unit selector dropdown
     */
    public function withUnitSelector(bool $show = true): static
    {
        $this->showUnitSelector = $show;
        
        if ($show && empty($this->unitSelectorField)) {
            $this->unitSelectorField = $this->getName() . '_unit';
        }
        
        return $this;
    }

    /**
     * Set the default input unit
     */
    public function defaultUnit(string $unit): static
    {
        $this->inputUnit = $unit;
        return $this;
    }

    /**
     * Get whether unit selector is enabled
     */
    public function getShowUnitSelector(): bool
    {
        return $this->showUnitSelector;
    }

    /**
     * Get the unit selector field name
     */
    public function getUnitSelectorField(): string
    {
        return $this->unitSelectorField;
    }
}
