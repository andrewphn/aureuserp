# TCS Cabinet System - Complete Data Architecture

## Overview
This document defines the complete cabinet data architecture optimized for TCS Woodwork's workflows, based on user personas and business processes.

---

## User Personas & Workflows

### Bryan Patton (Owner)
**Key Traits:**
- ADHD - needs minimal clicks, visual summaries, smart defaults
- Jumps between strategic calls, shop QC, project reviews constantly
- Values: Speed > Features, Visual > Text

**Primary Tasks:**
- Quoting cabinets to customers
- Managing projects
- Reviewing production status

**Workflow Needs:**
- Fast product selection (visual cabinet type cards)
- Instant pricing calculations
- Minimal data entry

### Lead Woodworker / Shop Floor
**Key Traits:**
- Needs clear, unambiguous fabrication instructions
- Works from physical dimensions and material specs
- Creates actual cabinets from specifications

**Primary Tasks:**
- Reading cut lists
- Pulling materials
- Building cabinets to spec

**Workflow Needs:**
- Exact dimensions (L×W×D×H)
- Material specifications
- Assembly instructions
- Hardware lists

---

## 3-Tier Data Architecture

### **Tier 1: Product Catalog (For Pricing)**
**Purpose:** Product variants with calculated pricing for quoting
**Storage:** `products_products` with `parent_id` pattern

```
Cabinet (Parent Product - $168/LF base)
├── Product Attributes (9 total):
│   1. Cabinet Type (12 options)      - Base/Wall/Tall configurations
│   2. Construction Style (2 options) - Face Frame/Frameless
│   3. Door Style (9 options)         - Shaker/Raised Panel/etc
│   4. Primary Material (9 options)   - Oak/Maple/Walnut/MDF
│   5. Finish Type (3 options)        - Paint/Stain/Clear
│   6. Edge Profile (7 options)       - Roundover/Chamfer/Ogee
│   7. Drawer Box (3 options)         - Baltic Birch/Dovetail
│   8. Door Overlay (3 options)       - Full/Partial/Inset
│   9. Box Material (3 options)       - Plywood/MDF/Baltic Birch
│
└── Product Variants (auto-generated):
    ├── Cabinet - Base Standard - Face Frame - Shaker - Maple Stain ($202/LF)
    ├── Cabinet - Wall Standard - Frameless - Slab - MDF Paint ($160/LF)
    ├── Cabinet - Tall Pantry - Face Frame - Raised Panel - Oak ($268/LF)
    └── ... (thousands of combinations)
```

**Pricing Formula:**
```
Final Price/LF = Base Cabinet Type Price
    + Construction Style Modifier
    + Door Style Modifier
    + Primary Material Modifier
    + Finish Type Modifier
    + Edge Profile Modifier
    + Drawer Box Modifier
    + Door Overlay Modifier (if Inset)
    + Box Material Modifier

Example:
Base Standard ($168) + Frameless ($15) + Shaker ($12) + Maple Stain ($22) + Clear ($12) = $229/LF
```

### **Tier 2: Cabinet Specifications (Bridge Layer)**
**Purpose:** Store custom dimensions and details for each cabinet ordered
**Storage:** `projects_cabinet_specifications` table

```sql
CREATE TABLE projects_cabinet_specifications (
    id BIGINT PRIMARY KEY,

    -- Links
    order_line_id       → sales_order_lines (for quotes/orders)
    project_id          → projects_projects (optional direct link)
    product_variant_id  → products_products (selected variant)

    -- Physical Dimensions (shop floor needs)
    length_inches       DECIMAL(8,2)    -- Determines linear feet
    width_inches        DECIMAL(8,2)    -- Cabinet width
    depth_inches        DECIMAL(8,2)    -- 12" wall, 24" base standard
    height_inches       DECIMAL(8,2)    -- 30" base, 84-96" tall

    -- Calculated
    linear_feet         DECIMAL(8,2)    -- length_inches / 12
    quantity            INT             -- Number of identical cabinets

    -- Pricing (auto-calculated)
    unit_price_per_lf   DECIMAL(10,2)   -- From product variant
    total_price         DECIMAL(10,2)   -- unit_price × linear_feet × quantity

    -- Custom specs (fabrication)
    hardware_notes      TEXT            -- Hinges, pulls, slides
    custom_modifications TEXT           -- Extra shelves, lazy susan
    shop_notes          TEXT            -- Internal production notes

    -- Metadata
    creator_id          → users
    created_at, updated_at, deleted_at
)
```

**Purpose:**
- Bridges product catalog (what to build) with fabrication (how to build)
- Stores actual cabinet dimensions (varies per order)
- Links selected variant (attributes/pricing) to physical specs

