# Product Variant Migration Strategy - TCS Woodwork

## Database Schema Overview

### Variant System Architecture

```
products_products (parent_id field)
    └─> products_product_attributes (product → attribute link)
            └─> products_attributes (attribute definitions)
                    └─> products_attribute_options (attribute values)
                            └─> products_product_attribute_values (variant → value link)
```

### Key Tables

1. **products_products**
   - `parent_id` → Links variant to parent product
   - Variants have `parent_id` set, parent products have `parent_id = NULL`

2. **products_attributes**
   - Defines attribute types (Grit, Length, Size, Color, etc.)
   - `type` field → attribute data type

3. **products_attribute_options**
   - Values for each attribute (80, 120, 150 for Grit)
   - `extra_price` → additional cost for this option
   - `color` → visual indicator for UI

4. **products_product_attributes**
   - Links parent products to attributes they use
   - One parent can have multiple attributes

5. **products_product_attribute_values**
   - Links variant products to their specific attribute values
   - Defines which combination of values each variant has

## Product Type Attribute Strategy

### 1. Sanding Products

#### Sanding Discs
**Attributes:**
- **Grit** (primary) → Values: 40, 60, 80, 100, 120, 150, 180, 220, 320
- **Size** (secondary) → Values: 5", 6", 8"
- **Type** (tertiary) → Values: PSA (Adhesive), Hook & Loop, No Backing

**Current Products to Consolidate:**
- Product 14: Serious Grit 6-Inch 120 Grit → Variant
- Product 15: Serious Grit 6-Inch 80 Grit → Variant
- **Parent**: "Serious Grit 6-Inch Ceramic Sanding Discs"

#### Sanding Rolls
**Attributes:**
- **Grit** (primary) → Values: 80, 120, 150, 180, 220
- **Width** (secondary) → Values: 2.75", 3", 4"
- **Length** (tertiary) → Values: 10 yards, 20 yards, 30 yards

**Current Products:**
- Product 3: Serious Grit 120 Grit 2.75" x 20 Yard Roll (standalone for now)

### 2. Hardware Products

#### Bungee Cords
**Attributes:**
- **Length** (primary) → Values: 24", 36", 48", 60", 72", 80", 96"
- **Pack Size** (secondary) → Values: 2-pack, 4-pack, 6-pack
- **Hook Type** (tertiary) → Values: Standard, Carabiner, D-Ring

**Current Products to Consolidate:**
- Product 9: 80" Bungee (4-pack) → Variant
- Product 10: 96" Bungee (2-pack) → Variant
- Product 12: 72" Bungee (6-pack) → Variant
- **Parent**: "Heavy Duty Carabiner Bungee Cords"

#### Hinges
**Attributes:**
- **Type** (primary) → Values: CLIP top, CLIP top BLUMOTION, Inserta
- **Opening Angle** (secondary) → Values: 95°, 107°, 110°, 120°
- **Mounting** (tertiary) → Values: Face Frame, Frameless, Inset

**Current Products:**
- Product 1: CLIP top BLUMOTION Hinge (can add variants as needed)

### 3. Office/Shop Supplies

#### Printer Cartridges
**Attributes:**
- **Model Number** (primary) → Values: 812, 822, T812XXL
- **Color** (secondary) → Values: Black, Cyan, Magenta, Yellow, Multi-pack
- **Capacity** (tertiary) → Values: Standard, High Capacity, Extra-High Capacity

**Current Products:**
- Product 2: EPSON 812 (standalone for now, create variants if more EPSON products added)

#### Label Tape
**Attributes:**
- **Width** (primary) → Values: 6mm, 9mm, 12mm, 18mm, 24mm
- **Color** (secondary) → Values: White, Clear, Yellow, Blue
- **Text Color** (tertiary) → Values: Black, Red, Blue

**Current Products:**
- Product 5: Label Tape 24mm (standalone for now)

### 4. CNC/Tools

#### Tool Holders
**Attributes:**
- **Standard** (primary) → Values: ISO30, ISO40, CAT40, BT30
- **Length** (secondary) → Values: Standard, Extended, Stubby
- **Type** (tertiary) → Values: Straight, ER Collet, Shell Mill

**Current Products:**
- Product 8: ISO30 Tool Holder (standalone for now)

## Migration Phases

### Phase 1: Create Global Attributes (Do Once)

Create attribute definitions that can be reused across products:

