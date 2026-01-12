# Measurement Conversion Combinations

Complete reference for all unit conversion combinations available in the measurement tools.

## 📐 Linear (Length) Conversions

All measurements are stored in **inches** as decimals. These conversions convert to/from inches.

| From Unit | To Unit | PHP Method | JavaScript Method | Conversion Factor |
|-----------|---------|------------|-------------------|-------------------|
| Inches | Millimeters | `inchesToMm($inches)` | `inchesToMm(inches)` | × 25.4 |
| Millimeters | Inches | `mmToInches($mm)` | `mmToInches(mm)` | ÷ 25.4 |
| Inches | Feet | `inchesToFeet($inches)` | `inchesToFeet(inches)` | × (1/12) |
| Feet | Inches | `feetToInches($feet)` | `feetToInches(feet)` | × 12 |
| Inches | Centimeters | `inchesToCm($inches)` | `inchesToCm(inches)` | × 2.54 |
| Centimeters | Inches | `cmToInches($cm)` | `cmToInches(cm)` | ÷ 2.54 |
| Inches | Meters | `inchesToMeters($inches)` | `inchesToMeters(inches)` | × 0.0254 |
| Meters | Inches | `metersToInches($meters)` | `metersToInches(meters)` | ÷ 0.0254 |

### Examples:
```php
$formatter = app(MeasurementFormatter::class);

// Inches to other units
$mm = $formatter->inchesToMm(24.5);        // 622.3 mm
$feet = $formatter->inchesToFeet(24.5);    // 2.0417 ft
$cm = $formatter->inchesToCm(24.5);        // 62.23 cm
$meters = $formatter->inchesToMeters(24.5); // 0.6223 m

// Other units to inches
$inches = $formatter->mmToInches(622.3);    // 24.5 in
$inches = $formatter->feetToInches(2.5);    // 30.0 in
$inches = $formatter->cmToInches(62.23);    // 24.5 in
$inches = $formatter->metersToInches(0.6223); // 24.5 in
```

---

## 📏 Area Conversions

Convert between square inches and square feet, or calculate area from dimensions.

| From | To | PHP Method | JavaScript Method | Conversion Factor |
|------|-----|------------|-------------------|-------------------|
| Square Inches | Square Feet | `sqInchesToSqFeet($sqInches)` | `sqInchesToSqFeet(sqInches)` | × (1/144) |
| Square Feet | Square Inches | `sqFeetToSqInches($sqFeet)` | `sqFeetToSqInches(sqFeet)` | × 144 |
| W × H (inches) | Square Feet | `calculateSquareFeet($w, $h)` | `calculateSquareFeet(w, h)` | (W × H) ÷ 144 |

### Examples:
```php
$formatter = app(MeasurementFormatter::class);

// Square inches to square feet
$sqFeet = $formatter->sqInchesToSqFeet(864); // 6.0 sq ft

// Square feet to square inches
$sqInches = $formatter->sqFeetToSqInches(6.0); // 864 sq in

// Calculate from dimensions (24" × 36" = 864 sq in = 6 sq ft)
$sqFeet = $formatter->calculateSquareFeet(24, 36); // 6.0 sq ft
```

---

## 📦 Volume Conversions

Convert between cubic inches and cubic feet, or calculate volume from dimensions.

| From | To | PHP Method | JavaScript Method | Conversion Factor |
|------|-----|------------|-------------------|-------------------|
| Cubic Inches | Cubic Feet | `cubicInchesToCubicFeet($cuIn)` | `cubicInchesToCubicFeet(cuIn)` | × (1/1728) |
| Cubic Feet | Cubic Inches | `cubicFeetToCubicInches($cuFt)` | `cubicFeetToCubicInches(cuFt)` | × 1728 |
| W × H × D (inches) | Cubic Feet | `calculateCubicFeet($w, $h, $d)` | `calculateCubicFeet(w, h, d)` | (W × H × D) ÷ 1728 |

### Examples:
```php
$formatter = app(MeasurementFormatter::class);

// Cubic inches to cubic feet
$cubicFeet = $formatter->cubicInchesToCubicFeet(20736); // 12.0 cu ft

// Cubic feet to cubic inches
$cubicInches = $formatter->cubicFeetToCubicInches(12.0); // 20736 cu in

// Calculate from dimensions (24" × 36" × 24" = 20736 cu in = 12 cu ft)
$cubicFeet = $formatter->calculateCubicFeet(24, 36, 24); // 12.0 cu ft
```

---

## 📊 Linear Feet Conversions

Convert inches to linear feet (commonly used for material calculations).

