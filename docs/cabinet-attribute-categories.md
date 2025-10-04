# Cabinet Product Attribute Categories
## TCS Pricing Structure Classification

Based on the TCS Wholesale Pricing Sheets and cabinet research, product attributes are categorized into distinct types that align with the pricing methodology.

---

## Category 1: MATERIAL ATTRIBUTES
**Definition**: Attributes related to the physical materials and wood species used in construction.
**Pricing Impact**: Material category upgrades (added to base price per LF)

### 1. Primary Material ✅
**Type**: Select dropdown
**Category**: MATERIAL
**Options**:
- MDF (Paint Grade) - $0/LF base
- Hard Maple (Paint Grade) - +$10/LF
- Poplar (Paint Grade) - +$8/LF
- Red Oak (Stain Grade) - +$18/LF
- Hard Maple (Stain Grade) - +$22/LF
- Cherry (Premium) - +$35/LF
- Rifted White Oak (Premium) - +$45/LF
- Quarter Sawn White Oak (Premium) - +$50/LF
- Black Walnut (Premium) - +$60/LF

**TCS Pricing Alignment**:
- Paint Grade Materials: $138/LF upgrade (from price sheet)
- Stain Grade Materials: $156/LF upgrade (from price sheet)
- Premium Materials: $185/LF upgrade (from price sheet)

### 2. Box Material (Carcass) ✅
**Type**: Select dropdown
**Category**: MATERIAL
**Options**:
- 3/4" Plywood (Standard) - $0
- 3/4" MDF - +$3/LF
- Baltic Birch Plywood - +$8/LF

**Purpose**: Defines the structural material for cabinet box construction

### 3. Finish Type ✅
**Type**: Radio (single choice)
**Category**: MATERIAL/PROCESS (hybrid)
**Options**:
- Paint Grade - $0 (requires paint-ready materials)
- Stain Grade - +$15/LF (requires stain-grade wood species)
- Clear Coat (Natural) - +$12/LF (shows natural wood grain)

**TCS Pricing Note**: Finish type determines material selection and finishing labor

---

## Category 2: CONSTRUCTION / PROCESS ATTRIBUTES
**Definition**: Attributes related to fabrication methods, assembly techniques, and construction complexity.
**Pricing Impact**: Reflects construction labor and complexity (often included in base level pricing)

### 4. Construction Style ✅
**Type**: Radio (single choice)
**Category**: CONSTRUCTION/PROCESS
**Options**:
- Face Frame Traditional - $0 (standard TCS method)
- Frameless Euro-Style - +$15/LF

**Construction Impact**:
- Face Frame: Adds solid wood frame to cabinet front (traditional)
- Frameless: Direct door mounting to box edges (modern, maximizes interior space)

### 5. Door Style ✅
**Type**: Radio (single choice)
**Category**: CONSTRUCTION/PROCESS
**Options**:
- Slab (Flat Panel) - $0 base
- Shaker (5-Piece Frame) - +$12/LF
- Raised Panel Square - +$18/LF
- Raised Panel Arch - +$22/LF
- Recessed Panel Square - +$15/LF
- Glass Panel Single Lite - +$25/LF
- Beadboard Panel - +$20/LF
- V-Groove Panel - +$18/LF
- Louvered - +$28/LF

**TCS Pricing Alignment**:
- Reflects cabinet complexity levels (Level 1-5 pricing)
- More complex door styles = higher labor/machining costs

### 6. Drawer Box Construction ✅
**Type**: Select dropdown
**Category**: CONSTRUCTION/PROCESS
**Options**:
- Baltic Birch Plywood (Standard) - $0
- Dovetail Solid Wood - +$15/LF
- Undermount Soft-Close Ready - +$8/LF

**Construction Impact**: Joinery method and slide compatibility

### 7. Door Overlay Type ✅
**Type**: Radio (single choice)
**Category**: CONSTRUCTION/PROCESS
**Options**:
- Full Overlay (Modern) - $0
- Partial Overlay (Traditional) - $0
- Inset (Flush Premium) - +$25/LF

**Construction Impact**:
- Full: Doors cover box/frame edges completely
- Partial: Doors reveal frame edges (traditional look)
- Inset: Doors sit flush inside frame (requires precise fitting, premium)

### 8. Edge Profile ✅
**Type**: Select dropdown
**Category**: CONSTRUCTION/PROCESS
**Options**:
- Square / Straight (No Profile) - $0
- Roundover 1/8" - +$2/LF
- Roundover 1/4" - +$3/LF
- Chamfer 1/8" - +$2/LF
- Chamfer 1/4" - +$3/LF
- Ogee - +$5/LF
- Cove - +$4/LF

**Construction Impact**: Router/shaper operations, adds visual detail

---

## Pricing Calculation Logic

### Base Price Determination (TCS Pricing Levels)
```
Level 1: $138/LF - Open boxes only (no doors/drawers)
Level 2: $168/LF - Semi-European, flat/shaker doors
Level 3: $192/LF - Stain grade, semi-complicated
Level 4: $210/LF - Beaded frames, specialty doors
Level 5: $225/LF - Unique custom work
```

### Attribute Price Modifiers
```php
Final Price/LF = Base Level Price
    + Primary Material Modifier
    + Finish Type Modifier
    + Construction Style Modifier
    + Door Style Modifier
    + Edge Profile Modifier
    + Drawer Box Modifier
    + Overlay Type Modifier (if Inset)
    + Box Material Modifier
```

