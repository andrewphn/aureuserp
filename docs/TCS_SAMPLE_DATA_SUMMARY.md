# TCS Sample Data Summary

## Overview

This document summarizes the comprehensive sample data seeded across all TCS-related tables to demonstrate the complete cabinet pricing and material BOM system.

## Seeded Data

### 1. Inventory Products (10 wood materials)

All products created in `products_products` table with proper pricing and UOM assignments:

#### Paint Grade Materials
- **Hard Maple Lumber - 4/4 S2S** (WOOD-MAPLE-HARD-44)
  - Cost: $8.50/LF | Price: $12.75/LF
  - UOM: Linear Foot (27)
  - Primary material for paint grade cabinets

- **Poplar Lumber - 4/4 S2S** (WOOD-POPLAR-44)
  - Cost: $6.25/LF | Price: $9.50/LF
  - UOM: Linear Foot (27)
  - Cost-effective paint grade option

- **Birch Plywood - 3/4" A2** (PLYWOOD-BIRCH-34-A2)
  - Cost: $65.00/sheet | Price: $95.00/sheet
  - UOM: Units (1) - sold as 4x8 sheets
  - Sheet good for paint grade cabinet boxes

#### Stain Grade Materials
- **Red Oak Lumber - 4/4 Select** (WOOD-OAK-RED-44-SEL)
  - Cost: $9.75/LF | Price: $14.50/LF
  - UOM: Linear Foot (27)
  - Classic stain grade hardwood

- **White Oak Lumber - 4/4 Select** (WOOD-OAK-WHITE-44-SEL)
  - Cost: $11.25/LF | Price: $16.75/LF
  - UOM: Linear Foot (27)
  - Premium stain grade hardwood

- **Hard Maple Lumber - 4/4 Select (Stain)** (WOOD-MAPLE-HARD-44-STAIN)
  - Cost: $10.50/LF | Price: $15.75/LF
  - UOM: Linear Foot (27)
  - Stain grade maple - higher select than paint grade

#### Premium Materials
- **Rift White Oak Lumber - 4/4 Premium** (WOOD-OAK-RIFT-44-PREM)
  - Cost: $18.50/LF | Price: $27.75/LF
  - UOM: Linear Foot (27)
  - Premium straight grain pattern

- **Black Walnut Lumber - 4/4 Select** (WOOD-WALNUT-BLACK-44-SEL)
  - Cost: $22.00/LF | Price: $33.00/LF
  - UOM: Linear Foot (27)
  - Premium dark hardwood

- **Cherry Lumber - 4/4 Select** (WOOD-CHERRY-44-SEL)
  - Cost: $15.75/LF | Price: $23.50/LF
  - UOM: Linear Foot (27)
  - Premium reddish hardwood

#### Custom/Exotic Materials
- **Exotic Hardwood - Various Species** (WOOD-EXOTIC-CUSTOM)
  - Cost: $25.00/LF | Price: $40.00/LF
  - UOM: Linear Foot (27)
  - Custom pricing per species (mahogany, teak, etc.)

### 2. Material Inventory Mappings

Updated all 10 existing material mappings in `tcs_material_inventory_mappings` with `inventory_product_id` links:

| TCS Material | Wood Species | Product Reference | BF/LF or SF/LF |
|-------------|--------------|-------------------|----------------|
| Paint Grade | Hard Maple | WOOD-MAPLE-HARD-44 | 2.50 BF/LF |
| Paint Grade | Poplar | WOOD-POPLAR-44 | 2.50 BF/LF |
| Paint Grade | Birch Plywood | PLYWOOD-BIRCH-34-A2 | 6.00 SF/LF |
| Stain Grade | Red Oak | WOOD-OAK-RED-44-SEL | 2.50 BF/LF |
| Stain Grade | White Oak | WOOD-OAK-WHITE-44-SEL | 2.50 BF/LF |
| Stain Grade | Hard Maple (Stain) | WOOD-MAPLE-HARD-44-STAIN | 2.50 BF/LF |
| Premium | Rifted White Oak | WOOD-OAK-RIFT-44-PREM | 2.80 BF/LF |
| Premium | Black Walnut | WOOD-WALNUT-BLACK-44-SEL | 2.80 BF/LF |
| Premium | Cherry | WOOD-CHERRY-44-SEL | 2.70 BF/LF |
| Custom | Exotic/Custom Wood | WOOD-EXOTIC-CUSTOM | 3.00 BF/LF |

### 3. Sample Project

**Project:** TCS Sample Kitchen Renovation
- Demonstrates complete TCS cabinet pricing hierarchy
- Includes hierarchical material category inheritance
- Shows BOM generation across multiple material grades

#### Room 1: Main Kitchen (Stain Grade)
- **Material:** Stain Grade (Red Oak/White Oak/Hard Maple)
- **Cabinet Level:** 3 ($192/LF base)
- **Finish:** Natural Stain (+$65/LF)
- **Unit Price:** $413/LF (192 + 156 + 65)

**Locations:**
1. Main Wall - North
2. Center Island

**Cabinet Runs:**
- Base Cabinet Run (8 cabinets, 18 LF total)
- Wall Cabinet Run (5 cabinets, 15 LF total)
- Island Base Run (3 cabinets, 8 LF total)

**Base Cabinets (Main Wall):**
- B1: 18" × 24" × 30" (1.5 LF) - $619.50
- B2: 24" × 24" × 30" (2.0 LF) - $826.00
- B3: 36" × 24" × 30" (3.0 LF) - $1,239.00 (Sink base)
- B4: 30" × 24" × 30" (2.5 LF) - $1,032.50
- B5: 24" × 24" × 30" (2.0 LF) - $826.00
- B6: 36" × 24" × 30" (3.0 LF) - $1,239.00
- B7: 24" × 24" × 30" (2.0 LF) - $826.00
- B8: 24" × 24" × 30" (2.0 LF) - $826.00