| From | To | PHP Method | JavaScript Method | Conversion Factor |
|------|-----|------------|-------------------|-------------------|
| Inches | Linear Feet | `calculateLinearFeet($inches)` | `calculateLinearFeet(inches)` | × (1/12) |
| Inches | Linear Feet | `inchesToFeet($inches)` | `inchesToFeet(inches)` | × (1/12) |

### Examples:
```php
$formatter = app(MeasurementFormatter::class);

// Calculate linear feet from inches
$linearFeet = $formatter->calculateLinearFeet(144); // 12.0 LF
$linearFeet = $formatter->inchesToFeet(144);      // 12.0 LF (same result)
```

---

## 🔄 Fraction ↔ Decimal Conversions

Parse fractional input and format decimal output as fractions.

| From | To | PHP Method | JavaScript Method | Notes |
|------|-----|------------|-------------------|-------|
| Fraction String | Decimal | `parseFractionalMeasurement($input)` | N/A (PHP only) | Parses "41 5/16" → 41.3125 |
| Decimal | Fraction String | `formatFraction($inches)` | `formatFraction(inches)` | Formats 41.3125 → "41 5/16"" |

### Supported Fraction Input Formats:
- `"12.5"` → 12.5 (decimal)
- `"12 1/2"` → 12.5 (whole + fraction with space)
- `"12-1/2"` → 12.5 (whole + fraction with dash)
- `"41 5/16"` → 41.3125 (whole + fraction)
- `"41-5/16"` → 41.3125 (whole + fraction with dash)
- `"3/4"` → 0.75 (fraction only)
- `"2'"` → 24 (feet to inches)
- `"2' 6"` → 30 (feet and inches)
- `"2' 6 1/2"` → 30.5 (feet, inches, and fraction)

### Examples:
```php
use Webkul\Support\Services\MeasurementFormatter;

// Parse fractional input
$decimal = MeasurementFormatter::parse("41 5/16"); // 41.3125
$decimal = MeasurementFormatter::parse("2' 6");    // 30.0

// Format decimal as fraction
$formatter = app(MeasurementFormatter::class);
$fraction = $formatter->formatFraction(41.3125); // "41 5/16""
```

---

## 🎯 Common Conversion Scenarios

### Scenario 1: User Inputs in Feet, Store in Inches
```php
$userInput = 2.5; // feet
$inches = $formatter->feetToInches($userInput); // 30.0 (stored in DB)
```

### Scenario 2: User Inputs in Millimeters, Store in Inches
```php
$userInput = 622.3; // mm
$inches = $formatter->mmToInches($userInput); // 24.5 (stored in DB)
```

### Scenario 3: Calculate Material Area Needed
```php
$width = 24;  // inches
$height = 36; // inches
$sqFeet = $formatter->calculateSquareFeet($width, $height); // 6.0 sq ft
```

### Scenario 4: Calculate Material Volume Needed
```php
$width = 24;  // inches
$height = 36; // inches
$depth = 24;  // inches
$cubicFeet = $formatter->calculateCubicFeet($width, $height, $depth); // 12.0 cu ft
```

### Scenario 5: Calculate Linear Feet for Material
```php
$totalLength = 144; // inches
$linearFeet = $formatter->calculateLinearFeet($totalLength); // 12.0 LF
```

### Scenario 6: Parse User Input with Fractions
```php
$userInput = "41 5/16"; // User types this
$decimal = MeasurementFormatter::parse($userInput); // 41.3125 (stored in DB)
```

---

## 📋 Conversion Constants Reference

All conversion factors are defined as class constants:

```php
MeasurementFormatter::INCHES_TO_MM = 25.4
MeasurementFormatter::INCHES_TO_CM = 2.54
MeasurementFormatter::INCHES_TO_M = 0.0254
MeasurementFormatter::INCHES_TO_FEET = 1 / 12
MeasurementFormatter::FEET_TO_INCHES = 12
MeasurementFormatter::SQ_INCHES_TO_SQ_FEET = 1 / 144
MeasurementFormatter::SQ_FEET_TO_SQ_INCHES = 144
MeasurementFormatter::CUBIC_INCHES_TO_CUBIC_FEET = 1 / 1728
MeasurementFormatter::CUBIC_FEET_TO_CUBIC_INCHES = 1728
```

---

## 🔗 Related Tools

- **MeasurementInput Component** - Form field with unit selector (inches, feet, millimeters)
- **MeasurementFormatter Service** - Main conversion service
- **format_dimension()** - Helper function for formatting
- **HasFormattedDimensions Trait** - Model accessors for formatted dimensions

See `MEASUREMENT_TOOLS.md` for complete documentation of all measurement tools.