### **Tier 3: Production Details (Future Enhancement)**
**Purpose:** Auto-generated fabrication instructions
**Storage:** `projects_cabinet_production_specs` (to be created)

```sql
-- Future table for shop floor automation
CREATE TABLE projects_cabinet_production_specs (
    cabinet_spec_id     → projects_cabinet_specifications

    -- Auto-generated from variant + dimensions
    cut_list_json       JSON    -- Parts to cut with dimensions
    material_list_json  JSON    -- Materials needed (from variant)
    assembly_steps_json JSON    -- Step-by-step instructions
    hardware_list_json  JSON    -- Hardware BOM
    cnc_program_path    VARCHAR -- Path to CNC program file

    shop_notes          TEXT
    estimated_hours     DECIMAL
    actual_hours        DECIMAL
)
```

---

## Complete Workflow Examples

### Workflow 1: Bryan Creates Quote (Fast Path)

**Step 1: Select Cabinet Type** (Visual Cards)
```
┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│ Base        │ │ Wall        │ │ Tall        │
│ Standard    │ │ Standard    │ │ Pantry      │
│ $168/LF     │ │ $145/LF     │ │ $218/LF     │
└─────────────┘ └─────────────┘ └─────────────┘
     ✓ Selected
```

**Step 2: Choose Attributes** (Quick Toggles)
```
Construction: ○ Face Frame  ● Frameless (+$15)
Door Style:   ○ Slab  ● Shaker (+$12)  ○ Raised Panel
Material:     ● Maple Stain (+$22)  ○ MDF Paint  ○ Oak
Finish:       ● Clear Coat (+$12)
```
**Live Price:** $168 + $15 + $12 + $22 + $12 = **$229/LF**

**Step 3: Enter Dimensions**
```
Length:   [36] inches  →  3 LF
Width:    [24] inches
Depth:    [12] inches  (auto from cabinet type)
Height:   [30] inches  (auto from cabinet type)
Quantity: [4] cabinets
```
**Calculation:** $229/LF × 3 LF × 4 cabinets = **$2,748**

**Step 4: Save**
- Creates order line item
- Creates cabinet specification record
- Links to selected product variant

**Total Time:** < 60 seconds

### Workflow 2: Shop Floor Fabrication

**Step 1: View Production Queue**
```
Filter: "In Production" + "Material Prep" tags
Results:
- Kitchen Renovation - 4 Base Cabinets (Job #TCS-2025-042)
```

**Step 2: Open Cabinet Spec**
```
Cabinet Specification #127
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Product: Cabinet - Base Standard - Frameless - Shaker - Maple Stain
Price: $229/LF × 3 LF = $687 each × 4 = $2,748 total

DIMENSIONS:
Length:  36" (3 LF)
Width:   24"
Depth:   12"
Height:  30"

ATTRIBUTES:
✓ Frameless Euro-Style Construction
✓ Shaker 5-Piece Frame Doors
✓ Hard Maple (Stain Grade)
✓ Clear Coat Finish
✓ 3/4" Plywood Box Material
✓ Baltic Birch Drawer Boxes
✓ Full Overlay Doors
✓ Roundover 1/4" Edge Profile

HARDWARE NOTES:
- Blum Euro hinges (110° soft-close)
- Brushed nickel pulls (3.75" centers)

SHOP NOTES:
- Customer wants extra shelf at 15" height
- Rush production - needed by Friday
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

**Step 3: Auto-Generated Materials** (Future)
```
MATERIAL LIST:
□ Hard Maple 3/4" × 6" × 36' (face frames)
□ Maple Plywood 3/4" 4×8 sheets (2)
□ Baltic Birch 1/2" 4×8 sheet (1) (drawer boxes)
□ Edge Banding Maple 1.5" × 50' roll
□ Blum 110° Euro Hinges × 8
□ Brushed Nickel Pulls × 8
□ Shelf Pins × 16
```

**Step 4: Cut List** (Future - Auto-generated)
```
CUT LIST - Cabinet #127 (Qty: 4)

PLYWOOD BOX PARTS:
- Top/Bottom:     23.25" × 11.25" × 4 pcs
- Sides:          30" × 11.25" × 8 pcs
- Back:           35.25" × 29.25" × 4 pcs
- Fixed Shelf:    35.25" × 11" × 4 pcs

FACE FRAME:
- Stiles:         30" × 2" × 8 pcs
- Rails:          32" × 2" × 12 pcs

