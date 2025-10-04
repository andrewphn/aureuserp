# Cabinet Product Attributes Mapping for AureusERP
## Based on cabinet_research.md Analysis

## Executive Summary

This document maps the 10-level hierarchical cabinet specification system from `cabinet_research.md` to AureusERP product attributes and variants. The goal is to determine which parameters should be **product-level attributes** (creating variants) versus **project-level specifications** (custom per order).

---

## I. Understanding the Hierarchical Structure

The cabinet research defines a 10-level system:

1. **Environment/Context** - Room layout (PROJECT-LEVEL)
2. **Cabinet Assembly/Layout** - Sequence, placement (PROJECT-LEVEL)
3. **Individual Cabinet Unit** - Type, dimensions (HYBRID: Type=PRODUCT, Dimensions=PROJECT)
4. **Carcass** - Construction details (PRODUCT-LEVEL)
5. **Face Frame** - Component dimensions (HYBRID)
6. **Doors & Drawer Fronts** - Styles, materials (PRODUCT-LEVEL)
7. **Drawer Boxes** - Construction (PRODUCT-LEVEL)
8. **Shelves** - Type, configuration (HYBRID)
9. **Joinery & Machining** - Specific cuts (PROJECT-LEVEL)
10. **Hardware Mounting** - Precise locations (PROJECT-LEVEL)

---

## II. Product-Level Attributes (Create Variants)

These attributes define different cabinet **types/variants** that customers can select:

### A. Construction Style (CRITICAL)
**Attribute Name:** "Construction Style"
**Type:** Radio (single choice)
**Options:**
- Face Frame Traditional ($0 base)
- Frameless Euro-Style ($15/LF extra)

**Impact:**
- Affects interior width
- Changes hinge types required
- Modifies door overlay calculations
- Face Frame adds traditional aesthetic

---

### B. Cabinet Unit Type (CRITICAL)
**Attribute Name:** "Cabinet Type"
**Type:** Select (dropdown)
**Options:**

