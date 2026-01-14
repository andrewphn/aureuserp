# Drawer Specification System Documentation

## Overview

The Drawer Specification System calculates drawer box dimensions, assigns hardware, and generates CNC cut lists based on cabinet opening dimensions. It uses the **Blum TANDEM 563H** slide system specifications and implements **shop-specific safety rules** for practical manufacturing.

---

## Architecture

```
┌────────────────────────────────────────────────────────────────────┐
│                         Services Layer                              │
├──────────────────────────────┬─────────────────────────────────────┤
│ DrawerConfiguratorService    │ DrawerHardwareService               │
│ - Dimension calculations     │ - Slide selection by depth          │
│ - Cut list generation        │ - Hardware cost aggregation         │
│ - Shop value rounding        │ - EAV attribute lookup              │
│ - Fractional formatting      │ - Auto-assignment for projects      │
└──────────────────────────────┴─────────────────────────────────────┘
                │                            │
                ▼                            ▼
┌────────────────────────────────────────────────────────────────────┐
│                          Models                                     │
│                                                                     │
│  Drawer (projects_drawers)                                         │
│  └── BelongsTo: Cabinet, CabinetSection, Product (slide)           │
│  └── HasMany: HardwareRequirements                                 │
└────────────────────────────────────────────────────────────────────┘
                │
                ▼
┌────────────────────────────────────────────────────────────────────┐
│                    Database Tables                                  │
│                                                                     │
│  projects_drawers          - Main drawer records                   │
│  products_products         - Slide products (EAV)                  │
│  products_attributes       - Slide Length, Weight Capacity, etc.   │
│  products_product_attribute_values - Attribute values              │
│  hardware_requirements     - Assigned hardware per cabinet         │
└────────────────────────────────────────────────────────────────────┘
```

---

## Key Services

### 1. DrawerConfiguratorService

**Location:** `app/Services/DrawerConfiguratorService.php`

Calculates drawer box dimensions from cabinet opening dimensions using Blum TANDEM 563H specifications.

#### Constants (Blum Specifications)

| Constant | Value | Description |
|----------|-------|-------------|
| `SIDE_DEDUCTION_5_8` | 0.40625" (13/32") | Width deduction for 5/8" drawer sides |
| `SIDE_DEDUCTION_1_2` | 0.625" (5/8") | Width deduction for 1/2" drawer sides |
| `INSIDE_WIDTH_DEDUCTION_5_8` | 1.3125" (1-5/16") | Inside width deduction for 5/8" sides |
| `INSIDE_WIDTH_DEDUCTION_1_2` | 1.65625" (1-21/32") | Inside width deduction for 1/2" sides |
| `TOP_CLEARANCE` | 0.25" (1/4") | Top clearance (6mm) |
| `BOTTOM_CLEARANCE` | 0.5625" (9/16") | Bottom clearance (14mm) |
| `HEIGHT_DEDUCTION` | 0.8125" (13/16") | Total height deduction (20mm) |

#### Construction Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `MATERIAL_THICKNESS` | 0.5" | Side/front/back material thickness |
| `BOTTOM_THICKNESS` | 0.25" | Bottom panel thickness |
| `DADO_DEPTH` | 0.25" | Dado groove depth |
| `BOTTOM_DADO_HEIGHT` | 0.5" | Dado position from bottom edge |

#### Shop Practice Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `SHOP_DEPTH_ADDITION` | 0.25" (1/4") | Added to slide length for safety |
| `SHOP_MIN_DEPTH_ADDITION` | 0.75" (3/4") | Added to slide length for min cabinet depth |

#### Blum Official Minimum Cabinet Depths

| Slide Length | Blum Spec | Shop Practice |
|--------------|-----------|---------------|
| 21" | 21-15/16" (557mm) | 21-3/4" |
| 18" | 18-29/32" (480mm) | 18-3/4" |
| 15" | 15-29/32" (404mm) | 15-3/4" |
| 12" | 12-29/32" (328mm) | 12-3/4" |
| 9" | 10-15/32" (266mm) | 9-3/4" |

#### Key Methods

```php
// Main calculation - returns all dimensions
public function calculateDrawerDimensions(
    float $openingWidth,
    float $openingHeight,
    float $openingDepth,
    float $drawerSideThickness = 0.5
): array

// Generate complete cut list with dadoes
public function getCutList(
    float $openingWidth,
    float $openingHeight,
    float $openingDepth,
    float $drawerSideThickness = 0.5
): array

// Formatted cut list with fractional dimensions
public function getFormattedCutList(
    float $openingWidth,
    float $openingHeight,
    float $openingDepth,
    float $drawerSideThickness = 0.5
): array

// Utility: Round DOWN to nearest 1/2"
public static function roundDownToHalfInch(float $inches): float

// Utility: Convert decimal to fraction string
public static function toFraction(float $decimal, int $denominator = 32): string

// Get minimum cabinet depth data
public static function getMinCabinetDepth(int $slideLength): array
public static function getAllMinCabinetDepths(): array
```

