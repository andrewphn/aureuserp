# MASTER IMPORT SPECIFICATION
## TCS Woodwork - AureusERP Product Import System

**Document Version:** 1.0
**Created:** 2025-09-30
**Purpose:** Complete field mapping and requirements for importing products from multiple sources

---

## TABLE OF CONTENTS

1. [Executive Summary](#1-executive-summary)
2. [Data Sources](#2-data-sources)
3. [Database Schema Requirements](#3-database-schema-requirements)
4. [Field Mapping Matrix](#4-field-mapping-matrix)
5. [Category Mapping Logic](#5-category-mapping-logic)
6. [Tag Assignment Logic](#6-tag-assignment-logic)
7. [Vendor/Partner Requirements](#7-vendorpartner-requirements)
8. [Import Workflow](#8-import-workflow)
9. [Validation Rules](#9-validation-rules)
10. [Migration Sequence](#10-migration-sequence)

---

## 1. EXECUTIVE SUMMARY

### 1.1 Import Goal
Import **~50 products** from two data sources into AureusERP with complete categorization, tagging, and vendor pricing information.

### 1.2 Data Sources
- **Source A:** Amazon Orders CSV (14 products)
- **Source B:** Existing Database Migrations (35 products)
- **Total:** ~50 products with full vendor pricing

### 1.3 Success Criteria
✅ All products have required fields populated
✅ All products assigned to correct categories
✅ All products tagged appropriately
✅ All products linked to vendor pricing
✅ All vendor URLs populated where applicable
✅ No orphaned or incomplete records

---

## 2. DATA SOURCES

### 2.1 Amazon Orders CSV

**File:** `orders_from_20250901_to_20250930_20250930_0935.csv`
**Products:** 14 unique items
**Date Range:** September 2025
**Account:** The Carpenter's Son Fine Woodworking

**Key Columns Used for Import:**

| Col | Field Name | Purpose | Example Value |
|-----|------------|---------|---------------|
| 29 | ASIN | Primary product ID | B08DX5F6XV |
| 30 | Title | Product name | "EPSON 812 DURABrite..." |
| 37 | Brand | Manufacturer | Epson |
| 41 | Part number | Mfr part number | T812XXL120-S |
| 45 | Purchase PPU | Unit price paid | 82.49 |
| 46 | Item Quantity | Qty ordered | 1 |
| 47 | Item Subtotal | Line total | 82.49 |
| 28 | Amazon Category | Category hint | "Office Product" |
| 31-35 | UNSPSC | Classification codes | 44103105 |
| 69 | Seller Name | Seller (ignore, use Amazon) | "Amazon.com" |

### 2.2 Database Migrations (Existing)

**Source:** `database/migrations/2025_09_30_160000_seed_tcs_vendors_and_prices.php`
**Products:** 35 vendor pricing records
**Status:** Already in `products_product_suppliers` table but NOT linked to products

**Vendors:**
- Richelieu Hardware: 23 products (CAD)
- Serious Grit: 3 products (USD)
- Amana Tool Corporation: 4 products (USD)
- YUEERIO: 1 product (USD)
- Others: 4 configured vendors (0 products)

---

## 3. DATABASE SCHEMA REQUIREMENTS

### 3.1 Required Fields by Table

#### products_products (Main Product Table)

**MUST PROVIDE (5 fields minimum):**
```
type          → string ('goods' or 'service')
name          → string (max 255)
uom_id        → foreignId (unit of measure)
uom_po_id     → foreignId (purchase order UOM)
category_id   → foreignId (product category)
```

**RECOMMENDED (additional 4 fields):**
```
reference     → string (internal SKU) [unique]
barcode       → string (ASIN or barcode) [unique]
price         → decimal (sales price, default 0)
cost          → decimal (cost price, default 0)
```

**OPTIONAL (enhancement fields):**
```
description              → text (rich text)
description_purchase     → text
description_sale         → text
enable_sales            → boolean (default null)
enable_purchase         → boolean (default null)
weight                  → decimal
volume                  → decimal
company_id              → foreignId (optional)
```

#### products_product_suppliers (Vendor Pricing)

**MUST PROVIDE (2 fields):**
```
partner_id    → foreignId (vendor/supplier)
currency_id   → foreignId (USD=1, CAD=3)
```

**AUTO-DEFAULT (can omit):**
```
delay         → integer (default 0, lead time in days)
min_qty       → decimal (default 0)
price         → decimal (default 0)
discount      → decimal (default 0)
```

**RECOMMENDED (additional fields):**
```
product_id    → foreignId (link to product) [nullable]
product_code  → string (vendor SKU/ASIN)
starts_at     → date (price valid from)
ends_at       → date (price valid until)
vendor_url    → text (product page URL) [TO BE ADDED]
```

#### products_categories (Category Assignment)

**Structure:** Direct foreign key (NOT pivot table)
- Each product belongs to ONE category via `products_products.category_id`
- Categories have parent/child hierarchy

**Existing Categories:**
```
Hardware → Hinges, Clips, Drawer Slides, Storage Systems
Fasteners → Screws, Nails
Adhesives → Glue, Epoxy, Edge Banding Adhesive
Sanding → Discs, Sheets, Rolls, Various Grits
Edge Banding → Wood Veneer, Unfinished
CNC → Router Bits, Specialty Bits
Shop Supplies → Dust Collection, Safety Equipment, Cleaning Supplies
Tools → Drill Bits, Measurement Tools, CNC Parts
Maintenance → Lubricants, Machine Oil, Grease
Blades → Saw Blades, Planer Blades, Jointer Blades
Office Supplies → Printer Cartridges, Paper Products, Writing Supplies, Computer Accessories
Shop Consumables → Toilet Paper, Paper Towels, Cleaning Products, First Aid
```

#### products_product_tag (Tag Assignment)

**Structure:** Many-to-many pivot table
- Products can have multiple tags
- Tags include vendor, type, material, application, status

**Existing Tags (69 total):**
```
Vendors:    Richelieu, Serious Grit, Amana Tool, YUEERIO, Felder, Festool, Amazon
Types:      Hinge, Clip, Drawer Slide, Screw, Nail, Glue, Sanding Disc, etc.
Materials:  Metal, Wood, Plastic, Steel, Aluminum, Brass, Composite
Status:     Consumable, Reorderable, High Priority, Low Stock
Apps:       Cabinet Hardware, CNC, Woodworking, Finishing, Office Supplies
```

### 3.2 Foreign Key Dependencies

**MUST EXIST BEFORE IMPORT:**
1. `currencies` table: USD (id=1), CAD (id=3)
2. `unit_of_measures` table: At least one UOM (e.g., "Each")
3. `unit_of_measure_categories` table: At least one category
4. `products_categories` table: All target categories
5. `products_tags` table: All target tags
6. `partners_partners` table: All vendors/suppliers
7. `companies` table: TCS company record

**Current Status:** ✅ All prerequisites exist in database

---

## 4. FIELD MAPPING MATRIX

### 4.1 Amazon CSV → products_products

| CSV Column | CSV Field | → | DB Field | Transformation | Example |
|------------|-----------|---|----------|----------------|---------|
| 29 | ASIN | → | barcode | Direct copy | B08DX5F6XV |
| 29 | ASIN | → | reference | Prefix: "AMZ-" | AMZ-B08DX5F6XV |
| 30 | Title | → | name | Truncate to 255 chars | "EPSON 812 DURABrite..." |
| 45 | Purchase PPU | → | cost | Parse decimal | 82.49 |
| 45 | Purchase PPU | → | price | Same as cost | 82.49 |
| - | - | → | type | Hard-code: 'goods' | goods |
| - | - | → | uom_id | Lookup: "Each" | 1 |
| - | - | → | uom_po_id | Same as uom_id | 1 |
| 28, 31-35 | Amazon Cat + UNSPSC | → | category_id | Logic mapping (see 5.1) | [ID] |
| 1 | Order Date | → | created_at | Parse MM/DD/YYYY | 2025-09-30 |
| - | - | → | enable_purchase | Hard-code: true | true |
| - | - | → | enable_sales | Hard-code: false | false |
| - | - | → | company_id | Lookup: TCS company | 1 |
| - | - | → | creator_id | Lookup: info@tcswoodwork.com | 1 |

### 4.2 Amazon CSV → products_product_suppliers

| CSV Column | CSV Field | → | DB Field | Transformation | Example |
|------------|-----------|---|----------|----------------|---------|
| - | - | → | partner_id | Lookup: "Amazon Business" | [ID] |
| 29 | ASIN | → | product_code | Direct copy | B08DX5F6XV |
| 45 | Purchase PPU | → | price | Parse decimal | 82.49 |
| 6 | Currency | → | currency_id | Lookup: "USD" | 1 |
| 1 | Order Date | → | starts_at | Parse date | 2025-09-30 |
| 1 | Order Date | → | ends_at | Add 1 year | 2026-09-30 |
| - | - | → | delay | Hard-code: 2 days | 2 |
| 29 | ASIN | → | vendor_url | Construct: amazon.com/dp/{ASIN} | https://amazon.com/dp/B08DX5F6XV |
| [product_id] | - | → | product_id | Link after product creation | [ID] |
| - | - | → | company_id | Lookup: TCS company | 1 |

### 4.3 Migration Data → products_products

| Migration Field | → | DB Field | Transformation | Example |
|----------------|---|----------|----------------|---------|
| product_name | → | name | Direct copy | "CLIP top BLUMOTION Hinge..." |
| product_code | → | barcode | Direct copy | 79B959180 |
| product_code | → | reference | Prefix: "TC-" | TC-79B959180 |
| price | → | cost | Direct copy | 8.25 |
| price | → | price | Same as cost | 8.25 |
| - | → | type | Hard-code: 'goods' | goods |
| - | → | uom_id | Lookup: "Each" | 1 |
| - | → | uom_po_id | Same as uom_id | 1 |
| [inferred] | → | category_id | Logic from product name | [ID] |
| - | → | enable_purchase | Hard-code: true | true |
| - | → | enable_sales | Hard-code: true | true |
| - | → | company_id | Lookup: TCS company | 1 |

### 4.4 Migration Data → products_product_suppliers

| Migration Field | → | DB Field | Transformation | Example |
|----------------|---|----------|----------------|---------|
| partner_id | → | partner_id | Already set | [Richelieu ID] |
| product_code | → | product_code | Already set | 79B959180 |
| price | → | price | Already set | 8.25 |
| currency_id | → | currency_id | Already set (fixed by migration) | 3 (CAD) |
| - | → | starts_at | Current date | 2025-09-30 |
| - | → | ends_at | Current date + 1 year | 2026-09-30 |
| delay | → | delay | Already set | 2 |
| [construct] | → | vendor_url | Build from vendor + code | https://richelieu.com/.../79B959180 |
| [product_id] | → | product_id | Link after creation | [ID] |
| company_id | → | company_id | Update if NULL | 1 |

---

## 5. CATEGORY MAPPING LOGIC

### 5.1 Amazon Products → Categories

**Method:** Multi-factor analysis
1. Product name keywords (highest priority)
2. Amazon internal category
3. UNSPSC classification codes
4. Brand-based hints

**Mapping Rules:**

| Product Pattern | → | Category | Confidence |
|----------------|---|----------|------------|
| Title contains "ink" OR "cartridge" | → | Office Supplies / Printer Cartridges | HIGH |
| Title contains "sanding disc" | → | Sanding / Discs | HIGH |
| Title contains "sanding roll" OR "sandpaper roll" | → | Sanding / Rolls | HIGH |
| Title contains "label" OR "tape" (office context) | → | Office Supplies / Writing Supplies | HIGH |
| Title contains "tramming" OR "calibration" | → | Tools / Measurement Tools | MEDIUM |
| Title contains "table stiffener" | → | Hardware / Storage Systems | MEDIUM |
| Title contains "clamp" AND (router OR tool) | → | Tools / CNC Parts | MEDIUM |
| Title contains "ISO30" OR "tool holder" | → | CNC / CNC Parts | HIGH |
| Title contains "bungee" OR "d-ring" | → | Shop Supplies / Safety Equipment | MEDIUM |
| Title contains "dust collector" OR "dust collection" | → | Shop Supplies / Dust Collection | HIGH |
| Amazon Cat = "Office Product" | → | Office Supplies / [subcategory] | LOW |
| Amazon Cat = "Home Improvement" | → | Shop Supplies / [subcategory] | LOW |
| UNSPSC Segment = "44103105" (Office) | → | Office Supplies / [subcategory] | MEDIUM |
| Default (no match) | → | Shop Supplies / General | FALLBACK |

**Specific Amazon Product Mappings:**

| ASIN | Product Name | → | Category |
|------|--------------|---|----------|
| B08DX5F6XV | EPSON 812 Ink | → | Office Supplies / Printer Cartridges |
| B0BYTMRKDY | Serious Grit PSA Roll | → | Sanding / Rolls |
| B07D84Y1ZD | SST Tramming System | → | Tools / Measurement Tools |
| B0F6MPF69J | Labelife Label Tape | → | Office Supplies / Writing Supplies |
| B01DOZDUEK | Regency Table Stiffner | → | Hardware / Storage Systems |
| B00Y0JIGSK | DEWALT Sub Base Clamp | → | Tools / CNC Parts |
| B07WLCT6F4 | HOZLY ISO30 Clamp | → | CNC / CNC Parts |
| B0BZL4PL9M, B0BZL49345 | Bungee Cords | → | Shop Supplies / Safety Equipment |
| B0B8HXJB48 | D-Rings Tie Down | → | Shop Supplies / Safety Equipment |
| B0DLH1HRJZ | VBEST Bungee Cords | → | Shop Supplies / Safety Equipment |
| B09QCKYL7K | O'SKOOL Dust Switch | → | Shop Supplies / Dust Collection |
| B0D4S6XP21 | Serious Grit 120 Grit Disc | → | Sanding / Discs |
| B0D4RB41NV | Serious Grit 80 Grit Disc | → | Sanding / Discs |

### 5.2 Migration Products → Categories

**Method:** Product name keyword analysis

**Mapping Rules:**

| Product Name Pattern | → | Category |
|---------------------|---|----------|
| Contains "hinge" | → | Hardware / Hinges |
| Contains "clip" (hardware context) | → | Hardware / Clips |
| Contains "drawer slide" OR "tandem" OR "movento" | → | Hardware / Drawer Slides |
| Contains "rev-a-shelf" OR "LeMans" | → | Hardware / Storage Systems |
| Contains "screw" | → | Fasteners / Screws |
| Contains "nail" OR "brad" OR "pin" | → | Fasteners / Nails |
| Contains "glue" OR "titebond" | → | Adhesives / Glue |
| Contains "epoxy" | → | Adhesives / Epoxy |
| Contains "hot melt" OR "jowatherm" | → | Adhesives / Edge Banding Adhesive |
| Contains "edge band" OR "edgebanding" | → | Edge Banding / Wood Veneer |
| Contains "sanding disc" | → | Sanding / Discs |
| Contains "sanding sheet" | → | Sanding / Sheets |
| Contains "sanding roll" | → | Sanding / Rolls |
| Contains "router bit" | → | CNC / Router Bits |
| Contains "dust" AND "bag" | → | Shop Supplies / Dust Collection |

---

## 6. TAG ASSIGNMENT LOGIC

### 6.1 Automatic Tag Assignment

**For Each Product, Assign:**

#### 6.1.1 Vendor/Brand Tag (REQUIRED)
- Amazon products → "Amazon"
- Richelieu products → "Richelieu"
- Serious Grit products → "Serious Grit"
- Amana Tool products → "Amana Tool"
- YUEERIO products → "YUEERIO"

#### 6.1.2 Product Type Tags (CONDITIONAL)
Based on product name/category:
- Hinge → "Hinge"
- Clip → "Clip"
- Drawer Slide → "Drawer Slide"
- Screw → "Screw"
- Nail → "Nail"
- Glue → "Glue"
- Sanding Disc → "Sanding Disc"
- Sanding Sheet → "Sanding Sheet"
- Sanding Roll → "Sanding Roll"
- Router Bit → "Router Bit"
- Drill Bit → "Drill Bit"
- Edge Banding → "Edge Banding"

#### 6.1.3 Material Tags (CONDITIONAL)
Based on product name:
- Contains "steel" → "Steel"
- Contains "aluminum" → "Aluminum"
- Contains "brass" → "Brass"
- Contains "metal" → "Metal"
- Contains "wood" → "Wood"
- Contains "plastic" → "Plastic"

#### 6.1.4 Characteristic Tags (CONDITIONAL)
- Consumable items (sanding, adhesives, fasteners) → "Consumable"
- All products → "Reorderable" (default)

#### 6.1.5 Application Tags (CONDITIONAL)
- Richelieu products → "Cabinet Hardware"
- CNC products → "CNC"
- Woodworking products → "Woodworking"
- Office products → "Office Supplies"
- Shop supplies → "Shop Supplies"

### 6.2 Tag Assignment Matrix

| Product | Vendor Tag | Type Tags | Material Tags | Characteristic Tags | Application Tags |
|---------|-----------|-----------|---------------|-------------------|----------------|
| EPSON Ink | Amazon | - | - | Consumable | Office Supplies |
| Serious Grit Disc | Serious Grit | Sanding Disc | - | Consumable | Woodworking |
| CLIP Hinge | Richelieu | Hinge | Metal | Reorderable | Cabinet Hardware |
| Bungee Cord | Amazon | - | - | Reorderable | Shop Supplies |
| Router Bit | Amana Tool | Router Bit | - | Reorderable | CNC, Woodworking |

---

## 7. VENDOR/PARTNER REQUIREMENTS

### 7.1 Required Vendors

**Must Exist Before Import:**

#### Amazon Business
```php
name: "Amazon Business"
account_type: "company"
sub_type: "vendor"
website: "https://business.amazon.com"
currency: USD (id=1)
delivery_lead_time: 2 days
```

#### Richelieu Hardware
```php
name: "Richelieu Hardware"
account_type: "company"
sub_type: "vendor"
address: "7900 Henri-Bourassa West"
city: "Montreal"
state: "QC"
country: "Canada"
website: "https://www.richelieu.com"
currency: CAD (id=3)
delivery_lead_time: 2 days
```

#### Serious Grit
```php
name: "Serious Grit"
account_type: "company"
sub_type: "vendor"
city: "CARLSBAD"
state: "CA"
currency: USD (id=1)
delivery_lead_time: 1 day
```

#### Amana Tool Corporation
```php
name: "Amana Tool Corporation"
account_type: "company"
sub_type: "vendor"
city: "Farmingdale"
state: "NY"
currency: USD (id=1)
delivery_lead_time: 1 day
```

#### YUEERIO
```php
name: "YUEERIO"
account_type: "company"
sub_type: "vendor"
currency: USD (id=1)
delivery_lead_time: 1 day
```

**Current Status:** ✅ All vendors exist in `partners_partners` table

### 7.2 Vendor URL Construction

**Amazon Products:**
```
Format: https://www.amazon.com/dp/{ASIN}
Example: https://www.amazon.com/dp/B08DX5F6XV
```

**Richelieu Products:**
```
Format: https://www.richelieu.com/us/en/category/cabinet-hardware/{product_code}
Example: https://www.richelieu.com/us/en/category/cabinet-hardware/79B959180
```

**Other Vendors:**
```
Leave NULL (can be manually added later)
```

---

## 8. IMPORT WORKFLOW

### 8.1 Pre-Import Phase (Analysis)

**Step 1: Analyze Amazon CSV**
- Parse CSV file
- Extract 14 unique products
- Map to categories using logic
- Generate preliminary JSON: `amazon_product_mapping.json`

**Step 2: Analyze Migration Data**
- Query `products_product_suppliers` table
- Extract 35 vendor pricing records
- Infer product details from pricing data
- Generate preliminary JSON: `migration_product_mapping.json`

**Step 3: Gap Analysis**
- Check if all categories exist
- Check if all tags exist
- Identify missing prerequisites
- Generate report: `import_readiness_report.json`

**Step 4: Human Approval**
- Review category mappings
- Approve tag assignments
- Confirm vendor information
- Sign off on field mappings

### 8.2 Import Execution Phase

**Phase 1: Schema Updates**
```sql
-- Migration 1: Add vendor_url column
ALTER TABLE products_product_suppliers
ADD COLUMN vendor_url TEXT AFTER product_code;
```

**Phase 2: Clean Existing Data**
```sql
-- Migration 2: Remove test/incomplete data
DELETE FROM products_product_suppliers WHERE id != 32;
-- (Keep only the one we manually created for validation)
```

**Phase 3: Import Amazon Products**
```
For each row in amazon_product_mapping.json:
  1. Create product in products_products
  2. Store product_id in mapping
  3. Link to category (insert into products_products.category_id)
  4. Create vendor pricing (insert into products_product_suppliers)
  5. Link tags (insert into products_product_tag pivot)
```

**Phase 4: Import Migration Products**
```
For each row in migration_product_mapping.json:
  1. Create product in products_products
  2. Store product_id in mapping
  3. Link to category
  4. Update existing vendor pricing with product_id
  5. Link tags
```

**Phase 5: Validation**
```
1. Count products created
2. Verify all have vendor pricing
3. Verify all have categories
4. Verify all have tags
5. Check for orphaned records
6. Generate validation report
```

### 8.3 Post-Import Phase

**Step 1: Manual Review**
- Browse products in FilamentPHP admin
- Verify category assignments
- Check vendor URLs work
- Confirm pricing accuracy

**Step 2: Cleanup**
- Delete temporary mapping tables/files
- Remove debug logs
- Archive import specification

**Step 3: Documentation**
- Update product catalog documentation
- Note any manual adjustments made
- Record lessons learned

---

## 9. VALIDATION RULES

### 9.1 Pre-Import Validation

**Check Before Starting:**
- [ ] All vendor records exist in `partners_partners`
- [ ] All categories exist in `products_categories`
- [ ] All tags exist in `products_tags`
- [ ] At least one UOM exists
- [ ] USD and CAD currencies exist
- [ ] TCS company record exists

### 9.2 Per-Product Validation

**For Each Product:**
- [ ] `name` is not empty
- [ ] `name` length ≤ 255 characters
- [ ] `reference` is unique (if provided)
- [ ] `barcode` is unique (if provided)
- [ ] `category_id` references valid category
- [ ] `uom_id` references valid UOM
- [ ] `uom_po_id` references valid UOM
- [ ] `type` is 'goods' or 'service'
- [ ] `price` ≥ 0 (if provided)
- [ ] `cost` ≥ 0 (if provided)

**For Each Vendor Pricing:**
- [ ] `partner_id` references valid partner
- [ ] `currency_id` references valid currency
- [ ] `price` ≥ 0
- [ ] `starts_at` ≤ `ends_at` (if both provided)
- [ ] `vendor_url` is valid URL format (if provided)

**For Each Tag Assignment:**
- [ ] `tag_id` references valid tag
- [ ] `product_id` references valid product
- [ ] No duplicate (product_id, tag_id) pairs

### 9.3 Post-Import Validation

**Verify Counts:**
```sql
-- Expected: ~50 products
SELECT COUNT(*) FROM products_products WHERE reference LIKE 'TC-%' OR reference LIKE 'AMZ-%';

-- Expected: ~50 vendor pricing records
SELECT COUNT(*) FROM products_product_suppliers WHERE product_id IS NOT NULL;

-- Expected: All products have category
SELECT COUNT(*) FROM products_products WHERE category_id IS NULL;
-- Should be 0

-- Expected: All products have at least 2 tags (vendor + reorderable)
SELECT product_id, COUNT(*) as tag_count
FROM products_product_tag
GROUP BY product_id
HAVING tag_count < 2;
-- Should be empty
```

**Verify Relationships:**
```sql
-- Check for orphaned vendor pricing
SELECT * FROM products_product_suppliers WHERE product_id NOT IN (SELECT id FROM products_products);

-- Check for invalid categories
SELECT * FROM products_products WHERE category_id NOT IN (SELECT id FROM products_categories);

-- Check for invalid tags
SELECT * FROM products_product_tag WHERE tag_id NOT IN (SELECT id FROM products_tags);
```

---

## 10. MIGRATION SEQUENCE

### 10.1 Migration Files to Create

```
database/migrations/
├── 2025_09_30_163000_add_vendor_url_to_product_suppliers.php
├── 2025_09_30_164000_cleanup_test_vendor_pricing.php
├── 2025_09_30_165000_seed_amazon_partner.php (optional - if not exists)
├── 2025_09_30_166000_import_amazon_products.php
├── 2025_09_30_167000_import_migration_products.php
└── 2025_09_30_168000_validate_product_import.php
```

### 10.2 Execution Order

```bash
# 1. Add URL column
php artisan migrate --path=database/migrations/2025_09_30_163000_add_vendor_url_to_product_suppliers.php

# 2. Clean test data
php artisan migrate --path=database/migrations/2025_09_30_164000_cleanup_test_vendor_pricing.php

# 3. Ensure Amazon vendor exists
php artisan migrate --path=database/migrations/2025_09_30_165000_seed_amazon_partner.php

# 4. Import Amazon products
php artisan migrate --path=database/migrations/2025_09_30_166000_import_amazon_products.php

# 5. Import migration products
php artisan migrate --path=database/migrations/2025_09_30_167000_import_migration_products.php

# 6. Validate everything
php artisan migrate --path=database/migrations/2025_09_30_168000_validate_product_import.php
```

### 10.3 Rollback Strategy

**Each migration must implement `down()` method:**
```php
public function down(): void
{
    // Remove products created by this migration
    $productIds = DB::table('temp_import_mapping')
        ->where('migration', '2025_09_30_166000')
        ->pluck('product_id');

    // Remove tag links
    DB::table('products_product_tag')
        ->whereIn('product_id', $productIds)
        ->delete();

    // Remove vendor pricing
    DB::table('products_product_suppliers')
        ->whereIn('product_id', $productIds)
        ->delete();

    // Remove products
    DB::table('products_products')
        ->whereIn('id', $productIds)
        ->delete();
}
```

---

## 11. APPROVAL CHECKLIST

### Before Implementation:

- [ ] **Data Sources Confirmed:** Amazon CSV location verified, migration data understood
- [ ] **Field Mappings Approved:** All CSV→DB mappings reviewed and approved
- [ ] **Category Logic Approved:** Category assignment rules validated
- [ ] **Tag Logic Approved:** Tag assignment rules validated
- [ ] **Vendor Info Confirmed:** All vendor records exist and are correct
- [ ] **URL Construction Approved:** URL formats confirmed for Amazon and Richelieu
- [ ] **Validation Rules Approved:** Pre/post validation rules reviewed
- [ ] **Migration Sequence Approved:** Order and structure of migrations confirmed
- [ ] **Rollback Strategy Approved:** Rollback procedures tested and approved

### Sign-Off:

**Prepared By:** Claude Code AI Assistant
**Review Date:** _________________
**Approved By:** _________________
**Approval Date:** _________________

---

## APPENDICES

### Appendix A: Database Schema Diagrams

```
products_products
├── id (PK)
├── type (ENUM: goods, service)
├── name (VARCHAR 255) *required
├── reference (VARCHAR 255) [unique]
├── barcode (VARCHAR 255) [unique]
├── price (DECIMAL)
├── cost (DECIMAL)
├── category_id (FK → products_categories.id) *required
├── uom_id (FK → unit_of_measures.id) *required
├── uom_po_id (FK → unit_of_measures.id) *required
└── company_id (FK → companies.id)

products_product_suppliers
├── id (PK)
├── partner_id (FK → partners_partners.id) *required
├── product_id (FK → products_products.id) [nullable]
├── product_code (VARCHAR 255)
├── price (DECIMAL, default 0)
├── currency_id (FK → currencies.id) *required
├── starts_at (DATE)
├── ends_at (DATE)
├── delay (INT, default 0)
├── vendor_url (TEXT) [NEW FIELD]
└── company_id (FK → companies.id)

products_product_tag (PIVOT)
├── product_id (FK → products_products.id) *required
└── tag_id (FK → products_tags.id) *required

products_categories
├── id (PK)
├── name (VARCHAR 255) *required
├── full_name (VARCHAR 255) [auto-generated]
├── parent_path (VARCHAR 255) [auto-generated]
└── parent_id (FK → products_categories.id) [self-reference]

products_tags
├── id (PK)
├── name (VARCHAR 255) *required [UNIQUE]
└── color (VARCHAR 255)

partners_partners
├── id (PK)
├── name (VARCHAR 255) *required
├── account_type (ENUM: individual, company, address)
├── sub_type (VARCHAR 255: vendor, customer, partner)
└── [many other fields...]
```

### Appendix B: Sample Import JSON

**amazon_product_mapping.json:**
```json
{
  "products": [
    {
      "source": "amazon",
      "asin": "B08DX5F6XV",
      "product": {
        "type": "goods",
        "name": "EPSON 812 DURABrite Ultra Ink Extra-high Capacity Black Cartridge",
        "reference": "AMZ-B08DX5F6XV",
        "barcode": "B08DX5F6XV",
        "price": 82.49,
        "cost": 82.49,
        "uom_id": 1,
        "uom_po_id": 1,
        "category_id": 45,
        "enable_purchase": true,
        "enable_sales": false
      },
      "vendor_pricing": {
        "partner_id": 8,
        "product_code": "B08DX5F6XV",
        "price": 82.49,
        "currency_id": 1,
        "starts_at": "2025-09-30",
        "ends_at": "2026-09-30",
        "delay": 2,
        "vendor_url": "https://www.amazon.com/dp/B08DX5F6XV"
      },
      "tags": [
        "Amazon",
        "Consumable",
        "Reorderable",
        "Office Supplies"
      ],
      "category_path": "Office Supplies / Printer Cartridges"
    }
  ]
}
```

---

**END OF DOCUMENT**