```sql
-- Grit attribute (for all sanding products)
INSERT INTO products_attributes (name, type, sort, creator_id, created_at, updated_at)
VALUES ('Grit', 'select', 1, 1, NOW(), NOW());

-- Length attribute (for bungee cords, etc.)
INSERT INTO products_attributes (name, type, sort, creator_id, created_at, updated_at)
VALUES ('Length', 'select', 2, 1, NOW(), NOW());

-- Size attribute (for discs, etc.)
INSERT INTO products_attributes (name, type, sort, creator_id, created_at, updated_at)
VALUES ('Size', 'select', 3, 1, NOW(), NOW());

-- Pack Size attribute
INSERT INTO products_attributes (name, type, sort, creator_id, created_at, updated_at)
VALUES ('Pack Size', 'select', 4, 1, NOW(), NOW());

-- Width attribute
INSERT INTO products_attributes (name, type, sort, creator_id, created_at, updated_at)
VALUES ('Width', 'select', 5, 1, NOW(), NOW());

-- Color attribute
INSERT INTO products_attributes (name, type, sort, creator_id, created_at, updated_at)
VALUES ('Color', 'select', 6, 1, NOW(), NOW());

-- Type attribute (generic for various product types)
INSERT INTO products_attributes (name, type, sort, creator_id, created_at, updated_at)
VALUES ('Type', 'select', 7, 1, NOW(), NOW());

-- Standard attribute (for tool holders)
INSERT INTO products_attributes (name, type, sort, creator_id, created_at, updated_at)
VALUES ('Standard', 'select', 8, 1, NOW(), NOW());
```

### Phase 2: Create Attribute Options

Create value options for each attribute:

```sql
-- Grit options
INSERT INTO products_attribute_options (name, attribute_id, sort, creator_id, created_at, updated_at)
VALUES
    ('40', (SELECT id FROM products_attributes WHERE name='Grit'), 1, 1, NOW(), NOW()),
    ('60', (SELECT id FROM products_attributes WHERE name='Grit'), 2, 1, NOW(), NOW()),
    ('80', (SELECT id FROM products_attributes WHERE name='Grit'), 3, 1, NOW(), NOW()),
    ('100', (SELECT id FROM products_attributes WHERE name='Grit'), 4, 1, NOW(), NOW()),
    ('120', (SELECT id FROM products_attributes WHERE name='Grit'), 5, 1, NOW(), NOW()),
    ('150', (SELECT id FROM products_attributes WHERE name='Grit'), 6, 1, NOW(), NOW()),
    ('180', (SELECT id FROM products_attributes WHERE name='Grit'), 7, 1, NOW(), NOW()),
    ('220', (SELECT id FROM products_attributes WHERE name='Grit'), 8, 1, NOW(), NOW());

-- Length options for bungee cords
INSERT INTO products_attribute_options (name, attribute_id, sort, creator_id, created_at, updated_at)
VALUES
    ('24"', (SELECT id FROM products_attributes WHERE name='Length'), 1, 1, NOW(), NOW()),
    ('36"', (SELECT id FROM products_attributes WHERE name='Length'), 2, 1, NOW(), NOW()),
    ('48"', (SELECT id FROM products_attributes WHERE name='Length'), 3, 1, NOW(), NOW()),
    ('60"', (SELECT id FROM products_attributes WHERE name='Length'), 4, 1, NOW(), NOW()),
    ('72"', (SELECT id FROM products_attributes WHERE name='Length'), 5, 1, NOW(), NOW()),
    ('80"', (SELECT id FROM products_attributes WHERE name='Length'), 6, 1, NOW(), NOW()),
    ('96"', (SELECT id FROM products_attributes WHERE name='Length'), 7, 1, NOW(), NOW());

-- Pack Size options
INSERT INTO products_attribute_options (name, attribute_id, sort, creator_id, created_at, updated_at)
VALUES
    ('2-pack', (SELECT id FROM products_attributes WHERE name='Pack Size'), 1, 1, NOW(), NOW()),
    ('4-pack', (SELECT id FROM products_attributes WHERE name='Pack Size'), 2, 1, NOW(), NOW()),
    ('6-pack', (SELECT id FROM products_attributes WHERE name='Pack Size'), 3, 1, NOW(), NOW()),
    ('10-pack', (SELECT id FROM products_attributes WHERE name='Pack Size'), 4, 1, NOW(), NOW());
```

### Phase 3: Consolidate Existing Products

#### Example: Sanding Discs (Products 14 & 15)

**Step 1: Choose Parent Product**
- Keep Product 14 as parent
- Update name to be more generic

```sql
UPDATE products_products
SET name = 'Serious Grit 6-Inch Ceramic Sanding Discs',
    reference = 'SG-6IN-CERAMIC',
    barcode = NULL
WHERE id = 14;
```

**Step 2: Link Parent to Attribute**