**Base Cabinets:**
- Base Standard (12-36" width) ($0 base)
- Base Sink (30-36" width) (+$25/LF)
- Base Drawer Stack (3-4 drawers) (+$35/LF)
- Base Cooktop (drop-in range) (+$30/LF)
- Base Corner Lazy Susan (+$75/LF)
- Base Corner Blind (+$45/LF)
- Base Corner Diagonal (+$40/LF)
- Base Dishwasher Space (placeholder) ($0)
- Base Appliance Opening (+$35/LF)
- Base End Shelf (+$20/LF)

**Wall Cabinets:**
- Wall Standard (12-36" width) ($0 base for wall)
- Wall Corner Diagonal (+$35/LF)
- Wall Corner L-Shape (+$40/LF)
- Wall Corner Blind (+$30/LF)
- Wall Over Fridge (24" depth) (+$15/LF)
- Wall Microwave Shelf (+$25/LF)
- Wall Plate Rack (+$30/LF)
- Wall Wine Rack (+$40/LF)

**Tall Cabinets:**
- Tall Pantry (84-96" height) (+$50/LF)
- Tall Oven (wall oven housing) (+$60/LF)
- Tall Utility (broom storage) (+$35/LF)

---

### C. Door/Drawer Style (CRITICAL)
**Attribute Name:** "Door Style"
**Type:** Radio with image preview
**Options:**
- Slab (flat, modern) ($0 base)
- Shaker (5-piece frame) (+$12/LF)
- Raised Panel Square (+$18/LF)
- Raised Panel Arch (+$22/LF)
- Recessed Panel Square (+$15/LF)
- Glass Panel Single Lite (+$25/LF)
- Beadboard Panel (+$20/LF)
- V-Groove Panel (+$18/LF)
- Louvered (+$28/LF)

**Impact:**
- Affects fabrication complexity
- Changes material requirements
- Modifies machining operations

---

### D. Primary Wood Species/Material (CRITICAL)
**Attribute Name:** "Primary Material"
**Type:** Select
**Options:**

**Paint Grade:**
- MDF (smooth paint surface) ($0 base)
- Hard Maple (paint grade) (+$10/LF)
- Poplar (paint grade) (+$8/LF)

**Stain Grade:**
- Red Oak (classic) (+$18/LF)
- Hard Maple (stain) (+$22/LF)
- Cherry (premium) (+$35/LF)

**Premium:**
- Rifted White Oak (+$45/LF)
- Quarter Sawn White Oak (+$50/LF)
- Black Walnut (+$60/LF)

**Exotic (Custom Quote):**
- Specify in project notes

---

### E. Finish Type (CRITICAL)
**Attribute Name:** "Finish"
**Type:** Radio
**Options:**
- Paint Grade (MDF/Maple/Poplar) ($0 base)
- Stain Grade (natural wood grain) (+$15/LF)
- Clear Coat (natural, no stain) (+$12/LF)

**Impact:**
- Paint grade uses MDF or paint-ready wood
- Stain grade requires premium hardwoods
- Clear coat shows natural wood character

---

### F. Edge Profile (OPTIONAL)
**Attribute Name:** "Edge Profile"
**Type:** Select
**Options:**
- Square/Straight (no profile) ($0)
- Roundover 1/8" (+$2/LF)
- Roundover 1/4" (+$3/LF)
- Chamfer 1/8" (+$2/LF)
- Chamfer 1/4" (+$3/LF)
- Ogee (+$5/LF)
- Cove (+$4/LF)

---

### G. Drawer Box Construction (OPTIONAL)
**Attribute Name:** "Drawer Box Type"
**Type:** Select
**Options:**
- Baltic Birch Plywood (standard) ($0)
- Dovetail Solid Wood (+$15/LF)
- Undermount Soft-Close Ready (+$8/LF)

---

### H. Carcass Material (OPTIONAL)
**Attribute Name:** "Box Material"
**Type:** Select
**Options:**
- 3/4" Plywood (standard) ($0)
- 3/4" MDF (+$3/LF)
- Baltic Birch Plywood (+$8/LF)

---

### I. Overlay Type (TECHNICAL)
**Attribute Name:** "Door Overlay"
**Type:** Radio
**Options:**
- Full Overlay (modern) ($0)
- Partial Overlay (traditional) ($0)
- Inset (flush, premium) (+$25/LF)

**Impact:**
- Full overlay: doors cover frame/box edges
- Partial: reveals frame edges
- Inset: doors sit flush inside frame (requires precise fitting)

---

## III. Project-Level Specifications (Custom Per Order)

These parameters vary per project and should NOT be product variants:

### Custom Dimensions
- Exact width (12-48")
- Exact height (varies by cabinet type)
- Exact depth (varies by cabinet type)
- Custom sizes beyond standard

### Hardware Specifications
- Specific hinge brand/model (Blum, Salice, etc.)
- Drawer slide type (undermount, side-mount)
- Pull/knob selection (customer choice)
- Lazy Susan hardware model

### Installation Details
- Scribe strips (left/right)
- Finished end panels
- Filler strips
- Toe kick height/depth adjustments

### Joinery Details (Level 9)
- Dado/groove dimensions
- Rabbet specifications
- Dovetail/box joint patterns
- Pocket hole locations

### Hardware Mounting (Level 10)
- Precise hinge cup locations
- Shelf pin hole patterns (32mm system)
- Drawer slide mounting points
- Pull/handle drilling coordinates

### Special Requirements
- Sink cutout dimensions
- Appliance opening specs
- Ventilation cutouts
- Electrical/plumbing considerations

---

## IV. Recommended Product Structure

### Base Product: "Custom Cabinet - [Type]"

**Example 1: Base Standard Cabinet**
```
Product Name: Custom Base Cabinet - Standard
Base Price: $168/LF (Level 2 pricing)

Attributes (creates variants):
1. Construction Style: Face Frame / Frameless
2. Door Style: Slab / Shaker / Raised Panel / etc.
3. Primary Material: MDF / Maple / Oak / Walnut / etc.
4. Finish: Paint / Stain / Clear
5. Edge Profile: Square / Roundover / Chamfer / Ogee
6. Drawer Box: Baltic Birch / Dovetail / Soft-Close
7. Overlay: Full / Partial / Inset

Total Variants: 7 attributes × options = 1,000+ combinations
```

**Example 2: Wall Cabinet - Standard**
```
Product Name: Custom Wall Cabinet - Standard
Base Price: $145/LF

Same attribute structure as base, but:
- Different base dimensions (12" depth vs 24")
- No toe kick
- Mounting method becomes relevant
```

### Variant Pricing Logic
```
Final Price = Base Price
  + Construction Style Modifier
  + Door Style Modifier
  + Material Upgrade
  + Finish Type Modifier
  + Edge Profile Cost
  + Drawer Box Upgrade
  + Overlay Modifier (if inset)
```

**Example Calculation:**
```
Base Cabinet - Standard (Level 2): $168/LF
+ Frameless: $15/LF
+ Shaker Doors: $12/LF
+ Hard Maple (stain): $22/LF
+ Stain Grade Finish: $15/LF
+ Roundover 1/4": $3/LF
+ Dovetail Drawers: $15/LF
+ Full Overlay: $0
─────────────────────────
TOTAL: $250/LF
```

---

## V. Implementation Strategy

### Phase 1: Core Attributes (Week 1)
Create these essential attributes first:
1. ✅ Construction Style (2 options)
2. ✅ Cabinet Type (25+ options)
3. ✅ Door Style (9 options)
4. ✅ Primary Material (10+ options)
5. ✅ Finish Type (3 options)

### Phase 2: Enhanced Attributes (Week 2)
Add refinement options:
6. Edge Profile (7 options)
7. Drawer Box Construction (3 options)
8. Carcass Material (3 options)
9. Overlay Type (3 options)

### Phase 3: Integration (Week 3)
- Connect attributes to production estimates
- Link to linear feet calculator
- Integrate with project wizard
- Auto-calculate pricing based on selections

### Phase 4: Advanced Features (Week 4)
- Hardware selection (separate products)
- Custom dimension input
- 3D preview integration (future)
- Cut list generation

---

## VI. Database Schema Requirements

### Products Table Extensions
```sql
-- Already exists: products_products table

-- Add columns for cabinet-specific data:
ALTER TABLE products_products ADD COLUMN unit_type VARCHAR(50);
ALTER TABLE products_products ADD COLUMN construction_style VARCHAR(50);
ALTER TABLE products_products ADD COLUMN standard_width DECIMAL(5,2);
ALTER TABLE products_products ADD COLUMN standard_height DECIMAL(5,2);
ALTER TABLE products_products ADD COLUMN standard_depth DECIMAL(5,2);
```

### Product Attributes Structure
```
products_attributes
- id
- name (Construction Style, Door Style, etc.)
- type (radio, select, color)
- display_order

products_attribute_options
- id
- attribute_id
- name
- extra_price_per_lf
- image_url (for door styles)
- technical_specs (JSON)

products_product_attributes (pivot)
- product_id
- attribute_id
- is_required
```

### Variant Generation
```
products_variants (auto-generated)
- id
- product_id
- sku (generated)
- attribute_values (JSON: {"construction_style": "face_frame", "door_style": "shaker"})
- calculated_price_per_lf
- stock_status (made_to_order)
```

---

## VII. Key Decisions & Rationale

### Why These Attributes Are Product-Level:

**Construction Style:**
- Fundamentally changes cabinet appearance
- Affects manufacturing process
- Requires different hardware
- Customer sees visual difference

**Cabinet Type:**
- Defines cabinet function
- Changes internal configuration
- Affects pricing significantly
- Standard industry categories

**Door Style:**
- Primary visual element
- Affects material usage
- Changes machining complexity
- Customer's main design choice

**Material/Finish:**
- Defines aesthetic category
- Major cost driver
- Industry-standard classifications
- Paint vs Stain vs Premium tiers

### Why Dimensions Are Project-Level:

- Every project has unique sizes
- Cannot create variants for every 1/8" increment
- Would create millions of meaningless SKUs
- Better handled as custom inputs per project

### Why Hardware Is Project-Level:

- Customer/designer specific choices
- Brand/model preferences vary
- Should be separate line items
- Allows for easy substitutions

---

## VIII. User Experience Flow

### Step 1: Select Base Cabinet Type
```
User selects: "Base Cabinet - Standard"
System shows: Base price $168/LF
```

### Step 2: Choose Construction Style
```
Options displayed as cards with images:
[ Face Frame Traditional ] [ Frameless Euro-Style +$15/LF ]
```

### Step 3: Select Door Style
```
Visual grid showing door styles:
[Slab] [Shaker +$12] [Raised Panel +$18] ...
Each with preview image
```

### Step 4: Pick Material & Finish
```
Material dropdown categorized:
- Paint Grade: MDF, Hard Maple, Poplar
- Stain Grade: Oak, Maple, Cherry
- Premium: White Oak, Walnut

Then: Finish type (Paint/Stain/Clear)
```

### Step 5: Add Details
```
- Edge Profile (visual selector)
- Drawer Box Construction
- Overlay Type
```

### Step 6: Enter Project Specs
```
Custom inputs (not variants):
- Width: ____ inches
- Height: ____ inches
- Depth: ____ inches
- Quantity: ____ linear feet
```

### Step 7: See Calculated Price
```
Configuration Summary:
Base Cabinet - Standard
- Frameless Construction
- Shaker Doors
- Hard Maple (Stain)
- Stain Grade Finish
- Roundover 1/4" Edge
- Dovetail Drawers
- Full Overlay

Price: $250/LF × 10 LF = $2,500
```

---

## IX. Next Steps

### Immediate Actions:
1. ✅ Create product attributes in AureusERP
2. ✅ Define attribute options with pricing
3. ✅ Set up variant generation logic
4. ✅ Configure pricing calculator

### Testing Phase:
1. Create sample base cabinet product
2. Add 5 core attributes
3. Generate variants (test with limited options first)
4. Validate pricing calculations
5. Test with real project from TCS

### Rollout:
1. Start with 3 base cabinet types
2. Add 3 wall cabinet types
3. Expand to full catalog (25+ types)
4. Train staff on system
5. Gather feedback and refine

---

## X. Business Rules & Constraints

### Attribute Compatibility Rules:

**Rule 1: Paint Grade Materials**
```
IF Finish = "Paint Grade"
THEN Material IN (MDF, Hard Maple, Poplar)
DISABLE (Oak, Cherry, Walnut, etc.)
```

**Rule 2: Inset Overlay**
```
IF Overlay = "Inset"
THEN Construction_Style MUST = "Face Frame"
DISABLE Frameless option
```

**Rule 3: Frameless Implications**
```
IF Construction_Style = "Frameless"
THEN Overlay = "Full Overlay" (default/only)
EDGE_BANDING required = true
```

**Rule 4: Door Style Complexity**
```
IF Door_Style IN (Raised Panel, Glass Panel, Louvered)
THEN Minimum_Price_Level = Level 3 ($192/LF base)
```

### Minimum Order Rules:
- Base Cabinet minimum: 12" width
- Wall Cabinet minimum: 12" width
- Tall Cabinet minimum: 18" width
- Corner units: specific footprint requirements

### Lead Time by Complexity:
- Slab/Paint Grade: 2-3 weeks
- Shaker/Stain Grade: 3-4 weeks
- Raised Panel/Premium: 4-6 weeks
- Custom/Exotic: 6-8 weeks

---

## XI. Advanced Considerations

### Future Enhancements:

**3D Visualization:**
- Integrate with cabinet design software
- Generate preview from attribute selections
- Show door style on cabinet type

**Cut List Generation:**
- Auto-generate component dimensions
- Export to CNC/CAM systems
- Material optimization

**Hardware Integration:**
- Link hinge products to door styles
- Suggest slide types for drawer configs
- Bundle recommendations

**AI-Powered Suggestions:**
- "Customers who selected Shaker also chose..."
- "This material pairs well with..."
- "Popular combinations for [style]"

---

## XII. Summary & Recommendations

### Core Product Attributes to Implement:
1. **Construction Style** (2 options) - CRITICAL
2. **Cabinet Type** (25+ options) - CRITICAL
3. **Door Style** (9 options) - CRITICAL
4. **Primary Material** (10+ options) - CRITICAL
5. **Finish Type** (3 options) - CRITICAL
6. **Edge Profile** (7 options) - OPTIONAL
7. **Drawer Box Construction** (3 options) - OPTIONAL
8. **Overlay Type** (3 options) - TECHNICAL

### Project-Level Inputs (NOT Attributes):
- Custom dimensions (W×H×D)
- Hardware selections (separate products)
- Joinery specifications
- Installation details
- Special requirements

### Implementation Priority:
**Phase 1 (Week 1):** Core 5 attributes, 1 base cabinet type
**Phase 2 (Week 2):** Add optional attributes, 3 more cabinet types
**Phase 3 (Week 3):** Full cabinet type catalog, pricing integration
**Phase 4 (Week 4):** Advanced features, testing, refinement

### Success Metrics:
- ✅ Accurate pricing from attribute selections
- ✅ Production-ready specifications
- ✅ Reduced quoting time (from 2 hours to 15 minutes)
- ✅ Fewer errors in material ordering
- ✅ Better customer visualization of options

---

**Status:** Ready for implementation
**Next Action:** Create first product attribute "Construction Style" in AureusERP
**Owner:** Development Team
**Timeline:** 4 weeks to full rollout
