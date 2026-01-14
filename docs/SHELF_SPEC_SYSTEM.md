# Adjustable Shelf Specification System

## Overview

The Adjustable Shelf Specification System calculates shelf dimensions, pin hole layouts, and generates CNC cut lists for cabinet shelves. This document captures **shop practices from carpenter interviews** along with database schema and implementation details.

---

## Shop Research Summary

### Source: Carpenter Interview (January 2026)

#### Hardware: 5mm Shelf Pins

| Specification | Value | Notes |
|---------------|-------|-------|
| Pin diameter | 5mm | Standard/generic |
| Pin type | Spoon style | Common for adjustable shelves |
| Source | Generic suppliers | Consistent across vendors |
| Has spec sheet | Yes (in inventory) | Check product catalog |

#### CNC Drilling

| Specification | Value | Notes |
|---------------|-------|-------|
| Drill bit | 5mm brad point | CNC operation |
| Hole depth | 3/8" or 5/8" | Depends on specific hardware |
| Drilling method | CNC | Automated |

#### Pin Hole Layout (Cabinet Sides)

```
┌─────────────────────────────────────────────────────────┐
│                    CABINET SIDE VIEW                     │
│                                                          │
│  ←── 2" ──→                              ←── 2" ──→     │
│                                                          │
│      ●                                        ●          │
│      │                                        │          │
│      │ 2" spacing (1 up, 1 down from shelf)  │          │
│      │                                        │          │
│      ●  ◄─── Shelf pin hole row (5mm)        ●          │
│      │                                        │          │
│      │                                        │          │
│      ●                                        ●          │
│                                                          │
│   Front                                    Back          │
│   Edge                                     Edge          │
└─────────────────────────────────────────────────────────┘

Pin Hole Columns:
- Front column: 2" from front edge
- Back column: 2" from back edge
- CENTER column: At depth ÷ 2 (only for shelves ≥ 28" DEEP)

Pin Hole Rows:
- Vertical spacing: 2" apart
- Adjustment range: 1 hole up, 1 hole down from nominal position
- Total adjustment: ~4" range per shelf
```

#### Center Support Rule (28"+ DEPTH)

**Critical Rule:** At **28 inches or greater DEPTH**, add a **3rd center column** of pin holes.

Pin holes are drilled into **cabinet SIDES**, spanning the front-to-back (depth) dimension. The center support prevents sag across long depth spans.

```
Cabinet Side Panel (Left or Right):

Standard Depth (<28"):          Deep Shelf (≥28"):

Front ●──────────● Back        Front ●─────●─────● Back
      2"        2"                   2"  CENTER  2"
      |<─ DEPTH ─>|                  |<── DEPTH ──>|
                                          ↑
                                     depth ÷ 2

2 columns, 4 notches            3 columns, 6 notches
4 pins per shelf                6 pins per shelf
```

| Depth | Columns | Notches | Pins | Notes |
|-------|---------|---------|------|-------|
| < 28" | 2 (Front + Back) | 4 | 4 | Standard layout |
| ≥ 28" | 3 (Front + Center + Back) | 6 | 6 | Center support required |

**Why 28"?** This is the depth threshold where 3/4" plywood shelves can begin to sag under typical loads across the front-to-back span. The center support prevents deflection.

#### Shelf Notch Specifications

```
┌─────────────────────────────────────────────────────────┐
│                    SHELF - TOP VIEW                      │
│                                                          │
│  ┌───┐                                          ┌───┐   │
│  │   │ ◄── Notch for shelf pin                 │   │   │
│  └───┘     (cut into shelf corner)             └───┘   │
│                                                          │
│                       SHELF                              │
│                                                          │
│  ┌───┐                                          ┌───┐   │
│  │   │                                          │   │   │
│  └───┘                                          └───┘   │
│                                                          │
│   Front Edge (edge banded)                               │
└─────────────────────────────────────────────────────────┘

Notch Dimensions:
- Depth into shelf: 3/8" to 5/8" (depends on pin hardware)
- Standard: 4 corners (shelves under 28")
- Wide shelves: 6 notches (4 corners + 2 center for ≥28" width)
```