### 2. DrawerHardwareService

**Location:** `app/Services/DrawerHardwareService.php`

Auto-assigns drawer hardware (slides, locking devices) based on drawer dimensions using the EAV product attribute system.

#### Key Methods

```php
// Get appropriate slide for drawer depth
public function getSlideForDepth(float $drawerDepthInches): array

// Get slide product model
public function getSlideProductForDepth(float $drawerDepthInches): ?Product

// Get slide specifications from attributes
public function getSlideSpecs(Product $slide): array

// Get complete hardware list with costs
public function getHardwareForDrawers(
    float $drawerDepthInches,
    int $drawerCount = 1,
    bool $includeLockingDevice = true
): array

// Auto-assign for entire project
public function autoAssignForProject(int $projectId): array
```

---

## Database Schema

### Main Table: `projects_drawers`

**Migration:** `plugins/webkul/projects/database/migrations/2025_11_21_000003_create_projects_drawers_table.php`

#### Core Fields

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `cabinet_specification_id` | FK | Parent cabinet |
| `section_id` | FK | Parent section (optional) |
| `drawer_number` | int | Position (1, 2, 3...) |
| `drawer_name` | varchar(100) | Display name (DR1, DR2...) |
| `drawer_position` | varchar(50) | top, middle, bottom |
| `sort_order` | int | Display order |

#### Drawer Front Dimensions

| Column | Type | Description |
|--------|------|-------------|
| `front_width_inches` | decimal(8,3) | Front face width |
| `front_height_inches` | decimal(8,3) | Front face height |
| `front_thickness_inches` | decimal(5,3) | Front material thickness |
| `profile_type` | varchar(100) | shaker, flat_panel, etc. |
| `fabrication_method` | varchar(50) | cnc, five_piece_manual, slab |

#### Drawer Box Dimensions

| Column | Type | Description |
|--------|------|-------------|
| `box_width_inches` | decimal(8,3) | Box width |
| `box_depth_inches` | decimal(8,3) | Box depth |
| `box_height_inches` | decimal(8,3) | Box height |
| `box_material` | varchar(100) | maple, birch, baltic_birch |
| `box_thickness` | decimal(5,3) | Side thickness (0.5" or 0.75") |
| `joinery_method` | varchar(50) | dovetail, pocket_screw, dado |

#### Slide Hardware

| Column | Type | Description |
|--------|------|-------------|
| `slide_type` | varchar(100) | blum_tandem, full_extension |
| `slide_model` | varchar(100) | Specific model number |
| `slide_length_inches` | decimal(5,2) | 15", 18", 21" |
| `slide_quantity` | int | Pairs of slides |
| `soft_close` | boolean | Soft close feature |

### Extended Fields (Cut List Migration)

**Migration:** `database/migrations/2026_01_15_100006_add_cut_list_fields_to_drawers.php`

#### Opening Reference

| Column | Type | Description |
|--------|------|-------------|
| `opening_width_inches` | decimal(8,4) | Cabinet opening width |
| `opening_height_inches` | decimal(8,4) | Cabinet opening height |
| `opening_depth_inches` | decimal(8,4) | Cabinet opening depth |

#### Calculated Box Dimensions

| Column | Type | Description |
|--------|------|-------------|
| `box_outside_width_inches` | decimal(8,4) | Outside width |
| `box_inside_width_inches` | decimal(8,4) | Inside width (usable) |

#### Material Specifications

| Column | Type | Description |
|--------|------|-------------|
| `side_thickness_inches` | decimal(8,4) | 0.5 or 0.625 |
| `bottom_thickness_inches` | decimal(8,4) | Typically 0.25 |

#### Dado Specifications

| Column | Type | Description |
|--------|------|-------------|
| `dado_depth_inches` | decimal(8,4) | Groove depth |
| `dado_width_inches` | decimal(8,4) | Groove width |
| `dado_height_inches` | decimal(8,4) | Position from bottom |

#### CNC Cut List

| Column | Type | Description |
|--------|------|-------------|
| `side_cut_height_inches` | decimal(8,4) | Side piece height (theoretical) |
| `side_cut_length_inches` | decimal(8,4) | Side piece length (= depth) |
| `front_cut_height_inches` | decimal(8,4) | Front piece height |
| `front_cut_width_inches` | decimal(8,4) | Front piece width |
| `back_cut_height_inches` | decimal(8,4) | Back piece height |
| `back_cut_width_inches` | decimal(8,4) | Back piece width |
| `bottom_cut_width_inches` | decimal(8,4) | Bottom panel width |
| `bottom_cut_depth_inches` | decimal(8,4) | Bottom panel depth |

