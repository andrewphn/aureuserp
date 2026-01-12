# Measurement Tools Reference

This document lists all available measurement tools, services, and utilities in the codebase.

## 📦 Core Services

### 1. **MeasurementFormatter Service** (PHP)
**Location:** `plugins/webkul/support/src/Services/MeasurementFormatter.php`

Main service for formatting and converting measurements. All measurements are stored in inches and converted for display only.

#### Key Methods:

**Formatting Methods:**
- `format(?float $inches, ?bool $showSymbol = null): string` - Format according to global settings
- `formatDecimal(float $inches, bool $showSymbol = true): string` - Format as decimal (e.g., "24.5"")
- `formatFraction(float $inches, bool $showSymbol = true): string` - Format as fraction (e.g., "24 1/2"")
- `formatMetric(float $inches, bool $showSymbol = true): string` - Format as millimeters (e.g., "622 mm")
- `formatDimensions(?float $width, ?float $height, ?float $depth = null): string` - Format W x H x D
- `formatLinearFeet(float $linearFeet): string` - Format linear feet (e.g., "2.50 LF")

**Conversion Methods (Linear):**
- `inchesToMm(float $inches): float` - Convert inches to millimeters
- `mmToInches(float $mm): float` - Convert millimeters to inches
- `inchesToFeet(float $inches): float` - Convert inches to feet
- `feetToInches(float $feet): float` - Convert feet to inches
- `inchesToCm(float $inches): float` - Convert inches to centimeters
- `cmToInches(float $cm): float` - Convert centimeters to inches
- `inchesToMeters(float $inches): float` - Convert inches to meters
- `metersToInches(float $meters): float` - Convert meters to inches
- `parseFractionalMeasurement($input): ?float` - Parse fractional input to decimal inches
- `static parse($input): ?float` - Static helper for parsing

**Conversion Methods (Area):**
- `sqInchesToSqFeet(float $sqInches): float` - Convert square inches to square feet
- `sqFeetToSqInches(float $sqFeet): float` - Convert square feet to square inches
- `calculateSquareFeet(float $widthInches, float $heightInches): float` - Calculate sq ft from W x H
- `formatSquareFeet(float $sqFeet, int $precision = 2): string` - Format square feet value

**Conversion Methods (Volume):**
- `cubicInchesToCubicFeet(float $cubicInches): float` - Convert cubic inches to cubic feet
- `cubicFeetToCubicInches(float $cubicFeet): float` - Convert cubic feet to cubic inches
- `calculateCubicFeet(float $widthInches, float $heightInches, float $depthInches): float` - Calculate cu ft from W x H x D
- `formatCubicFeet(float $cubicFeet, int $precision = 2): string` - Format cubic feet value

**Conversion Methods (Length):**
- `calculateLinearFeet(float $inches): float` - Calculate linear feet from inches

**Parsing Support:**
- `"12.5"` → 12.5 (decimal)
- `"12 1/2"` → 12.5 (whole + fraction with space)
- `"12-1/2"` → 12.5 (whole + fraction with dash)
- `"41 5/16"` → 41.3125 (whole + fraction)
- `"41-5/16"` → 41.3125 (whole + fraction with dash)
- `"3/4"` → 0.75 (fraction only)
- `"2'"` → 24 (feet to inches)
- `"2' 6"` → 30 (feet and inches)
- `"2' 6 1/2"` → 30.5 (feet, inches, and fraction)

**Settings Methods:**
- `getInputStep(): float` - Get input step increment
- `getDisplayUnit(): string` - Get current display unit
- `getFractionPrecision(): int` - Get fraction precision
- `isFractionMode(): bool` - Check if in fraction mode
- `isMetricMode(): bool` - Check if in metric mode
- `getSettingsArray(): array` - Get all settings as array (for JavaScript)

**Constants:**
- `INCHES_TO_MM = 25.4` - 1 inch = 25.4 mm
- `INCHES_TO_CM = 2.54` - 1 inch = 2.54 cm
- `INCHES_TO_M = 0.0254` - 1 inch = 0.0254 m
- `INCHES_TO_FEET = 1/12` - 1 inch = 1/12 feet
- `FEET_TO_INCHES = 12` - 1 foot = 12 inches
- `SQ_INCHES_TO_SQ_FEET = 1/144` - 1 sq inch = 1/144 sq feet
- `SQ_FEET_TO_SQ_INCHES = 144` - 1 sq foot = 144 sq inches
- `CUBIC_INCHES_TO_CUBIC_FEET = 1/1728` - 1 cu inch = 1/1728 cu feet
- `CUBIC_FEET_TO_CUBIC_INCHES = 1728` - 1 cu foot = 1728 cu inches

#### Usage:
```php
use Webkul\Support\Services\MeasurementFormatter;

$formatter = app(MeasurementFormatter::class);
// or
$formatter = new MeasurementFormatter();

// Format a dimension
$formatted = $formatter->format(24.5); // "24.5"" or "24 1/2"" depending on settings

// Parse fractional input
$decimal = MeasurementFormatter::parse("41 5/16"); // 41.3125

// Convert units
$mm = $formatter->inchesToMm(24.5); // 622.3
$inches = $formatter->mmToInches(622.3); // 24.5
```

---

### 2. **MeasurementFormatter JavaScript** (Client-side)
**Location:** `resources/js/measurement-formatter.js`

JavaScript companion that mirrors the PHP implementation for client-side formatting.

#### Key Methods:

**Formatting:**
- `format(inches, showSymbol = null)` - Format according to settings
- `formatDecimal(inches, showSymbol = true)` - Format as decimal
- `formatFraction(inches, showSymbol = true)` - Format as fraction
- `formatMetric(inches, showSymbol = true)` - Format as millimeters
- `formatDimensions(width, height, depth = null)` - Format W x H x D
- `formatLinearFeet(linearFeet)` - Format linear feet

**Conversion:**
- `inchesToMm(inches)` - Convert inches to millimeters
- `mmToInches(mm)` - Convert millimeters to inches

**Utilities:**
- `init(settings)` - Initialize with PHP settings
- `getInputStep()` - Get input step value
- `isFractionMode()` - Check if in fraction mode
- `isMetricMode()` - Check if in metric mode

**Alpine.js Magic Properties:**
- `$dimension(inches, showSymbol)` - Format a single dimension
- `$dimensions(w, h, d)` - Format W x H x D
- `$linearFeet(lf)` - Format linear feet
- `$measurementSettings` - Get current settings

**Global Functions:**
- `window.formatDimension(inches, showSymbol)` - Format dimension
- `window.formatDimensions(w, h, d)` - Format dimensions
- `window.formatLinearFeet(lf)` - Format linear feet

#### Usage:
```javascript
// Initialize with PHP settings
MeasurementFormatter.init(measurementSettings());

// Format a dimension
const formatted = MeasurementFormatter.format(24.5); // "24.5"" or "24 1/2""

// In Alpine.js
<div x-text="$dimension(24.5)"></div>
<div x-text="$dimensions(24, 36, 24)"></div>
```

---

## 🎨 Form Components

### 3. **MeasurementInput Component** (Filament)
**Location:** `plugins/webkul/support/src/Filament/Forms/Components/MeasurementInput.php`
**View:** `resources/views/forms/components/measurement-input.blade.php`

Filament form component for entering measurements with unit conversion support.

#### Features:
- ✅ Fractional input support ("41 5/16", "41-5/16")
- ✅ Unit selector (inches, feet, millimeters)
- ✅ Automatic conversion to decimal inches for storage
- ✅ Formatted measurement display in helper text
- ✅ Editable fractional values

#### Usage:
```php
use Webkul\Support\Filament\Forms\Components\MeasurementInput;

MeasurementInput::make('width_inches')
    ->label('Width')
    ->default(24)
    ->required()
    ->withUnitSelector() // Enable unit selector dropdown
    ->defaultUnit('inches') // Set default input unit
```

#### Methods:
- `withUnitSelector(bool $show = true)` - Enable/disable unit selector
- `defaultUnit(string $unit)` - Set default input unit

---

### 4. **CabinetDimensionsFields Component**
**Location:** `plugins/webkul/projects/src/Filament/Forms/Schemas/Components/CabinetDimensionsFields.php`

Molecule component for cabinet dimension fields using MeasurementInput.

#### Methods:
- `getCabinetDimensionsGrid(int $columns = 5)` - Get full dimensions grid
- `getSimplifiedDimensionsGrid()` - Get simplified grid (width, height, qty)
- `getDimensionInput(...)` - Get single dimension input with unit selector

#### Usage:
```php
use Webkul\Project\Filament\Forms\Schemas\Components\CabinetDimensionsFields;

// Full grid
CabinetDimensionsFields::getCabinetDimensionsGrid();

// Simplified grid
CabinetDimensionsFields::getSimplifiedDimensionsGrid();

// Single input
CabinetDimensionsFields::getDimensionInput(
    'width_inches',
    'Width',
    24,
    true, // required
    6,    // minValue
    96,   // maxValue
    true  // withUnitSelector
);
```

---

## 🔧 Helper Functions (PHP)

**Location:** `plugins/webkul/support/src/helpers.php`

Global helper functions for easy access to measurement formatting.

### Functions:

1. **`format_dimension(?float $inches, ?bool $showSymbol = null): string`**
   ```php
   format_dimension(24.5); // "24.5"" or "24 1/2"" depending on settings
   ```

2. **`format_dimensions(?float $width, ?float $height, ?float $depth = null): string`**
   ```php
   format_dimensions(24, 36, 24); // "24"W x 36"H x 24"D"
   ```

3. **`format_linear_feet(float $linearFeet): string`**
   ```php
   format_linear_feet(2.5); // "2.50 LF"
   ```

4. **`measurement_settings(): array`**
   ```php
   $settings = measurement_settings(); // Get settings for JavaScript
   ```

---

## 🎯 Model Traits

### 5. **HasFormattedDimensions Trait**
**Location:** `plugins/webkul/support/src/Traits/HasFormattedDimensions.php`

Trait for models with dimension attributes. Provides formatted dimension accessors.

#### Accessors:
- `$model->formatted_width` - Formatted width
- `$model->formatted_height` - Formatted height
- `$model->formatted_depth` - Formatted depth
- `$model->formatted_length` - Formatted length
- `$model->formatted_dimensions` - Formatted W x H x D
- `$model->formatted_linear_feet` - Formatted linear feet
- `$model->measurement_settings` - Get settings array

#### Methods:
- `formatDimension(string $field, ?bool $showSymbol = null): string` - Format specific field

#### Usage:
```php
use Webkul\Support\Traits\HasFormattedDimensions;

class Cabinet extends Model
{
    use HasFormattedDimensions;
    
    // Override field names if needed
    protected function getWidthField(): string
    {
        return 'length_inches'; // Cabinet uses length_inches for width
    }
}

// Usage
$cabinet->formatted_width; // "24 1/2""
$cabinet->formatted_dimensions; // "24"W x 36"H x 24"D"
$cabinet->formatDimension('width_inches'); // Format specific field
```

**Used by:** Cabinet, Door, Drawer, Shelf, Pullout, CabinetSection, CabinetRun

---

## ⚙️ Settings & Configuration

### 6. **MeasurementSettings**
**Location:** `plugins/webkul/support/src/Settings/MeasurementSettings.php`

Global measurement settings using Spatie Laravel Settings.

#### Settings:
- `display_unit` - 'imperial_decimal', 'imperial_fraction', or 'metric'
- `fraction_precision` - 2, 4, 8, or 16 (1/2, 1/4, 1/8, 1/16)
- `show_unit_symbol` - Whether to show " or mm
- `metric_precision` - Decimal places for mm (0-2)
- `linear_feet_precision` - Decimal places for LF (1-4)
- `input_step` - Step increment for form fields (0.0625, 0.125, 0.25, etc.)

### 7. **ManageMeasurement Settings Page**
**Location:** `plugins/webkul/support/src/Filament/Clusters/Settings/Pages/ManageMeasurement.php`

Filament admin page for managing global measurement settings.

**Access:** Admin → Settings → Measurement

---

## 📝 Other Measurement Tools

### 8. **CabinetParsingService**
**Location:** `plugins/webkul/projects/src/Services/CabinetParsingService.php`

Service for parsing cabinet codes and dimensions.

#### Methods:
- `parseFromName(string $name): array` - Parse cabinet code (e.g., "B24" → type: 'base', width: 24)
- `parseWidthInput(string $input): ?float` - Parse width input (deprecated, use MeasurementFormatter::parse())

---

## 🔄 Conversion Tools Summary

### All Available Conversion Combinations:

#### **Linear (Length) Conversions:**
| From | To | Method | Conversion Factor |
|------|-----|--------|-------------------|
| Inches | Millimeters | `inchesToMm()` | × 25.4 |
| Millimeters | Inches | `mmToInches()` | ÷ 25.4 |
| Inches | Feet | `inchesToFeet()` | × (1/12) |
| Feet | Inches | `feetToInches()` | × 12 |
| Inches | Centimeters | `inchesToCm()` | × 2.54 |
| Centimeters | Inches | `cmToInches()` | ÷ 2.54 |
| Inches | Meters | `inchesToMeters()` | × 0.0254 |
| Meters | Inches | `metersToInches()` | ÷ 0.0254 |
| Fraction | Decimal | `parseFractionalMeasurement()` | Parsed |
| Decimal | Fraction | `formatFraction()` | Formatted |

#### **Area Conversions:**
| From | To | Method | Conversion Factor |
|------|-----|--------|-------------------|
| Square Inches | Square Feet | `sqInchesToSqFeet()` | × (1/144) |
| Square Feet | Square Inches | `sqFeetToSqInches()` | × 144 |
| W × H (inches) | Square Feet | `calculateSquareFeet($w, $h)` | (W × H) ÷ 144 |

#### **Volume Conversions:**
| From | To | Method | Conversion Factor |
|------|-----|--------|-------------------|
| Cubic Inches | Cubic Feet | `cubicInchesToCubicFeet()` | × (1/1728) |
| Cubic Feet | Cubic Inches | `cubicFeetToCubicInches()` | × 1728 |
| W × H × D (inches) | Cubic Feet | `calculateCubicFeet($w, $h, $d)` | (W × H × D) ÷ 1728 |

#### **Length/Linear Conversions:**
| From | To | Method | Conversion Factor |
|------|-----|--------|-------------------|
| Inches | Linear Feet | `calculateLinearFeet()` | × (1/12) |
| Inches | Linear Feet | `inchesToFeet()` | × (1/12) |

### Conversion Chain Examples:

**Complete Conversion Matrix:**
```
Inches ↔ Feet ↔ Millimeters ↔ Centimeters ↔ Meters
  ↓        ↓          ↓             ↓            ↓
Linear Feet    Square Feet    Cubic Feet
```

**Common Conversion Paths:**
1. **Input in Feet → Store in Inches:**
   ```php
   $feet = 2.5;
   $inches = $formatter->feetToInches($feet); // 30.0
   ```

2. **Input in Millimeters → Store in Inches:**
   ```php
   $mm = 622.3;
   $inches = $formatter->mmToInches($mm); // 24.5
   ```

3. **Calculate Area from Dimensions:**
   ```php
   $sqFeet = $formatter->calculateSquareFeet(24, 36); // 6.0 sq ft
   ```

4. **Calculate Volume from Dimensions:**
   ```php
   $cubicFeet = $formatter->calculateCubicFeet(24, 36, 24); // 12.0 cu ft
   ```

5. **Calculate Linear Feet:**
   ```php
   $linearFeet = $formatter->calculateLinearFeet(144); // 12.0 LF
   ```

### Supported Input Formats:
- Decimal: `"12.5"`, `"41.3125"`
- Fraction (space): `"12 1/2"`, `"41 5/16"`
- Fraction (dash): `"12-1/2"`, `"41-5/16"`
- Fraction only: `"3/4"`, `"1/2"`
- Feet notation: `"2'"`, `"2' 6"`, `"2' 6 1/2"`

### Display Formats:
- **Imperial Decimal:** `24.5"`
- **Imperial Fraction:** `24 1/2"` (precision: 1/2, 1/4, 1/8, 1/16)
- **Metric:** `622 mm` (precision: 0-2 decimal places)

---

## 📍 File Locations Summary

```
plugins/webkul/support/src/
├── Services/
│   └── MeasurementFormatter.php          # Main service
├── Settings/
│   └── MeasurementSettings.php            # Settings class
├── Filament/
│   ├── Forms/Components/
│   │   └── MeasurementInput.php          # Form component
│   └── Clusters/Settings/Pages/
│       └── ManageMeasurement.php         # Settings page
├── Traits/
│   └── HasFormattedDimensions.php        # Model trait
└── helpers.php                            # Helper functions

resources/
├── js/
│   └── measurement-formatter.js           # JavaScript service
└── views/forms/components/
    └── measurement-input.blade.php        # Component view

plugins/webkul/projects/src/
├── Filament/Forms/Schemas/Components/
│   └── CabinetDimensionsFields.php       # Dimension fields component
└── Services/
    └── CabinetParsingService.php          # Cabinet parsing service
```

---

## 🚀 Quick Start Examples

### PHP Example:
```php
use Webkul\Support\Services\MeasurementFormatter;

// Format a dimension
$formatted = format_dimension(24.5); // Uses global settings

// Parse user input
$decimal = MeasurementFormatter::parse("41 5/16"); // 41.3125

// Convert units
$formatter = app(MeasurementFormatter::class);
$mm = $formatter->inchesToMm(24.5); // 622.3
```

### JavaScript Example:
```javascript
// Initialize
MeasurementFormatter.init(measurementSettings());

// Format
const formatted = MeasurementFormatter.format(24.5);

// In Alpine.js
<div x-text="$dimension(24.5)"></div>
```

### Filament Form Example:
```php
use Webkul\Support\Filament\Forms\Components\MeasurementInput;

MeasurementInput::make('width_inches')
    ->label('Width')
    ->withUnitSelector()
    ->required()
```

### Model Example:
```php
use Webkul\Support\Traits\HasFormattedDimensions;

class Cabinet extends Model
{
    use HasFormattedDimensions;
}

// Usage
$cabinet->formatted_dimensions; // "24"W x 36"H x 24"D"
```

---

## 📚 Additional Notes

- All measurements are **stored in inches** as decimals
- Display format is controlled by global settings
- Fraction precision can be 1/2, 1/4, 1/8, or 1/16
- Unit selector in MeasurementInput converts input to inches for storage
- Helper text shows measurement in all formats (fraction, decimal, metric)
