# TCS Material BOM System

## Overview

The TCS Material BOM (Bill of Materials) system connects TCS pricing material categories to actual inventory products, enabling automatic material requirement calculations for cabinet specifications.

## System Components

### 1. Material Inventory Mappings

**Table**: `tcs_material_inventory_mappings`
**Model**: `Webkul\Project\Models\TcsMaterialInventoryMapping`

Maps TCS pricing materials to actual inventory products:

- **Paint Grade** (Hard Maple/Poplar) - +$138/LF
  - Hard Maple (2.5 BF/LF, Priority 10)
  - Poplar (2.5 BF/LF, Priority 20)
  - Birch Plywood (6.0 SF/LF, Priority 15)

- **Stain Grade** (Oak/Maple) - +$156/LF
  - Red Oak (2.5 BF/LF, Priority 10)
  - White Oak (2.5 BF/LF, Priority 15)
  - Hard Maple Stain (2.5 BF/LF, Priority 20)

- **Premium** (Rifted White Oak/Black Walnut) - +$185/LF
  - Rifted White Oak (2.8 BF/LF, Priority 10)
  - Black Walnut (2.8 BF/LF, Priority 15)
  - Cherry (2.7 BF/LF, Priority 20)

- **Custom/Exotic** - Price TBD
  - Exotic/Custom Wood (3.0 BF/LF, Priority 10)

### 2. Material BOM Service

**Service**: `Webkul\Project\Services\MaterialBomService`

Provides comprehensive BOM generation functionality:

```php
use Webkul\Project\Services\MaterialBomService;

$bomService = new MaterialBomService();

// Generate BOM for single cabinet
$bom = $bomService->generateBomForCabinet($cabinet);

// Generate BOM for multiple cabinets (aggregated)
$bom = $bomService->generateBomForCabinets($cabinets);

// Format for display
$formatted = $bomService->formatBom($bom, includeProducts: true);

// Estimate material cost
$cost = $bomService->estimateMaterialCost($bom);

// Check inventory availability
$availability = $bomService->checkMaterialAvailability($bom);

// Get material recommendations
$recommendations = $bomService->getMaterialRecommendations($cabinet, 'box');
```

### 3. Model Integration

#### CabinetSpecification Model

```php
// Generate BOM for this cabinet
$bom = $cabinet->generateBom();

// Get formatted BOM with product details
$formatted = $cabinet->getFormattedBom();

// Estimate material cost
$cost = $cabinet->estimateMaterialCost();

// Check material availability
$availability = $cabinet->checkMaterialAvailability();

// Get material recommendations
$recommendations = $cabinet->getMaterialRecommendations('door');

// Check if material category is assigned
$hasCategory = $cabinet->hasMaterialCategory();

// Get inherited material category from parent
$effectiveCategory = $cabinet->effective_material_category;
```

#### CabinetRun Model

```php
// Generate BOM for entire cabinet run
$bom = $cabinetRun->generateBom();

// Get formatted BOM
$formatted = $cabinetRun->getFormattedBom();

// Estimate total material cost for run
$cost = $cabinetRun->estimateMaterialCost();

// Check material availability for entire run
$availability = $cabinetRun->checkMaterialAvailability();
```

## Usage Examples

### Example 1: Basic BOM Generation

```php
use Webkul\Project\Models\CabinetSpecification;

$cabinet = CabinetSpecification::find(1);

// Cabinet: 36" × 24" × 30", 3.0 LF, Stain Grade
$bom = $cabinet->generateBom();

// Result:
// [
//   ['wood_species' => 'Red Oak', 'quantity' => 8.25, 'unit' => 'board_feet'],
//   ['wood_species' => 'White Oak', 'quantity' => 8.25, 'unit' => 'board_feet'],
//   ['wood_species' => 'Hard Maple (Stain)', 'quantity' => 8.25, 'unit' => 'board_feet']
// ]
```

### Example 2: Material Cost Estimation

```php
$cabinetRun = CabinetRun::with('cabinets')->find(1);

// Get BOM for entire run
$bom = $cabinetRun->generateBom();

// Estimate material cost (requires product.cost_price set)
$materialCost = $cabinetRun->estimateMaterialCost();

echo "Estimated material cost for run: $" . number_format($materialCost, 2);
```

### Example 3: Inventory Availability Check

```php
use Webkul\Project\Services\MaterialBomService;

$bomService = new MaterialBomService();
$bom = $bomService->generateBomForCabinets($cabinets);

$availability = $bomService->checkMaterialAvailability($bom);

// Result:
// [
//   [
//     'product_id' => 123,
//     'product_name' => 'Red Oak Lumber',
//     'wood_species' => 'Red Oak',
//     'required' => 55.0,
//     'available' => 100.0,
//     'sufficient' => true,
//     'shortage' => 0,
//     'unit' => 'board_feet'
//   ]
// ]
```

### Example 4: Material Recommendations

```php
$cabinet = CabinetSpecification::find(1);

// Get recommended materials for cabinet boxes
$boxMaterials = $cabinet->getMaterialRecommendations('box');

// Get recommended materials for face frames
$frameMaterials = $cabinet->getMaterialRecommendations('face_frame');

// Get recommended materials for doors
$doorMaterials = $cabinet->getMaterialRecommendations('door');
```