#### Clearances Applied

| Column | Type | Description |
|--------|------|-------------|
| `clearance_side_inches` | decimal(8,4) | Side deduction used |
| `clearance_top_inches` | decimal(8,4) | Top clearance used |
| `clearance_bottom_inches` | decimal(8,4) | Bottom clearance used |

### Shop Height Fields

**Migration:** `database/migrations/2026_01_15_100007_add_shop_height_fields_to_drawers.php`

Shop heights = theoretical rounded **DOWN** to nearest 1/2" for safety.

| Column | Type | Description |
|--------|------|-------------|
| `box_height_shop_inches` | decimal(8,4) | Box shop height |
| `side_cut_height_shop_inches` | decimal(8,4) | Side shop height |
| `front_cut_height_shop_inches` | decimal(8,4) | Front shop height |
| `back_cut_height_shop_inches` | decimal(8,4) | Back shop height |

### Shop Depth Fields

**Migration:** `database/migrations/2026_01_15_100008_add_shop_depth_fields_to_drawers.php`

Shop depth = nominal slide length **+ 1/4"** for safety.

| Column | Type | Description |
|--------|------|-------------|
| `box_depth_shop_inches` | decimal(8,4) | Box shop depth |
| `side_cut_length_shop_inches` | decimal(8,4) | Side shop length |

### Minimum Cabinet Depth Fields

**Migration:** `database/migrations/2026_01_15_100009_add_min_cabinet_depth_fields_to_drawers.php`

| Column | Type | Description |
|--------|------|-------------|
| `min_cabinet_depth_blum_inches` | decimal(8,4) | Blum official spec |
| `min_cabinet_depth_shop_inches` | decimal(8,4) | Shop practice (slide + 3/4") |

---

## Eloquent Model

**Location:** `plugins/webkul/projects/src/Models/Drawer.php`

```php
class Drawer extends Model implements CabinetComponentInterface
{
    use HasFactory, SoftDeletes, HasFullCode, HasComplexityScore, 
        HasFormattedDimensions, HasEntityLock;

    protected $table = 'projects_drawers';

    // Relationships
    public function cabinet(): BelongsTo
    public function section(): BelongsTo
    public function product(): BelongsTo
    public function slideProduct(): BelongsTo
    public function decorativeHardwareProduct(): BelongsTo
    public function hardwareRequirements(): HasMany

    // Component Interface
    public function getComponentCode(): string  // Returns "DRW1", "DRW2"...
    public static function getComponentType(): string  // Returns "drawer"
}
```

---

## Calculation Flow

### Input → Output

```
Cabinet Opening (W × H × D)
        │
        ▼
┌───────────────────────────────────────┐
│  DrawerConfiguratorService            │
│  calculateDrawerDimensions()          │
│                                       │
│  1. Determine drawer side thickness   │
│  2. Select appropriate constants      │
│  3. Calculate box dimensions          │
│  4. Apply shop rounding rules         │
│  5. Get hardware from service         │
│  6. Validate constraints              │
└───────────────────────────────────────┘
        │
        ▼
┌───────────────────────────────────────┐
│  Output Array                         │
│                                       │
│  ├── opening: {width, height, depth}  │
│  ├── drawer_box:                      │
│  │   ├── outside_width (theoretical)  │
│  │   ├── inside_width                 │
│  │   ├── height (theoretical)         │
│  │   ├── height_shop (rounded down)   │
│  │   ├── depth (= slide length)       │
│  │   └── depth_shop (+ 1/4")          │
│  ├── clearances: {side, top, bottom}  │
│  ├── hardware: {slide info, mins}     │
│  └── validation: {valid, issues}      │
└───────────────────────────────────────┘
```

### Example Calculation

**Input:** 20" × 6" × 14" opening, 1/2" drawer sides

```
Opening Width:  20.0"
Opening Height:  6.0"
Opening Depth:  14.0" → selects 12" slide

Box Outside Width = 20.0 - 0.625 = 19.375" (19-3/8")
Box Inside Width  = 20.0 - 1.65625 = 18.34375" (18-11/32")
Box Height (theoretical) = 6.0 - 0.8125 = 5.1875" (5-3/16")
Box Height (shop) = floor(5.1875 × 2) / 2 = 5.0" (5")
Box Depth (theoretical) = 12.0" (slide length)
Box Depth (shop) = 12.0 + 0.25 = 12.25" (12-1/4")

Min Cabinet Depth (Blum): 12-29/32" (12.90625")
Min Cabinet Depth (Shop): 12-3/4" (12.75")
```

---

## Shop Rules Summary

### Height Rounding Rule
- **Rule:** Round theoretical height **DOWN** to nearest 1/2"
- **Purpose:** Ensures drawers always fit with adequate clearance
- **Applied to:** Box height, side height, front height, back height
- **Example:** 5-3/16" (5.1875") → 5" (5.0")