```sql
-- Link parent product to "Grit" attribute
INSERT INTO products_product_attributes (product_id, attribute_id, creator_id, created_at, updated_at)
VALUES (14, (SELECT id FROM products_attributes WHERE name='Grit'), 1, NOW(), NOW());
```

**Step 3: Convert Product 14 to Variant (120 Grit)**

```sql
-- Create variant for 120 grit
INSERT INTO products_products
    (parent_id, type, name, reference, barcode, price, cost, uom_id, uom_po_id,
     category_id, enable_purchase, enable_sales, company_id, creator_id, created_at, updated_at)
SELECT
    14 as parent_id,  -- parent is product 14
    type,
    CONCAT(name, ' - 120 Grit') as name,
    'AMZ-B0D4S6XP21' as reference,  -- keep original ASIN
    'B0D4S6XP21' as barcode,
    price, cost, uom_id, uom_po_id, category_id, enable_purchase, enable_sales,
    company_id, creator_id, NOW(), NOW()
FROM products_products
WHERE id = 14;

SET @variant_120_id = LAST_INSERT_ID();

-- Link variant to Grit=120 attribute value
INSERT INTO products_product_attribute_values
    (product_id, attribute_id, product_attribute_id, attribute_option_id)
VALUES (
    @variant_120_id,
    (SELECT id FROM products_attributes WHERE name='Grit'),
    (SELECT id FROM products_product_attributes WHERE product_id=14 AND attribute_id=(SELECT id FROM products_attributes WHERE name='Grit')),
    (SELECT id FROM products_attribute_options WHERE name='120' AND attribute_id=(SELECT id FROM products_attributes WHERE name='Grit'))
);

-- Move vendor pricing to variant
UPDATE products_product_suppliers
SET product_id = @variant_120_id
WHERE product_id = 14;
```

**Step 4: Convert Product 15 to Variant (80 Grit)**

```sql
-- Update product 15 to be variant of product 14
UPDATE products_products
SET parent_id = 14,
    name = 'Serious Grit 6-Inch Ceramic Sanding Discs - 80 Grit'
WHERE id = 15;

-- Link variant to Grit=80 attribute value
INSERT INTO products_product_attribute_values
    (product_id, attribute_id, product_attribute_id, attribute_option_id)
VALUES (
    15,
    (SELECT id FROM products_attributes WHERE name='Grit'),
    (SELECT id FROM products_product_attributes WHERE product_id=14 AND attribute_id=(SELECT id FROM products_attributes WHERE name='Grit')),
    (SELECT id FROM products_attribute_options WHERE name='80' AND attribute_id=(SELECT id FROM products_attributes WHERE name='Grit'))
);
```

## Implementation Plan

### Immediate Actions (High Priority)

1. **Create Global Attributes Migration**
   - Create all reusable attributes
   - Create common attribute options
   - Run once, benefits all future products

2. **Consolidate Sanding Discs**
   - Products 14 & 15 → Parent + 2 variants
   - Clear business value (same product family)

3. **Consolidate Bungee Cords**
   - Products 9, 10, 12 → Parent + 3 variants
   - Clear variants by length

### Future Actions (Medium Priority)

4. **Add Variant Detection to Import Process**
   - Modify import migrations to detect variant patterns
   - Auto-create attributes and variants during import

5. **Create Variant Templates**
   - Pre-defined attribute sets for common product types
   - Woodworking supplies
   - Hardware
   - Office supplies

### Long-term Strategy (Low Priority)

6. **Variant Pricing Rules**
   - Use `extra_price` field for premium variants
   - E.g., finer grit = slightly higher price

7. **Vendor Variants**
   - Some vendors offer same product with different options
   - Link all variants to appropriate vendor URLs

## Best Practices

### Naming Conventions

- **Parent Product**: Generic, covers all variants
  - ✅ "Serious Grit 6-Inch Ceramic Sanding Discs"
  - ❌ "Serious Grit 6-Inch 120 Grit Ceramic Sanding Discs"

- **Variant Product**: Include variant-specific detail
  - ✅ "Serious Grit 6-Inch Ceramic Sanding Discs - 120 Grit"
  - ✅ "Heavy Duty Bungee Cords - 80\" - 4 Pack"

- **Reference Codes**:
  - Parent: Generic code (SG-6IN-CERAMIC)
  - Variants: Original ASINs/SKUs (AMZ-B0D4S6XP21)

### Data Preservation

- **Always preserve**:
  - Original ASINs/barcodes (in variant)
  - Vendor pricing (link to variant, not parent)
  - Vendor URLs (variant-specific)
  - Tags (can be on parent or variant)