## Material Usage Multipliers

### Solid Wood Materials (Board Feet per LF)

- **Paint Grade**: 2.5 BF/LF
- **Stain Grade**: 2.5 BF/LF
- **Premium**: 2.7-2.8 BF/LF
- **Custom/Exotic**: 3.0 BF/LF

### Sheet Goods Materials (Square Feet per LF)

- **Paint Grade Plywood**: 6.0 SF/LF

### Waste Factor

All calculations automatically include a 10% waste factor.

**Example**: 3.0 LF of Stain Grade Red Oak:
- Base requirement: 3.0 LF × 2.5 BF/LF = 7.5 BF
- With waste: 7.5 BF × 1.10 = 8.25 BF

## Material Priority System

Materials are prioritized by the `priority` field (lower = preferred):

1. **Priority 10**: Primary/preferred materials
2. **Priority 15**: Secondary alternatives
3. **Priority 20**: Tertiary alternatives

When generating BOM, all materials for a category are included, but UI/recommendations use priority ordering.

## Usage Flags

Each material mapping has usage flags indicating where it can be used:

- `is_box_material`: Cabinet box construction
- `is_face_frame_material`: Face frame construction
- `is_door_material`: Door/drawer construction

## Integration with TCS Pricing

The material categories map directly to TCS pricing materials:

| Pricing Material | Database Slug | Price Modifier |
|-----------------|---------------|----------------|
| Paint Grade (Hard Maple/Poplar) | `paint_grade` | +$138/LF |
| Stain Grade (Oak/Maple) | `stain_grade` | +$156/LF |
| Premium (Rifted White Oak/Black Walnut) | `premium` | +$185/LF |
| Custom/Exotic Wood | `custom_exotic` | Price TBD |

## Hierarchical Material Category Inheritance

Material categories cascade from parent entities:

```
Room → RoomLocation → CabinetRun → CabinetSpecification
```

Use `$cabinet->effective_material_category` to get the inherited value.

## Database Schema

```sql
CREATE TABLE `tcs_material_inventory_mappings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tcs_material_slug` varchar(50) NOT NULL,        -- 'paint_grade', 'stain_grade', etc.
  `wood_species` varchar(100) NOT NULL,             -- 'Red Oak', 'Hard Maple', etc.
  `inventory_product_id` bigint unsigned NULL,      -- FK to products_products
  `material_category_id` bigint unsigned NULL,      -- FK to woodworking_material_categories
  `board_feet_per_lf` decimal(8,4) DEFAULT 0,      -- Solid wood multiplier
  `sheet_sqft_per_lf` decimal(8,4) DEFAULT 0,      -- Sheet goods multiplier
  `is_box_material` tinyint(1) DEFAULT 0,
  `is_face_frame_material` tinyint(1) DEFAULT 0,
  `is_door_material` tinyint(1) DEFAULT 0,
  `priority` int DEFAULT 100,
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tcs_material_species_unique` (`tcs_material_slug`, `wood_species`),
  KEY `tcs_material_inventory_mappings_tcs_material_slug_index` (`tcs_material_slug`),
  FOREIGN KEY (`inventory_product_id`) REFERENCES `products_products` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`material_category_id`) REFERENCES `woodworking_material_categories` (`id`) ON DELETE SET NULL
);
```

## Files

### Migrations
- `plugins/webkul/projects/database/migrations/2025_10_28_140000_create_tcs_material_inventory_mappings_table.php`

### Models
- `plugins/webkul/projects/src/Models/TcsMaterialInventoryMapping.php`
- `plugins/webkul/projects/src/Models/CabinetSpecification.php` (enhanced with BOM methods)
- `plugins/webkul/projects/src/Models/CabinetRun.php` (enhanced with BOM methods)

### Services
- `plugins/webkul/projects/src/Services/MaterialBomService.php`

### Tests
- `test-material-bom.php`

## Testing

Run the test script to verify the system:

```bash
DB_CONNECTION=mysql php test-material-bom.php
```

Expected output:
- List of all material mappings
- BOM generation for sample cabinet
- Material recommendations by category
- Material requirements calculator

## Future Enhancements

1. **Sales Order Integration**: Automatically create order lines from BOM
2. **Inventory Reservation**: Reserve materials when cabinets are added to projects
3. **Material Cost Tracking**: Track actual vs estimated material costs
4. **Waste Analysis**: Track and analyze actual waste percentages
5. **Alternative Materials**: Suggest substitutions when preferred materials are unavailable
6. **Batch BOM Generation**: Generate BOM for entire projects
7. **Material Purchase Orders**: Generate POs directly from BOM shortages
8. **Real-time Availability**: Live inventory checks during cabinet specification

## Notes

- Material mappings are seeded with initial data but can be modified via UI (to be implemented)
- All calculations assume standard TCS construction methods
- Waste factor (10%) is industry standard but can be adjusted per material
- Priority system allows for automatic material selection based on availability
- System supports both solid wood (board feet) and sheet goods (square feet) calculations
