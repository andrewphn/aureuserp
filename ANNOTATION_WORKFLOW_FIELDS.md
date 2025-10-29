# Annotation Workflow - Nullable Fields

## Overview
During the annotation stage (marking up PDF architectural drawings), you're just identifying cabinet runs and cabinet locations without detailed measurements or pricing. These fields can be filled in later as the project progresses through different stages.

## Migration Changes

### Cabinet Runs Table (`projects_cabinet_runs`)
**Migration**: `2025_10_29_111825_make_total_linear_feet_nullable_in_cabinet_runs_table.php`

- ✅ `total_linear_feet` - NOW NULLABLE
  - Was: REQUIRED (caused constraint violation error)
  - Now: Optional during annotation, auto-calculated from cabinets later
  - Helper text: "Optional: Auto-calculated from cabinets if left blank"

### Cabinet Specifications Table (`projects_cabinet_specifications`)
**Migration**: `2025_10_29_113649_make_cabinet_spec_dimensions_and_pricing_nullable.php`

- ✅ `length_inches` - NOW NULLABLE
  - Was: REQUIRED
  - Now: Optional during annotation stage
  - Helper text: "Optional during annotation stage"
  - Form field: No longer has `->required()`

- ✅ `linear_feet` - NOW NULLABLE
  - Was: REQUIRED
  - Now: Auto-calculated from length_inches when entered
  - Automatically populated by form when length is entered

- ✅ `unit_price_per_lf` - NOW NULLABLE
  - Was: REQUIRED
  - Now: Optional, comes from product variant selection later
  - Can be added during pricing stage

- ✅ `total_price` - NOW NULLABLE
  - Was: REQUIRED
  - Now: Optional, auto-calculated when pricing info is available
  - Calculated as: unit_price_per_lf × linear_feet × quantity

## Workflow Stages

### Stage 1: Annotation (Current - Fixed)
**What you can do**: Mark cabinet runs and cabinet locations on PDF
**What's optional**:
- Cabinet run total linear feet
- Cabinet dimensions (length, width, depth, height)
- Pricing information

**Minimum required fields**:
- Cabinet Run: name, run_type, room_location_id
- Cabinet: cabinet_number, room_id, quantity (defaults to 1)

### Stage 2: Measurement
**Add**:
- length_inches (triggers auto-calculation of linear_feet)
- width_inches, depth_inches, height_inches
- wall_position_start_inches
- Cabinet run total_linear_feet

### Stage 3: Pricing
**Add**:
- unit_price_per_lf (from product variant)
- total_price (auto-calculated or manual override)

## Error Before Fix

```
Error saving annotation: SQLSTATE[23000]: Integrity constraint violation:
1048 Column 'total_linear_feet' cannot be null
```

## Error Fixed ✅

Both cabinet runs and cabinet specifications can now be created during annotation without measurements or pricing.

## Form Updates

### CabinetRunsRelationManager.php
- Removed `->default(0)` from total_linear_feet
- Added helper text: "Optional: Auto-calculated from cabinets if left blank"

### CabinetsRelationManager.php
- Removed `->required()` from length_inches
- Added helper text: "Optional during annotation stage"
- Auto-calculation still works when length is entered

## Database Schema

All changes maintain backwards compatibility - existing records are unaffected.

### Rollback Available
Both migrations include `down()` methods to revert changes if needed:
```bash
php artisan migrate:rollback --step=2
```

## Files Modified

1. `plugins/webkul/projects/database/migrations/2025_10_29_111825_make_total_linear_feet_nullable_in_cabinet_runs_table.php`
2. `plugins/webkul/projects/database/migrations/2025_10_29_113649_make_cabinet_spec_dimensions_and_pricing_nullable.php`
3. `plugins/webkul/projects/src/Filament/Resources/ProjectResource/RelationManagers/CabinetRunsRelationManager.php`
4. `plugins/webkul/projects/src/Filament/Resources/ProjectResource/RelationManagers/CabinetsRelationManager.php`

---

**Date Fixed**: October 29, 2025
**Issue**: Annotation editor constraint violations on required numeric fields
**Solution**: Made measurement and pricing fields nullable to support progressive data entry workflow