- **Share at parent level**:
  - Category
  - Description
  - Images (optionally override on variant)
  - Common tags

### Inventory Tracking

- Parent products are NOT stocked
- Each variant has own inventory
- Parent serves as organizational/UI container

## Database Migration Files

### 2025_10_01_170000_create_product_attributes.php
Creates all global attributes and their options

### 2025_10_01_171000_consolidate_sanding_disc_variants.php
Converts products 14 & 15 into parent + variants

### 2025_10_01_172000_consolidate_bungee_cord_variants.php
Converts products 9, 10, 12 into parent + variants

## Rollback Strategy

⚠️ **Warning**: FilamentPHP warns that changing attributes deletes and recreates variants.

**Safe Rollback**:
1. Export variant data (ASIN, pricing, vendor info)
2. Convert variants back to standalone products
3. Restore all data
4. Delete parent product
5. Remove attribute links

**Automated Rollback**:
Each migration's `down()` method should:
1. Convert variants back to standalone products
2. Restore original names, references
3. Clean up attribute links
4. Preserve vendor pricing

## Success Metrics

- ✅ Reduced product count (15 products → 8 parents + 10 variants)
- ✅ Easier inventory management
- ✅ Better customer UX (select options instead of searching)
- ✅ Consistent naming
- ✅ Scalable for future imports

## Pre-Import Variant Detection Strategy

### Problem Statement

Products imported separately (from Amazon orders CSV, migration spreadsheets) need to be identified as variants BEFORE import to preserve:
- Original ASINs/product codes
- Vendor-specific pricing
- Vendor URLs
- Stock/inventory data per variant

### Detection Methodology

**Step 1: Analyze Source Data**

Scan product names/descriptions for variant indicators:
- **Grit variations**: "80 Grit", "120 Grit", "150 Grit"
- **Size variations**: "6-inch", "8-inch", "5\""
- **Length variations**: "72\"", "80\"", "96\""
- **Pack size variations**: "2-pack", "4-pack", "6-pack"
- **Color variations**: "Black", "White", "Red"
- **Capacity variations**: "Standard", "High Capacity", "Extra-High"

**Step 2: Group Similar Products**

Products are variant candidates if they share:
- Same base product name (after removing variant indicators)
- Same brand
- Same category
- Same general use case

Example:
```
Original: "Serious Grit 6-Inch 120 Grit Ceramic Sanding Discs..."
Original: "Serious Grit 6-Inch 80 Grit Ceramic Sanding Discs..."
Base: "Serious Grit 6-Inch Ceramic Sanding Discs"
Variant indicator: Grit (120 vs 80)
```

**Step 3: Create Variant Import Rules**

For each detected variant group:
1. Create parent product with generic name
2. Create child products with specific variant values
3. Preserve original ASIN/code on child
4. Link vendor pricing to child (not parent)
5. Set appropriate attribute values

### Spreadsheet Analysis Needed

**Amazon Orders CSV** (`orders_from_20250901_to_20250930_20250930_0935.csv`):
- Scan for products with similar names but different specs
- Group by base product name
- Identify variant attributes (grit, size, length, pack size)

**Migration Spreadsheet** (Richelieu/other vendors):
- Same process as Amazon
- Additional vendor-specific codes may help identify variants

### Implementation: Pre-Import Variant Detection Script

Create `analyze-variant-candidates.php`:
```php
// Parse both spreadsheets
// Group products by similarity
// Output variant groups with suggested attributes
// Generate import mapping file
```

Output format:
```json
{
  "variant_groups": [
    {
      "parent_name": "Serious Grit 6-Inch Ceramic Sanding Discs",
      "attribute": "Grit",
      "products": [
        {
          "original_name": "Serious Grit 6-Inch 120 Grit...",
          "asin": "B0D4S6XP21",
          "attribute_value": "120",
          "price": 37.99
        },
        {
          "original_name": "Serious Grit 6-Inch 80 Grit...",
          "asin": "B0D4RB41NV",
          "attribute_value": "80",
          "price": 37.99
        }
      ]
    }
  ]
}
```

## Next Steps

1. ✅ Review this strategy document
2. ✅ Create attributes migration
3. ⏳ **Analyze source spreadsheets for variant candidates**
4. ⏳ **Create pre-import variant detection script**
5. ⏳ **Update import migrations to use variant mapping**
6. ⏳ Test with sanding discs first
7. ⏳ Validate in FilamentPHP UI
8. ⏳ Roll out to other product groups

---

**Document Version**: 1.0
**Last Updated**: September 30, 2025
**Author**: Claude Code AI Assistant