DOORS (Shaker):
- Door Stiles:    26" × 2.25" × 8 pcs
- Door Rails:     14.5" × 2.25" × 16 pcs
- Door Panels:    22.5" × 10" × 8 pcs
```

**Step 5: Assembly Instructions** (Future)
```
ASSEMBLY SEQUENCE:
1. Cut all plywood box parts
2. Edge band exposed edges (front only for frameless)
3. Drill shelf pin holes (32mm spacing)
4. Assemble box: Pocket screws + glue
5. Build face frames: Domino joints
6. Attach face frames: Brad nails + glue
7. Build Shaker doors:
   - Cope & stick rails/stiles
   - Float panels in grooves
8. Install Euro hinges on doors
9. Mount doors to box
10. Install pulls
11. Add shelf at 15" (CUSTOM NOTE)
12. Apply 3 coats clear finish
13. Final QC and pack
```

---

## Database Relationships

```
products_products (Parent)
    ↓ has many
products_products (Variants via parent_id)
    ↑ belongs to
    ↓ has many
projects_cabinet_specifications
    ↑ belongs to product_variant_id
    ↓ belongs to
sales_order_lines
    ↑ has many cabinet_specifications
    ↓ belongs to
projects_projects
```

---

## Key Implementation Files

### Migrations Created
1. `2025_10_04_121615_seed_tcs_cabinet_product_attributes.php`
   - Creates 8 core attributes (Construction, Door, Material, Finish, Edge, Drawer, Overlay, Box)
   - Seeds 39 attribute options with pricing modifiers

2. `2025_10_04_123550_add_cabinet_type_attribute.php`
   - Creates Cabinet Type attribute
   - Seeds 12 options (Base/Wall/Tall variants)

3. `2025_10_04_123502_create_tcs_cabinet_products_with_attributes.php`
   - Creates single "Cabinet" product ($168 base)
   - Maps all 9 attributes to product

4. `2025_10_04_124625_create_projects_cabinet_specifications_table.php`
   - Creates specifications table
   - Links variants to actual dimensions
   - Stores custom fabrication details

### Models Needed (To Create)
```php
// app/Models/CabinetSpecification.php
class CabinetSpecification extends Model
{
    protected $table = 'projects_cabinet_specifications';

    // Relationships
    public function orderLine() { ... }
    public function project() { ... }
    public function productVariant() { ... }
    public function creator() { ... }

    // Calculated attributes
    public function getLinearFeetAttribute() {
        return $this->length_inches / 12;
    }

    public function getTotalPriceAttribute() {
        return $this->unit_price_per_lf
            × $this->linear_feet
            × $this->quantity;
    }