### Depth Addition Rule
- **Rule:** Add **1/4"** to nominal slide length
- **Purpose:** Provides safety clearance for proper slide operation
- **Applied to:** Box depth, side length
- **Example:** 18" slide → 18-1/4" (18.25") shop depth

### Minimum Cabinet Depth Rule
- **Rule:** Slide length **+ 3/4"**
- **Purpose:** Simpler than Blum's spec, works reliably in practice
- **Applied to:** Validation only (not cut list)
- **Example:** 12" slide → 12-3/4" min cabinet depth

---

## EAV Product Attribute Integration

The `DrawerHardwareService` queries slide products using the EAV system:

```php
// Query slide products by "Slide Length" attribute
$slides = DB::table('products_products as p')
    ->join('products_product_attribute_values as pav', 'p.id', '=', 'pav.product_id')
    ->where('pav.attribute_id', $slideLengthAttrId)
    ->whereNotNull('pav.numeric_value')
    ->select('p.id', 'p.name', 'p.price', 'pav.numeric_value as slide_length')
    ->get();
```

### Required Product Attributes

| Attribute Name | Type | Description |
|----------------|------|-------------|
| Slide Length | numeric | Length in inches (15, 18, 21) |
| Min Cabinet Depth | numeric | Minimum cabinet depth |
| Weight Capacity | numeric | Weight capacity in lbs |
| Slide Side Clearance | numeric | Side clearance required |
| Slide Top Clearance | numeric | Top clearance required |
| Slide Bottom Clearance | numeric | Bottom clearance required |

---

## Usage Example

```php
use App\Services\DrawerConfiguratorService;
use App\Services\DrawerHardwareService;

$hardwareService = new DrawerHardwareService();
$configurator = new DrawerConfiguratorService($hardwareService);

// Calculate dimensions
$result = $configurator->calculateDrawerDimensions(
    openingWidth: 20.0,
    openingHeight: 6.0,
    openingDepth: 14.0,
    drawerSideThickness: 0.5  // 1/2" sides
);

// Get formatted cut list
$cutList = $configurator->getFormattedCutList(20.0, 6.0, 14.0);

// Access values
$boxWidth = $result['drawer_box']['outside_width'];
$shopHeight = $result['drawer_box']['height_shop'];
$shopDepth = $result['drawer_box']['depth_shop'];
$slideLength = $result['hardware']['slide_length'];
$minCabinetDepthShop = $result['hardware']['min_cabinet_depth_shop'];
```

---

## Output Files

### HTML Specifications

- `drawer-spec.html` - Sample 12" × 6" × 19" opening
- `drawer-spec-20x6x13.html` - Sample 20" × 6" × 13" opening

These include:
- Summary tables with theoretical and shop values
- 2D SVG flat drawings of each CNC cut piece
- Dado groove locations marked
- Minimum cabinet depth comparison table
- Color-coded dimensions (red = theoretical, green = shop)

---

## Entity Hierarchy

```
Project
└── Room
    └── Room Location
        └── Cabinet Run
            └── Cabinet (projects_cabinet_specifications)
                └── Cabinet Section
                    └── Drawer (projects_drawers)
                        ├── slide_product_id → Product
                        └── decorative_hardware_product_id → Product
```

---

## Related Tables

| Table | Purpose |
|-------|---------|
| `projects_cabinet_specifications` | Parent cabinet |
| `projects_cabinet_sections` | Grouping within cabinet |
| `products_products` | Slide products |
| `products_attributes` | Attribute definitions |
| `products_product_attribute_values` | Attribute values |
| `hardware_requirements` | Assigned hardware |

---

## Migration Status

| Migration | Status | Description |
|-----------|--------|-------------|
| `2025_11_21_000003_create_projects_drawers_table` | ✅ Applied | Base table |
| `2026_01_15_100006_add_cut_list_fields_to_drawers` | 📝 Pending | Extended cut list |
| `2026_01_15_100007_add_shop_height_fields_to_drawers` | 📝 Pending | Shop heights |
| `2026_01_15_100008_add_shop_depth_fields_to_drawers` | 📝 Pending | Shop depths |
| `2026_01_15_100009_add_min_cabinet_depth_fields_to_drawers` | 📝 Pending | Min depths |

**Note:** Pending migrations require the `projects_drawers` table to exist. Run after confirming table creation.

---

## Next Steps for Door Spec System

The Door Spec system will follow a similar pattern:
1. Create `DoorConfiguratorService` for dimension calculations
2. Create `DoorHardwareService` for hinge selection
3. Define hinge overlay types (full overlay, half overlay, inset)
4. Add opening/clearance calculations specific to doors
5. Generate HTML specifications similar to drawer specs