#### Minimum Opening Height

| Type | Minimum | Recommended | Notes |
|------|---------|-------------|-------|
| Absolute bare | 3/4" | - | Technically possible |
| Practical minimum | 5" - 5.5" | 5.5" | Shop standard |
| Usable opening | 5.5"+ | 6"+ | Comfortable access |

#### Edge Banding

| Edge | Treatment | Notes |
|------|-----------|-------|
| Front (nose) | Edge banded | Matches cabinet interior |
| Back | NOT edge banded | Against cabinet back |
| Left/Right | NOT edge banded | Against cabinet sides |

```
Edge Banding Material:
- Sold in: 500 foot rolls
- Matches: Cabinet box interior material
- Typical: Pre-finished maple (for standard interiors)
- Exception: Painted/open shelving may differ
```

#### Material Selection

| Cabinet Type | Shelf Material | Notes |
|--------------|----------------|-------|
| Standard interior | Pre-finished maple | Most common |
| Painted cabinet | Painted MDF/plywood | Matches cabinet |
| Open/visible | May vary | Could be different |

**Rule:** Shelf material = Cabinet box material

---

## Architecture

```
┌────────────────────────────────────────────────────────────────────┐
│                         Services Layer                              │
├──────────────────────────────┬─────────────────────────────────────┤
│ ShelfConfiguratorService     │ ShelfHardwareService                │
│ - Dimension calculations     │ - Shelf pin selection               │
│ - Pin hole layout            │ - Edge banding calculation          │
│ - Notch specifications       │ - EAV attribute lookup              │
│ - Opening validation         │ - Material matching                 │
└──────────────────────────────┴─────────────────────────────────────┘
                │                            │
                ▼                            ▼
┌────────────────────────────────────────────────────────────────────┐
│                          Models                                     │
│                                                                     │
│  Shelf (projects_shelves)                                          │
│  └── BelongsTo: Cabinet, CabinetSection, Product (slide)           │
│  └── HasMany: HardwareRequirements                                 │
│                                                                     │
│  ShelfPreset (projects_shelf_presets)                              │
│  └── Preset configurations for common shelf types                  │
└────────────────────────────────────────────────────────────────────┘
                │
                ▼
┌────────────────────────────────────────────────────────────────────┐
│                    Database Tables                                  │
│                                                                     │
│  projects_shelves          - Main shelf records                    │
│  projects_shelf_presets    - Reusable configurations               │
│  products_products         - Shelf pin products (EAV)              │
│  hardware_requirements     - Assigned hardware per cabinet         │
└────────────────────────────────────────────────────────────────────┘
```

---

## Constants

### Shop Practice Constants

