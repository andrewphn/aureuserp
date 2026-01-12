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

        // Set helper text to show formatted measurement and conversion
        // Helper text always shows decimal form for clarity
        $this->helperText(function (callable $get) {
            $value = $get($this->getName());
            $unit = $this->showUnitSelector ? ($get($this->unitSelectorField) ?? 'inches') : 'inches';
            
            if ($value === null || $value === '') {
                return 'Enter as decimal (41.3125), fraction (41 5/16), or with unit (41 yd, 41 mm, 41 cm, 41 m). Defaults to inches.';
            }

            // Detect unit from input string (before parsing)
            $detectedUnit = $this->detectUnitFromInput($value);
            $inputUnit = $detectedUnit ?? $unit;
            
            // Extract numeric value from input (remove unit suffix for display)
            $numericValue = $value;
            if ($detectedUnit) {
                $unitPattern = '/\s*(yd|yard|yards|ft|feet|foot|\'|in|inch|inches|"|mm|millimeter|millimeters|cm|centimeter|centimeters|m|meter|meters)\s*$/i';
                $numericValue = preg_replace($unitPattern, '', $value);
            }
            
            // Parse the input value (handles fractions, decimals, etc.) - this converts to inches
            $inches = MeasurementFormatter::parse($value);
            
            if ($inches === null) {
                return 'Invalid format. Enter as decimal (41.3125), fraction (41 5/16), or with unit (41 yd, 41 mm).';
            }

            // Format the measurement for helper text - always show decimal prominently
            $formatter = new MeasurementFormatter();
            $decimal = $formatter->formatDecimal($inches, true);
            
            // Show the conversion that took place if unit was detected or different from inches
            $conversionText = '';
            if ($detectedUnit && $detectedUnit !== 'inches') {
                $unitSymbol = $this->getUnitSymbol($detectedUnit);
                $conversionText = "{$numericValue} {$unitSymbol} → ";
            } elseif ($inputUnit !== 'inches' && $this->showUnitSelector) {
                $unitSymbol = $this->getUnitSymbol($inputUnit);
                $conversionText = "{$numericValue} {$unitSymbol} → ";
            }
            
            // Build helper text: show conversion (if any), then decimal, then other formats
            $parts = [];
            if ($conversionText) {
                $parts[] = $conversionText . $decimal;
            }
            $parts[] = 'Stored as: ' . $decimal;
            
            // Show other formats for reference
            $fraction = $formatter->formatFraction($inches, true);
            $metric = $formatter->formatMetric($inches, true);
            if ($decimal !== $fraction) {
                $parts[] = $fraction;
            }
            $parts[] = $metric;
            
            return implode(' | ', $parts);
        });

        // Enable live updates on blur/tab (not while typing)
        // This allows user to type freely, then normalizes on blur
        $this->live(onBlur: true);

        // Format state for display - use settings to determine display format (default: fractional)
        $this->formatStateUsing(function ($state, callable $get) {
            // Return null for empty states to show placeholder
            if ($state === null || $state === '' || $state === 0) {
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

            // If it's a decimal number, format according to settings (default: fractional)
            if (is_numeric($state) && (float) $state > 0) {
                $formatter = new MeasurementFormatter();
                $settings = $formatter->getSettings();
                
                // Use settings to determine display format (default to imperial_fraction)
                $displayFormat = $settings->display_unit ?? 'imperial_fraction';
                
                return match($displayFormat) {
                    'imperial_fraction' => $formatter->formatFraction((float) $state, false), // false = no symbol
                    'imperial_decimal' => $formatter->formatDecimal((float) $state, false),
                    'metric' => $formatter->formatMetric((float) $state, false),
                    default => $formatter->formatFraction((float) $state, false), // Default to fraction
                };
            }

            // Return null for invalid states to show placeholder
            return null;
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

        // After state updated (on blur/tab) - normalize the display value according to settings
        // This ensures the field shows the correct format (fraction/decimal/metric) while storing as decimal
        // Normalizes fractional input (e.g., "41-5/16" → "41 5/16")
        $this->afterStateUpdated(function ($state, callable $set, callable $get) {
            if ($state === null || $state === '') {
                return;
            }

            // Check if input looks like a fraction (with dash or space)
            $isFractionInput = is_string($state) && (
                str_contains($state, '/') || 
                preg_match('/\d+\s+\d+\/\d+/', $state) ||
                preg_match('/\d+-\d+\/\d+/', $state)
            );

            if ($isFractionInput) {
                // User typed a fraction - normalize it by parsing to decimal then formatting back
                // This converts "41-5/16" → 41.3125 → "41 5/16" (proper format with space)
                $formatter = new MeasurementFormatter();
                
                // Parse the fraction to decimal
                $decimal = MeasurementFormatter::parse($state);
                
                if ($decimal !== null) {
                    // Get settings to determine display format (default to imperial_fraction)
                    $settings = $formatter->getSettings();
                    $displayFormat = $settings->display_unit ?? 'imperial_fraction';
                    
                    // Format back according to settings (this normalizes dash to space for fractions)
                    $formatted = match($displayFormat) {
                        'imperial_fraction' => $formatter->formatFraction($decimal, false), // false = no symbol
                        'imperial_decimal' => $formatter->formatDecimal($decimal, false),
                        'metric' => $formatter->formatMetric($decimal, false),
                        default => $formatter->formatFraction($decimal, false), // Default to fraction
                    };
                    
                    // Update the field with normalized format
                    $set($this->getName(), $formatted);
                }
            } else {
                // User typed a decimal or other format - format according to settings
                if (is_numeric($state)) {
                    $formatter = new MeasurementFormatter();
                    $settings = $formatter->getSettings();
                    
                    // Use settings to determine display format (default to imperial_fraction)
                    $displayFormat = $settings->display_unit ?? 'imperial_fraction';
                    
                    $formatted = match($displayFormat) {
                        'imperial_fraction' => $formatter->formatFraction((float) $state, false),
                        'imperial_decimal' => $formatter->formatDecimal((float) $state, false),
                        'metric' => $formatter->formatMetric((float) $state, false),
                        default => $formatter->formatFraction((float) $state, false), // Default to fraction
                    };
                    
                    $set($this->getName(), $formatted);
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
     * Detect unit from input string
     *
     * @param string $input The input string
     * @return string|null The detected unit or null if not detected
     */
    protected function detectUnitFromInput(string $input): ?string
    {
        $input = trim($input);
        
        // Pattern to match unit suffixes
        $unitPattern = '/\s*(yd|yard|yards|ft|feet|foot|\'|in|inch|inches|"|mm|millimeter|millimeters|cm|centimeter|centimeters|m|meter|meters)\s*$/i';
        
        if (preg_match($unitPattern, $input, $matches)) {
            $unitStr = strtolower(trim($matches[1]));
            
            return match($unitStr) {
                'yd', 'yard', 'yards' => 'yards',
                'ft', 'feet', 'foot', "'" => 'feet',
                'in', 'inch', 'inches', '"' => 'inches',
                'mm', 'millimeter', 'millimeters' => 'millimeters',
                'cm', 'centimeter', 'centimeters' => 'centimeters',
                'm', 'meter', 'meters' => 'meters',
                default => null,
            };
        }
        
        return null;
    }

    /**
     * Get unit symbol for display
     *
     * @param string $unit The unit name
     * @return string The unit symbol
     */
    protected function getUnitSymbol(string $unit): string
    {
        return match($unit) {
            'inches' => 'in',
            'feet' => 'ft',
            'yards' => 'yd',
            'millimeters' => 'mm',
            'centimeters' => 'cm',
            'meters' => 'm',
            default => 'in',
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