    // Auto-generate cut list (future)
    public function generateCutList() { ... }
    public function generateMaterialList() { ... }
}
```

---

## UI Components Needed

### 1. Cabinet Variant Selector (For Bryan)
**Location:** Quote/Order creation form
**Component:** `CabinetVariantSelector.php`

```php
// Visual card-based selector
┌─────────────────────────────────────┐
│ CABINET TYPE                        │
│ ┌───────┐ ┌───────┐ ┌───────┐      │
│ │ Base  │ │ Wall  │ │ Tall  │      │
│ │ $168  │ │ $145  │ │ $218  │      │
│ └───────┘ └───────┘ └───────┘      │
│                                     │
│ CONSTRUCTION                        │
│ ○ Face Frame  ● Frameless (+$15)   │
│                                     │
│ DOOR STYLE                          │
│ ○ Slab  ● Shaker (+$12)  ○ Raised  │
│                                     │
│ MATERIAL                            │
│ ● Maple Stain (+$22)  ○ MDF  ○ Oak │
│                                     │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │
│ Price/LF: $229                      │
└─────────────────────────────────────┘
```

### 2. Cabinet Dimensions Form
**Location:** After variant selection
**Component:** `CabinetDimensionsForm.php`

```php
┌─────────────────────────────────────┐
│ CABINET DIMENSIONS                  │
│ Length:   [36] inches → 3 LF        │
│ Width:    [24] inches               │
│ Depth:    [12] inches (auto)        │
│ Height:   [30] inches (auto)        │
│                                     │
│ Quantity: [4] cabinets              │
│                                     │
│ HARDWARE (optional)                 │
│ [Blum soft-close hinges, brushed... │
│                                     │
│ CUSTOM NOTES (optional)             │
│ [Extra shelf at 15" height...       │
│                                     │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │
│ Total: $2,748                       │
│        ($229/LF × 3LF × 4 cabinets) │
└─────────────────────────────────────┘
```

### 3. Cabinet Spec View (For Shop)
**Location:** Production queue
**Component:** `CabinetSpecificationView.php`

```php
// Mobile-friendly for shop floor tablets
┌─────────────────────────────────────┐
│ 📦 Cabinet Spec #127                │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │
│                                     │
│ 📐 DIMENSIONS                       │
│ L: 36" | W: 24" | D: 12" | H: 30"  │
│                                     │
│ 🔨 BUILD SPECS                      │
│ ✓ Frameless Euro                   │
│ ✓ Shaker Doors                     │
│ ✓ Maple Stain                      │
│ ✓ Clear Coat                       │
│                                     │
│ 🔧 HARDWARE                         │
│ - Blum hinges (110°)               │
│ - Brushed nickel pulls             │
│                                     │
│ ⚠️ CUSTOM NOTES                     │
│ Extra shelf at 15" height          │
│ RUSH - Due Friday                  │
│                                     │
│ [View Cut List] [Mark Complete]    │
└─────────────────────────────────────┘
```

---

## Business Rules & Validations

### Rule 1: Dimension Validation by Cabinet Type
```php
// Base Cabinets
if (cabinet_type == 'Base*') {
    depth_inches = 24"  (standard)
    height_inches = 30" (standard)
    length_inches >= 12" and <= 48" (validate)
}

// Wall Cabinets
if (cabinet_type == 'Wall*') {
    depth_inches = 12"  (standard)
    height_inches = 30" or 36" (options)
    length_inches >= 12" and <= 48"
}

// Tall Cabinets
if (cabinet_type == 'Tall*') {
    depth_inches = 24"  (standard)
    height_inches >= 84" and <= 96"
    length_inches >= 18" and <= 36"
}
```

### Rule 2: Auto-Calculate Linear Feet
```php
// Always calculate from length
$linear_feet = $length_inches / 12;

// Round to nearest 0.25 LF for pricing
$linear_feet = ceil($linear_feet * 4) / 4;
```

### Rule 3: Price Inheritance
```php
// When variant selected, copy price to spec
$spec->unit_price_per_lf = $variant->price;
$spec->total_price = $spec->unit_price_per_lf
    × $spec->linear_feet
    × $spec->quantity;
```

### Rule 4: Material Compatibility
```php
// Paint materials can't have stain finish
if ($finish_type == 'Stain Grade') {
    $allowed_materials = ['Red Oak', 'Hard Maple', 'Cherry', 'Walnut'];
}

if ($finish_type == 'Paint Grade') {
    $allowed_materials = ['MDF', 'Hard Maple', 'Poplar'];
}
```

---

## Future Enhancements

### Phase 1 (Immediate - Q1 2025)
- [ ] Create `CabinetSpecification` model
- [ ] Build cabinet variant selector UI
- [ ] Implement dimensions form
- [ ] Auto-calculate pricing
- [ ] Link to order lines

### Phase 2 (Cut List Generation - Q2 2025)
- [ ] Create `projects_cabinet_production_specs` table
- [ ] Build cut list generator from dimensions
- [ ] Material list calculator from variant attributes
- [ ] Shop floor mobile view

### Phase 3 (Full Automation - Q3 2025)
- [ ] Assembly instructions generator
- [ ] CNC program generation
- [ ] Hardware BOM auto-generation
- [ ] Time estimation based on complexity
- [ ] Integration with shop scheduling

### Phase 4 (Advanced - Q4 2025)
- [ ] 3D preview of cabinet from specs
- [ ] Customer-facing cabinet configurator
- [ ] AI-powered cut optimization
- [ ] Real-time material availability check

---

## Success Metrics

### For Bryan (Speed & Simplicity)
- Quote creation time: < 2 minutes (vs 10-15 minutes manual)
- Pricing accuracy: 100% (auto-calculated)
- Clicks to quote: < 10 (visual selectors)

### For Shop Floor (Clarity & Accuracy)
- Spec interpretation errors: 0 (clear dimensional data)
- Material waste: < 5% (optimized cut lists)
- Rework due to spec confusion: 0

### For Business (Efficiency & Profitability)
- Quote-to-order conversion: +20%
- Production time per cabinet: -15%
- Material cost accuracy: ±2%
- Customer satisfaction: +25%

---

## Conclusion

This 3-tier architecture cleanly separates concerns:

1. **Product Catalog (Tier 1)** = What to sell (variants with pricing)
2. **Cabinet Specifications (Tier 2)** = What to build (dimensions + details)
3. **Production Details (Tier 3)** = How to build (cut lists + instructions)

**Benefits:**
- ✅ Bryan gets fast, visual cabinet quoting
- ✅ Shop gets clear, unambiguous fabrication specs
- ✅ System auto-calculates pricing and materials
- ✅ Scales from simple to complex cabinets
- ✅ Supports custom dimensions per order
- ✅ Future-ready for cut list automation

**Next Steps:**
1. Create CabinetSpecification model
2. Build cabinet variant selector UI
3. Test with real TCS cabinet quote
4. Iterate based on Bryan's feedback
5. Deploy to production

---

**Status:** ✅ Database architecture complete
**Next Action:** Build UI components for cabinet quoting workflow