```php
class ShelfConfiguratorService
{
    // ========================================
    // PIN HOLE LAYOUT CONSTANTS
    // ========================================
    
    /**
     * Distance from front/back edges to pin hole columns.
     * Shop standard: 2" from each edge.
     */
    public const PIN_HOLE_SETBACK_INCHES = 2.0;
    
    /**
     * Vertical spacing between pin holes.
     * Shop standard: 2" apart for adjustment range.
     */
    public const PIN_HOLE_VERTICAL_SPACING_INCHES = 2.0;
    
    /**
     * Pin hole diameter.
     * Industry standard: 5mm (≈0.1969")
     */
    public const PIN_HOLE_DIAMETER_MM = 5.0;
    public const PIN_HOLE_DIAMETER_INCHES = 0.1969;  // 5mm converted
    
    // ========================================
    // CENTER SUPPORT THRESHOLD
    // ========================================
    
    /**
     * DEPTH threshold for adding center support column.
     * At 28" or greater DEPTH, add a 3rd column of pin holes
     * (at depth ÷ 2) to prevent shelf sag.
     * 
     * Pin holes are on cabinet SIDES, spanning front-to-back (depth).
     */
    public const CENTER_SUPPORT_THRESHOLD_INCHES = 28.0;
    
    // ========================================
    // SHELF NOTCH CONSTANTS
    // ========================================
    
    /**
     * Notch depth options (depends on pin hardware).
     * Common: 3/8" or 5/8"
     */
    public const NOTCH_DEPTH_STANDARD = 0.375;   // 3/8"
    public const NOTCH_DEPTH_DEEP = 0.625;       // 5/8"
    
    /**
     * Default notch depth.
     */
    public const NOTCH_DEPTH_DEFAULT = 0.375;    // 3/8"
    
    // ========================================
    // MINIMUM OPENING REQUIREMENTS
    // ========================================
    
    /**
     * Absolute minimum opening height (technically possible).
     */
    public const MIN_OPENING_HEIGHT_ABSOLUTE = 0.75;  // 3/4"
    
    /**
     * Recommended minimum opening height (practical use).
     */
    public const MIN_OPENING_HEIGHT_RECOMMENDED = 5.5;  // 5-1/2"
    
    // ========================================
    // CLEARANCE CONSTANTS
    // ========================================
    
    /**
     * Clearance between shelf and cabinet sides.
     * Allows shelf to slide in/out easily.
     */
    public const SIDE_CLEARANCE_INCHES = 0.0625;  // 1/16" per side
    
    /**
     * Clearance from back of cabinet.
     * Accounts for back panel and pin protrusion.
     */
    public const BACK_CLEARANCE_INCHES = 0.25;    // 1/4"
    
    // ========================================
    // MATERIAL CONSTANTS
    // ========================================
    
    /**
     * Standard shelf thickness.
     */
    public const THICKNESS_STANDARD = 0.75;       // 3/4"
    public const THICKNESS_THIN = 0.5;            // 1/2"
    
    // ========================================
    // EDGE BANDING
    // ========================================
    
    /**
     * Edge banding is applied to front edge only.
     * Back and sides are NOT edge banded.
     */
    public const EDGE_BAND_FRONT_ONLY = true;
    
    /**
     * Edge banding thickness.
     */
    public const EDGE_BAND_THICKNESS = 0.02;      // Typical PVC/veneer
}
```

---

## Database Schema

### Main Table: `projects_shelves`

**Migration:** `plugins/webkul/projects/database/migrations/2025_11_21_000004_create_projects_shelves_table.php`

#### Existing Fields

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `cabinet_specification_id` | FK | Parent cabinet |
| `section_id` | FK | Parent section (optional) |
| `shelf_number` | int | Position (1, 2, 3...) |
| `shelf_name` | varchar(100) | Display name (S1, S2...) |
| `sort_order` | int | Display order |
| `width_inches` | decimal(8,3) | Shelf width |
| `depth_inches` | decimal(8,3) | Shelf depth |
| `thickness_inches` | decimal(5,3) | Material thickness |
| `shelf_type` | varchar(50) | adjustable, fixed, pullout |
| `material` | varchar(100) | plywood, solid_edge, melamine |
| `edge_treatment` | varchar(100) | edge_banded, solid_edge, exposed |
| `pin_hole_spacing` | decimal(5,3) | 1.25" or 32mm typical |
| `number_of_positions` | int | Adjustment positions available |

### Missing Fields (Migration Needed)

Based on shop research, these fields should be added:

```php
// ===== OPENING REFERENCE (source dimensions) =====
$table->decimal('opening_width_inches', 8, 4)->nullable()
    ->comment('Cabinet opening width - source dimension');
$table->decimal('opening_height_inches', 8, 4)->nullable()
    ->comment('Cabinet opening height - source dimension');
$table->decimal('opening_depth_inches', 8, 4)->nullable()
    ->comment('Cabinet opening depth - source dimension');

// ===== PIN HOLE SPECIFICATIONS =====
$table->decimal('pin_setback_front_inches', 8, 4)->nullable()
    ->comment('Distance from front edge to pin hole column (shop: 2")');
$table->decimal('pin_setback_back_inches', 8, 4)->nullable()
    ->comment('Distance from back edge to pin hole column (shop: 2")');
$table->decimal('pin_vertical_spacing_inches', 8, 4)->nullable()
    ->comment('Vertical spacing between pin holes (shop: 2")');
$table->decimal('pin_hole_diameter_mm', 5, 2)->nullable()
    ->comment('Pin hole diameter (standard: 5mm)');

// ===== NOTCH SPECIFICATIONS =====
$table->decimal('notch_depth_inches', 8, 4)->nullable()
    ->comment('Notch depth for shelf pins (3/8" or 5/8")');
$table->integer('notch_count')->nullable()
    ->comment('Number of notches (typically 4 corners or 2 back)');

// ===== CLEARANCES =====
$table->decimal('clearance_side_inches', 8, 4)->nullable()
    ->comment('Side clearance per side (typically 1/16")');
$table->decimal('clearance_back_inches', 8, 4)->nullable()
    ->comment('Back clearance (typically 1/4")');

// ===== EDGE BANDING =====
$table->boolean('edge_band_front')->default(true)
    ->comment('Front edge banded (shop: always yes)');
$table->boolean('edge_band_back')->default(false)
    ->comment('Back edge banded (shop: never)');
$table->boolean('edge_band_sides')->default(false)
    ->comment('Side edges banded (shop: never for adjustable)');
$table->decimal('edge_band_length_inches', 8, 4)->nullable()
    ->comment('Linear feet of edge banding needed');

// ===== CALCULATED DIMENSIONS =====
$table->decimal('cut_width_inches', 8, 4)->nullable()
    ->comment('Final cut width (opening - clearances)');
$table->decimal('cut_depth_inches', 8, 4)->nullable()
    ->comment('Final cut depth (opening - back clearance)');

// ===== HARDWARE =====
$table->foreignId('shelf_pin_product_id')->nullable()
    ->constrained('products_products')
    ->comment('Shelf pin product from inventory');
$table->integer('shelf_pin_quantity')->nullable()
    ->comment('Number of pins needed (typically 4)');
```

---

## Calculation Flow

### Input → Output

```
Cabinet Opening (W × H × D)
        │
        ▼
┌───────────────────────────────────────┐
│  ShelfConfiguratorService             │
│  calculateShelfDimensions()           │
│                                       │
│  1. Validate opening height (≥ 5.5")  │
│  2. Calculate shelf width             │
│     width = opening - (2 × side gap)  │
│  3. Calculate shelf depth             │
│     depth = opening - back clearance  │
│  4. Determine notch specs             │
│  5. Calculate edge banding length     │
│  6. Generate pin hole layout          │
└───────────────────────────────────────┘
        │
        ▼
┌───────────────────────────────────────┐
│  Output Array                         │
│                                       │
│  ├── opening: {width, height, depth}  │
│  ├── shelf:                           │
│  │   ├── cut_width                    │
│  │   ├── cut_depth                    │
│  │   └── thickness                    │
│  ├── notches:                         │
│  │   ├── depth (3/8" or 5/8")         │
│  │   └── count (4)                    │
│  ├── pin_holes:                       │
│  │   ├── setback_front (2")           │
│  │   ├── setback_back (2")            │
│  │   ├── diameter (5mm)               │
│  │   └── vertical_spacing (2")        │
│  ├── edge_banding:                    │
│  │   ├── front_only (true)            │
│  │   └── length_inches                │
│  ├── hardware:                        │
│  │   ├── pin_product_id               │
│  │   └── pin_quantity (4)             │
│  └── validation: {valid, issues}      │
└───────────────────────────────────────┘
```