**Wall Cabinets:**
- W1: 30" × 12" × 30" (2.5 LF) - $1,032.50
- W2: 36" × 12" × 30" (3.0 LF) - $1,239.00
- W3: 30" × 12" × 30" (2.5 LF) - $1,032.50
- W4: 24" × 12" × 30" (2.0 LF) - $826.00
- W5: 36" × 12" × 30" (3.0 LF) - $1,239.00

**Island Cabinets:**
- I1: 36" × 24" × 30" (3.0 LF) - $1,239.00
- I2: 24" × 24" × 30" (2.0 LF) - $826.00
- I3: 36" × 24" × 30" (3.0 LF) - $1,239.00

#### Room 2: Butler's Pantry (Premium)
- **Material:** Premium (Black Walnut/Rifted White Oak/Cherry)
- **Cabinet Level:** 4 ($210/LF base)
- **Finish:** Custom Stain (+$125/LF)
- **Unit Price:** $520/LF (210 + 185 + 125)

**Location:**
- Pantry Storage Wall

**Cabinet Run:**
- Tall Pantry Run (3 cabinets, 6 LF total)

**Tall Cabinets:**
- T1: 18" × 24" × 84" (1.5 LF) - $780.00
- T2: 24" × 24" × 96" (2.0 LF) - $1,040.00
- T3: 30" × 24" × 84" (2.5 LF) - $1,300.00

### 4. Total Project Summary

**Cabinet Count:**
- Base Cabinets: 11 units (26 LF)
- Wall Cabinets: 5 units (15 LF)
- Tall Cabinets: 3 units (6 LF)
- **Total: 19 cabinets, 47 linear feet**

**Estimated Cabinet Pricing:**
- Kitchen (Stain Grade): 41 LF × $413/LF = $16,933
- Pantry (Premium): 6 LF × $520/LF = $3,120
- **Total Cabinet Price: $20,053**

**Material BOM Example (Kitchen Base Cabinets):**

For B1 (18" cabinet, 1.5 LF, Stain Grade):
- Red Oak: 4.13 board feet (1.5 LF × 2.5 BF/LF × 1.10 waste)
- White Oak: 4.13 board feet
- Hard Maple (Stain): 4.13 board feet

For entire Base Run (18 LF):
- Red Oak: 49.50 board feet
- White Oak: 49.50 board feet
- Hard Maple (Stain): 49.50 board feet

**Material Cost Estimates:**
- Red Oak: 49.50 BF × $9.75/BF = $482.63
- White Oak: 49.50 BF × $11.25/BF = $556.88
- Hard Maple: 49.50 BF × $10.50/BF = $519.75

## BOM System Features Demonstrated

1. **Hierarchical Material Inheritance**
   - Room level: Sets base material category
   - Location level: Can override room material
   - Cabinet Run level: Can override location material
   - Cabinet level: Can override run material

2. **Material Usage Multipliers**
   - Solid wood: 2.5-3.0 board feet per linear foot
   - Sheet goods: 6.0 square feet per linear foot
   - Automatic 10% waste factor included

3. **TCS Pricing Calculation**
   - Base Cabinet Level: $138-$225/LF
   - Material Category: $138-$185/LF
   - Finish Option: $0-$255/LF
   - Total: Sum of all three components

4. **Material Recommendations**
   - Priority-based material selection
   - Usage type filtering (box, face frame, doors)
   - Availability checking

5. **Cost Estimation**
   - Links to actual inventory product costs
   - Calculates total material cost for cabinets
   - Accounts for waste in estimates

## Testing the Sample Data

### View Material Mappings
```php
$mappings = TcsMaterialInventoryMapping::with('inventoryProduct')->get();
```

### Generate BOM for a Cabinet
```php
$cabinet = CabinetSpecification::where('cabinet_number', 'B1')->first();
$bom = $cabinet->generateBom();
```

### Generate BOM for Entire Run
```php
$run = CabinetRun::where('name', 'Base Cabinet Run')->first();
$bom = $run->generateBom();
```

### Check Material Costs
```php
$cabinet = CabinetSpecification::find(1);
$cost = $cabinet->estimateMaterialCost();
```

### Test Material Recommendations
```php
$cabinet = CabinetSpecification::find(1);
$boxMaterials = $cabinet->getMaterialRecommendations('box');
$frameMaterials = $cabinet->getMaterialRecommendations('face_frame');
```

## Re-running the Seeder

To regenerate all sample data:

```bash
DB_CONNECTION=mysql php artisan db:seed --class='Webkul\Project\Database\Seeders\TcsSampleDataSeeder'
```

**Note:** This will create duplicate data if run multiple times. To start fresh, you would need to manually delete the created records or truncate the relevant tables.

## Files Created/Modified

### Seeders
- `plugins/webkul/projects/database/seeders/TcsSampleDataSeeder.php` - Main seeder

### Documentation
- `docs/TCS_MATERIAL_BOM_SYSTEM.md` - System documentation
- `docs/TCS_SAMPLE_DATA_SUMMARY.md` - This file

### Test Scripts
- `test-material-bom.php` - BOM system validation script

## Next Steps

1. **UI Development**: Create FilamentPHP interfaces for:
   - Material mapping management
   - BOM viewing and export
   - Material cost reporting

2. **Sales Order Integration**: Auto-generate order lines from BOM

3. **Inventory Integration**: Reserve/allocate materials when cabinets are created

4. **Purchase Order Generation**: Create POs for material shortages

5. **Material Waste Tracking**: Track actual vs estimated waste

6. **Alternative Materials**: Suggest substitutions when materials unavailable