### Example Calculation
```
Base Cabinet Level 2: $168/LF
+ Frameless Construction: $15/LF
+ Shaker Doors: $12/LF
+ Hard Maple (Stain): $22/LF
+ Stain Grade Finish: $15/LF
+ Roundover 1/4" Edge: $3/LF
+ Dovetail Drawers: $15/LF
+ Full Overlay: $0
+ 3/4" Plywood Box: $0
─────────────────────────
TOTAL: $250/LF
```

---

## Attribute Dependencies & Business Rules

### Rule 1: Paint vs Stain Material Compatibility
```
IF Finish Type = "Paint Grade"
THEN Primary Material MUST BE IN:
  - MDF (Paint Grade)
  - Hard Maple (Paint Grade)
  - Poplar (Paint Grade)

IF Finish Type = "Stain Grade" OR "Clear Coat"
THEN Primary Material MUST BE IN:
  - Red Oak (Stain Grade)
  - Hard Maple (Stain Grade)
  - Cherry (Premium)
  - Rifted White Oak (Premium)
  - Quarter Sawn White Oak (Premium)
  - Black Walnut (Premium)
```

### Rule 2: Construction Style Constraints
```
IF Construction Style = "Frameless"
THEN:
  - Door Overlay Type = "Full Overlay" (forced/only option)
  - Edge banding required on all exposed edges
  - Hinges must be Euro/concealed type

IF Construction Style = "Face Frame"
THEN:
  - Door Overlay Type = Any (Full, Partial, or Inset allowed)
  - Face frame provides finished edges
  - Multiple hinge types available
```

### Rule 3: Inset Overlay Restriction
```
IF Door Overlay Type = "Inset"
THEN Construction Style MUST = "Face Frame"
(Inset doors cannot exist without a frame to sit flush with)
```

### Rule 4: Door Style Complexity Alignment
```
IF Door Style IN ["Raised Panel Arch", "Glass Panel", "Louvered"]
THEN Minimum Cabinet Level = 3 (or higher)
(Complex door styles require higher skill/equipment)
```

---

## UI/UX Recommendations

### Attribute Display Order (by Priority)
1. **Construction Style** - Fundamental choice (Frame vs Frameless)
2. **Door Style** - Primary visual element
3. **Primary Material** - Material tier selection
4. **Finish Type** - Paint/Stain/Clear
5. **Edge Profile** - Detail enhancement
6. **Drawer Box Construction** - Quality tier
7. **Door Overlay Type** - Technical detail
8. **Box Material** - Internal construction

### Visual Grouping
```
┌─ CONSTRUCTION METHOD ─────────┐
│ • Construction Style          │
│ • Door Overlay Type           │
└───────────────────────────────┘

┌─ MATERIALS ───────────────────┐
│ • Primary Material            │
│ • Box Material                │
│ • Finish Type                 │
└───────────────────────────────┘

┌─ DETAILS & UPGRADES ──────────┐
│ • Door Style                  │
│ • Edge Profile                │
│ • Drawer Box Construction     │
└───────────────────────────────┘
```

### Conditional Display Logic
- Show "Door Overlay Type" ONLY if Construction Style is selected
- Disable Inset option if Frameless is selected
- Filter Primary Material options based on Finish Type
- Show material tier pricing hints (Paint/Stain/Premium)

---

## Database Schema Summary

### Tables Used
```
products_attributes
├── id (PK)
├── name (Construction Style, Door Style, etc.)
├── type (radio, select, color)
├── sort (display order)
├── creator_id (FK to users)
└── timestamps

products_attribute_options
├── id (PK)
├── name (Face Frame Traditional, Shaker, etc.)
├── color (optional, for color type attributes)
├── extra_price (decimal - price modifier per LF)
├── sort (option display order)
├── attribute_id (FK to products_attributes)
├── creator_id (FK to users)
└── timestamps

products_product_attributes (pivot)
├── product_id (FK to products_products)
├── attribute_id (FK to products_attributes)
└── (other fields as needed)
```

---

## Migration Reference

**File**: `database/migrations/2025_10_04_121615_seed_tcs_cabinet_product_attributes.php`

**Seeded Data**:
- 8 product attributes
- 39 attribute options
- Pricing modifiers aligned with TCS wholesale pricing
- Categories mapped to cabinet research specifications

**Rollback**: `php artisan migrate:rollback` removes all seeded attributes and options

---

## Next Steps

### Phase 1: Attribute Assignment ✅ COMPLETE
- [x] Create core attributes
- [x] Define attribute options with pricing
- [x] Seed database via migration

### Phase 2: Product Integration (Next)
- [ ] Assign attributes to cabinet products
- [ ] Create product variants from attribute combinations
- [ ] Test pricing calculation logic
- [ ] Validate attribute dependencies

### Phase 3: UI Enhancement
- [ ] Create visual attribute selectors (cards for Construction Style, Door Style)
- [ ] Implement conditional logic (hide/show/disable based on selections)
- [ ] Add image previews for Door Styles
- [ ] Real-time price calculator display

### Phase 4: Advanced Features
- [ ] Cabinet Type attribute (Base_Standard, Wall_Standard, etc.)
- [ ] Hardware selection integration
- [ ] Cut list generation from variant data
- [ ] 3D preview integration (future)

---

**Status**: Attributes Created & Seeded ✅
**Next Action**: Assign attributes to product and test variant generation
**Documentation**: See `/docs/cabinet-product-attributes-mapping.md` for full specifications