### Example Calculation

**Input:** 30" W × 12" H × 23" D cabinet opening

```
Opening Width:  30.0"
Opening Height: 12.0" (validates: ≥ 5.5" ✓)
Opening Depth:  23.0"

Shelf Cut Width:
  = 30.0" - (2 × 0.0625")  // Side clearances
  = 30.0" - 0.125"
  = 29.875" (29-7/8")

Shelf Cut Depth:
  = 23.0" - 0.25"  // Back clearance
  = 22.75" (22-3/4")

Notches:
  - Depth: 3/8" (standard)
  - Count: 4 (all corners)

Pin Holes (in cabinet sides):
  - Front column: 2" from front
  - Back column: 2" from back (21" from front)
  - Diameter: 5mm
  - Vertical spacing: 2"

Edge Banding:
  - Front edge only: 29.875"
  - Total length: ~30" (round up)

Hardware:
  - Shelf pins: 4 pcs
  - Product: 5mm spoon-style pin
```

---

## CNC Operations

### Cabinet Side Panel: Pin Hole Drilling

```
Operation: Drill shelf pin holes
Tool: 5mm brad point drill bit
Depth: 3/8" to 5/8" (per hardware spec)

Layout (per side panel):
┌─────────────────────────────────────────┐
│                                         │
│   X = Pin hole location                 │
│                                         │
│   2"                              2"    │
│   ←→                              ←→    │
│                                         │
│   X ──── 2" ──── X                      │
│   X ──── 2" ──── X                      │
│   X ──── 2" ──── X  ◄── Repeated for    │
│   X ──── 2" ──── X      each shelf      │
│   X ──── 2" ──── X      position        │
│                                         │
└─────────────────────────────────────────┘
```

### Shelf Panel: Notch Cutting

```
Operation: Cut corner notches for pins
Tool: Router or CNC mill
Depth: 3/8" (standard) or 5/8" (deep)

Layout:
┌───┐─────────────────────────────────┌───┐
│ N │                                 │ N │
└───┘                                 └───┘
│                                         │
│              SHELF PANEL                │
│                                         │
└───┐                                 ┌───┘
│ N │                                 │ N │
└───┘─────────────────────────────────└───┘

N = Notch (size depends on pin hardware)
```

---

## Shelf Types

### 1. Adjustable Shelf (Primary Focus)

| Feature | Specification |
|---------|---------------|
| Support | 5mm shelf pins in drilled holes |
| Notches | 4 corners (standard) |
| Edge banding | Front only |
| Adjustment | 2" increments (1 up, 1 down) |

### 2. Fixed Shelf

| Feature | Specification |
|---------|---------------|
| Support | Dado groove in cabinet sides |
| Notches | None |
| Edge banding | Front only |
| Adjustment | None (permanent position) |

### 3. Roll-Out Shelf (Pullout)

| Feature | Specification |
|---------|---------------|
| Support | Drawer slides (same as drawers) |
| Notches | None |
| Edge banding | All edges (visible) |
| Uses | DrawerConfiguratorService for slide specs |

---

## Eloquent Model

**Location:** `plugins/webkul/projects/src/Models/Shelf.php`

```php
class Shelf extends Model implements CabinetComponentInterface
{
    use HasFactory, SoftDeletes, HasFullCode, HasComplexityScore, 
        HasFormattedDimensions, HasEntityLock;

    protected $table = 'projects_shelves';

    // Shelf type constants
    public const SHELF_TYPES = [
        'fixed' => 'Fixed Shelf',
        'adjustable' => 'Adjustable Shelf',
        'roll_out' => 'Roll-Out Shelf',
        'pull_down' => 'Pull-Down Shelf',
        'corner' => 'Corner Shelf',
        'floating' => 'Floating Shelf',
    ];

    // Material options
    public const MATERIALS = [
        'plywood' => 'Plywood',
        'mdf' => 'MDF',
        'melamine' => 'Melamine',
        'solid_wood' => 'Solid Wood',
        'glass' => 'Glass',
        'wire' => 'Wire',
    ];

    // Relationships
    public function cabinet(): BelongsTo
    public function section(): BelongsTo
    public function product(): BelongsTo
    public function slideProduct(): BelongsTo
    public function hardwareRequirements(): HasMany

    // Component Interface
    public function getComponentCode(): string  // Returns "SHELF1", "SHELF2"...
    public static function getComponentType(): string  // Returns "shelf"
}
```

---

## Shop Rules Summary

### Pin Hole Layout Rule
- **Rule:** Holes are 2" from front edge, 2" from back edge
- **Vertical spacing:** 2" between holes
- **Adjustment range:** 1 hole up, 1 hole down (4" total range)
- **Drill bit:** 5mm brad point

### Shelf Notch Rule
- **Rule:** Cut notches at corners for pin support
- **Depth:** 3/8" (standard) or 5/8" (depends on pin hardware)
- **Count:** 4 notches (all corners)

### Edge Banding Rule
- **Rule:** Front edge ONLY gets edge banding
- **Back:** NOT edge banded (against cabinet back)
- **Sides:** NOT edge banded (clearance needed)

### Minimum Opening Rule
- **Absolute minimum:** 3/4" (technically possible)
- **Shop minimum:** 5.5" (practical/recommended)

### Material Matching Rule
- **Rule:** Shelf material = Cabinet box material
- **Standard:** Pre-finished maple
- **Exception:** Painted cabinets get painted shelves

---

## Hardware Requirements

### Per Adjustable Shelf

| Item | Quantity | Notes |
|------|----------|-------|
| 5mm shelf pins | 4 | One per corner |
| Edge banding | ~width | Front edge only (linear inches) |

### Pin Specifications

| Attribute | Value |
|-----------|-------|
| Diameter | 5mm |
| Type | Spoon-style (typical) |
| Material | Metal (zinc plated) or nylon |
| Source | Generic (interchangeable) |

---

## Implementation Status

### Existing (Base)

- [x] `projects_shelves` table (base schema)
- [x] `Shelf` model with relationships
- [x] `ShelfPreset` model for templates
- [x] Basic dimension fields

### Implemented (January 2026)

- [x] `ShelfConfiguratorService` - dimension calculations with 28" center support threshold
- [x] `ShelfHardwareService` - pin/edge banding selection from EAV
- [x] Extended fields migration (`database/migrations/2026_01_15_110000_add_shelf_spec_fields.php`)
- [x] `Shelf` model updated with new fields and `shelfPinProduct()` relationship
- [x] HTML spec templates:
  - `shelf-spec.html` (24" standard - 2 columns)
  - `shelf-spec-32x12x22.html` (32" wide - 3 columns with center support)
- [x] SVG diagrams showing 2-column and 3-column layouts

---

## Files Created

```
app/Services/ShelfConfiguratorService.php     ✓ Created
app/Services/ShelfHardwareService.php         ✓ Created

database/migrations/
└── 2026_01_15_110000_add_shelf_spec_fields.php  ✓ Created
    (Combined: pin holes, notches, edge banding, clearances)

plugins/webkul/projects/src/Models/Shelf.php  ✓ Updated
    (Added new fillable fields, casts, shelfPinProduct relationship)

shelf-spec.html                               ✓ Created (24"×12"×22" - standard depth)
shelf-spec-24x12x30-deep.html                 ✓ Created (24"×12"×30" - deep w/ center)
```

---

## Next Steps

1. **Create migrations** for missing database fields
2. **Build ShelfConfiguratorService** with constants and calculations
3. **Build ShelfHardwareService** for pin selection from EAV
4. **Generate HTML spec** with pin hole diagram and cut list
5. **Test with carpenters** to validate calculations
